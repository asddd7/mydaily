const installButton = document.getElementById('installAppButton');

if (installButton) {
    let installPrompt = null;
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;

    if ('serviceWorker' in navigator && window.isSecureContext) {
        navigator.serviceWorker.register('/firebase-messaging-sw.js', { scope: '/' })
            .catch(error => console.error('Gagal menyiapkan service worker aplikasi:', error));
    }

    if (isStandalone) {
        installButton.hidden = true;
    }

    window.addEventListener('beforeinstallprompt', event => {
        event.preventDefault();
        installPrompt = event;
        installButton.classList.add('is-installable');
    });

    window.addEventListener('appinstalled', () => {
        installPrompt = null;
        installButton.hidden = true;
    });

    installButton.addEventListener('click', async () => {
        if (isStandalone) return;

        if (!installPrompt) {
            const instructions = /iphone|ipad|ipod/i.test(navigator.userAgent)
                ? 'Di Safari, tekan Bagikan lalu pilih Tambahkan ke Layar Utama.'
                : 'Buka menu browser (⋮), lalu pilih Install app atau Tambahkan ke layar utama.';
            window.alert(instructions);
            return;
        }

        installPrompt.prompt();
        await installPrompt.userChoice;
        installPrompt = null;
        installButton.classList.remove('is-installable');
    });
}
