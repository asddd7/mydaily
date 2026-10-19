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
        $userId = $request->attributes->get('currentUser')->id;
        $subscriptions = DB::table('push_subscriptions')
            ->where('user_id', $userId)
            ->get(['token']);

        if ($subscriptions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada perangkat berlangganan. Aktifkan notifikasi lewat ikon lonceng terlebih dahulu.',
            ], 422);
        }

        $sent = 0;
        $failed = 0;
        foreach ($subscriptions as $subscription) {
            try {
                if ($messaging->send(
                    $subscription->token,
                    'Tes notifikasi MyDaily',
                    'Berhasil! Stopwatch MyDaily mengirim notifikasi ke perangkat ini.',
                    route('clock.edit', absolute: false)
                )) {
                    $sent++;
                } else {
                    $failed++;
                }
            } catch (Throwable $error) {
                report($error);
                $failed++;
            }
        }

        if ($sent === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi gagal dikirim. Periksa konfigurasi Firebase dan izin perangkat.',
            ], 502);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi tes berhasil dikirim.',
            'sent' => $sent,
            'failed' => $failed,
        ]);
    }
}
