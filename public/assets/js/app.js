/* DOSHost — tiny fetch helpers, no frameworks. */
(() => {
    'use strict';

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    /** POST helper: returns the parsed JSON ({ok, message, errors, redirect}). */
    async function api(url, body = null) {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch', 'X-CSRF-Token': csrf },
            body,
            credentials: 'same-origin',
        });
        try {
            return await response.json();
        } catch {
            return { ok: false, message: response.status === 413 ? 'Upload too large' : `HTTP ${response.status}` };
        }
    }

    function toast(message, type = 'light') {
        if (!message) return;
        const el = document.createElement('div');
        el.className = `toast align-items-center border-0 text-bg-${type === 'error' ? 'danger' : 'light'}`;
        el.setAttribute('role', 'status');
        el.innerHTML = '<div class="d-flex"><div class="toast-body"></div><button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button></div>';
        el.querySelector('.toast-body').textContent = message;
        document.getElementById('toasts')?.append(el);
        el.addEventListener('hidden.bs.toast', () => el.remove());
        bootstrap.Toast.getOrCreateInstance(el, { delay: 4000 }).show();
    }

    function showErrors(form, errors = {}) {
        form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
        form.querySelectorAll('[data-error-for]').forEach((el) => { el.textContent = ''; el.style.display = ''; });
        for (const [field, message] of Object.entries(errors)) {
            const input = form.querySelector(`[name="${field}"], [name="${field}[]"]`);
            const feedback = form.querySelector(`[data-error-for="${field}"]`);
            input?.classList.add('is-invalid');
            if (feedback) {
                feedback.textContent = message;
                feedback.style.display = 'block';
            }
        }
    }

    function handle(data) {
        if (data.reload) {
            window.location.reload();
            return;
        }
        if (data.redirect) {
            window.location.href = data.redirect;
            return;
        }
        toast(data.message, data.ok ? 'light' : 'error');
    }

    // Shared confirmation modal: confirm(text) resolves true/false.
    const modalEl = document.getElementById('confirm-modal');
    const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
    let resolveConfirm = null;

    async function confirmDialog(text, trigger = null) {
        if (!modal) return window.confirm(text);
        // Bootstrap can't stack modals: close the one the trigger lives in first
        const parent = trigger?.closest('.modal.show');
        if (parent) {
            await new Promise((resolve) => {
                parent.addEventListener('hidden.bs.modal', resolve, { once: true });
                bootstrap.Modal.getInstance(parent).hide();
            });
        }
        modalEl.querySelector('[data-confirm-text]').textContent = text;
        modal.show();
        return new Promise((resolve) => { resolveConfirm = resolve; });
    }

    if (modalEl) {
        modalEl.querySelector('[data-confirm-ok]').addEventListener('click', () => {
            resolveConfirm?.(true);
            resolveConfirm = null;
            modal.hide();
        });
        modalEl.addEventListener('hidden.bs.modal', () => {
            resolveConfirm?.(false);
            resolveConfirm = null;
        });
    }

    async function busy(element, task) {
        element?.setAttribute('disabled', '');
        try {
            return await task();
        } catch (error) {
            toast(error.message, 'error');
        } finally {
            element?.removeAttribute('disabled');
        }
    }

    // <form data-ajax> → submitted with fetch, errors shown inline.
    // data-ask="..." asks for confirmation first, data-reset clears the form on success.
    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!form.matches('form[data-ajax]')) return;
        event.preventDefault();
        if (form.dataset.ask && !(await confirmDialog(form.dataset.ask))) return;

        await busy(form.querySelector('[type="submit"]'), async () => {
            const data = await api(form.action, new FormData(form));
            showErrors(form, data.errors);
            if (data.ok && form.hasAttribute('data-reset')) form.reset();
            if (data.ok || !data.errors || !Object.keys(data.errors).length) handle(data);
        });
    });

    // [data-confirm][data-action] → confirmation, then POST. [data-post] → POST directly.
    document.addEventListener('click', async (event) => {
        const trigger = event.target.closest('[data-action], [data-post]');
        if (!trigger) return;
        if (trigger.dataset.confirm && !(await confirmDialog(trigger.dataset.confirm, trigger))) return;
        await busy(trigger, async () => handle(await api(trigger.dataset.action ?? trigger.dataset.post)));
    });

    // <select data-autosubmit> → submits its GET form on change.
    document.addEventListener('change', (event) => {
        if (event.target.matches('select[data-autosubmit]')) event.target.form.submit();
    });

    // Clear stale errors when a modal form is reopened; a trigger with data-fill='{"action": ..., "field": value}'
    // fills the form first (one modal can edit many items).
    document.addEventListener('show.bs.modal', (event) => {
        const form = event.target.querySelector('form[data-ajax]') ?? (event.target.matches('form') ? event.target : null);
        if (!form) return;
        showErrors(form);
        if (form.hasAttribute('data-reset')) form.reset();
        const fill = event.relatedTarget?.dataset.fill;
        if (!fill) return;
        const data = JSON.parse(fill);
        if (data.action) form.action = data.action;
        for (const [name, value] of Object.entries(data)) {
            const field = form.elements.namedItem(name);
            if (!field || name === 'action' || field.type === 'file') continue;
            if (field.type === 'checkbox') field.checked = Boolean(value);
            else field.value = value ?? '';
        }
        // data-bind="attribute:key,..." copies values onto other elements (e.g. a delete button); "text" sets the text
        event.target.querySelectorAll('[data-bind]').forEach((el) => {
            for (const pair of el.dataset.bind.split(',')) {
                const [attr, key] = pair.split(':');
                if (attr === 'text') el.textContent = data[key] ?? '';
                else el.setAttribute(attr, data[key] ?? '');
            }
        });
    });

    // Bootstrap .collapse[data-remember="key"] keeps its open state across reloads (per tab).
    document.querySelectorAll('.collapse[data-remember]').forEach((el) => {
        const key = `open:${el.dataset.remember}`;
        try {
            if (sessionStorage.getItem(key) === '1') {
                el.classList.add('show');
                document.querySelectorAll(`[data-bs-target="#${el.id}"]`).forEach((b) => b.setAttribute('aria-expanded', 'true'));
            }
        } catch { /* storage disabled */ }
        el.addEventListener('shown.bs.collapse', () => { try { sessionStorage.setItem(key, '1'); } catch { /* */ } });
        el.addEventListener('hidden.bs.collapse', () => { try { sessionStorage.setItem(key, '0'); } catch { /* */ } });
    });

    // <details data-remember="key"> keeps its open/closed state across reloads (per browser tab).
    document.querySelectorAll('details[data-remember]').forEach((el) => {
        const key = `open:${el.dataset.remember}`;
        try { if (sessionStorage.getItem(key) === '1') el.open = true; } catch { /* storage disabled */ }
        el.addEventListener('toggle', () => {
            try { sessionStorage.setItem(key, el.open ? '1' : '0'); } catch { /* storage disabled */ }
        });
    });

    // [data-toggle-all="selector"] opens all matching .collapse panels, or closes them if all are open.
    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-toggle-all]');
        if (!button) return;
        const items = [...document.querySelectorAll(button.dataset.toggleAll)];
        const open = !items.every((el) => el.classList.contains('show'));
        items.forEach((el) => bootstrap.Collapse.getOrCreateInstance(el, { toggle: false })[open ? 'show' : 'hide']());
    });

    // [data-dropzone]: a <label> wrapping a file input that also accepts drag & drop
    document.querySelectorAll('[data-dropzone]').forEach((zone) => {
        const input = zone.querySelector('input[type="file"]');
        const list = zone.querySelector('[data-dropzone-list]');
        const show = () => {
            list.textContent = [...input.files].map((f) => f.name).join(', ');
            if (input.files.length && input.form) showErrors(input.form);
        };
        input.addEventListener('change', show);
        input.form?.addEventListener('reset', () => setTimeout(show));
        ['dragenter', 'dragover'].forEach((type) => zone.addEventListener(type, (event) => {
            event.preventDefault();
            zone.classList.add('is-over');
        }));
        ['dragleave', 'drop'].forEach((type) => zone.addEventListener(type, () => zone.classList.remove('is-over')));
        zone.addEventListener('drop', (event) => {
            event.preventDefault();
            input.files = event.dataTransfer.files;
            show();
        });
    });

    window.DOSHost = { api, toast, confirm: confirmDialog };
})();
