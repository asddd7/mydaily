
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

    try {
        const response = await fetch('/fcm/devices', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({ fid })
        });

        if (!response.ok) {
            throw new Error(
                `Gagal menyimpan perangkat (${response.status}).`
            );
        }

        setStatus('Perangkat berhasil terdaftar untuk notifikasi.');
    } catch (error) {
        console.error(error);
        setStatus(
            'Pendaftaran Firebase berhasil dimulai, ' +
            'tetapi penyimpanan perangkat ke Laravel gagal.'
        );
    }
}

async function initializePush() {
    if (
        !('Notification' in window) ||
        !('serviceWorker' in navigator)
    ) {
        setStatus('Browser ini belum mendukung push notification.');
        if (button) button.disabled = true;
        return;
    }

    if (!(await isSupported())) {
        setStatus('Firebase Messaging tidak didukung browser ini.');
        if (button) button.disabled = true;
        return;
    }

    const app = initializeApp(firebaseConfig);
    const messaging = getMessaging(app);

    onRegistered(messaging, saveFid);

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

            await register(messaging, {
                vapidKey: VAPID_KEY,
                serviceWorkerRegistration: swRegistration
            });

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
