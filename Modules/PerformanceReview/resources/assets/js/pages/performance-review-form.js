/**
 * Modules/PerformanceReview/resources/assets/js/pages/performance-review-form.js
 *
 * Responsibilities:
 *   1. Inline validation — delegate to global initFormValidation
 *   2. Tab-aware submit guard — phát hiện required field trống ở tab ẩn
 *   3. Template TomSelect — khởi tạo thủ công, kết nối onChange → Alpine selectTemplate()
 *   4. Static TomSelect — auto-init mọi select.ts-init còn lại
 *   5. Flatpickr — khởi tạo date picker cho period_start / period_end
 *
 * Requires globals (core): initFormValidation, window.Alpine, window.Toast
 * Requires globals (lazy): window.TomSelect (tom-select.js), initDatePicker (flatpickr.js)
 */

import { createTs, initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL     = '[data-performance-review-form]';
const RE_TAB_XSHOW = /tab\s*===\s*['"](\w+)['"]/;  // compile 1 lần

// ── Entry point ────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    _setupTabGuard(form);
    initAllTomSelects(form);
    _initTemplateTomSelect(form);
    _initFlatpickr(form);
    _initJodit(form);
    _initOrgCascades(form);       // cascade: organization → employee, reviewer
    _initTemplateCascade(form);   // cascade: organization → template + Alpine criteria
});

// ── Template TomSelect (manual — cần onChange → Alpine) ───────────────────────

function _initTemplateTomSelect(form) {
    const el = form.querySelector('[name="template_id"]');
    if (!el || el.tomselect) return;

    let alpineRoot = null;

    createTs(el, {
        placeholder: '— Chọn mẫu đánh giá —',
        onChange(val) {
            try {
                alpineRoot ??= form.closest('[x-data]') ?? document.querySelector('[x-data]');
                const data = window.Alpine?.$data(alpineRoot);
                data?.selectTemplate?.(val ?? '');
            } catch { /* Alpine not ready */ }
        },
    });
}

// ── Org → employee + reviewer cascade ────────────────────────────────────────

function _initOrgCascades(form) {
    const orgEl = form.querySelector('#ts-organization');
    if (!orgEl) return; // orgLocked

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
        if (!orgId) { deps.forEach(d => d.ts.disable()); return; }
        deps.forEach(d => _loadOrgOptions(d.api, d.ts, orgId, d.extra, ''));
    });
}

function _loadOrgOptions(apiUrl, ts, orgId, extra, pending) {
    ts.disable();
    fetch(`${apiUrl}?organization_id=${encodeURIComponent(orgId)}${extra}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    })
        .then(r => r.ok ? r.json() : [])
        .then(items => {
            ts.addOptions(items.map(b => ({ value: String(b.id), text: b.text })));
            ts.enable();
            if (pending) ts.setValue(String(pending), true);
        })
        .catch(() => ts.enable());
}

// ── Org → template cascade (special: also updates Alpine criteria) ────────────

function _initTemplateCascade(form) {
    const orgEl = form.querySelector('#ts-organization');
    const tplEl = form.querySelector('[name="template_id"]');
    if (!orgEl || !tplEl) return;

    const apiUrl = tplEl.dataset.orgApiTemplates;
    if (!apiUrl) return; // orgLocked — PHP already rendered correct options

    const tsOrg = orgEl.tomselect;
    const tsTpl = tplEl.tomselect;
    if (!tsOrg || !tsTpl) return;

    let alpineRoot = null;
    const getAlpine = () => {
        alpineRoot ??= form.closest('[x-data]') ?? document.querySelector('[x-data]');
        try { return window.Alpine?.$data(alpineRoot) ?? null; } catch { return null; }
    };

    const loadTemplates = (orgId, pending) => {
        tsTpl.clear(true);
        tsTpl.clearOptions();
        tsTpl.disable();
        if (!orgId) return;

        fetch(`${apiUrl}?organization_id=${encodeURIComponent(orgId)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then(r => r.ok ? r.json() : [])
            .then(items => {
                tsTpl.addOptions(items.map(t => ({ value: String(t.id), text: t.text })));
                tsTpl.enable();
                // Sync Alpine templates array so criteria scoring works
                const data = getAlpine();
                if (data) data.templates = items;
                if (pending) {
                    tsTpl.setValue(String(pending), true);
                    getAlpine()?.selectTemplate?.(String(pending));
                }
            })
            .catch(() => tsTpl.enable());
    };

    const initialOrgId = tsOrg.getValue() ?? '';
    const pending      = tplEl.dataset.selectedValue || '';
    if (initialOrgId) {
        loadTemplates(initialOrgId, pending);
    } else {
        tsTpl.disable();
    }

    tsOrg.on('change', (orgId) => loadTemplates(orgId, ''));
}

// ── Jodit rich-text editors ───────────────────────────────────────────────────

function _initJodit(form) {
    if (form.querySelector('.jodit-editor') && typeof initJoditAll === 'function') {
        initJoditAll('.jodit-editor');
    }
}

// ── Flatpickr date pickers ─────────────────────────────────────────────────────

function _initFlatpickr(form) {
    if (typeof initDatePicker !== 'function') return;
    const opts = { altInput: true, altFormat: 'd/m/Y', dateFormat: 'Y-m-d' };
    const start = form.querySelector('#fp-period-start');
    const end   = form.querySelector('#fp-period-end');
    if (start) initDatePicker(start, opts);
    if (end)   initDatePicker(end, opts);
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
    } catch { /* Alpine not ready */ }
}

function _toastHiddenErrors(errors) {
    if (!window.Toast) return;
    const lines = Array.from(errors.values(), ({ label, fields }) =>
        `${label}: ${fields.join(', ')}`
    );
    Toast.warning(`Còn thiếu thông tin bắt buộc:\n${lines.join('\n')}`, { duration: 5000 });
}
