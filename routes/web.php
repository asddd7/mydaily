<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\MoneyPlanController;
use App\Http\Controllers\NotesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ClockController;
use App\Http\Controllers\DatabaseStructureController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate'])->name('login.store');
Route::get('/register', [AuthController::class, 'register'])->name('register');
Route::post('/register', [AuthController::class, 'storeRegistration'])->name('register.store');
Route::get('/forgot-password', [AuthController::class, 'forgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'resetPassword'])->name('password.reset');
Route::post('/reset-password/{token}', [AuthController::class, 'updatePassword'])->name('password.update');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/dashboard/check-in', [DashboardController::class, 'checkIn'])->name('attendance.check-in');
Route::post('/dashboard/check-out', [DashboardController::class, 'checkOut'])->name('attendance.check-out');

Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
Route::post('/tasks/{task}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');
Route::post('/tasks/{task}/copy', [TaskController::class, 'copy'])->name('tasks.copy');
Route::post('/tasks/{task}/subtasks', [TaskController::class, 'storeSubtask'])->name('tasks.subtasks.store');
Route::put('/subtasks/{task}', [TaskController::class, 'updateSubtask'])->name('tasks.subtasks.update');
Route::delete('/subtasks/{task}', [TaskController::class, 'destroySubtask'])->name('tasks.subtasks.destroy');
Route::post('/tasks/reorder', [TaskController::class, 'reorder'])->name('tasks.reorder');
Route::post('/tasks/import', [TaskController::class, 'import'])->name('tasks.import');

Route::get('/money-plan', [MoneyPlanController::class, 'index'])->name('money.index');
Route::post('/money-plan', [MoneyPlanController::class, 'store'])->name('money.store');
Route::post('/money-plan/adjust', [MoneyPlanController::class, 'adjust'])->name('money.adjust');
Route::delete('/money-plan/{transaction}', [MoneyPlanController::class, 'destroy'])->name('money.destroy');

Route::get('/notes', [NotesController::class, 'index'])->name('notes.index');
Route::post('/notes', [NotesController::class, 'store'])->name('notes.store');
Route::get('/notes/{note}/image', [NotesController::class, 'image'])->name('notes.image');
Route::get('/notes/{note}', [NotesController::class, 'show'])->name('notes.show');
Route::put('/notes/{note}', [NotesController::class, 'update'])->name('notes.update');
Route::delete('/notes/{note}', [NotesController::class, 'destroy'])->name('notes.destroy');

Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

Route::get('/file-manager', [FileManagerController::class, 'index'])->name('files.index');
Route::post('/file-manager', [FileManagerController::class, 'store'])->name('files.store');
Route::put('/file-manager/{file}', [FileManagerController::class, 'update'])->name('files.update');
Route::delete('/file-manager/{file}', [FileManagerController::class, 'destroy'])->name('files.destroy');
Route::get('/file-manager/{file}/download', [FileManagerController::class, 'download'])->name('files.download');

Route::get('/calendar/marks', [CalendarController::class, 'marks'])->name('calendar.marks');
Route::post('/calendar/marks', [CalendarController::class, 'store'])->name('calendar.store');
Route::patch('/calendar/marks/{mark}', [CalendarController::class, 'toggle'])->name('calendar.toggle');
Route::delete('/calendar/marks/{mark}', [CalendarController::class, 'destroy'])->name('calendar.destroy');
Route::get('/calendar/holidays', [CalendarController::class, 'holidays'])->name('calendar.holidays');
Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
Route::get('/calendar/task-marks', [CalendarController::class, 'taskMarks'])->name('calendar.task-marks');
Route::get('/notifications/count', [TaskController::class, 'notificationCount'])->name('notifications.count');
Route::get('/clock', [ClockController::class, 'edit'])->name('clock.edit');
Route::put('/clock', [ClockController::class, 'update'])->name('clock.update');
Route::get('/admin/database-structure', [DatabaseStructureController::class, 'index'])->name('admin.database-structure');

Route::get('/koneksi/login.php', fn () => redirect()->route('login'));
Route::post('/koneksi/login.php', [AuthController::class, 'authenticate'])->name('compat.login.store');
Route::get('/koneksi/register.php', fn () => redirect()->route('register'));
Route::get('/koneksi/forgot_password.php', fn () => redirect()->route('password.request'));
Route::get('/koneksi/reset_password.php', fn (Request $request) => redirect()->route('password.reset', ['token' => $request->query('token', '')]));
Route::get('/dashboard.php', fn () => redirect()->route('dashboard'));
Route::get('/index.php', fn () => redirect()->route('dashboard'));
Route::get('/task.php', fn (Request $request) => redirect()->route('tasks.index', $request->query()));
Route::post('/task.php', [TaskController::class, 'compatibilityAction']);
Route::get('/money_plan.php', fn (Request $request) => redirect()->route('money.index', $request->query()));
Route::post('/money_plan.php', [MoneyPlanController::class, 'compatibilityAction']);
Route::get('/notes.php', fn () => redirect()->route('notes.index'));
Route::post('/notes.php', [NotesController::class, 'compatibilityAction']);
Route::get('/profile.php', fn () => redirect()->route('profile.show'));
Route::post('/profile.php', [ProfileController::class, 'update']);
Route::get('/file_manager.php', fn () => redirect()->route('files.index'));
Route::post('/file_manager.php', function (Request $request) {
    if ($request->has('edit_file')) {
        return app(FileManagerController::class)->update($request, $request->integer('file_id'));
    }

    return app(FileManagerController::class)->store($request);
});
Route::get('/sub/note_detail.php', fn (Request $request) => redirect()->route('notes.show', ['note' => $request->integer('id')]));
Route::get('/koneksi/get_notif.php', [TaskController::class, 'notificationCount']);
Route::get('/calendar/get_holidays.php', [CalendarController::class, 'holidays']);
Route::get('/calendar/calendar_marks.php', [CalendarController::class, 'marks']);
Route::get('/calendar/calendar_marks_tasks.php', [CalendarController::class, 'taskMarks']);
Route::post('/calendar/calendar_add.php', [CalendarController::class, 'store']);
Route::post('/calendar/calendar_toggle.php', function (Request $request) {
    return app(CalendarController::class)->toggle($request, $request->integer('id'));
});
Route::post('/calendar/calendar_delete.php', function (Request $request) {
    return app(CalendarController::class)->destroy($request, $request->integer('id'));
});
Route::post('/koneksi/logout.php', [AuthController::class, 'logout']);
Route::get('/sub/clock.php', fn () => redirect()->route('clock.edit'));
Route::post('/sub/clock.php', [ClockController::class, 'update']);
Route::get('/koneksi/database_structure.php', fn () => redirect()->route('admin.database-structure'));
Route::get('/koneksi/download.php', fn () => redirect()->route('files.index'));
