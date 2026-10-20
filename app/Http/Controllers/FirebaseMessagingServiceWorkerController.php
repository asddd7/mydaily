<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class FirebaseMessagingServiceWorkerController extends Controller
{
    public function __invoke(): Response
    {
        $config = json_encode(
            config('firebase.web'),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
        );
        $source = <<<JAVASCRIPT
importScripts('https://www.gstatic.com/firebasejs/13.0.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/13.0.0/firebase-messaging-compat.js');

firebase.initializeApp({$config});
const messaging = firebase.messaging();
const myDailyManifest = {
    id: '/dashboard',
    name: 'MyDaily',
    short_name: 'MyDaily',
    description: 'Aplikasi produktivitas pribadi MyDaily.',
    start_url: '/dashboard',
    scope: '/',
    display: 'standalone',
    display_override: ['standalone', 'minimal-ui'],
    prefer_related_applications: false,
    orientation: 'portrait',
    theme_color: '#1d4ed8',
    background_color: '#f8fafc',
    icons: [
        { src: '/icons/icon-192.png', sizes: '192x192', type: 'image/png', purpose: 'any' },
        { src: '/icons/icon-512.png', sizes: '512x512', type: 'image/png', purpose: 'any' },
        { src: '/icons/icon-maskable-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
    ],
};

// Provide a valid manifest when the host's browser challenge intercepts it.
self.addEventListener('fetch', event => {
    const requestUrl = new URL(event.request.url);
    const manifestPaths = ['/manifest.php', '/manifest.json', '/manifest.webmanifest'];

    if (
        event.request.method === 'GET' &&
        requestUrl.origin === self.location.origin &&
        manifestPaths.includes(requestUrl.pathname)
    ) {
        event.respondWith(new Response(JSON.stringify(myDailyManifest), {
            status: 200,
            headers: {
                'Content-Type': 'application/manifest+json; charset=utf-8',
                'Cache-Control': 'no-cache, no-store, must-revalidate',
            },
        }));
        return;
    }

    if (event.request.mode !== 'navigate' || event.request.method !== 'GET') return;

    event.respondWith(fetch(event.request).catch(() => new Response(
        '<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MyDaily offline</title><body style="font:16px system-ui;padding:2rem"><h1>MyDaily</h1><p>Koneksi internet terputus. Periksa koneksi lalu muat ulang halaman.</p></body></html>',
        { status: 200, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
    )));
});

// Handle background FCM messages.
messaging.onBackgroundMessage(payload => {
    const data = payload.data || {};
    return self.registration.showNotification(data.title || payload.notification?.title || 'MyDaily', {
        body: data.body || payload.notification?.body || 'Ada pengingat tugas yang belum selesai.',
        icon: '/icons/icon-192.png',
        badge: '/icons/icon-192.png',
        data: { url: data.url || payload.fcmOptions?.link || '/dashboard' }
    });
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    const targetUrl = new URL(event.notification.data?.url || '/', self.location.origin).href;
    event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clients => {
        const existing = clients.find(client => client.url === targetUrl);
        return existing ? existing.focus() : self.clients.openWindow(targetUrl);
    }));
});
JAVASCRIPT;

        return response($source, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
