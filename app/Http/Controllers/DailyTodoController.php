<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DailyTodoController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'date' => ['sometimes', 'date_format:Y-m-d'],
        ]);
        $date = $validated['date'] ?? now()->toDateString();
        $userId = $request->attributes->get('currentUser')->id;
        $items = DB::table('daily_todos')
            ->where('user_id', $userId)
            ->where('todo_date', $date)
            ->orderBy('id')
            ->get();

        return view('daily-todos.index', [
            'items' => $items,
            'date' => $date,
            'selectedDate' => Carbon::parse($date),
            'completedCount' => $items->where('is_completed', true)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
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

        return redirect()->route('daily-todos.index', ['date' => $data['date']])
            ->with('status', 'To-do berhasil ditambahkan.');
    }

    public function toggle(Request $request, int $item): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
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

        return redirect()->route('daily-todos.index', ['date' => $data['date']]);
    }

    public function destroy(Request $request, int $item): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
        ]);
        $deleted = DB::table('daily_todos')
            ->where('id', $item)
            ->where('user_id', $request->attributes->get('currentUser')->id)
            ->delete();
        abort_unless($deleted, 404);

        return redirect()->route('daily-todos.index', ['date' => $data['date']])
            ->with('status', 'To-do berhasil dihapus.');
    }
}
