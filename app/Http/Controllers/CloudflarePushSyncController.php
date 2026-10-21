<?php

namespace App\Http\Controllers;

use App\Services\CloudflarePushSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CloudflarePushSyncController extends Controller
{
    public function __invoke(Request $request, CloudflarePushSyncService $sync): JsonResponse
    {
        try {
            $userId = $request->attributes->get('currentUser')->id;
            $snapshot = $sync->snapshotForUser($userId);

            return response()->json([
                'success' => true,
                'worker_url' => $sync->workerUrl(),
                'ticket' => $sync->issueBrowserTicket($userId),
                'snapshot' => $snapshot,
            ]);
        } catch (\Throwable $error) {
            report($error);

            return response()->json([
                'success' => false,
                'message' => 'Data sinkronisasi belum dapat disiapkan oleh Laravel.',
            ], 503);
        }
    }
}
