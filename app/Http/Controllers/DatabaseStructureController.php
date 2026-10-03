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

        return view('admin.database-structure', compact('tables'));
    }
}
