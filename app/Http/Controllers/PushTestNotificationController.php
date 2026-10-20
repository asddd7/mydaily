<?php

namespace App\Http\Controllers;

use App\Services\FirebaseMessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PushTestNotificationController extends Controller
{
    public function store(Request $request, FirebaseMessagingService $messaging): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
        ]);
        $userId = $request->attributes->get('currentUser')->id;
        $subscription = DB::table('push_subscriptions')
            ->where('user_id', $userId)
            ->where('token_hash', hash('sha256', $validated['token']))
            ->first(['token']);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Perangkat ini belum terdaftar. Aktifkan notifikasi lewat ikon lonceng di perangkat ini terlebih dahulu.',
            ], 422);
        }

        try {
            $sent = $messaging->send(
                $subscription->token,
                'Tes notifikasi MyDaily',
                'Berhasil! Stopwatch MyDaily mengirim notifikasi ke perangkat ini.',
                route('clock.edit', absolute: false)
            );
        } catch (Throwable $error) {
            report($error);

            return response()->json([
                'success' => false,
                'message' => 'Firebase gagal mengirim notifikasi ke perangkat ini.',
            ], 502);
        }

        if (!$sent) {
            return response()->json([
                'success' => false,
                'message' => 'Token perangkat sudah tidak aktif. Aktifkan ulang notifikasi di perangkat ini.',
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi tes berhasil dikirim.',
            'sent' => 1,
        ]);
    }
}
