<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class DueTaskPushSender
{
    public function __construct(private readonly FirebaseMessagingService $messaging)
    {
    }

    /**
     * @return array{sent: int, skipped: int, failed: int}
     */
    public function sendTodayDueTasks(): array
    {
        $tasks = DB::table('tugas')
            ->whereNull('parent_id')
            ->where('selesai', 0)
            ->whereDate('deadline', today()->toDateString())
            ->orderBy('id')
            ->get(['id', 'user_id', 'nama_tugas', 'deadline']);
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($tasks as $task) {
            $subscriptions = DB::table('push_subscriptions')
                ->where('user_id', $task->user_id)
                ->get(['id', 'token']);

            foreach ($subscriptions as $subscription) {
                $deliveryExists = DB::table('push_notification_deliveries')
                    ->where('push_subscription_id', $subscription->id)
                    ->where('task_id', $task->id)
                    ->where('deadline', $task->deadline)
                    ->exists();
                if ($deliveryExists) {
                    $skipped++;
                    continue;
                }

                try {
                    $delivered = $this->messaging->send(
                        $subscription->token,
                        'Pengingat tugas hari ini',
                        $task->nama_tugas.' jatuh tempo hari ini.',
                        route('tasks.show', ['task' => $task->id], absolute: false)
                    );
                    if (!$delivered) {
                        $skipped++;
                        continue;
                    }

                    DB::table('push_notification_deliveries')->insert([
                        'push_subscription_id' => $subscription->id,
                        'task_id' => $task->id,
                        'deadline' => $task->deadline,
                        'sent_at' => now(),
                    ]);
                    $sent++;
                } catch (Throwable $error) {
                    report($error);
                    $failed++;
                }
            }
        }

        if ($failed > 0) {
            throw new RuntimeException("Failed to send {$failed} task push notification(s).");
        }

        return compact('sent', 'skipped', 'failed');
    }
}
