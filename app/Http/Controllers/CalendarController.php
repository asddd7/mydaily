<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $marks = DB::table('calendar_marks')
            ->where('user_id', $request->attributes->get('currentUser')->id)
            ->whereMonth('tanggal', now()->month)
            ->whereYear('tanggal', now()->year)
            ->orderBy('tanggal')
            ->get();

        return view('calendar.index', compact('marks'));
    }

    public function marks(Request $request): JsonResponse
    {
        $data = $request->validate([
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'between:2000,2100'],
        ]);

        return response()->json(DB::table('calendar_marks')
            ->where('user_id', $request->attributes->get('currentUser')->id)
            ->whereMonth('tanggal', $data['month'])
            ->whereYear('tanggal', $data['year'])
            ->orderBy('tanggal')
            ->get(['id', 'tanggal', 'title', 'description', 'selesai']));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        DB::table('calendar_marks')->insert([
            'user_id' => $request->attributes->get('currentUser')->id,
            'tanggal' => $data['tanggal'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'selesai' => 0,
            'created_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Penanda berhasil disimpan.'], 201);
        }

        return redirect()->back()->with('status', 'Penanda berhasil disimpan.');
    }

    public function toggle(Request $request, int $mark): JsonResponse
    {
        $data = $request->validate(['selesai' => ['required', 'boolean']]);
        $updated = DB::table('calendar_marks')
            ->where('id', $mark)
            ->where('user_id', $request->attributes->get('currentUser')->id)
            ->update(['selesai' => (bool) $data['selesai']]);
        abort_unless($updated, 404);

        return response()->json(['status' => 'updated']);
    }

    public function destroy(Request $request, int $mark): JsonResponse|RedirectResponse
    {
        $deleted = DB::table('calendar_marks')
            ->where('id', $mark)
            ->where('user_id', $request->attributes->get('currentUser')->id)
            ->delete();
        abort_unless($deleted, 404);

        return $request->expectsJson()
            ? response()->json(['status' => 'deleted'])
            : redirect()->back()->with('status', 'Penanda dihapus.');
    }

    public function holidays(Request $request): JsonResponse
    {
        $year = $request->integer('year', (int) now()->year);
        abort_if($year < 2000 || $year > 2100, 422);
        $path = base_path("calendar/holidays_{$year}.json");

        return response()->json(File::isFile($path)
            ? json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR)
            : []);
    }

    public function taskMarks(Request $request): JsonResponse
    {
        $tasks = DB::table('tugas')
            ->where('user_id', $request->attributes->get('currentUser')->id)
            ->whereNull('parent_id')
            ->get(['id', 'nama_tugas as title', 'deadline as tanggal']);

        return response()->json($tasks->map(fn ($task) => [
            'id' => $task->id,
            'title' => $task->title,
            'tanggal' => $task->tanggal,
            'url' => route('tasks.index', ['date' => $task->tanggal]),
        ]));
    }
}
