<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class WhatsAppCloudApiService
{
    public function sendText(string $recipient, string $message): void
    {
        $accessToken = config('services.whatsapp.access_token');
        $phoneNumberId = config('services.whatsapp.phone_number_id');
        $version = config('services.whatsapp.graph_api_version');

        if (!is_string($accessToken) || $accessToken === ''
            || !is_string($phoneNumberId) || $phoneNumberId === ''
            || !is_string($version) || !preg_match('/^v\d+\.\d+$/', $version)) {
            throw new RuntimeException('WhatsApp Cloud API environment settings are incomplete.');
        }

        $response = Http::acceptJson()
            ->asJson()
            ->withToken($accessToken)
            ->timeout(15)
            ->post("https://graph.facebook.com/{$version}/{$phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $recipient,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => mb_substr($message, 0, 4096),
                ],
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('WhatsApp Cloud API request failed.', $response->status());
        }
    }
}
