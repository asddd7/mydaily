<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FirebaseMessagingService
{
    private ?ServiceAccountCredentials $credentials = null;

    private ?string $accessToken = null;

    private int $accessTokenExpiresAt = 0;

    public function send(string $deviceToken, string $title, string $body, string $url): bool
    {
        $projectId = (string) config('firebase.project_id');
        if ($projectId === '') {
            throw new RuntimeException('FIREBASE_PROJECT_ID is not configured.');
        }

        $accessToken = $this->getAccessToken();
        $endpoint = 'https://fcm.googleapis.com/v1/projects/'.rawurlencode($projectId).'/messages:send';
        $response = Http::timeout(20)
            ->withToken($accessToken)
            ->post($endpoint, [
                'message' => [
                    'token' => $deviceToken,
                    'data' => [
                        'title' => $title,
                        'body' => $body,
                        'url' => $url,
                    ],
                    'webpush' => [
                        'headers' => ['Urgency' => 'high'],
                    ],
                ],
            ]);

        if ($response->successful()) {
            return true;
        }

        $errorCode = data_get($response->json(), 'error.details.0.errorCode');
        if ($errorCode === 'UNREGISTERED') {
            $tokenHash = hash('sha256', $deviceToken);
            $subscriptionIds = DB::table('push_subscriptions')
                ->where('token_hash', $tokenHash)
                ->pluck('id');
            DB::table('push_notification_deliveries')
                ->whereIn('push_subscription_id', $subscriptionIds)
                ->delete();
            DB::table('push_subscriptions')->whereIn('id', $subscriptionIds)->delete();

            return false;
        }

        throw new RuntimeException(
            'Firebase Cloud Messaging rejected a push request (HTTP '.$response->status().').'
        );
    }

    private function getAccessToken(): string
    {
        if ($this->accessToken && $this->accessTokenExpiresAt > time() + 60) {
            return $this->accessToken;
        }

        if (!$this->credentials) {
            $keyJson = base64_decode((string) config('firebase.service_account_base64'), true);
            $credentialsData = $keyJson === false ? null : json_decode($keyJson, true);
            if (!is_array($credentialsData)) {
                throw new RuntimeException('FIREBASE_SERVICE_ACCOUNT_BASE64 is missing or invalid.');
            }

            $this->credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/firebase.messaging',
                $credentialsData
            );
        }

        $tokenData = $this->credentials->fetchAuthToken();
        $accessToken = $tokenData['access_token'] ?? null;
        if (!is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('Google did not return an access token for Firebase Cloud Messaging.');
        }

        $expiresIn = $tokenData['expires_in'] ?? null;
        if (!is_numeric($expiresIn) || (int) $expiresIn < 1) {
            throw new RuntimeException('Google returned an invalid access-token expiration.');
        }

        $this->accessToken = $accessToken;
        $this->accessTokenExpiresAt = time() + (int) $expiresIn;

        return $this->accessToken;
    }
}
