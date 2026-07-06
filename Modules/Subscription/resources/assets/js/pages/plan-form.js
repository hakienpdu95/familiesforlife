/**
 * plan-form.js
 *
 * Responsibilities:
 *   1. Inline validation — delegate to global initFormValidation
 *   2. Tab-aware submit guard — phát hiện required field trống ở tab ẩn,
 *      chuyển tab + Toast trước khi initFormValidation validate inline
 *   3. Slug auto-fill — sinh slug từ tên plan (Vietnamese-aware), khoá lại
 *      ngay khi user tự sửa, hoặc khi đang edit (slug đã có giá trị)
 *   4. TomSelect — auto-init mọi select.ts-init (tier...)
 *
 * Requires globals (core bundle):   initFormValidation, window.Alpine, window.Toast
 * Requires globals (lazy bundle):   window.TomSelect (tom-select.js)
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

// ── Constants ──────────────────────────────────────────────────────────────

const FORM_SEL = '[data-plan-form]';

/** Compile 1 lần — trích tên tab từ x-show="tab === 'basic'". */
const RE_TAB_XSHOW = /tab\s*===\s*['"](\w+)['"]/;

const VI_MAP = Object.freeze({
    à:'a', á:'a', ả:'a', ã:'a', ạ:'a',
    ă:'a', ằ:'a', ắ:'a', ẳ:'a', ẵ:'a', ặ:'a',
    â:'a', ầ:'a', ấ:'a', ẩ:'a', ẫ:'a', ậ:'a',
    è:'e', é:'e', ẻ:'e', ẽ:'e', ẹ:'e',
    ê:'e', ề:'e', ế:'e', ể:'e', ễ:'e', ệ:'e',
    ì:'i', í:'i', ỉ:'i', ĩ:'i', ị:'i',
    ò:'o', ó:'o', ỏ:'o', õ:'o', ọ:'o',
    ô:'o', ồ:'o', ố:'o', ổ:'o', ỗ:'o', ộ:'o',
    ơ:'o', ờ:'o', ớ:'o', ở:'o', ỡ:'o', ợ:'o',
    ù:'u', ú:'u', ủ:'u', ũ:'u', ụ:'u',
    ư:'u', ừ:'u', ứ:'u', ử:'u', ữ:'u', ự:'u',
    ỳ:'y', ý:'y', ỷ:'y', ỹ:'y', ỵ:'y',
    đ:'d',
});

// ── Entry point ────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    _setupTabGuard(form);
    _setupSlugAutoFill(form);
    initAllTomSelects(form);
});

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
        if (!map.has(tabKey)) map.set(tabKey, { label: panel.dataset.tabLabel ?? tabKey, fields: [] });
        map.get(tabKey).fields.push(_resolveFieldLabel(field));
    }
    return map;
}

function _resolveFieldLabel(field) {
    return field.closest('.form-control')?.querySelector('.label-text')
        ?.textContent.replace(/\s*\*\s*$/, '').trim()
        ?? field.placeholder ?? field.name ?? 'Trường bắt buộc';
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
    const lines = Array.from(errors.values(), ({ label, fields }) => `${label}: ${fields.join(', ')}`);
    Toast.warning(`Còn thiếu thông tin bắt buộc:\n${lines.join('\n')}`, { duration: 5000 });
}

// ── Slug auto-fill ─────────────────────────────────────────────────────────

function _setupSlugAutoFill(form) {
    const nameInput = form.querySelector('[name="name"]');
    const slugInput = form.querySelector('[name="slug"]');
    if (!nameInput || !slugInput) return;

    // Edit: slug đã có giá trị → locked từ đầu
    let locked = slugInput.value.trim() !== '';

    slugInput.addEventListener('input',  () => { locked = slugInput.value.trim() !== ''; }, { passive: true });
    slugInput.addEventListener('change', () => { if (!slugInput.value.trim()) locked = false; }, { passive: true });
    nameInput.addEventListener('input',  () => { if (!locked) slugInput.value = _toSlug(nameInput.value); }, { passive: true });
}

function _toSlug(str) {
    let out = '';
    for (const ch of str.toLowerCase()) out += VI_MAP[ch] ?? ch;
    return out.replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-').replace(/-{2,}/g, '-');
}
