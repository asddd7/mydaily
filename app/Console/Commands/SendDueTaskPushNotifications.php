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
            $result = $sender->sendTodayDueTasks();
        } catch (Throwable $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        }

        $this->info("Push notifications sent: {$result['sent']}; skipped: {$result['skipped']}.");

        return self::SUCCESS;
    }
}
