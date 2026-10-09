/**
 * pages/product-form.js
 *
 * Responsibilities:
 *   1. Inline validation — delegate to global initFormValidation (blur + format + submit)
 *   2. Tab-aware submit guard — phát hiện required field trống ở tab ẩn,
 *      chuyển tab, hiện Toast trước khi initFormValidation validate inline
 *   3. TomSelect — auto-init mọi select.ts-init (category, loại, trạng thái...)
 *   3b. #ts-status trong Sticky Submit Bar — init thủ công, dropdown mở lên trên (§11.3)
 *   4. Jodit "Nội dung" — giữ ảnh đã upload khi submit (clearJoditDraftTracking)
 *   4b. Ảnh đại diện = FilePond (luồng draft — Action gắn vào sản phẩm khi Lưu)
 *   4c. Xem trước nhãn giá, nút mở thử link affiliate
 *   4d. Khoá nút submit chống bấm 2 lần
 *   5. Dirty Form Guard (docs/form-ui-spec.md §17.7) — gọi cuối cùng
 *
 * Requires globals (core bundle): initFormValidation, window.Alpine, window.Toast
 * Requires globals (lazy bundle):  window.TomSelect (tom-select.js), initJodit (jodit.js),
 *                                  initFilePondUpload (filepond.js)
 */

import { createTs, initAllTomSelects } from '@shared/tom-select-factory.js';

// ── Constants & lookup tables ──────────────────────────────────────────────

const FORM_SEL = '[data-product-form]';

/** Regex trích tên tab từ x-show="tab === 'basic'" — compile 1 lần, dùng mãi. */
const RE_TAB_XSHOW = /tab\s*===\s*['"](\w+)['"]/;

// ── Entry point ────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    _setupTabGuard(form);
    initAllTomSelects(form);
    _initStatusSelect(form);
    const contentEditor = _initContentEditor(form);
    _initCoverUpload(form);
    _setupPricePreview(form);
    _setupAffiliateLinks(form);
    _setupSubmitLock(form);
    _setupDirtyGuard(form, [contentEditor]);
});

// ── Trạng thái trong Sticky Submit Bar (§11.3) ──────────────────────────────

function _initStatusSelect(form) {
    const el = form.querySelector('#ts-status');
    if (!el || el.tomselect) return; // edit: đã auto-init qua ts-init ở sidebar
    createTs(el, { dropdownParent: null, placeholder: 'Chọn trạng thái' });
}

// ── Ảnh đại diện — FilePond, luồng draft cho CẢ create lẫn edit ─────────────
// Không gửi X-Context-Type/Id: ảnh chỉ gắn vào sản phẩm khi bấm Lưu (UpdateProductAction ghi
// cover_image_url qua update() → HasApproval duyệt lại), bấm Hủy thì ảnh cũ còn nguyên.

function _initCoverUpload(form) {
    const el = form.querySelector('#cover-filepond');
    if (!el || !window.initFilePondUpload) return;
    initFilePondUpload(el, { collection: 'cover', bindTo: '#cover-media-uuid' });
}

// ── Xem trước giá hiển thị — khớp Product::getDisplayPriceAttribute() ──────

function _setupPricePreview(form) {
    const priceEl    = form.querySelector('[data-price-input]');
    const currencyEl = form.querySelector('[data-currency-input]');
    const labelEl    = form.querySelector('[data-price-label-input]');
    const out        = form.querySelector('[data-price-preview]');
    if (!priceEl || !out) return;

    const fmt = new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 });
    const render = () => {
        const label = labelEl?.value.trim();
        const price = priceEl.value.trim();
        out.textContent = label
            || (price !== '' && Number.isFinite(Number(price)) ? `${fmt.format(Math.round(Number(price)))} ${currencyEl?.value ?? ''}`.trim() : '—');
    };

    for (const el of [priceEl, currencyEl, labelEl]) el?.addEventListener('input', render, { passive: true });
    currencyEl?.addEventListener('change', render, { passive: true });
    render();
}

// ── Link affiliate: bật nút ↗ khi URL hợp lệ để mở thử ─────────────────────

