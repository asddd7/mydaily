<?php

namespace App\Console\Commands;

use App\Services\DueTaskPushSender;
use Illuminate\Console\Command;
use Throwable;

class SendDueTaskPushNotifications extends Command
{
    protected $signature = 'notifications:send-due-task-pushes';

    protected $description = 'Send push notifications for unfinished tasks due today.';

    public function handle(DueTaskPushSender $sender): int
    {
        try {
            $dueTasks = $sender->sendTodayDueTasks();
            $reminders = $sender->sendScheduledReminders();
        } catch (Throwable $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        }

        $sent = $dueTasks['sent'] + $reminders['sent'];
        $skipped = $dueTasks['skipped'] + $reminders['skipped'];
        $this->info("Push notifications sent: {$sent}; skipped: {$skipped}.");

        return self::SUCCESS;
    }
}
