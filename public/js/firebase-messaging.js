
import { initializeApp } from
    'https://www.gstatic.com/firebasejs/13.0.0/firebase-app.js';

import {
    getMessaging,
    isSupported,
    onMessage,
    onRegistered,
    register
} from
    'https://www.gstatic.com/firebasejs/13.0.0/firebase-messaging.js';

console.info('[FCM] File JavaScript berhasil dijalankan:', import.meta.url);

const firebaseConfig = {
    apiKey: 'AIzaSyC2_ycyhC3htGkh-oe1_kj6JQI9lmgBcuY',
    authDomain: 'mydaily-app-51b1d.firebaseapp.com',
    projectId: 'mydaily-app-51b1d',
    storageBucket: 'mydaily-app-51b1d.firebasestorage.app',
    messagingSenderId: '1062236684058',
    appId: '1:1062236684058:web:4a97d94a410dc3cd61b6a3'
};

const VAPID_KEY = 'BJ-nT8Pt0FAXZNxprw5iUFrRplF0TBXbP3vqIUSNhfR1ZHCdoh0DOj_JKzbWEC87LGD-3RHAX_ppJcfOK74hseQ';

const button = document.getElementById('enable-push');
const statusElement = document.getElementById('push-status');

function setStatus(message) {
    if (statusElement) {
        statusElement.textContent = message;
    }
}

function getCsrfToken() {
    return document.querySelector(
        'meta[name="csrf-token"]'
    )?.content ?? '';
}


async function saveFid(fid) {
    console.log('MyDaily FCM installation ID:', fid);

    const endpoint = document.querySelector(
        'meta[name="fcm-device-url"]'
    )?.content;

    if (!endpoint) {
        console.error('URL endpoint FCM tidak ditemukan.');
        setStatus('URL endpoint Laravel tidak ditemukan.');
        return;
    }

    console.log('FCM endpoint:', endpoint);

    setStatus('FID didapatkan. Menyimpan perangkat ke Laravel...');

    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN':
                    document.querySelector(
                        'meta[name="csrf-token"]'
                    )?.content ?? ''
            },
            body: JSON.stringify({ fid })
        });

        if (response.redirected) {
            throw new Error(
                `Request dialihkan ke ${response.url}. Periksa sesi login.`
            );
        }

        const contentType =
            response.headers.get('content-type') ?? '';

        if (!response.ok) {
            throw new Error(
                `HTTP ${response.status}: ${await response.text()}`
            );
        }

        if (!contentType.includes('application/json')) {
            throw new Error(
                'Laravel tidak mengembalikan respons JSON.'
            );
        }

        const result = await response.json();

        console.log('Respons Laravel:', result);

        if (!result.success) {
            throw new Error(
                result.message ?? 'Penyimpanan FID gagal.'
            );
        }

        setStatus('Perangkat berhasil disimpan ke MyDaily.');
    } catch (error) {
        console.error('Gagal menyimpan FID:', error);
        setStatus(`Gagal menyimpan perangkat: ${error.message}`);
    }
}

async function initializePush() {
    console.info('[FCM] initializePush dimulai');
    if (
        !('Notification' in window) ||
        !('serviceWorker' in navigator)
    ) {
        setStatus('Browser ini belum mendukung push notification.');
        if (button) button.disabled = true;
        return;
    }

    const supported = await isSupported();

    console.info('[FCM] Browser mendukung FCM:', supported);

    if (!supported) {
        setStatus('Firebase Messaging tidak didukung browser ini.');
        if (button) button.disabled = true;
        return;
    }

console.info('[FCM] Tombol ditemukan:', Boolean(button));

    const app = initializeApp(firebaseConfig);
    const messaging = getMessaging(app);

    onRegistered(messaging, (fid) => {
    console.info('[FCM] FID diterima:', fid);
    return saveFid(fid);
    });

    onMessage(messaging, (payload) => {
        const title = payload.notification?.title ?? 'MyDaily';
        const body = payload.notification?.body ?? 'Ada pembaruan baru.';

        setStatus(`${title}: ${body}`);

        // Tampilkan notifikasi sistem saat halaman sedang aktif.
        if (
            Notification.permission === 'granted' &&
            document.visibilityState === 'visible'
        ) {
            new Notification(title, { body });
        }
    });

    if (!button) return;

    button.addEventListener('click', async () => {
    console.info('[FCM] Tombol Aktifkan Notifikasi diklik');
        button.disabled = true;

        try {
            if (!getCsrfToken()) {
                throw new Error('Token CSRF tidak ditemukan di Blade.');
            }

            const permission = await Notification.requestPermission();

            if (permission !== 'granted') {
                setStatus('Izin notifikasi belum diberikan.');
                return;
            }

            const swRegistration =
                await navigator.serviceWorker.register(
                    '/firebase-messaging-sw.js',
                    { scope: '/' }
                );
            
            console.info(
                '[FCM] Memanggil register(), scope:',
                swRegistration.scope
            );
            await register(messaging, {
                vapidKey: VAPID_KEY,
                serviceWorkerRegistration: swRegistration
            });
            console.info('[FCM] register() selesai; menunggu callback FID');

            setStatus(
                'Izin diberikan. Sedang mendaftarkan perangkat ke Firebase...'
            );
        } catch (error) {
            console.error('FCM setup error:', error);
            setStatus(`Gagal mengaktifkan notifikasi: ${error.message}`);
        } finally {
            button.disabled = false;
        }
    });
}

initializePush().catch((error) => {
    console.error('Firebase initialization error:', error);
    setStatus(`Firebase gagal diinisialisasi: ${error.message}`);
});
