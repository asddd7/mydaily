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
        if (now()->format('H:i') < '08:00') {
            return ['sent' => 0, 'skipped' => 0, 'failed' => 0];
        }

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

    /**
     * @return array{sent: int, skipped: int, failed: int}
     */
    public function sendScheduledReminders(): array
    {
        $tasks = DB::table('tugas')
            ->whereNull('parent_id')
            ->where('selesai', 0)
            ->whereNotNull('reminder_at')
            ->whereNull('reminder_sent_at')
            ->where('reminder_at', '<=', now())
            ->orderBy('reminder_at')
            ->orderBy('id')
            ->get(['id', 'user_id', 'nama_tugas', 'reminder_at']);
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($tasks as $task) {
            $subscriptions = DB::table('push_subscriptions')
                ->where('user_id', $task->user_id)
                ->get(['id', 'token']);
            $taskFailed = false;

            foreach ($subscriptions as $subscription) {
                $alreadyDelivered = DB::table('task_reminder_deliveries')
                    ->where('push_subscription_id', $subscription->id)
                    ->where('task_id', $task->id)
                    ->where('reminder_at', $task->reminder_at)
                    ->exists();
                if ($alreadyDelivered) {
                    $skipped++;
                    continue;
                }

                try {
                    $delivered = $this->messaging->send(
                        $subscription->token,
                        'Pengingat tugas MyDaily',
                        'Waktunya mengerjakan: '.$task->nama_tugas,
                        route('tasks.show', ['task' => $task->id], absolute: false)
                    );
                    if (!$delivered) {
                        $skipped++;
                        continue;
                    }

                    DB::table('task_reminder_deliveries')->insert([
                        'push_subscription_id' => $subscription->id,
                        'task_id' => $task->id,
                        'reminder_at' => $task->reminder_at,
                        'sent_at' => now(),
                    ]);
                    $sent++;
                } catch (Throwable $error) {
                    report($error);
                    $failed++;
                    $taskFailed = true;
                }
            }

            if (!$taskFailed) {
                DB::table('tugas')
                    ->where('id', $task->id)
                    ->where('reminder_at', $task->reminder_at)
                    ->whereNull('reminder_sent_at')
                    ->update(['reminder_sent_at' => now()]);
            }
        }

        if ($failed > 0) {
            throw new RuntimeException("Failed to send {$failed} scheduled task reminder(s).");
        }

        return compact('sent', 'skipped', 'failed');
    }
}
