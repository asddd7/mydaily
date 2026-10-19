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
