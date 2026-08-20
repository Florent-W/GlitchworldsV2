(function () {
    const ROOT_SELECTOR = '.gw-bbcode-gallery.gallery';
    const JQUERY_URL = 'https://code.jquery.com/jquery-3.7.1.min.js';
    const JQUERY_INTEGRITY = 'sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=';
    const PLUGIN_PATH = '/dist/js/lightgallery-all.min.js';
    const CSS_PATH = '/dist/css/lightgallery.min.css';

    let assetsPromise = null;

    function basePath() {
        const path = document.documentElement.getAttribute('data-gw-base-path') || '';

        return path.endsWith('/') ? path.slice(0, -1) : path;
    }

    function assetUrl(path) {
        return `${basePath()}${path.startsWith('/') ? path : `/${path}`}`;
    }

    const PLUGIN_URL = assetUrl(PLUGIN_PATH);
    const CSS_URL = assetUrl(CSS_PATH);

    function scriptsReady() {
        return Boolean(window.jQuery?.fn?.lightGallery);
    }

    function loadStylesheet(href) {
        return new Promise((resolve, reject) => {
            if (document.querySelector(`link[rel="stylesheet"][href="${href}"]`)) {
                resolve();
                return;
            }

            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            link.onload = () => resolve();
            link.onerror = () => reject(new Error(`Impossible de charger ${href}.`));
            document.head.appendChild(link);
        });
    }

    function loadScript(src, integrity) {
        return new Promise((resolve, reject) => {
            if (document.querySelector(`script[src="${src}"]`)) {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = src;
            script.defer = true;
            if (integrity) {
                script.integrity = integrity;
                script.crossOrigin = 'anonymous';
            }
            script.onload = () => resolve();
            script.onerror = () => reject(new Error(`Impossible de charger ${src}.`));
            document.head.appendChild(script);
        });
    }

    function ensureAssets() {
        if (assetsPromise) {
            return assetsPromise;
        }

        assetsPromise = (async () => {
            await loadStylesheet(CSS_URL);
            if (!window.jQuery) {
                await loadScript(JQUERY_URL, JQUERY_INTEGRITY);
            }
            if (!scriptsReady()) {
                await loadScript(PLUGIN_URL);
            }
        })();

        return assetsPromise;
    }

    function destroy() {
        if (!scriptsReady()) {
            return;
        }

        document.querySelectorAll(ROOT_SELECTOR).forEach((element) => {
            if (element.dataset.gwLightgalleryReady !== '1') {
                return;
            }

            try {
                const instance = window.jQuery(element).data('lightGallery');
                if (instance) {
                    instance.destroy(true);
                }
            } catch (error) {
                // Le plugin peut déjà être détruit lors d'une navigation Turbo.
            }

            delete element.dataset.gwLightgalleryReady;
        });
    }

    async function init() {
        const roots = document.querySelectorAll(ROOT_SELECTOR);
        if (roots.length === 0) {
            return;
        }

        try {
            await ensureAssets();
        } catch (error) {
            console.error('lightGallery : impossible de charger les assets.', error);
            return;
        }

        roots.forEach((element) => {
            if (element.dataset.gwLightgalleryReady === '1' || !element.querySelector('.gw-lg-item')) {
                return;
            }

            window.jQuery(element).lightGallery({
                selector: '.gw-lg-item',
                addClass: 'fixed-size',
                counter: true,
                startClass: '',
                download: false,
                zoom: true,
                fullScreen: true,
                thumbnail: true,
            });

            element.dataset.gwLightgalleryReady = '1';
        });
    }

    document.addEventListener('turbo:load', init);
    document.addEventListener('turbo:before-cache', destroy);
    document.addEventListener('DOMContentLoaded', init);
})();
