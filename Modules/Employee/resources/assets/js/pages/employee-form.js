/**
 * pages/employee-form.js
 *
 * Responsibilities:
 *   1. Inline validation — delegate to global initFormValidation
 *   2. TomSelect — khởi tạo tất cả select.ts-init trong form
 *   3. Tab-aware submit guard — phát hiện required field trống ở tab ẩn,
 *      chuyển tab, hiện Toast trước khi initFormValidation validate inline
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

// ── Constants ──────────────────────────────────────────────────────────────────

const FORM_SEL = '[data-employee-form]';

const RE_TAB_XSHOW = /tab\s*===\s*['"](\w+)['"]/;

// ── Entry point ────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    window.initAllDatePickers?.(form);
    initAllTomSelects(form);
    _setupTabGuard(form);
    _initJodit(form);
    _initOrgCascades(form);   // cascade: organization → branch, department, job_title, manager
});

// ── Org → dependent selects cascade ───────────────────────────────────────

function _initOrgCascades(form) {
    const orgEl = form.querySelector('#ts-organization');
    if (!orgEl) return; // orgLocked — org fixed, PHP đã render đúng options

    // Thu thập tất cả select có data-org-api (các trường phụ thuộc org)
    const deps = [...form.querySelectorAll('[data-org-api]')].map(el => ({
        el,
        ts:      el.tomselect,
        api:     el.dataset.orgApi,
        extra:   el.dataset.orgApiExtra || '',
        pending: el.dataset.selectedValue || '',
    })).filter(d => d.ts && d.api);

    if (!deps.length) return;

    const initialOrgId = orgEl.tomselect?.getValue() ?? '';
    if (initialOrgId) {
        deps.forEach(d => _loadOrgOptions(d.api, d.ts, initialOrgId, d.extra, d.pending));
    } else {
        deps.forEach(d => d.ts.disable());
    }

    orgEl.tomselect?.on('change', (orgId) => {
        deps.forEach(d => { d.ts.clear(true); d.ts.clearOptions(); });
        if (!orgId) {
            deps.forEach(d => d.ts.disable());
            return;
        }
        deps.forEach(d => _loadOrgOptions(d.api, d.ts, orgId, d.extra, ''));
    });
}

function _loadOrgOptions(apiUrl, ts, orgId, extra, pending) {
    ts.disable();
    fetch(`${apiUrl}?organization_id=${encodeURIComponent(orgId)}${extra}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept':           'application/json',
        },
    })
        .then(r => r.ok ? r.json() : [])
        .then(items => {
            ts.addOptions(items.map(b => ({ value: String(b.id), text: b.text })));
            ts.enable();
            if (pending) ts.setValue(String(pending), true);
        })
        .catch(() => ts.enable());
}

function _initJodit(form) {
    if (form.querySelector('.jodit-editor') && typeof initJoditAll === 'function') {
        initJoditAll('.jodit-editor');
    }
}

// ── Tab-aware submit guard ─────────────────────────────────────────────────────

function _setupTabGuard(form) {
    let wrapper = null;

    form.addEventListener('submit', (e) => {
        const errors = _collectHiddenErrors(form);
        if (!errors.size) return;

        e.preventDefault();

        wrapper ??= form.closest('[x-data]') ?? document.querySelector('[x-data]');
        _switchAlpineTab(wrapper, errors.keys().next().value);
        _toastHiddenErrors(errors);
    }, /* capture */ true);
}

function _collectHiddenErrors(form) {
    const map = new Map();

    for (const field of form.querySelectorAll('[data-req]')) {
        if (field.value.trim()) continue;

        const panel = field.closest('[x-show]');
        if (!panel || panel.style.display !== 'none') continue;

        const tabKey = RE_TAB_XSHOW.exec(panel.getAttribute('x-show') ?? '')?.[1];
        if (!tabKey) continue;

        if (!map.has(tabKey)) {
            map.set(tabKey, { label: panel.dataset.tabLabel ?? tabKey, fields: [] });
        }
        map.get(tabKey).fields.push(_resolveFieldLabel(field));
    }

    return map;
}

function _resolveFieldLabel(field) {
    const labelText = field.closest('.form-control')
        ?.querySelector('.label-text')
        ?.textContent.replace(/\s*\*\s*$/, '').trim();
    return labelText || field.placeholder || field.name || 'Trường bắt buộc';
}

function _switchAlpineTab(wrapper, tabKey) {
    if (!wrapper) return;
    try {
        const data = window.Alpine?.$data(wrapper);
        if (data?.tab !== undefined) data.tab = tabKey;
    } catch { /* Alpine chưa mount */ }
}

function _toastHiddenErrors(errors) {
    if (!window.Toast) return;

    const lines = Array.from(errors.values(), ({ label, fields }) =>
        `${label}: ${fields.join(', ')}`
    );

    Toast.warning(`Còn thiếu thông tin bắt buộc:\n${lines.join('\n')}`, {
        duration: 5000,
    });
}
