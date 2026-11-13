<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class WhatsAppCommandService
{
    public function __construct(private readonly TaskCompletionService $taskCompletion)
    {
    }

    public function handle(string $messageId, string $sender, string $body): ?string
    {
        return DB::transaction(function () use ($messageId, $sender, $body): ?string {
            $inserted = DB::table('whatsapp_inbound_messages')->insertOrIgnore([
                'message_id' => $messageId,
                'sender' => $sender,
                'received_at' => now(),
            ]);

            if ($inserted === 0) {
                return null;
            }

            $account = DB::table('whatsapp_accounts')
                ->where('phone_number', $sender)
                ->first();

            if ($account) {
                $reply = $this->runCommand((int) $account->user_id, trim($body));
            } else {
                $reply = $this->pairAccount($sender, trim($body));
            }

            DB::table('whatsapp_inbound_messages')
                ->where('message_id', $messageId)
                ->update(['processed_at' => now()]);

            return $reply;
        });
    }

    private function pairAccount(string $sender, string $body): string
    {
        if (!preg_match('/^(?:LINK|TAUTKAN)\s+(\d{8})$/iu', $body, $matches)) {
            return "Nomor ini belum terhubung ke MyDaily. Buat kode di Pengaturan > WhatsApp Bot, lalu kirim: TAUTKAN 12345678";
        }

        $rateLimitKey = 'whatsapp-pair:'.hash('sha256', $sender);
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return 'Terlalu banyak percobaan. Coba tautkan kembali dalam 15 menit.';
        }

        RateLimiter::hit($rateLimitKey, 900);
        $codeHash = hash('sha256', $matches[1]);
        $account = DB::table('whatsapp_accounts')
            ->where('pairing_code_hash', $codeHash)
            ->where('pairing_expires_at', '>', now())
            ->lockForUpdate()
            ->first();

        if (!$account) {
            return 'Kode tautan tidak valid atau sudah kedaluwarsa. Buat kode baru dari MyDaily.';
        }

        $phoneAlreadyLinked = DB::table('whatsapp_accounts')
            ->where('phone_number', $sender)
            ->exists();
        if ($phoneAlreadyLinked) {
            return 'Nomor WhatsApp ini sudah terhubung ke akun MyDaily lain.';
        }

        DB::table('whatsapp_accounts')
            ->where('id', $account->id)
            ->update([
                'phone_number' => $sender,
                'pairing_code_hash' => null,
                'pairing_expires_at' => null,
                'linked_at' => now(),
                'updated_at' => now(),
            ]);
        RateLimiter::clear($rateLimitKey);

        return "Akun MyDaily berhasil ditautkan.\nKirim BANTUAN untuk melihat perintah.";
    }

    private function runCommand(int $userId, string $body): string
    {
        if (preg_match('/^(?:BANTUAN|HELP)$/iu', $body)) {
            return $this->helpMessage();
        }

        if (preg_match('/^DAFTAR$/iu', $body)) {
            return $this->listItems($userId);
        }

        if (preg_match('/^TODO\s+TAMBAH\s+(.+)$/isu', $body, $matches)) {
            $title = $this->normalizeTitle($matches[1]);
            if ($title === '' || mb_strlen($title) > 255) {
                return 'Judul to-do harus berisi 1-255 karakter.';
            }

            DB::table('daily_todos')->insert([
                'user_id' => $userId,
                'todo_date' => today()->toDateString(),
                'title' => $title,
                'is_completed' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return 'To-do hari ini berhasil ditambahkan: '.$title;
        }

        if (preg_match('/^TODO\s+UBAH\s+(\d+)\s+(.+)$/isu', $body, $matches)) {
            $title = $this->normalizeTitle($matches[2]);
            if ($title === '' || mb_strlen($title) > 255) {
                return 'Judul to-do harus berisi 1-255 karakter.';
            }

            $updated = DB::table('daily_todos')
                ->where('id', (int) $matches[1])
                ->where('user_id', $userId)
                ->update(['title' => $title, 'updated_at' => now()]);

            return $updated ? 'To-do berhasil diubah.' : 'To-do tidak ditemukan atau tidak berubah.';
        }

        if (preg_match('/^TODO\s+SELESAI\s+(\d+)$/iu', $body, $matches)) {
            $updated = DB::table('daily_todos')
                ->where('id', (int) $matches[1])
                ->where('user_id', $userId)
                ->update(['is_completed' => true, 'updated_at' => now()]);

            return $updated ? 'To-do ditandai selesai.' : 'To-do tidak ditemukan.';
        }

        if (preg_match('/^TODO\s+HAPUS\s+(\d+)\s+KONFIRMASI$/iu', $body, $matches)) {
            $deleted = DB::table('daily_todos')
                ->where('id', (int) $matches[1])
                ->where('user_id', $userId)
                ->delete();

            return $deleted ? 'To-do berhasil dihapus.' : 'To-do tidak ditemukan.';
        }

        if (preg_match('/^TASK\s+TAMBAH\s+(.+)$/isu', $body, $matches)) {
            $title = $this->normalizeTitle($matches[1]);
            if ($title === '' || mb_strlen($title) > 100) {
                return 'Judul task harus berisi 1-100 karakter.';
            }

            DB::table('tugas')->insert([
                'user_id' => $userId,
                'nama_tugas' => $title,
                'deadline' => today()->toDateString(),
                'selesai' => false,
                'recurring_type' => 'none',
                'kategori' => 'casual',
                'urutan' => 0,
            ]);

            return 'Task berhasil ditambahkan dengan tenggat hari ini: '.$title;
        }

        if (preg_match('/^TASK\s+UBAH\s+(\d+)\s+(.+)$/isu', $body, $matches)) {
            $title = $this->normalizeTitle($matches[2]);
            if ($title === '' || mb_strlen($title) > 100) {
                return 'Judul task harus berisi 1-100 karakter.';
            }

            $updated = DB::table('tugas')
                ->where('id', (int) $matches[1])
                ->where('user_id', $userId)
                ->whereNull('parent_id')
                ->update(['nama_tugas' => $title]);

            return $updated ? 'Task berhasil diubah.' : 'Task tidak ditemukan atau tidak berubah.';
        }

        if (preg_match('/^TASK\s+SELESAI\s+(\d+)$/iu', $body, $matches)) {
            $taskId = (int) $matches[1];
            $exists = DB::table('tugas')
                ->where('id', $taskId)
                ->where('user_id', $userId)
                ->whereNull('parent_id')
                ->exists();

            if (!$exists) {
                return 'Task tidak ditemukan.';
            }

            $this->taskCompletion->setCompleted($userId, $taskId, true);

            return 'Task ditandai selesai.';
        }

        if (preg_match('/^TASK\s+HAPUS\s+(\d+)\s+KONFIRMASI$/iu', $body, $matches)) {
            $taskId = (int) $matches[1];
            $deleted = DB::transaction(function () use ($taskId, $userId): int {
                $exists = DB::table('tugas')
                    ->where('id', $taskId)
                    ->where('user_id', $userId)
                    ->whereNull('parent_id')
                    ->lockForUpdate()
                    ->exists();
                if (!$exists) {
                    return 0;
                }

                DB::table('tugas')->where('parent_id', $taskId)->where('user_id', $userId)->delete();

                return DB::table('tugas')->where('id', $taskId)->where('user_id', $userId)->delete();
            });

            return $deleted ? 'Task dan subtugasnya berhasil dihapus.' : 'Task tidak ditemukan.';
        }

        return "Perintah tidak dikenali. Kirim BANTUAN untuk melihat daftar perintah.";
    }

    private function listItems(int $userId): string
    {
        $todos = DB::table('daily_todos')
            ->where('user_id', $userId)
            ->whereDate('todo_date', today()->toDateString())
            ->orderBy('id')
            ->limit(10)
            ->get(['id', 'title', 'is_completed']);
        $tasks = DB::table('tugas')
            ->where('user_id', $userId)
            ->whereNull('parent_id')
            ->where('selesai', false)
            ->orderBy('deadline')
            ->orderBy('urutan')
            ->limit(10)
            ->get(['id', 'nama_tugas', 'deadline']);

        $lines = ['To-do hari ini:'];
        foreach ($todos as $todo) {
            $mark = $todo->is_completed ? '[x]' : '[ ]';
            $lines[] = "{$mark} TODO #{$todo->id} ".mb_substr($todo->title, 0, 120);
        }
        if ($todos->isEmpty()) {
            $lines[] = 'Belum ada to-do.';
        }

        $lines[] = '';
        $lines[] = 'Task yang belum selesai:';
        foreach ($tasks as $task) {
            $lines[] = "[ ] TASK #{$task->id} ".mb_substr($task->nama_tugas, 0, 120)." ({$task->deadline})";
        }
        if ($tasks->isEmpty()) {
            $lines[] = 'Tidak ada task yang belum selesai.';
        }
        $lines[] = '';
        $lines[] = 'Kirim BANTUAN untuk perintah.';

        return implode("\n", $lines);
    }

    private function normalizeTitle(string $title): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $title));
    }

    private function helpMessage(): string
    {
        return implode("\n", [
            'Perintah MyDaily:',
            'DAFTAR',
            'TODO TAMBAH <judul>',
            'TODO UBAH <id> <judul>',
            'TODO SELESAI <id>',
            'TODO HAPUS <id> KONFIRMASI',
            'TASK TAMBAH <judul> (tenggat hari ini)',
            'TASK UBAH <id> <judul>',
            'TASK SELESAI <id>',
            'TASK HAPUS <id> KONFIRMASI',
            '',
            'ID item terlihat pada hasil DAFTAR. Untuk menautkan akun: TAUTKAN <kode>.',
        ]);
    }
}
