const SOUND_CONFIG = {
    route: '/sounds/alarmas/critico.wav',
    volumeInitial: 0.25,
    volumeStep: 0.15,
    volumeMax: 1,
    cadenceBaseMs: 3000,
    cadenceIncrement: 50,
    cadenceMaxBoost: 5,
    adjustEveryMs: 60000,
    storageKey: 'sentry-critical-sound-enabled',
    lockKey: 'sentry-critical-sound-lock',
};

function normalizeAlerts(alerts) {
    if (!Array.isArray(alerts)) return [];

    const seen = new Set();
    const result = [];
    for (const alert of alerts) {
        const rawId = alert?.objetivoId ?? alert?.id ?? alert;
        const id = String(rawId ?? '').trim();
        if (!id || seen.has(id)) continue;
        seen.add(id);
        result.push(id);
    }
    return result;
}

function createCriticalSoundController() {
    const state = {
        audio: null,
        activeAlertIds: [],
        playbackController: null,
        playbackIntervalId: null,
        adjustIntervalId: null,
        volumeCurrent: SOUND_CONFIG.volumeInitial,
        cadenceBoost: 0,
        unlockRequired: false,
        assetMissing: false,
        enableRequested: false,
        uiBound: false,
        lastError: null,
    };

    const dom = {
        root: null,
        text: null,
        button: null,
        status: null,
    };

    function readEnabledPreference() {
        try {
            return window.localStorage.getItem(SOUND_CONFIG.storageKey) !== 'disabled';
        } catch (_) {
            return true;
        }
    }

    function writeEnabledPreference(enabled) {
        try {
            window.localStorage.setItem(SOUND_CONFIG.storageKey, enabled ? 'enabled' : 'disabled');
        } catch (_) {
            // Ignore storage errors in private/incognito contexts.
        }
    }

    function getPublicState() {
        return {
            activeCount: state.activeAlertIds.length,
            isPlaying: Boolean(state.playbackController),
            unlockRequired: state.unlockRequired,
            assetMissing: state.assetMissing,
            enabledPreference: readEnabledPreference(),
            lastError: state.lastError,
        };
    }

    function emitStateChange() {
        const detail = getPublicState();
        updateUi();
        window.dispatchEvent(new CustomEvent('sentry-critical-sound-state', { detail }));
    }

    function clearIntervals() {
        if (state.playbackIntervalId) {
            window.clearInterval(state.playbackIntervalId);
            state.playbackIntervalId = null;
        }
        if (state.adjustIntervalId) {
            window.clearInterval(state.adjustIntervalId);
            state.adjustIntervalId = null;
        }
    }

    function resetPlaybackMetrics() {
        state.volumeCurrent = SOUND_CONFIG.volumeInitial;
        state.cadenceBoost = 0;
    }

    function ensureAudio() {
        if (state.audio) return state.audio;

        const audio = new Audio(SOUND_CONFIG.route);
        audio.preload = 'auto';
        audio.volume = SOUND_CONFIG.volumeInitial;
        audio.addEventListener('error', () => {
            state.assetMissing = true;
            state.lastError = 'No se pudo cargar el audio crítico.';
            stopPlayback();
            emitStateChange();
        });
        state.audio = audio;
        return audio;
    }

    function computeCadenceMs(boost) {
        const factor = 1 + Math.max(0, boost);
        const base = SOUND_CONFIG.cadenceBaseMs;
        const minCadence = base / (1 + SOUND_CONFIG.cadenceMaxBoost);
        return Math.max(base / factor, minCadence);
    }

    async function playOnce() {
        const audio = ensureAudio();
        audio.currentTime = 0;
        return audio.play();
    }

    function recreatePlaybackInterval() {
        if (!state.audio) return;
        if (state.playbackIntervalId) window.clearInterval(state.playbackIntervalId);

        const cadenceMs = computeCadenceMs(state.cadenceBoost);
        state.playbackIntervalId = window.setInterval(() => {
            playOnce().catch((error) => {
                handlePlaybackError(error);
            });
        }, cadenceMs);
    }

    function adjustPlayback() {
        if (!state.audio) return;

        if (state.volumeCurrent < SOUND_CONFIG.volumeMax && SOUND_CONFIG.volumeStep > 0) {
            state.volumeCurrent = Math.min(SOUND_CONFIG.volumeMax, state.volumeCurrent + SOUND_CONFIG.volumeStep);
            state.audio.volume = state.volumeCurrent;
            emitStateChange();
            return;
        }

        if (SOUND_CONFIG.cadenceIncrement <= 0 || state.cadenceBoost >= SOUND_CONFIG.cadenceMaxBoost) return;

        state.cadenceBoost = Math.min(SOUND_CONFIG.cadenceMaxBoost, state.cadenceBoost + SOUND_CONFIG.cadenceIncrement);
        recreatePlaybackInterval();
        emitStateChange();
    }

    function handlePlaybackError(error) {
        const name = String(error?.name || '');
        if (name === 'NotAllowedError') {
            state.unlockRequired = true;
            state.lastError = 'El navegador necesita una interacción para habilitar el sonido crítico.';
            emitStateChange();
            return;
        }

        state.lastError = error?.message || 'No se pudo reproducir el sonido crítico.';
        emitStateChange();
        window.console.warn('Critical sound playback error:', error);
    }

    async function runPlayback(signal) {
        if (signal.aborted || state.assetMissing || !readEnabledPreference()) return;

        const audio = ensureAudio();
        resetPlaybackMetrics();
        audio.volume = state.volumeCurrent;

        try {
            await playOnce();
        } catch (error) {
            handlePlaybackError(error);
            return;
        }

        recreatePlaybackInterval();
        state.adjustIntervalId = window.setInterval(adjustPlayback, SOUND_CONFIG.adjustEveryMs);
        emitStateChange();

        await new Promise((resolve) => {
            signal.addEventListener('abort', resolve, { once: true });
        });
    }

    function stopPlayback() {
        if (state.playbackController) {
            state.playbackController.abort();
            state.playbackController = null;
        }

        clearIntervals();

        if (state.audio) {
            state.audio.pause();
            state.audio.currentTime = 0;
            state.audio.volume = SOUND_CONFIG.volumeInitial;
        }

        resetPlaybackMetrics();
        emitStateChange();
    }

    async function acquirePlaybackLockAndRun(controller) {
        if (navigator.locks?.request) {
            await navigator.locks.request(
                SOUND_CONFIG.lockKey,
                { ifAvailable: true },
                async (lock) => {
                    if (!lock || controller.signal.aborted) return;
                    await runPlayback(controller.signal);
                }
            );
            return;
        }

        await runPlayback(controller.signal);
    }

    function startPlayback() {
        if (state.playbackController || state.activeAlertIds.length === 0 || state.assetMissing || !readEnabledPreference()) {
            emitStateChange();
            return;
        }

        state.playbackController = new AbortController();
        const { signal } = state.playbackController;

        void acquirePlaybackLockAndRun(state.playbackController)
            .catch((error) => {
                handlePlaybackError(error);
            })
            .finally(() => {
                if (state.playbackController?.signal === signal) {
                    state.playbackController = null;
                    clearIntervals();
                    if (state.audio) {
                        state.audio.pause();
                        state.audio.currentTime = 0;
                        state.audio.volume = SOUND_CONFIG.volumeInitial;
                    }
                    resetPlaybackMetrics();
                    emitStateChange();
                }
            });
    }

    function syncCriticalAlerts(alerts) {
        state.activeAlertIds = normalizeAlerts(alerts);

        if (state.activeAlertIds.length === 0) {
            stopPlayback();
            return;
        }

        if (!state.playbackController) {
            startPlayback();
            return;
        }

        emitStateChange();
    }

    async function enableWithGesture() {
        state.enableRequested = true;
        state.lastError = null;
        emitStateChange();

        try {
            const audio = ensureAudio();
            audio.volume = 0;
            audio.currentTime = 0;
            await audio.play();
            audio.pause();
            audio.currentTime = 0;
            audio.volume = SOUND_CONFIG.volumeInitial;

            state.unlockRequired = false;
            state.enableRequested = false;
            writeEnabledPreference(true);

            if (state.activeAlertIds.length > 0) {
                startPlayback();
            } else {
                emitStateChange();
            }
        } catch (error) {
            state.enableRequested = false;
            state.unlockRequired = true;
            state.lastError = error?.message || 'No se pudo habilitar el sonido crítico.';
            writeEnabledPreference(false);
            emitStateChange();
        }
    }

    function dismissUnlockPrompt() {
        state.unlockRequired = false;
        writeEnabledPreference(false);
        stopPlayback();
    }

    function bindUi() {
        dom.root = document.getElementById('critical-sound-unlock');
        dom.text = document.getElementById('critical-sound-unlock-text');
        dom.button = document.getElementById('critical-sound-unlock-btn');
        dom.status = document.getElementById('critical-sound-unlock-status');

        if (!dom.root || !dom.text || !dom.button || !dom.status || state.uiBound) return;

        state.uiBound = true;
        dom.button.addEventListener('click', () => {
            void enableWithGesture();
        });

        const dismiss = document.getElementById('critical-sound-dismiss-btn');
        dismiss?.addEventListener('click', () => {
            dismissUnlockPrompt();
        });

        updateUi();
    }

    function updateUi() {
        if (!dom.root || !dom.text || !dom.button || !dom.status) return;

        const shouldShow = state.unlockRequired || state.assetMissing;
        dom.root.classList.toggle('hidden', !shouldShow);

        if (state.assetMissing) {
            dom.status.textContent = 'No se encontró el archivo de audio crítico.';
            dom.text.textContent = 'Falta el asset del sonido crítico.';
        } else if (state.unlockRequired) {
            dom.text.textContent = 'El navegador bloqueó el sonido crítico. Habilitalo para escuchar alertas.';
            dom.status.textContent = state.lastError || 'Se requiere una interacción para reproducir audio.';
        } else {
            dom.status.textContent = '';
        }

        dom.button.disabled = state.enableRequested || state.assetMissing;
        dom.button.textContent = state.assetMissing
            ? 'Audio pendiente'
            : (state.enableRequested ? 'Habilitando...' : 'Activar sonido');
    }

    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bindUi, { once: true });
        } else {
            bindUi();
        }

        emitStateChange();
    }

    window.addEventListener('beforeunload', () => {
        stopPlayback();
    });

    return {
        init,
        syncCriticalAlerts,
        stop: stopPlayback,
        enableWithGesture,
        getState: getPublicState,
    };
}

if (!window.SENTRY_CRITICAL_SOUND) {
    window.SENTRY_CRITICAL_SOUND = createCriticalSoundController();
    window.SENTRY_CRITICAL_SOUND.init();
}