function _setupAffiliateLinks(form) {
    for (const input of form.querySelectorAll('[data-affiliate-input]')) {
        const btn = input.parentElement.querySelector('[data-affiliate-open]');
        if (!btn) continue;

        const sync = () => {
            const ok = /^https?:\/\/\S+\.\S+/i.test(input.value.trim());
            btn.classList.toggle('btn-disabled', !ok);
            btn.setAttribute('aria-disabled', String(!ok));
            btn.href = ok ? input.value.trim() : '#';
        };

        btn.addEventListener('click', (e) => { if (btn.classList.contains('btn-disabled')) e.preventDefault(); });
        input.addEventListener('input', sync, { passive: true });
        sync();
    }
}

// ── Khoá nút submit chống bấm 2 lần ────────────────────────────────────────

function _setupSubmitLock(form) {
    const buttons = () => form.querySelectorAll('.submit-actions button[type="submit"]');

    document.addEventListener('submit', (e) => {
        if (e.target !== form || e.defaultPrevented) return;
        const submitter = e.submitter;
        // setTimeout: disable SAU khi trình duyệt đã gom form data (giữ name/value của submitter)
        setTimeout(() => {
            for (const btn of buttons()) btn.disabled = true;
            if (submitter?.classList.contains('btn')) {
                submitter.insertAdjacentHTML('afterbegin', '<span class="loading loading-spinner loading-xs"></span>');
            }
        }, 0);
    });

    window.addEventListener('pageshow', (e) => {
        if (!e.persisted) return;
        for (const btn of buttons()) btn.disabled = false;
        for (const spin of form.querySelectorAll('.submit-actions .loading')) spin.remove();
    });
}

// ── Jodit "Nội dung" ───────────────────────────────────────────────────────

function _initContentEditor(form) {
    const el = form.querySelector('#product-content');
    if (!el || !window.initJodit) return null;

    const editor = initJodit(el, { showWordsCounter: true, showCharsCounter: true });

    // jodit.js xoá mọi ảnh upload trong phiên khi pagehide — submit hợp lệ phải tắt tracking
    // để ảnh còn tồn tại cho UpdateProductAction/CreateProductAction "nhận" vào sản phẩm.
    document.addEventListener('submit', (e) => {
        if (e.target === form && !e.defaultPrevented) window.clearJoditDraftTracking?.(el.id);
    });

    return editor;
}

// ── Dirty Form Guard (§17.7) ───────────────────────────────────────────────

function _setupDirtyGuard(form, editors = []) {
    let isDirty = false;

    const onBeforeUnload = (e) => {
        e.preventDefault();
        e.returnValue = '';
    };

    const markDirty = () => {
        if (isDirty) return;
        isDirty = true;
        window.addEventListener('beforeunload', onBeforeUnload);
    };

    const clearDirty = () => {
        isDirty = false;
        window.removeEventListener('beforeunload', onBeforeUnload);
    };

    form.addEventListener('input',  markDirty, { passive: true });
    form.addEventListener('change', markDirty, { passive: true });

    for (const editor of editors) {
        if (!editor) continue;
        const initial = editor.value;
        editor.events.on('change', () => { if (editor.value !== initial) markDirty(); });
    }

    document.addEventListener('submit', (e) => {
        if (e.target === form && !e.defaultPrevented) clearDirty();
    });
}

// ── Tab-aware submit guard ─────────────────────────────────────────────────

function _setupTabGuard(form) {
    let wrapper = null; // cache Alpine wrapper

    form.addEventListener('submit', (e) => {
        const errors = _collectHiddenErrors(form);
        if (!errors.size) return; // Không có lỗi ở tab ẩn → initFormValidation lo

        e.preventDefault();

        wrapper ??= form.closest('[x-data]') ?? document.querySelector('[x-data]');
        _switchAlpineTab(wrapper, errors.keys().next().value);
        _toastHiddenErrors(errors);
    }, /* capture */ true);
}

/**
 * Quét [data-req] toàn form, thu thập những field trống đang nằm ở tab ẩn.
 * @returns {Map<string, {label:string, fields:string[]}>}
 */
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
