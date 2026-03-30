export function bootWhenReady(flagName, init) {
    const run = () => {
        if (window[flagName]) return;
        window[flagName] = true;
        init();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run, { once: true });
    } else {
        run();
    }
}
