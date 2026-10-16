/*
 * Keyboards → DOSBox.
 *
 * PhysicalKeyboard: js-dos maps keys through `keyCode`, which on macOS and with non-US
 * layouts depends on the printed character, so punctuation reached DOS on the wrong keys.
 * Here every key is sent by its physical position (`event.code`), which is what the DOS
 * keyboard layout of the instance (keyb it, de, fr...) expects.
 *
 * MobileKeyboard: phone keyboard → DOSBox.
 * A hidden <input type="text"> receives what the user types on the native keyboard
 * (js-dos ignores key events coming from text inputs, so nothing is typed twice).
 * Each character is translated into the key presses that produce it with the
 * instance's DOS keyboard layout (US and Italian supported, others use US).
 */
(() => {
    'use strict';

    const K = {
        esc: 256, enter: 257, tab: 258, backspace: 259, delete: 261,
        right: 262, left: 263, down: 264, up: 265,
        shift: 340, ctrl: 341, alt: 342, altgr: 346, lt: 348, space: 32,
    };
    for (let i = 1; i <= 12; i++) K[`f${i}`] = 289 + i;

    // char → [keyCode, modifier?]   (letters and digits are handled generically)
    const LAYOUTS = {
        us: {
            plain: { ' ': 32, '`': 96, '-': 45, '=': 61, '[': 91, ']': 93, '\\': 92, ';': 59, "'": 39, ',': 44, '.': 46, '/': 47 },
            shift: { '~': 96, '!': 49, '@': 50, '#': 51, '$': 52, '%': 53, '^': 54, '&': 55, '*': 56, '(': 57, ')': 48,
                '_': 45, '+': 61, '{': 91, '}': 93, '|': 92, ':': 59, '"': 39, '<': 44, '>': 46, '?': 47 },
            altgr: {},
        },
        it: {
            plain: { ' ': 32, '\\': 96, "'": 45, 'ì': 61, 'è': 91, '+': 93, 'ò': 59, 'à': 39, 'ù': 92, ',': 44, '.': 46, '-': 47, '<': K.lt },
            shift: { '|': 96, '!': 49, '"': 50, '£': 51, '$': 52, '%': 53, '&': 54, '/': 55, '(': 56, ')': 57, '=': 48,
                '?': 45, '^': 61, 'é': 91, '*': 93, 'ç': 59, '°': 39, '§': 92, ';': 44, ':': 46, '_': 47, '>': K.lt },
            altgr: { '@': 59, '#': 39, '[': 91, ']': 93 },
        },
    };

    const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

    class MobileKeyboard {
        constructor({ getCi, input, keysBar, toggleButton, layout }) {
            this.getCi = getCi;
            this.input = input;
            this.keysBar = keysBar;
            this.toggleButton = toggleButton;
            this.layout = LAYOUTS[layout] ?? LAYOUTS.us;
            this.queue = Promise.resolve();
            this.mods = { ctrl: false, alt: false };
            this.sentinel = '··';
            this.wanted = false; // the user opened it and didn't close it with ⌨
            this.bind();
        }

        bind() {
            this.reset();
            this.input.addEventListener('input', () => this.onInput());
            this.input.addEventListener('keydown', (e) => this.onKeyDown(e));
            this.input.addEventListener('focus', () => this.toggleButton?.setAttribute('aria-pressed', 'true'));
            this.input.addEventListener('blur', () => this.toggleButton?.setAttribute('aria-pressed', 'false'));

            this.toggleButton?.addEventListener('click', () => {
                if (document.activeElement === this.input) {
                    this.wanted = false;
                    this.input.blur();
                } else {
                    this.open();
                }
            });

            // Extra keys: keep the phone keyboard open while tapping them
            this.keysBar.addEventListener('pointerdown', (e) => {
                if (e.target.closest('button')) e.preventDefault();
            });
            this.keysBar.addEventListener('click', (e) => {
                const button = e.target.closest('button');
                if (!button) return;
                if (button.dataset.mod) {
                    const mod = button.dataset.mod;
                    this.mods[mod] = !this.mods[mod];
                    button.setAttribute('aria-pressed', String(this.mods[mod]));
                } else if (button.dataset.key) {
                    this.press(K[button.dataset.key]);
                }
            });
        }

        open() {
            this.wanted = true;
            this.input.focus({ preventScroll: true });
            this.reset();
        }

        /** Reopens the keyboard if something (e.g. entering full screen) closed it behind the user's back. */
        restore() {
            if (this.wanted && document.activeElement !== this.input) this.open();
        }

        reset() {
            this.input.value = this.sentinel;
            this.previous = this.sentinel;
            const end = this.sentinel.length;
            try { this.input.setSelectionRange(end, end); } catch { /* not focused */ }
        }

        // Diff the field against its previous value: works with autocorrect/composition too.
        onInput() {
            const value = this.input.value;
            const prev = this.previous;
            let common = 0;
            while (common < value.length && common < prev.length && value[common] === prev[common]) common++;

            for (let i = common; i < prev.length; i++) this.press(K.backspace);
            for (const ch of value.slice(common)) this.typeChar(ch);

            this.previous = value;
            if (value.length < this.sentinel.length || value.length > 40) this.reset();
        }

        onKeyDown(e) {
            // Backspace is not here: it edits the field and onInput() turns it into a key press
            const special = { Enter: 'enter', Escape: 'esc', Tab: 'tab',
                ArrowUp: 'up', ArrowDown: 'down', ArrowLeft: 'left', ArrowRight: 'right' }[e.key];
            if (special === undefined) return;
            e.preventDefault();
            this.press(K[special]);
        }

        typeChar(ch) {
            if (ch === '\n') return this.press(K.enter);
            if (/^[a-z]$/.test(ch)) return this.press(ch.toUpperCase().charCodeAt(0));
            if (/^[A-Z]$/.test(ch)) return this.press(ch.charCodeAt(0), K.shift);
            if (/^[0-9]$/.test(ch)) return this.press(ch.charCodeAt(0));
            if (ch in this.layout.plain) return this.press(this.layout.plain[ch]);
            if (ch in this.layout.shift) return this.press(this.layout.shift[ch], K.shift);
            if (ch in this.layout.altgr) return this.press(this.layout.altgr[ch], K.altgr);
            // unknown character: ignored
        }

        /** Queue a key press (with modifiers) so events reach DOSBox in order. */
        press(code, ...extraMods) {
            if (!code) return;
            const mods = [...extraMods];
            if (this.mods.ctrl) mods.push(K.ctrl);
            if (this.mods.alt) mods.push(K.alt);
            this.releaseStickyMods();

            this.queue = this.queue.then(async () => {
                const ci = this.getCi();
                if (!ci) return;
                for (const m of mods) { ci.sendKeyEvent(m, true); await sleep(15); }
                ci.sendKeyEvent(code, true);
                await sleep(30);
                ci.sendKeyEvent(code, false);
                for (const m of mods.reverse()) { await sleep(15); ci.sendKeyEvent(m, false); }
                await sleep(15);
            });
        }

        releaseStickyMods() {
            if (!this.mods.ctrl && !this.mods.alt) return;
            this.mods = { ctrl: false, alt: false };
            this.keysBar.querySelectorAll('[data-mod]').forEach((b) => b.setAttribute('aria-pressed', 'false'));
        }
    }

    // event.code → DOSBox key code (physical position, US reference layout)
    const CODES = {
        Backquote: 96, Minus: 45, Equal: 61, BracketLeft: 91, BracketRight: 93, Backslash: 92,
        Semicolon: 59, Quote: 39, Comma: 44, Period: 46, Slash: 47, IntlBackslash: K.lt, Space: 32,
        Enter: K.enter, Escape: K.esc, Tab: K.tab, Backspace: K.backspace, Insert: 260, Delete: K.delete,
        ArrowRight: K.right, ArrowLeft: K.left, ArrowDown: K.down, ArrowUp: K.up,
        PageUp: 266, PageDown: 267, Home: 268, End: 269,
        CapsLock: 280, ScrollLock: 281, NumLock: 282, PrintScreen: 283, Pause: 284,
        ShiftLeft: K.shift, ControlLeft: K.ctrl, AltLeft: K.alt, ShiftRight: 344, ControlRight: 345, AltRight: K.altgr,
        NumpadDecimal: 330, NumpadDivide: 331, NumpadMultiply: 332, NumpadSubtract: 333, NumpadAdd: 334, NumpadEnter: 335,
    };
    for (let i = 0; i < 26; i++) CODES[`Key${String.fromCharCode(65 + i)}`] = 65 + i;
    for (let i = 0; i < 10; i++) { CODES[`Digit${i}`] = 48 + i; CODES[`Numpad${i}`] = 320 + i; }
    for (let i = 1; i <= 12; i++) CODES[`F${i}`] = K[`f${i}`];

    class PhysicalKeyboard {
        /**
         * @param {{getCi: () => any, blocked?: (event: KeyboardEvent) => boolean}} options
         * blocked(event) → true while the page owns the keyboard (e.g. a menu is open): keys don't reach DOS.
         */
        constructor({ getCi, blocked = () => false }) {
            const down = new Set();
            const handle = (pressed) => (event) => {
                const ci = getCi();
                const code = CODES[event.code];
                if (blocked(event)) {
                    event.stopImmediatePropagation();
                    if (!pressed && down.delete(code)) ci?.sendKeyEvent(code, false); // don't leave keys stuck
                    return;
                }
                if (!ci || code === undefined) return;
                if (event.target.closest?.('input, textarea, select, [contenteditable="true"]')) return; // typing in a field
                if (event.metaKey) return; // ⌘ shortcuts stay with the browser
                event.preventDefault();
                event.stopImmediatePropagation(); // js-dos must not send it a second time
                ci.sendKeyEvent(code, pressed);
                if (pressed) down.add(code); else down.delete(code);
            };
            // capture phase on window: runs before the js-dos listener
            window.addEventListener('keydown', handle(true), true);
            window.addEventListener('keyup', handle(false), true);
            window.addEventListener('blur', () => {
                down.forEach((code) => getCi()?.sendKeyEvent(code, false));
                down.clear();
            });
        }
    }

    window.MobileKeyboard = MobileKeyboard;
    window.PhysicalKeyboard = PhysicalKeyboard;
})();
