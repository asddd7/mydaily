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

        $definitions = [
            [
                'title' => 'Tugas',
                'description' => 'Tugas utama, subtugas, dan pengingat tugas.',
                'routes' => ['/tasks', '/tasks/{task}', '/tasks/{task}/subtasks', '/tasks/reorder', '/tasks/import'],
                'tables' => ['tugas', 'task_reminder_deliveries', 'users'],
                'files' => [
                    'Controller' => ['app/Http/Controllers/TaskController.php'],
                    'Service' => ['app/Services/CloudflarePushSyncService.php', 'app/Services/DueTaskPushSender.php'],
                    'View' => ['resources/views/tasks/index.blade.php', 'resources/views/tasks/show.blade.php'],
                    'Frontend' => ['resources/css/tasks.css', 'resources/js/push-notifications.js'],
                    'Cloudflare Worker' => ['cloudflare/push-worker/src/index.js', 'cloudflare/push-worker/schema.sql'],
                    'Routes' => ['routes/web.php'],
                ],
            ],
            [
                'title' => 'Dashboard',
                'description' => 'Ringkasan aktivitas, transaksi, catatan, dan tugas.',
                'routes' => ['/dashboard', '/notifications/count'],
                'tables' => ['absen', 'money_plan', 'notes', 'calendar_marks', 'tugas', 'users'],
                'files' => [
                    'Controller' => ['app/Http/Controllers/DashboardController.php', 'app/Http/Controllers/TaskController.php'],
                    'View' => ['resources/views/dashboard.blade.php', 'resources/views/layouts/app.blade.php'],
                    'Routes' => ['routes/web.php'],
                ],
            ],
            [
                'title' => 'Kalender',
                'description' => 'Penanda kalender dan tampilan tenggat tugas.',
                'routes' => ['/calendar', '/calendar/marks', '/calendar/task-marks', '/calendar/holidays'],
                'tables' => ['calendar_marks', 'tugas', 'users'],
                'files' => [
                    'Controller' => ['app/Http/Controllers/CalendarController.php'],
                    'View' => ['resources/views/calendar/index.blade.php', 'resources/views/calendar/widget.blade.php'],
                    'Routes' => ['routes/web.php'],
                ],
            ],
            [
                'title' => 'Keuangan',
                'description' => 'Transaksi, rincian barang, dan pemindaian struk.',
                'routes' => ['/money-plan', '/money-plan/adjust', '/money-plan/{transaction}'],
                'tables' => ['money_plan', 'money_plan_items', 'users'],
                'files' => [
                    'Controller' => ['app/Http/Controllers/MoneyPlanController.php'],
                    'View' => ['resources/views/money/index.blade.php'],
                    'Frontend' => ['resources/css/money.css', 'resources/js/money-currency.js', 'resources/js/money-receipt-ocr.js', 'resources/js/money-transaction-items.js'],
                    'Routes' => ['routes/web.php'],
                ],
            ],
            [
                'title' => 'Catatan',
                'description' => 'Catatan pribadi beserta lampirannya.',
                'routes' => ['/notes', '/notes/{note}', '/notes/{note}/image'],
                'tables' => ['notes', 'users'],
                'files' => [
                    'Controller' => ['app/Http/Controllers/NotesController.php'],
                    'View' => ['resources/views/notes/index.blade.php', 'resources/views/notes/show.blade.php'],
                    'Style' => ['resources/css/notes.css'],
                    'Routes' => ['routes/web.php'],
                ],
            ],
            [
                'title' => 'Kehadiran',
                'description' => 'Check-in dan check-out pengguna.',
                'routes' => ['/dashboard/check-in', '/dashboard/check-out'],
                'tables' => ['absen', 'users'],
                'files' => [
                    'Controller' => ['app/Http/Controllers/DashboardController.php'],
                    'View' => ['resources/views/dashboard.blade.php'],
                    'Routes' => ['routes/web.php'],
                ],
            ],
            [
                'title' => 'Berkas',
                'description' => 'Pengelolaan file pengguna.',
                'routes' => ['/file-manager', '/file-manager/{file}', '/file-manager/{file}/download'],
                'tables' => ['file_manager', 'users'],
                'files' => [
                    'Controller' => ['app/Http/Controllers/FileManagerController.php'],
                    'View' => ['resources/views/files/index.blade.php'],
                    'Routes' => ['routes/web.php'],
                ],
            ],
            [
                'title' => 'Profil dan Jam',
                'description' => 'Profil pengguna dan preferensi tampilan jam.',
                'routes' => ['/profile', '/clock'],
                'tables' => ['users'],
                'files' => [
                    'Controller' => ['app/Http/Controllers/ProfileController.php', 'app/Http/Controllers/ClockController.php'],
                    'View' => ['resources/views/profile.blade.php', 'resources/views/clock/edit.blade.php'],
                    'Routes' => ['routes/web.php'],
                ],
            ],
            [
                'title' => 'Notifikasi Push',
                'description' => 'Perangkat FCM, langganan push, dan catatan pengiriman.',
                'routes' => ['/notifications/push-subscriptions', '/notifications/push-test', '/notifications/cloudflare/sync'],
                'tables' => ['push_subscriptions', 'push_notification_deliveries', 'fcm_devices', 'task_reminder_deliveries', 'users'],
                'files' => [
                    'Controller' => ['app/Http/Controllers/PushSubscriptionController.php', 'app/Http/Controllers/PushTestNotificationController.php', 'app/Http/Controllers/CloudflarePushSyncController.php', 'app/Http/Controllers/FcmDeviceController.php'],
                    'Service' => ['app/Services/FirebaseMessagingService.php', 'app/Services/CloudflarePushSyncService.php'],
                    'Worker' => ['public/firebase-messaging-sw.js', 'cloudflare/push-worker/src/index.js', 'cloudflare/push-worker/schema.sql'],
                    'Frontend' => ['resources/js/push-notifications.js', 'resources/views/layouts/partials/topbar.blade.php'],
                    'Routes' => ['routes/web.php'],
                ],
            ],
            [
                'title' => 'Autentikasi',
                'description' => 'Login, pendaftaran, dan pemulihan kata sandi.',
                'routes' => ['/login', '/register', '/forgot-password', '/reset-password/{token}', '/logout'],
                'tables' => ['users', 'remember_tokens'],
                'files' => [
                    'Controller' => ['app/Http/Controllers/AuthController.php'],
                    'View' => ['resources/views/auth/login.blade.php', 'resources/views/auth/register.blade.php', 'resources/views/auth/forgot-password.blade.php', 'resources/views/auth/reset-password.blade.php'],
                    'Routes' => ['routes/web.php'],
                ],
            ],
            [
                'title' => 'PWA',
                'description' => 'Manifest, service worker, dan instalasi aplikasi.',
                'routes' => ['/manifest.webmanifest', '/manifest.json', '/firebase-messaging-worker.js'],
                'tables' => [],
                'files' => [
                    'Controller' => ['app/Http/Controllers/WebAppManifestController.php', 'app/Http/Controllers/FirebaseMessagingServiceWorkerController.php'],
                    'Worker dan aset' => ['public/firebase-messaging-sw.js', 'public/manifest.json'],
                    'Routes' => ['routes/web.php'],
                ],
            ],
            [
                'title' => 'Admin',
                'description' => 'Administrasi dan pemeriksaan skema database.',
                'routes' => ['/admin/database-structure'],
                'tables' => [],
                'files' => [
                    'Controller' => ['app/Http/Controllers/DatabaseStructureController.php'],
                    'View' => ['resources/views/admin/database-structure.blade.php'],
                    'Routes' => ['routes/web.php'],
                ],
            ],
        ];

        $assignedTables = [];
        $modules = [];
        foreach ($definitions as $definition) {
            $moduleTables = array_values(array_filter(
                $definition['tables'],
                fn (string $table): bool => isset($tables[$table])
            ));
            $assignedTables = [...$assignedTables, ...$moduleTables];
            $files = [];
            foreach ($definition['files'] as $category => $paths) {
                $existing = array_values(array_filter($paths, fn (string $path): bool => is_file(base_path($path))));
                if ($existing) {
                    $files[$category] = $existing;
                }
            }

            $searchTerms = array_merge(
                [$definition['title'], $definition['description']],
                $moduleTables,
                $definition['routes'],
                ...array_values($files),
            );

            $modules[] = [
                ...$definition,
                'tables' => $moduleTables,
                'files' => $files,
                'search' => strtolower(implode(' ', $searchTerms)),
            ];
        }

        $otherTables = array_values(array_diff(array_keys($tables), array_unique($assignedTables)));
        if ($otherTables) {
            $modules[] = [
                'title' => 'Tabel lainnya',
                'description' => 'Tabel database yang belum dipetakan ke modul aplikasi.',
                'routes' => [],
                'tables' => $otherTables,
                'files' => [],
            ];
        }

        return view('admin.database-structure', compact('tables', 'relations', 'modules'));
    }
}
