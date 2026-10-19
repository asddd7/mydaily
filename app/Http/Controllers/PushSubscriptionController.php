<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:4096'],
            'user_agent' => ['nullable', 'string', 'max:1000'],
        ]);

        $now = now();
        DB::table('push_subscriptions')->updateOrInsert(
            ['token_hash' => hash('sha256', $data['token'])],
            [
                'user_id' => $request->attributes->get('currentUser')->id,
                'token' => $data['token'],
                'user_agent' => $data['user_agent'] ?? null,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        return response()->json(['status' => 'registered']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'max:4096']]);
        $subscriptionIds = DB::table('push_subscriptions')
            ->where('user_id', $request->attributes->get('currentUser')->id)
            ->where('token_hash', hash('sha256', $data['token']))
            ->pluck('id');

        if ($subscriptionIds->isNotEmpty()) {
            DB::table('push_notification_deliveries')
                ->whereIn('push_subscription_id', $subscriptionIds)
                ->delete();
            DB::table('push_subscriptions')->whereIn('id', $subscriptionIds)->delete();
        }

        return response()->json(['status' => 'unregistered']);
    }
}
