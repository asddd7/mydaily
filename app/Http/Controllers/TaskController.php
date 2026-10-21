<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TaskController extends Controller
{
    private const TASK_CATEGORIES = [
        'work' => 'Work',
        'casual' => 'Casual',
        'game' => 'Game',
        'urgent' => 'Urgent',
    ];

    public function index(Request $request): View
    {
        $userId = $request->attributes->get('currentUser')->id;
        $knownCategories = DB::table('tugas')
            ->where('user_id', $userId)
            ->whereNull('parent_id')
            ->whereNotNull('kategori')
            ->distinct()
            ->orderBy('kategori')
            ->pluck('kategori')
            ->all();
        $categoryOptions = self::TASK_CATEGORIES;
        foreach ($knownCategories as $knownCategory) {
            if (!array_key_exists($knownCategory, $categoryOptions)) {
                $categoryOptions[$knownCategory] = ucfirst($knownCategory);
            }
        }

        $filters = $request->validate([
            'date' => ['sometimes', 'date'],
            'q' => ['sometimes', 'nullable', 'string', 'max:100'],
            'category' => ['sometimes', 'nullable', 'string', Rule::in(array_keys($categoryOptions))],
        ]);
        $search = trim($filters['q'] ?? '');
        $category = $filters['category'] ?? '';
        $tasks = DB::table('tugas')
            ->where('user_id', $userId)
            ->whereNull('parent_id')
            ->when(isset($filters['date']), fn ($query) => $query->whereDate('deadline', $filters['date']))
            ->when($category !== '', fn ($query) => $query->where('kategori', $category))
            ->when($search !== '', function ($query) use ($search, $userId): void {
                $query->where(function ($query) use ($search, $userId): void {
                    $query->where('nama_tugas', 'like', '%'.$search.'%')
                        ->orWhereExists(function ($subquery) use ($search, $userId): void {
                            $subquery->selectRaw('1')
                                ->from('tugas as matching_subtask')
                                ->whereColumn('matching_subtask.parent_id', 'tugas.id')
                                ->where('matching_subtask.user_id', $userId)
                                ->where('matching_subtask.nama_tugas', 'like', '%'.$search.'%');
                        });
                });
            })
            ->orderBy('deadline')
            ->orderBy('urutan')
            ->get();

        $subtasks = DB::table('tugas')
            ->where('user_id', $userId)
            ->whereIn('parent_id', $tasks->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('parent_id');

        if ($search !== '') {
            foreach ($tasks as $task) {
                if (mb_stripos($task->nama_tugas, $search) === false) {
                    $subtasks[$task->id] = ($subtasks[$task->id] ?? collect())
                        ->filter(fn ($subtask): bool => mb_stripos($subtask->nama_tugas, $search) !== false)
                        ->values();
                }
            }
        }

        return view('tasks.index', [
            'tasks' => $tasks,
            'subtasks' => $subtasks,
            'search' => $search,
            'category' => $category,
            'categoryOptions' => $categoryOptions,
        ]);
    }

    public function show(Request $request, int $task): View
    {
        $userId = $request->attributes->get('currentUser')->id;
        $task = DB::table('tugas')
            ->where('id', $task)
            ->where('user_id', $userId)
            ->whereNull('parent_id')
            ->first();
        abort_unless($task, 404);

        $subtasks = DB::table('tugas')
            ->where('parent_id', $task->id)
            ->where('user_id', $userId)
            ->orderBy('id')
            ->get();

        return view('tasks.show', compact('task', 'subtasks'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama_tugas' => ['required', 'string', 'max:100'],
            'deadline' => ['required', 'date'],
            'reminder_enabled' => ['required', 'boolean'],
            'reminder_at' => ['nullable', 'required_if:reminder_enabled,1', 'date'],
            'recurring_type' => ['nullable', Rule::in(['none', 'daily', 'weekly', 'monthly', 'yearly'])],
            'kategori' => ['required', Rule::in(array_keys(self::TASK_CATEGORIES))],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:5120'],
        ]);

        $imageName = $request->file('image')?->hashName();
        $imagePath = null;
        if ($imageName) {
            $imageDirectory = base_path('uploads/tasks');
            File::ensureDirectoryExists($imageDirectory);
            $imagePath = $imageDirectory.'/'.$imageName;
            $request->file('image')->move($imageDirectory, $imageName);
        }

        try {
            $taskId = DB::table('tugas')->insertGetId([
                'nama_tugas' => $data['nama_tugas'],
                'deadline' => $data['deadline'],
                'reminder_at' => $request->boolean('reminder_enabled')
                    ? Carbon::parse($data['reminder_at'])->format('Y-m-d H:i:s')
                    : null,
                'reminder_sent_at' => null,
                'user_id' => $request->attributes->get('currentUser')->id,
                'recurring_type' => $data['recurring_type'] ?? 'none',
                'kategori' => $data['kategori'],
                'image' => $imageName,
            ]);
        } catch (\Throwable $error) {
            if ($imagePath && File::exists($imagePath) && !File::delete($imagePath)) {
                throw new \RuntimeException('Unable to remove the task image after a failed task save.', 0, $error);
            }

            throw $error;
        }

        $reminderSynced = app(\App\Services\CloudflarePushSyncService::class)->syncTask(
            $taskId,
            $request->attributes->get('currentUser')->id,
            $request->boolean('reminder_enabled') ? $data['nama_tugas'] : null,
            $request->boolean('reminder_enabled') ? $data['reminder_at'] : null,
        );

        $status = 'Tugas berhasil ditambahkan.';
        if ($request->boolean('reminder_enabled') && !$reminderSynced) {
            $status .= ' Pengingat belum tersinkron ke layanan notifikasi; periksa URL dan secret Cloudflare.';
        }

        return redirect()->route('tasks.index')->with('status', $status);
    }

    public function image(Request $request, int $task): BinaryFileResponse
    {
        $record = DB::table('tugas')
            ->where('id', $task)
            ->where('user_id', $request->attributes->get('currentUser')->id)
            ->first(['image']);
        abort_unless($record && $record->image, 404);

        $path = base_path('uploads/tasks/'.basename($record->image));
        abort_unless(File::isFile($path), 404);
        abort_unless(in_array(File::mimeType($path), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true), 404);

        return response()->file($path, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function update(Request $request, int $task): RedirectResponse
    {
        $userId = $request->attributes->get('currentUser')->id;
        $currentTask = DB::table('tugas')
            ->where('id', $task)
            ->where('user_id', $userId)
            ->whereNull('parent_id')
            ->first(['kategori', 'reminder_at', 'reminder_sent_at']);
        abort_unless($currentTask, 404);
        $currentCategory = $currentTask->kategori ?? 'daily';

        $data = $request->validate([
            'nama_tugas' => ['required', 'string', 'max:100'],
            'deadline' => ['required', 'date'],
            'reminder_enabled' => ['required', 'boolean'],
            'reminder_at' => ['nullable', 'required_if:reminder_enabled,1', 'date'],
            'recurring_type' => ['nullable', Rule::in(['none', 'daily', 'weekly', 'monthly', 'yearly'])],
            'kategori' => ['required', Rule::in(array_values(array_unique([...array_keys(self::TASK_CATEGORIES), $currentCategory])))],
        ]);

        $updated = DB::table('tugas')
            ->where('id', $task)
            ->where('user_id', $userId)
            ->whereNull('parent_id')
            ->update([
                'nama_tugas' => $data['nama_tugas'],
                'deadline' => $data['deadline'],
                'reminder_at' => $request->boolean('reminder_enabled')
                    ? Carbon::parse($data['reminder_at'])->format('Y-m-d H:i:s')
                    : null,
                'reminder_sent_at' => $request->boolean('reminder_enabled')
                    && $currentTask->reminder_at === Carbon::parse($data['reminder_at'])->format('Y-m-d H:i:s')
                    ? $currentTask->reminder_sent_at
                    : null,
                'recurring_type' => $data['recurring_type'] ?? 'none',
                'kategori' => $data['kategori'],
            ]);

        abort_unless($updated, 404);

        $reminderSynced = app(\App\Services\CloudflarePushSyncService::class)->syncTask(
            $task,
            $userId,
            $request->boolean('reminder_enabled') ? $data['nama_tugas'] : null,
            $request->boolean('reminder_enabled') ? $data['reminder_at'] : null,
        );

        $status = 'Tugas berhasil diperbarui.';
        if ($request->boolean('reminder_enabled') && !$reminderSynced) {
            $status .= ' Pengingat belum tersinkron ke layanan notifikasi; periksa URL dan secret Cloudflare.';
        }

        return redirect()->route('tasks.index')->with('status', $status);
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

        app(\App\Services\CloudflarePushSyncService::class)->syncTask($task, $userId, null, null);

        return redirect()->route('tasks.index')->with('status', 'Tugas berhasil dihapus.');
    }

    public function toggle(Request $request, int $task): RedirectResponse|JsonResponse
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
                'image' => $item->image,
            ]);

            $children = DB::table('tugas')->where('parent_id', $task)->where('user_id', $userId)->get();
            foreach ($children as $child) {
                DB::table('tugas')->insert([
                    'nama_tugas' => $child->nama_tugas,
                    'deadline' => $nextDeadline->toDateString(),
                    'user_id' => $userId,
                    'parent_id' => $nextId,
                    'kategori' => $item->kategori,
                ]);
            }

            DB::table('tugas')->where('id', $task)->update(['recurring_generated' => 1]);
        });

        $taskRecord = DB::table('tugas')
            ->where('id', $task)
            ->where('user_id', $userId)
            ->whereNull('parent_id')
            ->first(['id', 'user_id', 'nama_tugas', 'reminder_at', 'selesai']);
        if ($taskRecord) {
            $sync = app(\App\Services\CloudflarePushSyncService::class);
            if ($taskRecord->selesai) {
                $sync->syncTask($task, $userId, null, null);
            } else {
                $sync->syncTask($task, $userId, $taskRecord->nama_tugas, $taskRecord->reminder_at);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['status' => 'updated', 'selesai' => $done]);
        }

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
                'image' => $source->image,
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
        $data = $request->validate([
            'nama_subtask' => ['required', 'string', 'max:100'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:5120'],
        ]);
        $userId = $request->attributes->get('currentUser')->id;
        $parent = DB::table('tugas')->where('id', $task)->where('user_id', $userId)->whereNull('parent_id')->first();
        abort_unless($parent, 404);

        $imageName = $request->file('image')?->hashName();
        $imagePath = null;
        if ($imageName) {
            $imageDirectory = base_path('uploads/tasks');
            File::ensureDirectoryExists($imageDirectory);
            $imagePath = $imageDirectory.'/'.$imageName;
            $request->file('image')->move($imageDirectory, $imageName);
        }

        try {
            DB::table('tugas')->insert([
                'nama_tugas' => $data['nama_subtask'],
                'deadline' => $parent->deadline,
                'user_id' => $userId,
                'parent_id' => $task,
                'kategori' => $parent->kategori,
                'image' => $imageName,
            ]);
        } catch (\Throwable $error) {
            if ($imagePath && File::exists($imagePath) && !File::delete($imagePath)) {
                throw new \RuntimeException('Unable to remove the subtask image after a failed task save.', 0, $error);
            }

            throw $error;
        }

        $redirect = $request->boolean('return_to_detail')
            ? redirect()->route('tasks.show', $task)
            : redirect()->route('tasks.index');

        return $redirect->with('status', 'Subtugas berhasil ditambahkan.');
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

        $redirect = $request->boolean('return_to_detail')
            ? redirect()->route('tasks.show', $subtask->parent_id)
            : redirect()->route('tasks.index');

        return $redirect->with('status', 'Subtugas diperbarui.');
    }

    public function destroySubtask(Request $request, int $task): RedirectResponse
    {
        $userId = $request->attributes->get('currentUser')->id;
        $subtask = DB::table('tugas')->where('id', $task)->where('user_id', $userId)->whereNotNull('parent_id')->first();
        abort_unless($subtask, 404);

        DB::table('tugas')->where('id', $task)->delete();

        return redirect()->route('tasks.index')->with('status', 'Subtugas dihapus.');
    }

    public function destroySubtasks(Request $request, int $task): RedirectResponse
    {
        $data = $request->validate([
            'subtask_ids' => ['required', 'array', 'min:1'],
            'subtask_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ]);
        $userId = $request->attributes->get('currentUser')->id;
        $parentExists = DB::table('tugas')
            ->where('id', $task)
            ->where('user_id', $userId)
            ->whereNull('parent_id')
            ->exists();
        abort_unless($parentExists, 404);

        $selectedIds = $data['subtask_ids'];
        $ownedSubtaskIds = DB::table('tugas')
            ->where('user_id', $userId)
            ->where('parent_id', $task)
            ->whereIn('id', $selectedIds)
            ->pluck('id');
        abort_unless($ownedSubtaskIds->count() === count($selectedIds), 404);

        DB::table('tugas')
            ->where('user_id', $userId)
            ->where('parent_id', $task)
            ->whereIn('id', $selectedIds)
            ->delete();

        return redirect()->route('tasks.index')->with('status', 'Subtugas terpilih berhasil dihapus.');
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
                    'kategori' => 'work',
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
        $taskIds = DB::query()->fromSub($unfinishedParents, 'notifications')->select('task_id');
        $notifications = DB::table('tugas')
            ->select('id', 'nama_tugas', 'deadline')
            ->where('user_id', $userId)
            ->whereIn('id', $taskIds)
            ->orderBy('deadline')
            ->orderBy('id')
            ->limit(6)
            ->get();

        return response()->json([
            'count' => $count,
            'notifications' => $notifications,
        ]);
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
