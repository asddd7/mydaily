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

// Handle page navigations so Chrome can treat this as an installable PWA,
// while keeping authenticated MyDaily pages network-only.
self.addEventListener('fetch', event => {
    if (event.request.mode !== 'navigate' || event.request.method !== 'GET') return;

    event.respondWith(fetch(event.request).catch(() => new Response(
        '<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MyDaily offline</title><body style="font:16px system-ui;padding:2rem"><h1>MyDaily</h1><p>Koneksi internet terputus. Periksa koneksi lalu muat ulang halaman.</p></body></html>',
        { status: 200, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
    )));
});

messaging.onBackgroundMessage(payload => {
    const data = payload.data || {};
    self.registration.showNotification(data.title || 'MyDaily', {
        body: data.body || 'Ada pengingat tugas yang belum selesai.',
        icon: '/favicon.ico',
        data: { url: data.url || '/' }
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
