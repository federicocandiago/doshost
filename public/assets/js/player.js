/* DOSHost player — boots js-dos and keeps drive C: in sync with the server. */
(async () => {
    'use strict';

    const cfg = JSON.parse(document.getElementById('player-config').textContent);
    const t = cfg.i18n;
    const statusEl = document.getElementById('player-status');
    const usageEl = document.getElementById('player-usage');
    const bar = document.querySelector('.player-bar');

    let ci = null;          // js-dos CommandInterface, available once DOSBox runs
    const appFullscreen = window.matchMedia('(display-mode: fullscreen)').matches; // opened from the Home screen
    let fullscreenPending = cfg.startFullscreen && document.fullscreenEnabled && !appFullscreen; // waits for a user gesture
    let saving = null;      // pending save promise
    let lastHash = null;    // hash of the last uploaded disk, to skip identical saves
    let lastSave = 0;

    // Status text next to the menu button: messages that need attention stay, the others fade out.
    let statusTimer = null;
    function status(text, level = '', sticky = false) {
        statusEl.textContent = text;
        statusEl.dataset.level = level;
        statusEl.classList.add('visible');
        bar.classList.toggle('attention', level === 'error' || level === 'full');
        clearTimeout(statusTimer);
        if (!sticky && !level) statusTimer = setTimeout(() => statusEl.classList.remove('visible'), 3000);
    }

    function usage(percent, warning) {
        if (!usageEl || percent === undefined) return;
        usageEl.textContent = `${percent}%`;
        usageEl.classList.toggle('warn', Boolean(warning));
    }

    /**
     * Fingerprint of the disk, only to skip identical uploads. crypto.subtle exists only in secure
     * contexts (HTTPS or localhost): on plain HTTP fall back to FNV-1a, which is enough for this.
     */
    async function hash(data) {
        if (window.crypto?.subtle) {
            const digest = await crypto.subtle.digest('SHA-256', data);
            return Array.from(new Uint8Array(digest), (b) => b.toString(16).padStart(2, '0')).join('');
        }
        let h = 0x811c9dc5;
        for (let i = 0; i < data.length; i++) h = Math.imul(h ^ data[i], 0x01000193);
        return `fnv-${data.length}-${(h >>> 0).toString(16)}`;
    }

    async function upload(data) {
        const res = await fetch(cfg.saveUrl, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: data.byteLength < 60000, // lets small saves survive page unload
            headers: { 'Content-Type': 'application/octet-stream', 'Accept': 'application/json', 'X-CSRF-Token': cfg.csrf },
            body: data,
        });
        const json = await res.json().catch(() => ({}));
        if (res.status === 413) throw Object.assign(new Error(t.full), { full: true });
        if (!res.ok || !json.ok) throw new Error(json.message || `HTTP ${res.status}`);
        return json;
    }

    /** Takes a snapshot of the whole drive C: and uploads it if it changed. */
    function save() {
        if (!ci) return Promise.resolve();
        if (saving) return saving;

        status(t.saving, '', true);
        saving = (async () => {
            try {
                const data = await ci.persist(false); // full bundle: config + every file of C:
                if (!data) return status(t.nothing);
                if (data.byteLength > cfg.maxBytes) throw Object.assign(new Error(t.full), { full: true });

                const h = await hash(data);
                if (h === lastHash) {
                    status(t.nothing);
                } else {
                    const res = await upload(data);
                    lastHash = h;
                    usage(res.percent, res.warning);
                    status(t.saved.replace(':time', new Date().toLocaleTimeString(cfg.lang)));
                }
                lastSave = Date.now();
            } catch (error) {
                console.error(error);
                status(error.full ? t.full : `${t.error} ${error.message ?? ''}`, error.full ? 'full' : 'error');
            } finally {
                saving = null;
            }
        })();
        return saving;
    }

    /**
     * js-dos keeps a copy of every bundle in the browser (OPFS jsdos/caches/bundles).
     * The server is our source of truth, so that cache is wiped before booting.
     */
    async function clearBundleCache() {
        try {
            const root = await navigator.storage.getDirectory();
            const jsdos = await root.getDirectoryHandle('jsdos');
            const caches = await jsdos.getDirectoryHandle('caches');
            await caches.removeEntry('bundles', { recursive: true });
        } catch { /* nothing cached yet, or OPFS not available */ }
    }

    const startOverlay = document.getElementById('start-overlay');

    // Phones and tablets: stacked layout that follows the visible viewport (keyboard included)
    const isMobile = window.matchMedia('(pointer: coarse)').matches || window.innerWidth < 768;
    if (isMobile) {
        document.body.classList.add('is-mobile');
        const fit = () => {
            const height = window.visualViewport?.height ?? window.innerHeight;
            document.documentElement.style.setProperty('--app-height', `${height}px`);
            window.scrollTo(0, 0);
        };
        window.visualViewport?.addEventListener('resize', fit);
        window.visualViewport?.addEventListener('scroll', fit);
        window.addEventListener('resize', fit);
        fit();
    }

    status(t.loading, '', true);
    await clearBundleCache();

    const dos = Dos(document.getElementById('dos'), {
        url: cfg.bundleUrl,
        pathPrefix: cfg.pathPrefix,
        lang: ['en', 'ru', 'pt', 'ro'].includes(cfg.lang) ? cfg.lang : 'en', // languages of the js-dos UI
        theme: 'dark',
        autoStart: true,
        kiosk: true, // js-dos side bar hidden by default, see the "Tools" button
        noCursor: !cfg.showCursor, // per-instance default, toggled from the menu
        // The server is the only storage: nothing kept in the browser, and the
        // js-dos "save" button in the side bar runs our save().
        fsChanges: {
            local: false,
            pull: async () => null,
            push: async () => { await save(); },
        },
        onEvent: (event, arg) => {
            if (event === 'ci-ready') {
                ci = arg;
                lastSave = Date.now();
                if (isMobile) { startOverlay.hidden = false; status(t.ready); } // "Tap to start"
                else if (fullscreenPending) status(t.fullscreenHint, '', true);
                else status(t.ready);
                // Remember the initial state so an untouched disk is never re-uploaded
                ci.persist(false).then(async (data) => { if (data) lastHash = await hash(data); }).catch(() => {});
            }
        },
    });

    // Periodic autosave (AUTOSAVE_MINUTES in .env)
    setInterval(save, cfg.autosaveMinutes * 60 * 1000);

    // Save when the tab gets hidden (switching tab, closing, minimizing...)
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') save();
    });

    // Closing the page right after some work: save and ask the browser to wait.
    window.addEventListener('beforeunload', (event) => {
        if (ci && (saving || Date.now() - lastSave > 3000)) {
            save();
            event.preventDefault();
            event.returnValue = t.leave;
        }
    });

    // ☰ menu: Save, Tools, Save & exit
    const menuButton = document.getElementById('btn-menu');
    const menu = document.getElementById('player-menu');
    const toggleMenu = (open = menu.hidden) => {
        menu.hidden = !open;
        menuButton.setAttribute('aria-expanded', String(open));
    };
    menuButton.addEventListener('click', () => toggleMenu());
    menu.addEventListener('click', (event) => { if (event.target.closest('.menu-item')) toggleMenu(false); });
    document.addEventListener('pointerdown', (event) => {
        if (!menu.hidden && !event.target.closest('.player-menu')) toggleMenu(false);
    }, true);

    // Physical keyboard by key position, so the DOS layout of the instance is respected.
    // While the menu is open the keyboard belongs to the page (Esc closes the menu).
    new PhysicalKeyboard({
        getCi: () => ci,
        blocked: (event) => {
            if (menu.hidden) return false;
            if (event.type === 'keydown' && event.key === 'Escape') toggleMenu(false);
            return true;
        },
    });

    document.getElementById('btn-save').addEventListener('click', save);

    // Full screen: the whole player page, so the ☰ menu stays reachable
    const fullscreenButton = document.getElementById('btn-fullscreen');
    if (!document.fullscreenEnabled) {
        fullscreenButton.hidden = true; // e.g. iPhone Safari
    }
    fullscreenButton.addEventListener('click', () => {
        if (document.fullscreenElement) document.exitFullscreen();
        else document.documentElement.requestFullscreen().catch(() => {});
    });
    document.addEventListener('fullscreenchange', () => {
        fullscreenButton.setAttribute('aria-pressed', String(Boolean(document.fullscreenElement)));
    });

    // "Start in full screen" (desktop): browsers allow it only after a user gesture, so it happens
    // at the first click or key press (Esc excluded: it's the key that leaves full screen).
    // pointerup, not pointerdown: on touch screens only the release counts as a gesture.
    // On phones the "Tap to start" overlay below does it instead.
    if (fullscreenPending && !isMobile) {
        const goFullscreen = (event) => {
            if (event.type === 'keydown' && event.key === 'Escape') return;
            if (event.target.closest?.('.player-bar')) return; // the menu keeps working normally
            window.removeEventListener('pointerup', goFullscreen, true);
            window.removeEventListener('keydown', goFullscreen, true);
            fullscreenPending = false;
            if (!document.fullscreenElement) document.documentElement.requestFullscreen().catch(() => {});
            if (ci) status(t.ready);
        };
        window.addEventListener('pointerup', goFullscreen, true);
        window.addEventListener('keydown', goFullscreen, true);
    }

    // Mouse cursor over the emulator: on by default unless the instance settings say otherwise
    const cursorButton = document.getElementById('btn-cursor');
    let showCursor = cfg.showCursor;
    cursorButton.addEventListener('click', () => {
        showCursor = !showCursor;
        dos.setNoCursor(!showCursor);
        cursorButton.setAttribute('aria-pressed', String(showCursor));
    });

    // Mouse on touch screens: "follows the finger" (absolute, js-dos default) or touchpad
    // (drag to move, tap to click). Programs with their own mouse acceleration, like Windows 3.x,
    // can't follow the finger: DOSBox only sends relative movements. Remembered per instance here.
    const touchpadButton = document.getElementById('btn-touchpad');
    const touchpadKey = `doshost:touchpad:${cfg.uuid}`;
    let touchpad = false;
    try { touchpad = localStorage.getItem(touchpadKey) === '1'; } catch { /* storage disabled */ }
    const applyTouchpad = () => {
        dos.setMouseCapture(touchpad); // on touch screens js-dos turns "capture" into touchpad mode
        touchpadButton.setAttribute('aria-pressed', String(touchpad));
    };
    if (isMobile) applyTouchpad();
    touchpadButton.addEventListener('click', () => {
        touchpad = !touchpad;
        try { localStorage.setItem(touchpadKey, touchpad ? '1' : '0'); } catch { /* storage disabled */ }
        applyTouchpad();
    });

    // "Tools": shows/hides the js-dos side bar (file manager, settings...)
    const toolsButton = document.getElementById('btn-tools');
    let kiosk = true;
    toolsButton.addEventListener('click', () => {
        kiosk = !kiosk;
        dos.setKiosk(kiosk);
        toolsButton.setAttribute('aria-pressed', String(!kiosk));
    });

    // Phone keyboard: opens with "Tap to start" and the ⌨ button (taps on the DOS screen are the mouse)
    if (isMobile) {
        const keyboard = new MobileKeyboard({
            getCi: () => ci,
            input: document.getElementById('mobile-input'),
            keysBar: document.getElementById('mobile-keys'),
            toggleButton: document.getElementById('btn-keyboard'),
            layout: cfg.keyboardLayout,
        });

        // Buttons of the bar and of the menu must not steal the focus (it would close the keyboard)
        document.querySelector('.player-bar').addEventListener('pointerdown', (event) => {
            if (event.target.closest('button')) event.preventDefault();
        });
        // Using the screen as a mouse must not close the phone keyboard: stop the browser from moving
        // the focus. js-dos still gets the touch (it listens to pointer events, which fire anyway).
        const keepFocus = (event) => {
            const input = document.getElementById('mobile-input');
            if (document.activeElement === input && event.target.closest?.('#dos')) event.preventDefault();
        };
        window.addEventListener('touchstart', keepFocus, { capture: true, passive: false });
        window.addEventListener('mousedown', keepFocus, true);

        // Entering/leaving full screen closes the phone keyboard: reopen it if it was open
        document.addEventListener('fullscreenchange', () => setTimeout(() => keyboard.restore(), 150));

        // One tap does what browsers only allow after a gesture: full screen + keyboard
        startOverlay.addEventListener('click', () => {
            startOverlay.hidden = true;
            if (fullscreenPending) {
                fullscreenPending = false;
                document.documentElement.requestFullscreen().catch(() => {});
            }
            keyboard.open();
            status(t.ready);
        });
    }

    /** Save the disk, stop the emulator and go back to the dashboard. */
    let leaving = false;
    async function leave() {
        if (leaving) return;
        leaving = true;
        document.getElementById('btn-exit').disabled = true;
        await save();
        lastSave = Date.now();
        try { await dos.stop(); } catch { /* already stopped */ }
        window.location.href = cfg.backUrl;
    }

    document.getElementById('btn-exit').addEventListener('click', leave);

    // DOS "EXIT": the autoexec loop keeps DOSBox alive and drops a flag file on C:.
    // Seeing it, we remove it, save and leave (a dead DOSBox could not be saved anymore).
    let checkingExit = false;
    setInterval(async () => {
        if (!ci || leaving || checkingExit) return;
        checkingExit = true;
        try {
            // fsTree lists the in-memory files (cheap); fsReadFile can't be used: it hangs on a missing file
            const timeout = new Promise((resolve) => setTimeout(() => resolve(null), 2000));
            const tree = await Promise.race([ci.fsTree().catch(() => null), timeout]);
            const flagName = cfg.exitFlag.toLowerCase();
            if (tree?.nodes?.some((node) => node.nodes === null && node.name.toLowerCase() === flagName)) {
                await ci.fsDeleteFile(cfg.exitFlag).catch(() => {});
                await leave();
            }
        } finally {
            checkingExit = false;
        }
    }, 1500);
})();
