<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TaskCompletionService
{
    public function setCompleted(int|string $userId, int $taskId, bool $done): void
    {
        DB::transaction(function () use ($taskId, $userId, $done): void {
            $item = DB::table('tugas')
                ->where('id', $taskId)
                ->where('user_id', $userId)
                ->whereNull('parent_id')
                ->lockForUpdate()
                ->first();
            abort_unless($item, 404);

            DB::table('tugas')->where('id', $taskId)->update([
                'selesai' => $done,
                'selesai_at' => $done ? now() : null,
            ]);
            DB::table('tugas')->where('parent_id', $taskId)->where('user_id', $userId)->update([
                'selesai' => $done,
                'selesai_at' => $done ? now() : null,
            ]);

            if (!$done) {
                DB::table('tugas')->where('id', $taskId)->update(['recurring_generated' => 0]);

                return;
            }

            if ($item->recurring_type === 'none' || $item->recurring_generated) {
                return;
            }

            $baseDeadline = Carbon::parse($item->deadline ?? today()->toDateString());
            $nextDeadline = match ($item->recurring_type) {
                'daily' => $baseDeadline->copy()->addDay(),
                'weekly' => $baseDeadline->copy()->addWeek(),
                'monthly' => $baseDeadline->copy()->addMonth(),
                'yearly' => $baseDeadline->copy()->addYear(),
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

            $children = DB::table('tugas')->where('parent_id', $taskId)->where('user_id', $userId)->get();
            foreach ($children as $child) {
                DB::table('tugas')->insert([
                    'nama_tugas' => $child->nama_tugas,
                    'deadline' => $nextDeadline->toDateString(),
                    'user_id' => $userId,
                    'parent_id' => $nextId,
                    'kategori' => $item->kategori,
                ]);
            }

            DB::table('tugas')->where('id', $taskId)->update(['recurring_generated' => 1]);
        });
    }
}
