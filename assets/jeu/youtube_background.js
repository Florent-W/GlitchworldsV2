(function () {
    const SELECTOR = '#bgndVideo';
    const PLUGIN_EVENTS = 'YTPReady YTPPlay YTPPause YTPUnmuted YTPMuted YTPFullScreenStart YTPFullScreenEnd';
    const JQUERY_URL = 'https://code.jquery.com/jquery-3.7.1.min.js';
    const JQUERY_INTEGRITY = 'sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=';
    const YTPLAYER_URL = 'https://cdnjs.cloudflare.com/ajax/libs/jquery.mb.YTPlayer/3.3.9/jquery.mb.YTPlayer.min.js';
    const YTPLAYER_INTEGRITY = 'sha512-rVFx7vXgVV8cmgG7RsZNQ68CNBZ7GL3xTYl6GAVgl3iQiSwtuDjTeE1GESgPSCwkEn/ijFJyslZ1uzbN3smwYg==';

    let scriptsPromise = null;
    let bootInterval = null;
    let bootTimeout = null;
    let inlinePlayButtonObserver = null;
    let inlinePlayButtonInterval = null;
    let controlsHome = null;

    function onFullscreenKeydown(event) {
        if (event.key !== 'Escape' || !document.body.classList.contains('gw-video-fullscreen')) {
            return;
        }

        exitFullscreen();
    }

    function toggleFullscreen($player) {
        if (document.body.classList.contains('gw-video-fullscreen')) {
            $player.YTPFullscreen();
            setFullscreenMode(false);
            return;
        }

        setFullscreenMode(true);
        $player.YTPFullscreen();
    }

    function exitFullscreen() {
        const element = playerElement();

        if (!element || element.dataset.gwYtplayerReady !== '1' || !scriptsReady()) {
            return;
        }

        if (!document.body.classList.contains('gw-video-fullscreen')) {
            return;
        }

        toggleFullscreen(window.jQuery(element));
    }

    function mountControlsForFullscreen() {
        const controls = document.querySelector('.gw-ytplayer-controls');

        if (!controls || controls.dataset.gwFullscreenMounted === '1') {
            return;
        }

        controlsHome = {
            parent: controls.parentElement,
            nextSibling: controls.nextSibling,
        };

        document.body.appendChild(controls);
        controls.dataset.gwFullscreenMounted = '1';
        controls.classList.add('gw-ytplayer-controls--fullscreen');
    }

    function unmountControlsFromFullscreen() {
        const controls = document.querySelector('.gw-ytplayer-controls');

        if (!controls || !controlsHome?.parent) {
            controlsHome = null;
            return;
        }

        controls.classList.remove('gw-ytplayer-controls--fullscreen');
        delete controls.dataset.gwFullscreenMounted;

        if (controlsHome.nextSibling) {
            controlsHome.parent.insertBefore(controls, controlsHome.nextSibling);
        } else {
            controlsHome.parent.appendChild(controls);
        }

        controlsHome = null;
    }

    function playerElement() {
        return document.querySelector(SELECTOR);
    }

    function scriptsReady() {
        return Boolean(window.jQuery?.fn?.YTPlayer);
    }

    function loadScript(url, integrity) {
        const existing = document.querySelector(`script[src="${url}"]`);

        if (existing) {
            if (existing.dataset.gwLoaded === '1') {
                return Promise.resolve();
            }

            return new Promise((resolve, reject) => {
                existing.addEventListener('load', () => resolve(), { once: true });
                existing.addEventListener('error', () => reject(new Error(`Impossible de charger ${url}`)), { once: true });
            });
        }

        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = url;
            script.crossOrigin = 'anonymous';
            script.referrerPolicy = 'no-referrer';

            if (integrity) {
                script.integrity = integrity;
            }

            script.addEventListener('load', () => {
                script.dataset.gwLoaded = '1';
                resolve();
            }, { once: true });

            script.addEventListener('error', () => {
                reject(new Error(`Impossible de charger ${url}`));
            }, { once: true });

            document.head.appendChild(script);
        });
    }

    function ensureScripts() {
        if (scriptsReady()) {
            return Promise.resolve();
        }

        if (!scriptsPromise) {
            scriptsPromise = loadScript(JQUERY_URL, JQUERY_INTEGRITY)
                .then(() => loadScript(YTPLAYER_URL, YTPLAYER_INTEGRITY))
                .catch((error) => {
                    scriptsPromise = null;
                    throw error;
                });
        }

        return scriptsPromise;
    }

    function preparePlugin() {
        if (!window.jQuery?.mbYTPlayer?.controls) {
            return;
        }

        // Le plugin affiche sinon un gros « P / p » (.inlinePlayButton) en plein écran.
        Object.keys(window.jQuery.mbYTPlayer.controls).forEach((key) => {
            window.jQuery.mbYTPlayer.controls[key] = '';
        });
    }

    function removeInlinePlayButtons() {
        document.querySelectorAll('.inlinePlayButton, .inlinePlayButtonMobile').forEach((button) => {
            button.remove();
        });
    }

    function stopInlinePlayButtonWatch() {
        if (inlinePlayButtonObserver) {
            inlinePlayButtonObserver.disconnect();
            inlinePlayButtonObserver = null;
        }

        if (inlinePlayButtonInterval !== null) {
            window.clearInterval(inlinePlayButtonInterval);
            inlinePlayButtonInterval = null;
        }
    }

    function startInlinePlayButtonWatch() {
        stopInlinePlayButtonWatch();
        removeInlinePlayButtons();

        inlinePlayButtonObserver = new MutationObserver(() => {
            removeInlinePlayButtons();
        });

        inlinePlayButtonObserver.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['style', 'class'],
        });

        inlinePlayButtonInterval = window.setInterval(removeInlinePlayButtons, 200);
    }

    function setFullscreenMode(active) {
        document.body.classList.toggle('gw-video-fullscreen', active);

        document.querySelectorAll('.YTPOverlay').forEach((overlay) => {
            overlay.style.pointerEvents = active ? 'none' : '';
        });

        if (active) {
            mountControlsForFullscreen();
            startInlinePlayButtonWatch();
            document.addEventListener('keydown', onFullscreenKeydown);
            return;
        }

        document.removeEventListener('keydown', onFullscreenKeydown);
        unmountControlsFromFullscreen();
        stopInlinePlayButtonWatch();
        removeInlinePlayButtons();
    }

    function resetControls() {
        const btnPlay = document.getElementById('btnPlay');

        if (!btnPlay) {
            return;
        }

        btnPlay.disabled = true;
        btnPlay.setAttribute('aria-label', 'Chargement de la vidéo');

        const icon = btnPlay.querySelector('i');
        if (icon) {
            icon.className = 'bi bi-hourglass-split';
        }
    }

    function markControlsReady(playing = true) {
        const btnPlay = document.getElementById('btnPlay');

        if (!btnPlay) {
            return;
        }

        btnPlay.disabled = false;

        const icon = btnPlay.querySelector('i');
        if (icon) {
            icon.className = playing ? 'bi bi-pause-fill' : 'bi bi-play-fill';
        }

        btnPlay.setAttribute('aria-label', playing ? 'Mettre en pause' : 'Lire la vidéo');
    }

    function interfaceSoundActive() {
        return document.documentElement.dataset.gwSons !== '0';
    }

    function applyVideoVolumeState() {
        const element = playerElement();

        if (!element || element.dataset.gwYtplayerReady !== '1' || !scriptsReady()) {
            return;
        }

        const $player = window.jQuery(element);

        if (interfaceSoundActive() && element.dataset.gwSound === '1') {
            $player.YTPUnmute();
            return;
        }

        $player.YTPMute();
    }

    function clearBootPolling() {
        if (bootInterval !== null) {
            window.clearInterval(bootInterval);
            bootInterval = null;
        }

        if (bootTimeout !== null) {
            window.clearTimeout(bootTimeout);
            bootTimeout = null;
        }
    }

    function resetPluginState(element) {
        if (window.ytp) {
            window.ytp.backgroundIsInited = false;
        }

        delete element.dataset.gwYtplayerReady;
        delete element.dataset.gwYtplayerBooting;
    }

    function unbindControls(element) {
        const controls = document.querySelector('.gw-ytplayer-controls');

        if (controls) {
            delete controls.dataset.gwBound;
        }

        if (!element || !scriptsReady()) {
            return;
        }

        window.jQuery(element).off(PLUGIN_EVENTS);
    }

    function destroyPlayer() {
        clearBootPolling();
        setFullscreenMode(false);

        const element = playerElement();

        if (!element) {
            if (window.ytp) {
                window.ytp.backgroundIsInited = false;
            }

            resetControls();
            return;
        }

        unbindControls(element);

        if (element.dataset.gwYtplayerReady === '1' && scriptsReady()) {
            try {
                window.jQuery(element).YTPPause();
                window.jQuery(element).YTPDestroy();
            } catch (error) {
                // Le plugin peut déjà être détruit lors d'une navigation Turbo.
            }
        }

        resetPluginState(element);
        removeInlinePlayButtons();
        resetControls();
    }

    function bindSoundOnInteraction(element) {
        if (element.dataset.gwSound !== '1') {
            return;
        }

        const unmute = (event) => {
            if (!interfaceSoundActive()) {
                return;
            }

            if (event?.target?.closest?.('.gw-ytplayer-controls')) {
                return;
            }

            if (event?.target?.closest?.('[data-controller="son"]')) {
                return;
            }

            if (!scriptsReady() || element.dataset.gwYtplayerReady !== '1') {
                return;
            }

            window.jQuery(element).YTPUnmute();
            document.removeEventListener('touchstart', unmute);
            document.removeEventListener('click', unmute);
        };

        document.addEventListener('touchstart', unmute, { passive: true });
        document.addEventListener('click', unmute);
    }

    function bindControls(element) {
        const controls = document.querySelector('.gw-ytplayer-controls');
        const btnPlay = document.getElementById('btnPlay');

        if (!controls || !btnPlay || controls.dataset.gwBound === '1') {
            return;
        }

        controls.dataset.gwBound = '1';

        const $player = window.jQuery(element);
        let isPlaying = true;

        const setPlayState = (playing) => {
            isPlaying = playing;
            markControlsReady(playing);
        };

        controls.addEventListener('click', (event) => {
            const button = event.target.closest('button');

            if (!button || element.dataset.gwYtplayerReady !== '1') {
                return;
            }

            if (button.id === 'btnPlay') {
                if (isPlaying) {
                    $player.YTPPause();
                } else {
                    $player.YTPPlay();
                }

                return;
            }

            if (button.id === 'btnPleinEcran') {
                if (!isPlaying) {
                    $player.YTPPlay();
                }

                toggleFullscreen($player);
            }
        });

        const onPluginEvent = () => {
            removeInlinePlayButtons();
        };

        $player.on('YTPReady', () => {
            window.setTimeout(() => {
                setPlayState(true);
                applyVideoVolumeState();
                removeInlinePlayButtons();
            }, 300);
        });

        $player.on('YTPPlay', () => {
            setPlayState(true);
            onPluginEvent();
        });

        $player.on('YTPPause', () => {
            setPlayState(false);
            onPluginEvent();
        });

        $player.on('YTPFullScreenStart YTPFullScreenEnd', onPluginEvent);
    }

    function bindVolumeSync() {
        if (document.body.dataset.gwVideoVolumeBound === '1') {
            return;
        }

        document.body.dataset.gwVideoVolumeBound = '1';

        window.addEventListener('glitchworlds:sons-change', () => {
            applyVideoVolumeState();
        });

        window.addEventListener('glitchworlds:preferences-save-success', (event) => {
            const payload = event.detail?.payload || {};

            if (!Object.prototype.hasOwnProperty.call(payload, 'videoBackgroundSoundActive')) {
                return;
            }

            if (payload.videoBackgroundSoundActive) {
                playerElement()?.setAttribute('data-gw-sound', '1');
            } else {
                playerElement()?.setAttribute('data-gw-sound', '0');
            }

            applyVideoVolumeState();
        });
    }

    function failInit(element) {
        unbindControls(element);

        if (scriptsReady()) {
            try {
                window.jQuery(element).YTPDestroy();
            } catch (error) {
                // Le player peut ne jamais avoir été prêt.
            }
        }

        resetPluginState(element);
    }

    function initPlayer() {
        const element = playerElement();

        if (!element || !scriptsReady()) {
            return Boolean(element?.dataset.gwYtplayerReady === '1');
        }

        if (element.dataset.gwYtplayerReady === '1') {
            markControlsReady(true);
            return true;
        }

        if (element.dataset.gwYtplayerBooting === '1') {
            return false;
        }

        element.dataset.gwYtplayerBooting = '1';

        if (window.ytp?.backgroundIsInited) {
            window.ytp.backgroundIsInited = false;
        }

        preparePlugin();
        removeInlinePlayButtons();
        unbindControls(element);
        bindControls(element);

        const $player = window.jQuery(element);

        $player.one('YTPReady', () => {
            element.dataset.gwYtplayerReady = '1';
            delete element.dataset.gwYtplayerBooting;
            window.setTimeout(() => {
                markControlsReady(true);
                applyVideoVolumeState();
                removeInlinePlayButtons();
            }, 300);
        });

        window.setTimeout(() => {
            if (element.dataset.gwYtplayerReady !== '1' && element.dataset.gwYtplayerBooting === '1') {
                failInit(element);
            }
        }, 12000);

        try {
            $player.YTPlayer();
        } catch (error) {
            failInit(element);
            return false;
        }

        bindSoundOnInteraction(element);
        removeInlinePlayButtons();

        return false;
    }

    function boot() {
        clearBootPolling();
        bindVolumeSync();

        const element = playerElement();

        if (!element) {
            destroyPlayer();
            return;
        }

        preparePlugin();

        if (element.dataset.gwYtplayerReady !== '1') {
            resetControls();
        }

        ensureScripts()
            .then(() => {
                if (!playerElement()) {
                    return;
                }

                if (initPlayer()) {
                    return;
                }

                bootInterval = window.setInterval(() => {
                    if (initPlayer() || !playerElement()) {
                        clearBootPolling();
                    }
                }, 150);

                bootTimeout = window.setTimeout(() => clearBootPolling(), 15000);
            })
            .catch(() => {
                markControlsReady(false);
            });
    }

    document.addEventListener('turbo:before-cache', destroyPlayer);
    document.addEventListener('turbo:load', boot);
})();
