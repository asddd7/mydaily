const installButton = document.getElementById('installAppButton');

if (installButton) {
    let installPrompt = null;
    const isInAppBrowser = /FBAN|FBAV|FB_IAB|Instagram|\bwv\b/i.test(navigator.userAgent);
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;

    if ('serviceWorker' in navigator && window.isSecureContext) {
        navigator.serviceWorker.register('/firebase-messaging-worker.js', { scope: '/' })
            .catch(error => console.error('Gagal menyiapkan service worker aplikasi:', error));
    }

    if (isStandalone) {
        installButton.hidden = true;
    } else if (isInAppBrowser) {
        installButton.querySelector('.app-install-text').textContent = 'Buka di Chrome untuk memasang';
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
            const instructions = isInAppBrowser
                ? 'MyDaily sedang dibuka dari browser di dalam aplikasi. Browser ini tidak mendukung pemasangan PWA. Buka menu ⋮ lalu pilih Buka di browser / Open in Chrome. Setelah itu, pilih Install app dari menu Chrome.'
                : /iphone|ipad|ipod/i.test(navigator.userAgent)
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
