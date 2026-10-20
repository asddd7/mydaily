<?php

declare(strict_types=1);

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

echo json_encode([
    'id' => '/dashboard',
    'name' => 'MyDaily',
    'short_name' => 'MyDaily',
    'description' => 'Aplikasi produktivitas pribadi MyDaily.',
    'start_url' => '/dashboard',
    'scope' => '/',
    'display' => 'standalone',
    'display_override' => ['standalone', 'minimal-ui'],
    'prefer_related_applications' => false,
    'orientation' => 'portrait',
    'theme_color' => '#1d4ed8',
    'background_color' => '#f8fafc',
    'icons' => [
        [
            'src' => '/icons/icon-192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any',
        ],
        [
            'src' => '/icons/icon-512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any',
        ],
        [
            'src' => '/icons/icon-maskable-512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'maskable',
        ],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
