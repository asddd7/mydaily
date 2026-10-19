<?php

return [
    'web' => [
        'apiKey' => env('FIREBASE_API_KEY'),
        'authDomain' => env('FIREBASE_AUTH_DOMAIN'),
        'projectId' => env('FIREBASE_PROJECT_ID'),
        'storageBucket' => env('FIREBASE_STORAGE_BUCKET'),
        'messagingSenderId' => env('FIREBASE_MESSAGING_SENDER_ID'),
        'appId' => env('FIREBASE_APP_ID'),
    ],
    'project_id' => env('FIREBASE_PROJECT_ID'),
    'vapid_key' => env('FIREBASE_VAPID_KEY'),
    'service_account_base64' => env('FIREBASE_SERVICE_ACCOUNT_BASE64'),
    'cron_secret' => env('PUSH_CRON_SECRET'),
];
