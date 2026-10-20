
self.addEventListener('install', (event) => {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

// Keep authenticated pages network-first, and show a small fallback only if
// the network is unavailable. This also enables Chrome's install prompt.
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET' || event.request.mode !== 'navigate') return;

    event.respondWith(fetch(event.request).catch(() => new Response(
        '<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MyDaily offline</title><body style="font:16px system-ui;padding:2rem"><h1>MyDaily</h1><p>Koneksi internet terputus. Periksa koneksi lalu muat ulang halaman.</p></body></html>',
        { status: 200, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
    )));
});

self.addEventListener('push', (event) => {
    if (!event.data) return;

    let payload;

    try {
        payload = event.data.json();
    } catch (error) {
        console.error('Invalid FCM payload', error);
        return;
    }

    const notification = payload.notification ?? payload.data ?? {};
    const title = notification.title ?? 'MyDaily';
    const body = notification.body ?? 'Ada pembaruan di MyDaily.';

    const requestedUrl =
        payload.fcmOptions?.link ??
        payload.data?.url ??
        '/dashboard';

    const parsedUrl = new URL(requestedUrl, self.location.origin);

    const targetUrl =
        parsedUrl.origin === self.location.origin
            ? parsedUrl.href
            : new URL('/dashboard', self.location.origin).href;

    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        });

        const visiblePageExists = windows.some((client) => {
            return (
                client.visibilityState === 'visible' &&
                new URL(client.url).origin === self.location.origin
            );
        });

        // Saat tab aktif, penanganan pesan dilakukan di halaman.
        if (visiblePageExists) return;

        await self.registration.showNotification(title, {
            body,
            icon: '/favicon.ico',
            data: { url: targetUrl }
        });
    })());
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const requestedUrl =
        event.notification.data?.url ?? '/dashboard';

    const parsedUrl = new URL(requestedUrl, self.location.origin);

    const targetUrl =
        parsedUrl.origin === self.location.origin
            ? parsedUrl.href
            : new URL('/dashboard', self.location.origin).href;

    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        });

        const existingWindow = windows.find((client) => {
            return new URL(client.url).origin === self.location.origin;
        });

        if (existingWindow) {
            await existingWindow.navigate(targetUrl);
            await existingWindow.focus();
            return;
        }

        await self.clients.openWindow(targetUrl);
    })());
});
