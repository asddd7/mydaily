<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DailyTodoController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $date = $request->query('date');
        $weekDates = $this->currentWeekDateStrings();
        $todoDate = is_string($date) && in_array($date, $weekDates, true)
            ? $date
            : today()->toDateString();

        return redirect()->route('tasks.index', [
            'view' => 'todos',
            'todo_date' => $todoDate,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', Rule::in($this->currentWeekDateStrings())],
            'title' => ['required', 'string', 'max:255'],
        ]);

        DB::table('daily_todos')->insert([
            'user_id' => $request->attributes->get('currentUser')->id,
            'todo_date' => $data['date'],
            'title' => $data['title'],
            'is_completed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('tasks.index', [
            'view' => 'todos',
            'todo_date' => $data['date'],
        ])
            ->with('status', 'To-do berhasil ditambahkan.');
    }

    public function toggle(Request $request, int $item): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', Rule::in($this->currentWeekDateStrings())],
        ]);
        $userId = $request->attributes->get('currentUser')->id;
        $todo = DB::table('daily_todos')
            ->where('id', $item)
            ->where('user_id', $userId)
            ->first();
        abort_unless($todo, 404);

        DB::table('daily_todos')
            ->where('id', $item)
            ->where('user_id', $userId)
            ->update([
                'is_completed' => !$todo->is_completed,
                'updated_at' => now(),
            ]);

        return redirect()->route('tasks.index', [
            'view' => 'todos',
            'todo_date' => $data['date'],
        ]);
    }

    public function destroy(Request $request, int $item): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', Rule::in($this->currentWeekDateStrings())],
        ]);
        $deleted = DB::table('daily_todos')
            ->where('id', $item)
            ->where('user_id', $request->attributes->get('currentUser')->id)
            ->delete();
        abort_unless($deleted, 404);

        return redirect()->route('tasks.index', [
            'view' => 'todos',
            'todo_date' => $data['date'],
        ])
            ->with('status', 'To-do berhasil dihapus.');
    }

    /**
     * @return array<int, string>
     */
    private function currentWeekDateStrings(): array
    {
        $weekStart = today()->startOfWeek(\Illuminate\Support\Carbon::MONDAY);

        return array_map(
            static fn (int $offset): string => $weekStart->copy()->addDays($offset)->toDateString(),
            range(0, 6),
        );
    }
}
