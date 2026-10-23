<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DatabaseStructureController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->attributes->get('currentUser')->role === 'admin', 403);

        $tables = [];
        foreach (DB::select('SHOW TABLES') as $row) {
            $table = (string) array_values((array) $row)[0];
            $quotedTable = str_replace('`', '``', $table);
            $columns = DB::select("DESCRIBE `{$quotedTable}`");
            $tables[$table] = [
                'sql' => 'DESCRIBE '.$table.";\n\n".implode("\n", array_map(
                    fn (object $column): string => implode(' | ', array_values((array) $column)),
                    $columns
                )),
            ];
        }

        $relations = [];
        foreach (array_keys($tables) as $table) {
            $relations[$table] = ['outgoing' => [], 'incoming' => []];
        }

        $database = DB::connection()->getDatabaseName();
        $foreignKeys = DB::select(<<<'SQL'
            SELECT
                TABLE_NAME AS table_name,
                COLUMN_NAME AS column_name,
                CONSTRAINT_NAME AS constraint_name,
                REFERENCED_TABLE_NAME AS referenced_table,
                REFERENCED_COLUMN_NAME AS referenced_column
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
            ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION
        SQL, [$database]);

        foreach ($foreignKeys as $foreignKey) {
            $sourceTable = (string) $foreignKey->table_name;
            $targetTable = (string) $foreignKey->referenced_table;
            $relation = [
                'constraint' => (string) $foreignKey->constraint_name,
                'column' => (string) $foreignKey->column_name,
                'referenced_table' => $targetTable,
                'referenced_column' => (string) $foreignKey->referenced_column,
            ];

            if (isset($relations[$sourceTable])) {
                $relations[$sourceTable]['outgoing'][] = $relation;
            }
            if (isset($relations[$targetTable])) {
                $relations[$targetTable]['incoming'][] = [
                    ...$relation,
                    'source_table' => $sourceTable,
                ];
            }
        }

        return view('admin.database-structure', compact('tables', 'relations'));
    }
}
