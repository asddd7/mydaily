<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->attributes->get('currentUser')->id;
        $filters = $request->validate(['date' => ['sometimes', 'date']]);
        $tasks = DB::table('tugas')
            ->where('user_id', $userId)
            ->whereNull('parent_id')
            ->when(isset($filters['date']), fn ($query) => $query->whereDate('deadline', $filters['date']))
            ->orderBy('deadline')
            ->orderBy('urutan')
            ->get();

        $subtasks = DB::table('tugas')
            ->where('user_id', $userId)
            ->whereIn('parent_id', $tasks->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('parent_id');

        return view('tasks.index', [
            'tasks' => $tasks,
            'subtasks' => $subtasks,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_tugas' => ['required', 'string', 'max:100'],
            'deadline' => ['required', 'date'],
            'recurring_type' => ['nullable', Rule::in(['none', 'daily', 'weekly', 'monthly', 'yearly'])],
            'kategori' => ['nullable', 'string', 'max:100'],
        ]);

        DB::table('tugas')->insert([
            'nama_tugas' => $data['nama_tugas'],
            'deadline' => $data['deadline'],
            'user_id' => $request->attributes->get('currentUser')->id,
            'recurring_type' => $data['recurring_type'] ?? 'none',
            'kategori' => $data['kategori'] ?? 'daily',
        ]);

        return redirect()->route('tasks.index')->with('status', 'Tugas berhasil ditambahkan.');
    }

    public function update(Request $request, int $task): RedirectResponse
    {
        $data = $request->validate([
            'nama_tugas' => ['required', 'string', 'max:100'],
            'deadline' => ['required', 'date'],
            'recurring_type' => ['nullable', Rule::in(['none', 'daily', 'weekly', 'monthly', 'yearly'])],
            'kategori' => ['nullable', 'string', 'max:100'],
        ]);

        $updated = DB::table('tugas')
            ->where('id', $task)
            ->where('user_id', $request->attributes->get('currentUser')->id)
            ->whereNull('parent_id')
            ->update([
                'nama_tugas' => $data['nama_tugas'],
                'deadline' => $data['deadline'],
                'recurring_type' => $data['recurring_type'] ?? 'none',
                'kategori' => $data['kategori'] ?? 'daily',
            ]);

        abort_unless($updated, 404);

        return redirect()->route('tasks.index')->with('status', 'Tugas berhasil diperbarui.');
    }

    public function destroy(Request $request, int $task): RedirectResponse
    {
        $userId = $request->attributes->get('currentUser')->id;

        DB::transaction(function () use ($task, $userId): void {
            $owned = DB::table('tugas')->where('id', $task)->where('user_id', $userId)->whereNull('parent_id')->exists();
            abort_unless($owned, 404);

            DB::table('tugas')->where('parent_id', $task)->where('user_id', $userId)->delete();
            DB::table('tugas')->where('id', $task)->where('user_id', $userId)->delete();
        });

        return redirect()->route('tasks.index')->with('status', 'Tugas berhasil dihapus.');
    }

    public function toggle(Request $request, int $task): RedirectResponse
    {
        $data = $request->validate(['selesai' => ['required', 'boolean']]);
        $userId = $request->attributes->get('currentUser')->id;
        $done = (bool) $data['selesai'];

        DB::transaction(function () use ($task, $userId, $done): void {
            $item = DB::table('tugas')
                ->where('id', $task)
                ->where('user_id', $userId)
                ->whereNull('parent_id')
                ->lockForUpdate()
                ->first();
            abort_unless($item, 404);

            DB::table('tugas')->where('id', $task)->update([
                'selesai' => $done,
                'selesai_at' => $done ? now() : null,
            ]);
            DB::table('tugas')->where('parent_id', $task)->where('user_id', $userId)->update([
                'selesai' => $done,
                'selesai_at' => $done ? now() : null,
            ]);

            if (!$done) {
                DB::table('tugas')->where('id', $task)->update(['recurring_generated' => 0]);

                return;
            }

            if ($item->recurring_type === 'none' || $item->recurring_generated) {
                return;
            }

            $nextDeadline = match ($item->recurring_type) {
                'daily' => Carbon::parse($item->deadline)->addDay(),
                'weekly' => Carbon::parse($item->deadline)->addWeek(),
                'monthly' => Carbon::parse($item->deadline)->addMonth(),
                'yearly' => Carbon::parse($item->deadline)->addYear(),
                default => null,
            };

            if (!$nextDeadline) {
                return;
            }

            $nextId = DB::table('tugas')->insertGetId([
                'nama_tugas' => $item->nama_tugas,
                'deadline' => $nextDeadline->toDateString(),
                'user_id' => $userId,
                'recurring_type' => $item->recurring_type,
                'kategori' => $item->kategori,
            ]);

            $children = DB::table('tugas')->where('parent_id', $task)->where('user_id', $userId)->get();
            foreach ($children as $child) {
                DB::table('tugas')->insert([
                    'nama_tugas' => $child->nama_tugas,
                    'deadline' => $nextDeadline->toDateString(),
                    'user_id' => $userId,
                    'parent_id' => $nextId,
                ]);
            }

            DB::table('tugas')->where('id', $task)->update(['recurring_generated' => 1]);
        });

        return redirect()->route('tasks.index')->with('status', 'Status tugas diperbarui.');
    }

    public function copy(Request $request, int $task): RedirectResponse
    {
        $userId = $request->attributes->get('currentUser')->id;

        DB::transaction(function () use ($task, $userId): void {
            $source = DB::table('tugas')->where('id', $task)->where('user_id', $userId)->whereNull('parent_id')->first();
            abort_unless($source, 404);

            $newId = DB::table('tugas')->insertGetId([
                'nama_tugas' => $source->nama_tugas,
                'deadline' => today()->toDateString(),
                'user_id' => $userId,
                'recurring_type' => 'none',
                'kategori' => $source->kategori,
            ]);

            $children = DB::table('tugas')->where('parent_id', $task)->where('user_id', $userId)->get();
            foreach ($children as $child) {
                DB::table('tugas')->insert([
                    'nama_tugas' => $child->nama_tugas,
                    'deadline' => today()->toDateString(),
                    'user_id' => $userId,
                    'parent_id' => $newId,
                ]);
            }
        });

        return redirect()->route('tasks.index')->with('status', 'Tugas berhasil disalin.');
    }

    public function storeSubtask(Request $request, int $task): RedirectResponse
    {
        $data = $request->validate(['nama_subtask' => ['required', 'string', 'max:100']]);
        $userId = $request->attributes->get('currentUser')->id;
        $parent = DB::table('tugas')->where('id', $task)->where('user_id', $userId)->whereNull('parent_id')->first();
        abort_unless($parent, 404);

        DB::table('tugas')->insert([
            'nama_tugas' => $data['nama_subtask'],
            'deadline' => $parent->deadline,
            'user_id' => $userId,
            'parent_id' => $task,
        ]);

        return redirect()->route('tasks.index')->with('status', 'Subtugas berhasil ditambahkan.');
    }

    public function updateSubtask(Request $request, int $task): RedirectResponse
    {
        $data = $request->validate([
            'nama_tugas' => ['required', 'string', 'max:100'],
            'selesai' => ['sometimes', 'boolean'],
        ]);
        $userId = $request->attributes->get('currentUser')->id;
        $subtask = DB::table('tugas')->where('id', $task)->where('user_id', $userId)->whereNotNull('parent_id')->first();
        abort_unless($subtask, 404);

        $done = (bool) ($data['selesai'] ?? $subtask->selesai);
        DB::transaction(function () use ($data, $subtask, $task, $userId, $done): void {
            DB::table('tugas')->where('id', $task)->update([
                'nama_tugas' => $data['nama_tugas'],
                'selesai' => $done,
                'selesai_at' => $done ? now() : null,
            ]);

            $remaining = DB::table('tugas')->where('parent_id', $subtask->parent_id)->where('user_id', $userId)->where('selesai', 0)->count();
            DB::table('tugas')->where('id', $subtask->parent_id)->where('user_id', $userId)->update([
                'selesai' => $remaining === 0,
                'selesai_at' => $remaining === 0 ? now() : null,
            ]);
        });

        return redirect()->route('tasks.index')->with('status', 'Subtugas diperbarui.');
    }

    public function destroySubtask(Request $request, int $task): RedirectResponse
    {
        $userId = $request->attributes->get('currentUser')->id;
        $subtask = DB::table('tugas')->where('id', $task)->where('user_id', $userId)->whereNotNull('parent_id')->first();
        abort_unless($subtask, 404);

        DB::table('tugas')->where('id', $task)->delete();

        return redirect()->route('tasks.index')->with('status', 'Subtugas dihapus.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate(['task_ids' => ['required', 'array'], 'task_ids.*' => ['integer']]);
        $userId = $request->attributes->get('currentUser')->id;

        DB::transaction(function () use ($data, $userId): void {
            foreach ($data['task_ids'] as $order => $id) {
                DB::table('tugas')->where('id', $id)->where('user_id', $userId)->whereNull('parent_id')->update(['urutan' => $order]);
            }
        });

        return back()->with('status', 'Urutan tugas diperbarui.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file_excel' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240']]);
        $userId = $request->attributes->get('currentUser')->id;
        $sheet = IOFactory::load($request->file('file_excel')->getRealPath())->getActiveSheet();
        $rows = [];

        for ($row = 2; $row <= $sheet->getHighestRow(); $row++) {
            $title = trim((string) $sheet->getCell("A{$row}")->getValue());
            if ($title === '') {
                continue;
            }

            $rawDate = $sheet->getCell("B{$row}")->getValue();
            if (is_numeric($rawDate)) {
                $deadline = ExcelDate::excelToDateTimeObject((float) $rawDate)->format('Y-m-d');
            } else {
                $parsed = false;
                foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y'] as $format) {
                    $date = \DateTime::createFromFormat($format, (string) $rawDate);
                    if ($date !== false) {
                        $deadline = $date->format('Y-m-d');
                        $parsed = true;
                        break;
                    }
                }
                if (!$parsed) {
                    $deadline = today()->toDateString();
                }
            }

            $rows[] = [
                'nama_tugas' => $title,
                'deadline' => $deadline,
                'parent_name' => trim((string) $sheet->getCell("C{$row}")->getValue()),
            ];
        }

        DB::transaction(function () use ($rows, $userId): void {
            $idsByTitle = [];
            foreach ($rows as $row) {
                $idsByTitle[$row['nama_tugas']] = DB::table('tugas')->insertGetId([
                    'nama_tugas' => mb_substr($row['nama_tugas'], 0, 100),
                    'deadline' => $row['deadline'],
                    'user_id' => $userId,
                ]);
            }

            foreach ($rows as $row) {
                $parentId = $idsByTitle[$row['parent_name']] ?? null;
                $id = $idsByTitle[$row['nama_tugas']] ?? null;
                if ($parentId && $id && $parentId !== $id) {
                    DB::table('tugas')->where('id', $id)->where('user_id', $userId)->update(['parent_id' => $parentId]);
                }
            }
        });

        return redirect()->route('tasks.index')->with('status', 'Import tugas selesai.');
    }

    public function notificationCount(Request $request)
    {
        $userId = $request->attributes->get('currentUser')->id;
        $mainTasks = DB::table('tugas')
            ->select('id as task_id')
            ->where('user_id', $userId)
            ->whereNull('parent_id')
            ->where('selesai', 0);
        $unfinishedParents = DB::table('tugas as child')
            ->join('tugas as parent', 'child.parent_id', '=', 'parent.id')
            ->select('parent.id as task_id')
            ->where('child.user_id', $userId)
            ->where('child.selesai', 0)
            ->where('parent.selesai', 0)
            ->union($mainTasks);
        $count = DB::query()->fromSub($unfinishedParents, 'notifications')->count();

        return response()->json(['count' => $count]);
    }

    public function compatibilityAction(Request $request): RedirectResponse
    {
        if ($request->filled('delete_task')) {
            return $this->destroy($request, (int) $request->input('delete_task'));
        }

        if ($request->filled('copy_task')) {
            return $this->copy($request, (int) $request->input('copy_task'));
        }

        if ($request->filled('nama_tugas') && $request->filled('deadline')) {
            return $this->store($request);
        }

        if ($request->filled('parent_id') && $request->filled('nama_subtask')) {
            return $this->storeSubtask($request, $request->integer('parent_id'));
        }

        if ($request->filled('main_task_id')) {
            $request->merge(['selesai' => $request->boolean('selesai')]);

            return $this->toggle($request, $request->integer('main_task_id'));
        }

        return redirect()->route('tasks.index');
    }
}
