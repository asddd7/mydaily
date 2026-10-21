<?php

return [
    'cloudflare_push' => [
        'url' => env('CLOUDFLARE_PUSH_WORKER_URL'),
        'secret' => env('CLOUDFLARE_PUSH_SYNC_SECRET'),
    ],
];
