<?php

return [
    'whatsapp' => [
        'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
        'graph_api_version' => env('WHATSAPP_GRAPH_API_VERSION'),
    ],
    'cloudflare_push' => [
        'url' => env('CLOUDFLARE_PUSH_WORKER_URL'),
        'secret' => env('CLOUDFLARE_PUSH_SYNC_SECRET'),
    ],
];
