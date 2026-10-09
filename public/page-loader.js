(() => {
    const loader = document.getElementById('pageLoader');
    if (!loader) return;

    const showLoader = () => {
        loader.hidden = false;
        loader.setAttribute('aria-hidden', 'false');
        loader.classList.add('is-visible');
    };

    const navigateAfterPaint = (callback) => {
        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(callback);
        });
    };

    const isModifiedClick = (event) => (
        event.button !== 0
        || event.metaKey
        || event.ctrlKey
        || event.shiftKey
        || event.altKey
    );

    document.addEventListener('click', (event) => {
        if (event.defaultPrevented || isModifiedClick(event) || !(event.target instanceof Element)) return;

        const link = event.target.closest('a[href]');
        const target = link?.target.toLowerCase() ?? '';
        if (!link || link.hasAttribute('download') || target && target !== '_self') return;

        const destination = new URL(link.href, window.location.href);
        const currentPage = new URL(window.location.href);
        const isHashOnlyNavigation = destination.pathname === currentPage.pathname
            && destination.search === currentPage.search
            && destination.hash !== currentPage.hash;

        if (
            destination.origin !== currentPage.origin
            || (destination.protocol !== 'http:' && destination.protocol !== 'https:')
            || isHashOnlyNavigation
        ) {
            return;
        }

        event.preventDefault();
        showLoader();
        navigateAfterPaint(() => window.location.assign(destination.href));
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;

        const submitter = event.submitter;
        const target = (submitter?.getAttribute('formtarget') || form.getAttribute('target') || '').toLowerCase();

        if (form.method.toLowerCase() === 'dialog' || target && target !== '_self') return;

        showLoader();
    });

    window.addEventListener('pageshow', () => {
        loader.classList.remove('is-visible');
        loader.hidden = true;
        loader.setAttribute('aria-hidden', 'true');
    });
})();
