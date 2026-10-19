<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FcmDeviceController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'fid' => ['required', 'string', 'max:128'],
        ]);

        $userId = (string) $request->user()->getAuthIdentifier();

        DB::table('fcm_devices')->upsert(
            [[
                'fid' => $validated['fid'],
                'user_id' => $userId,
                'last_seen_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['fid'],
            ['user_id', 'last_seen_at', 'updated_at']
        );

        return response()->json([
            'success' => true,
            'message' => 'Perangkat berhasil didaftarkan.',
        ]);
    }
}