<?php

namespace App\Http\Controllers;

use App\Services\DueTaskPushSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class PushNotificationCronController extends Controller
{
    public function __invoke(Request $request, DueTaskPushSender $sender): JsonResponse
    {
        $secret = (string) config('firebase.cron_secret');
        $providedSecret = (string) $request->bearerToken();
        abort_if(strlen($secret) < 32 || !hash_equals($secret, $providedSecret), 403);

        try {
            $dueTasks = $sender->sendTodayDueTasks();
            $scheduledReminders = $sender->sendScheduledReminders();

            return response()->json([
                'sent' => $dueTasks['sent'] + $scheduledReminders['sent'],
                'skipped' => $dueTasks['skipped'] + $scheduledReminders['skipped'],
                'failed' => $dueTasks['failed'] + $scheduledReminders['failed'],
                'due_tasks' => $dueTasks,
                'scheduled_reminders' => $scheduledReminders,
            ]);
        } catch (Throwable $error) {
            report($error);

            throw new RuntimeException('Unable to send due-task push notifications.', 0, $error);
        }
    }
}
