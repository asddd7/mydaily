<?php

namespace App\Http\Controllers;

use App\Services\CloudflarePushSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CloudflarePushSyncController extends Controller
{
    public function __invoke(Request $request, CloudflarePushSyncService $sync): JsonResponse
    {
        $synced = $sync->syncUserSnapshot($request->attributes->get('currentUser')->id);

        return response()->json([
            'success' => $synced,
            'message' => $synced
                ? 'Pengingat dan perangkat disinkronkan.'
                : 'Sinkronisasi Cloudflare belum berhasil. Coba lagi saat membuka MyDaily berikutnya.',
        ], $synced ? 200 : 503);
    }
}
