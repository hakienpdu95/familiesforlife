/**
 * listing-form.js
 *
 * Responsibilities:
 *   1. Inline validation — delegate to global initFormValidation
 *   2. Tab-aware submit guard — phát hiện required field trống ở tab ẩn,
 *      chuyển tab, hiện Toast
 *   3. TomSelect — auto-init mọi select.ts-init; init listing_type thủ công
 *      với onChange để cập nhật Alpine listingType reactive var
 *   4. Flatpickr — auto-init mọi input.fp-init (expire_at)
 *   5. Jodit init — description, requirements, benefits
 */

import { createTs, initAllTomSelects } from '@shared/tom-select-factory.js';

// ── Constants ──────────────────────────────────────────────────────────────

const FORM_SEL = '[data-listing-form]';
const RE_TAB_XSHOW = /tab\s*===\s*['"](\w+)['"]/;

// ── Entry point ────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    _setupTabGuard(form);
    initAllTomSelects(form);
    _initListingTypeSelect(form);
    window.initAllDatePickers?.(form);
    _initJodit(form);
});

// ── Listing type select — khởi tạo thủ công để cập nhật Alpine ────────────

function _initListingTypeSelect(form) {
    const el = form.querySelector('[name="listing_type"]');
    if (!el || el.tomselect) return;

    const wrapper = form.closest('[x-data]') ?? document.querySelector('[x-data]');

    createTs(el, {
        onChange(val) {
            try {
                const data = window.Alpine?.$data(wrapper);
                if (data?.listingType !== undefined) data.listingType = val;
            } catch { /* Alpine not ready */ }
        },
    });
}

// ── Tab-aware submit guard ─────────────────────────────────────────────────

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

// ── Jodit ──────────────────────────────────────────────────────────────────

function _initJodit(form) {
    if (form.querySelector('.jodit-editor') && typeof initJoditAll === 'function') {
        initJoditAll('.jodit-editor');
    }
}
