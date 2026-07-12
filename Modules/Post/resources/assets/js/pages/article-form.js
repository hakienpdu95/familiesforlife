/**
 * pages/article-form.js
 *
 * Responsibilities:
 *   1. Inline validation — delegate to global initFormValidation
 *   2. Tab-aware submit guard — phát hiện required field trống ở tab ẩn,
 *      chuyển tab, hiện Toast trước khi initFormValidation validate inline
 *   3. TomSelect — auto-init select.ts-init (định dạng nội dung)
 *   4. Tags — biến input thường thành TomSelect tag-input (create-on-type,
 *      vẫn submit dạng chuỗi phân tách dấu phẩy qua name="tags" — không đổi contract backend)
 *   5. Flatpickr — auto-init input.fp-init (vd scheduled_at) trên toàn trang, KHÔNG scope theo
 *      `form` — field lên lịch xuất bản nằm ở 1 <form> riêng trong sidebar "Trạng thái", không
 *      lồng được trong <form data-article-form> chính (HTML không cho phép <form> lồng nhau)
 *
 * Requires globals (core bundle): initFormValidation, window.Alpine, window.Toast
 * Requires globals (lazy bundle):  window.TomSelect (tom-select.js), initAllDatePickers (flatpickr.js)
 *
 * Không đụng tới post-block-composer.js — composer tự tìm form qua .closest('form')
 * và tự đồng bộ blocks_json khi submit, độc lập với guard này.
 */

import { createTs, initAllTomSelects } from '@shared/tom-select-factory.js';

// ── Constants ──────────────────────────────────────────────────────────────

const FORM_SEL = '[data-article-form]';
const RE_TAB_XSHOW = /tab\s*===\s*['"](\w+)['"]/;

// ── Entry point ────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    window.initAllDatePickers?.();

    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    _setupTabGuard(form);
    initAllTomSelects(form);
    _setupTagsInput(form);
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
    } catch { /* Alpine chưa mount — bỏ qua */ }
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

// ── Tags — TomSelect tag-input trên input thường ────────────────────────────

/**
 * Biến <input name="tags"> (chuỗi "a, b, c") thành TomSelect tag-input:
 * gõ + Enter/phẩy để tạo tag mới, click x để xoá. TomSelect gắn trực tiếp lên
 * <input> (không cần <select>) vẫn submit đúng name="tags" dạng chuỗi phân
 * tách bởi delimiter — không cần đổi validation/Data class ở backend.
 */
function _setupTagsInput(form) {
    const input = form.querySelector('[name="tags"]');
    if (!input || input.tomselect) return;

    createTs(input, {
        create:          true,
        persist:         false,
        createOnBlur:    true,
        delimiter:       ',',
        hidePlaceholder: true,
        plugins:         ['remove_button'],
        placeholder:     'Gõ tag rồi Enter — VD: ngủ, sơ sinh, mẹo hay',
    });
}
