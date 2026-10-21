<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CloudflarePushSyncService
{
    public function syncTask(int|string $taskId, int|string $userId, ?string $title, mixed $reminderAt): bool
    {
        if ($reminderAt === null || $title === null) {
            return $this->request('DELETE', '/sync/task/'.rawurlencode((string) $taskId));
        }

        return $this->request('POST', '/sync/task', [
            'task_id' => (string) $taskId,
            'user_id' => (string) $userId,
            'title' => $title,
            'reminder_at' => Carbon::parse($reminderAt)->utc()->toISOString(),
            'target_url' => '/tasks/'.rawurlencode((string) $taskId),
        ]);
    }

    public function syncDevice(string $token, int|string $userId): bool
    {
        return $this->request('POST', '/sync/device', [
            'user_id' => (string) $userId,
            'token' => $token,
            'token_hash' => hash('sha256', $token),
        ]);
    }

    public function removeDevice(string $token): bool
    {
        return $this->request('DELETE', '/sync/device/'.hash('sha256', $token));
    }

    public function syncUserSnapshot(int|string $userId): bool
    {
        $reminders = DB::table('tugas')
            ->where('user_id', $userId)
            ->whereNull('parent_id')
            ->where('selesai', 0)
            ->whereNotNull('reminder_at')
            ->whereNull('reminder_sent_at')
            ->get(['id', 'nama_tugas', 'reminder_at']);
        $devices = DB::table('push_subscriptions')
            ->where('user_id', $userId)
            ->get(['token', 'token_hash']);

        return $this->request('POST', '/sync/snapshot', [
            'user_id' => (string) $userId,
            'reminders' => $reminders->map(fn ($task): array => [
                'task_id' => (string) $task->id,
                'title' => $task->nama_tugas,
                'reminder_at' => Carbon::parse($task->reminder_at)->utc()->toISOString(),
                'target_url' => '/tasks/'.rawurlencode((string) $task->id),
            ])->all(),
            'devices' => $devices->map(fn ($device): array => [
                'token' => $device->token,
                'token_hash' => $device->token_hash,
            ])->all(),
        ]);
    }

    private function request(string $method, string $path, ?array $payload = null): bool
    {
        $baseUrl = rtrim((string) config('services.cloudflare_push.url'), '/');
        $secret = (string) config('services.cloudflare_push.secret');

        if ($baseUrl === '' || strlen($secret) < 32) {
            Log::warning('Cloudflare push sync is not configured.');

            return false;
        }

        try {
            $request = Http::timeout(8)
                ->withToken($secret)
                ->acceptJson();

            $response = $method === 'DELETE'
                ? $request->delete($baseUrl.$path)
                : $request->post($baseUrl.$path, $payload ?? []);

            if (!$response->successful()) {
                Log::warning('Cloudflare push sync request failed.', [
                    'status' => $response->status(),
                    'path' => $path,
                ]);

                return false;
            }

            return true;
        } catch (Throwable $error) {
            Log::warning('Cloudflare push sync request could not be completed.', [
                'exception' => $error::class,
                'path' => $path,
            ]);

            return false;
        }
    }
}
