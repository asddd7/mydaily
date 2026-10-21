import { initializeApp } from '@firebase/app';
import {
    deleteToken,
    getMessaging,
    getToken,
    isSupported,
    onMessage,
} from '@firebase/messaging';

const readJson = id => {
    const element = document.getElementById(id);
    return element ? JSON.parse(element.textContent) : null;
};

const firebaseConfig = readJson('firebaseWebConfig');
const pushConfig = readJson('pushNotificationConfig');
const enableButton = document.getElementById('enablePushNotifications');
const disableButton = document.getElementById('disablePushNotifications');
const status = document.getElementById('pushNotificationStatus');

if (enableButton && disableButton && status && firebaseConfig && pushConfig) {
    const hasFirebaseConfig = [
        firebaseConfig.apiKey,
        firebaseConfig.authDomain,
        firebaseConfig.projectId,
        firebaseConfig.messagingSenderId,
        firebaseConfig.appId,
        pushConfig.vapidKey,
    ].every(value => typeof value === 'string' && value.trim() !== '');

    const updateStatus = message => {
        status.textContent = message;
    };

    let syncInProgress = null;

    const requestJson = async (url, options) => {
        const response = await fetch(url, {
            cache: 'no-store',
            credentials: 'same-origin',
            ...options,
            headers: {
                Accept: 'application/json',
                ...(options.headers || {}),
            },
        });

        const payload = await response.json().catch(() => null);
        if (!response.ok) {
            throw new Error(payload?.message || `Permintaan gagal (${response.status}).`);
        }

        return payload;
    };

    const syncExistingReminders = async () => {
        if (!pushConfig.syncUrl) return false;
        if (syncInProgress) return syncInProgress;

        syncInProgress = (async () => {
            const handoff = await requestJson(pushConfig.syncUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': pushConfig.csrfToken,
                },
                body: JSON.stringify({}),
            });
            const response = await fetch(`${handoff.worker_url}/sync/snapshot`, {
                method: 'POST',
                mode: 'cors',
                credentials: 'omit',
                headers: {
                    'Authorization': `Bearer ${handoff.ticket}`,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(handoff.snapshot),
            });
            const result = await response.json().catch(() => null);
            if (!response.ok || !result?.synced) {
                throw new Error(result?.error || `Worker menolak sinkronisasi (${response.status}).`);
            }

            return true;
        })();

        try {
            return await syncInProgress;
        } catch (error) {
            console.warn('Pengingat belum berhasil disinkronkan ke Cloudflare:', error.message);
            return false;
        } finally {
            syncInProgress = null;
        }
    };

    const setBusy = busy => {
        enableButton.disabled = busy;
        disableButton.disabled = busy;
    };

    const run = async () => {
        if (!hasFirebaseConfig) {
            enableButton.disabled = true;
            updateStatus('Firebase belum dikonfigurasi oleh administrator.');
            return;
        }

        if (!window.isSecureContext || !('serviceWorker' in navigator) || !('Notification' in window)) {
            enableButton.disabled = true;
            updateStatus('Browser ini memerlukan HTTPS dan dukungan notifikasi web.');
            return;
        }

        try {
            void syncExistingReminders();
            if (!await isSupported()) {
                enableButton.disabled = true;
                updateStatus('Push notification tidak didukung oleh browser ini.');
                return;
            }

            const app = initializeApp(firebaseConfig);
            const messaging = getMessaging(app);
            const registration = await navigator.serviceWorker.register(pushConfig.serviceWorkerUrl);
            const savedToken = localStorage.getItem('mydaily-push-token');

            const registerToken = async token => {
                await requestJson(pushConfig.subscribeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': pushConfig.csrfToken,
                    },
                    body: JSON.stringify({
                        token,
                        user_agent: navigator.userAgent,
                    }),
                });
                localStorage.setItem('mydaily-push-token', token);
                localStorage.removeItem('mydaily-push-disabled');
                disableButton.hidden = false;
                const cloudflareSynced = await syncExistingReminders();
                updateStatus(cloudflareSynced
                    ? 'Notifikasi aktif dan perangkat tersinkron ke pengingat Cloudflare.'
                    : 'Notifikasi aktif, tetapi pengingat Cloudflare belum tersinkron.');
            };

            const isDisabled = localStorage.getItem('mydaily-push-disabled') === '1';
            if (Notification.permission === 'granted' && !isDisabled) {
                const currentToken = await getToken(messaging, {
                    vapidKey: pushConfig.vapidKey,
                    serviceWorkerRegistration: registration,
                });
                if (!currentToken) {
                    throw new Error('Firebase belum dapat membuat token untuk perangkat ini.');
                }
                await registerToken(currentToken);
            } else if (Notification.permission === 'denied') {
                enableButton.disabled = true;
                updateStatus('Izin notifikasi diblokir. Ubah izin situs melalui pengaturan browser.');
            } else if (isDisabled) {
                updateStatus('Notifikasi dinonaktifkan di perangkat ini.');
            } else if (savedToken) {
                    await requestJson(pushConfig.unsubscribeUrl, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': pushConfig.csrfToken,
                    },
                    body: JSON.stringify({ token: savedToken }),
                });
                localStorage.removeItem('mydaily-push-token');
            }

            onMessage(messaging, payload => {
                const data = payload.data || {};
                if (Notification.permission !== 'granted') {
                    console.warn('Push diterima, tetapi izin notifikasi browser belum diberikan.');
                    return;
                }

                registration.showNotification(data.title || 'MyDaily', {
                    body: data.body || 'Ada pengingat tugas yang belum selesai.',
                    icon: '/favicon.ico',
                    data: { url: data.url || '/' },
                }).catch(error => {
                    console.error('Gagal menampilkan notifikasi foreground:', error);
                });
            });

            enableButton.addEventListener('click', async () => {
                setBusy(true);
                try {
                    const permission = await Notification.requestPermission();
                    if (permission !== 'granted') {
                        updateStatus('Izin notifikasi belum diberikan.');
                        return;
                    }
                    const token = await getToken(messaging, {
                        vapidKey: pushConfig.vapidKey,
                        serviceWorkerRegistration: registration,
                    });
                    if (!token) {
                        throw new Error('Firebase belum dapat membuat token untuk perangkat ini.');
                    }
                    await registerToken(token);
                    enableButton.disabled = false;
                } catch (error) {
                    updateStatus(error.message);
                    console.error(error);
                } finally {
                    setBusy(false);
                }
            });

            disableButton.addEventListener('click', async () => {
                setBusy(true);
                try {
                    const token = localStorage.getItem('mydaily-push-token');
                    if (token) {
                        await requestJson(pushConfig.unsubscribeUrl, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': pushConfig.csrfToken,
                            },
                            body: JSON.stringify({ token }),
                        });
                    }
                    localStorage.setItem('mydaily-push-disabled', '1');
                    await deleteToken(messaging);
                    localStorage.removeItem('mydaily-push-token');
                    await syncExistingReminders();
                    disableButton.hidden = true;
                    updateStatus('Notifikasi HP dinonaktifkan di perangkat ini.');
                } catch (error) {
                    updateStatus(error.message);
                    console.error(error);
                } finally {
                    setBusy(false);
                }
            });
        } catch (error) {
            updateStatus(`Push notification gagal disiapkan: ${error.message}`);
            console.error(error);
        }
    };

    run();
}
