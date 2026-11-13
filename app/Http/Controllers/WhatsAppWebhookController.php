<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppCloudApiService;
use App\Services\WhatsAppCommandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));
        $expectedToken = config('services.whatsapp.verify_token');

        if ($mode !== 'subscribe' || !is_string($token) || !is_string($expectedToken)
            || $expectedToken === '' || !hash_equals($expectedToken, $token)
            || !is_string($challenge)) {
            return response('Forbidden', 403);
        }

        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    public function receive(
        Request $request,
        WhatsAppCommandService $commands,
        WhatsAppCloudApiService $cloudApi,
    ): JsonResponse {
        if (strlen($request->getContent()) > 1_000_000) {
            return response()->json(['message' => 'Webhook payload is too large.'], 413);
        }

        $appSecret = config('services.whatsapp.app_secret');
        $signature = $request->header('X-Hub-Signature-256');
        if (!is_string($appSecret) || $appSecret === '' || !is_string($signature)
            || !preg_match('/^sha256=([a-f0-9]{64})$/i', $signature, $matches)
            || !hash_equals(hash_hmac('sha256', $request->getContent(), $appSecret), strtolower($matches[1]))) {
            return response()->json(['message' => 'Invalid webhook signature.'], 401);
        }

        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return response()->json(['message' => 'Invalid JSON payload.'], 400);
        }

        if (!is_array($payload) || ($payload['object'] ?? null) !== 'whatsapp_business_account') {
            return response()->json(['message' => 'Unsupported webhook payload.'], 400);
        }

        $processingFailed = false;
        foreach (($payload['entry'] ?? []) as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            foreach (($entry['changes'] ?? []) as $change) {
                if (!is_array($change) || !is_array($change['value'] ?? null)) {
                    continue;
                }
                foreach (($change['value']['messages'] ?? []) as $message) {
                    if (!is_array($message)) {
                        continue;
                    }
                    if (($message['type'] ?? null) !== 'text'
                        || !is_string($message['id'] ?? null)
                        || !is_string($message['from'] ?? null)
                        || !is_string($message['text']['body'] ?? null)) {
                        continue;
                    }

                    try {
                        $reply = $commands->handle(
                            $message['id'],
                            $message['from'],
                            $message['text']['body'],
                        );
                    } catch (Throwable $error) {
                        Log::error('WhatsApp webhook message could not be processed.', [
                            'exception' => $error::class,
                            'error_code' => $error->getCode(),
                        ]);
                        $processingFailed = true;
                        continue;
                    }

                    if ($reply === null) {
                        continue;
                    }

                    try {
                        $cloudApi->sendText($message['from'], $reply);
                    } catch (Throwable $error) {
                        Log::error('WhatsApp webhook reply could not be sent.', [
                            'exception' => $error::class,
                            'error_code' => $error->getCode(),
                        ]);
                    }
                }
            }
        }

        return response()->json(
            ['success' => !$processingFailed],
            $processingFailed ? 503 : 200,
        );
    }
}
