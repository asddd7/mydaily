<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CloudflarePushSyncService
{
    /**
     * Return this account's current reminder/device snapshot for the browser
     * to send directly to Cloudflare. InfinityFree blocks PHP connections to
     * Cloudflare Workers, so this service must not make outbound HTTP calls.
     *
     * @return array{user_id: string, reminders: array<int, array<string, string>>, devices: array<int, array<string, string>>}
     */
    public function snapshotForUser(int|string $userId): array
    {
        $through = now()->addDays(7)->toDateString();
        $reminders = DB::table('tugas')
            ->where('user_id', $userId)
            ->whereNull('parent_id')
            ->where('selesai', 0)
            ->whereNotNull('reminder_at')
            ->whereNull('reminder_sent_at')
            ->get(['id', 'nama_tugas', 'reminder_at', 'reminder_repeat_type']);
        $dailyTasks = DB::table('tugas')
            ->where('user_id', $userId)
            ->whereNull('parent_id')
            ->where('selesai', 0)
            ->whereDate('deadline', '<=', $through)
            ->orderBy('deadline')
            ->limit(100)
            ->get(['id', 'nama_tugas as title', 'deadline']);
        $dailyMarks = DB::table('calendar_marks')
            ->where('user_id', $userId)
            ->where(function ($query): void {
                $query->whereNull('selesai')->orWhere('selesai', 0);
            })
            ->whereDate('tanggal', '<=', $through)
            ->orderBy('tanggal')
            ->limit(100)
            ->get(['id', 'title', 'tanggal']);
        $devices = DB::table('push_subscriptions')
            ->where('user_id', $userId)
            ->get(['token', 'token_hash']);

        return [
            'user_id' => (string) $userId,
            'reminders' => $reminders->map(fn ($task): array => [
                'task_id' => (string) $task->id,
                'title' => $task->nama_tugas,
                'reminder_at' => Carbon::parse($task->reminder_at)->utc()->toISOString(),
                'repeat_type' => $task->reminder_repeat_type ?: 'once',
                'target_url' => '/tasks/'.rawurlencode((string) $task->id),
            ])->all(),
            'daily_items' => [
                ...$dailyTasks->map(fn ($task): array => [
                    'type' => 'task',
                    'id' => (string) $task->id,
                    'title' => (string) $task->title,
                    'due_date' => (string) $task->deadline,
                    'target_url' => '/tasks/'.rawurlencode((string) $task->id),
                ])->all(),
                ...$dailyMarks->map(fn ($mark): array => [
                    'type' => 'mark',
                    'id' => (string) $mark->id,
                    'title' => (string) $mark->title,
                    'due_date' => (string) $mark->tanggal,
                    'target_url' => '/calendar',
                ])->all(),
            ],
            'devices' => $devices->map(fn ($device): array => [
                'token' => $device->token,
                'token_hash' => $device->token_hash,
            ])->all(),
        ];
    }

    public function issueBrowserTicket(int|string $userId): string
    {
        $secret = (string) config('services.cloudflare_push.secret');
        if (strlen($secret) < 32) {
            throw new RuntimeException('CLOUDFLARE_PUSH_SYNC_SECRET is missing or too short.');
        }

        $encode = static fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        $header = $encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $payload = $encode(json_encode([
            'sub' => (string) $userId,
            'aud' => 'mydaily-push-sync',
            'exp' => now()->timestamp + 300,
        ], JSON_THROW_ON_ERROR));
        $unsigned = $header.'.'.$payload;
        $signature = $encode(hash_hmac('sha256', $unsigned, $secret, true));

        return $unsigned.'.'.$signature;
    }

    public function workerUrl(): string
    {
        $url = rtrim((string) config('services.cloudflare_push.url'), '/');
        if (!filter_var($url, FILTER_VALIDATE_URL) || !str_starts_with($url, 'https://')) {
            throw new RuntimeException('CLOUDFLARE_PUSH_WORKER_URL is missing or invalid.');
        }

        return $url;
    }
}
