/**
 * organization-form.js
 *
 * Responsibilities:
 *   1. Inline validation — delegate to global initFormValidation (blur + format + submit)
 *   2. Tab-aware submit guard — phát hiện required field trống ở tab ẩn,
 *      chuyển tab, hiện Toast trước khi initFormValidation validate inline
 *   3. Slug auto-fill — sinh slug từ tên tổ chức (Vietnamese-aware), khoá lại
 *      ngay khi user tự sửa slug
 *   4. TomSelect — auto-init mọi select.ts-init (status...)
 *   5. Jodit init
 *
 * Requires globals (core bundle): initFormValidation, window.Alpine, window.Toast
 * Requires globals (lazy bundle):  initJoditAll (jodit.js), window.TomSelect (tom-select.js)
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

// ── Constants & lookup tables ──────────────────────────────────────────────

/** Selector phải khớp với attribute trên form trong blade. */
const FORM_SEL = '[data-org-form]';

/**
 * Regex trích tên tab từ x-show="tab === 'basic'" — compile 1 lần, dùng mãi.
 * Capture group 1 → tên tab.
 */
const RE_TAB_XSHOW = /tab\s*===\s*['"](\w+)['"]/;

/**
 * Bảng chuyển ký tự tiếng Việt → Latin cho slug.
 * Object.freeze() để V8 tối ưu làm hidden class cố định, tránh re-shape.
 */
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
    _initJodit(form);
});

// ── Tab-aware submit guard ─────────────────────────────────────────────────

/**
 * Chạy ở capture phase (trước initFormValidation's bubble handler).
 * Nếu có required field trống trên tab ẩn:
 *   - Chuyển sang tab đó qua Alpine.$data()
 *   - Hiện Toast liệt kê tab + field
 *   - preventDefault để dừng submit
 * initFormValidation vẫn chạy sau (bubble) và sẽ highlight inline error
 * trên tab vừa được switch về.
 */
function _setupTabGuard(form) {
    // Lazy cache — Alpine wrapper ổn định sau DOMContentLoaded
    let wrapper = null;

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
        // Đã điền → bỏ qua ngay
        if (field.value.trim()) continue;

        // Tìm tab panel cha
        const panel = field.closest('[x-show]');
        if (!panel || panel.style.display !== 'none') continue;

        // Trích key tab từ x-show attr (dùng regex đã compile)
        const tabKey = RE_TAB_XSHOW.exec(panel.getAttribute('x-show') ?? '')?.[1];
        if (!tabKey) continue;

        if (!map.has(tabKey)) {
            // Label lấy từ data-tab-label (blade tự khai báo) → JS không hardcode
            map.set(tabKey, { label: panel.dataset.tabLabel ?? tabKey, fields: [] });
        }
        map.get(tabKey).fields.push(_resolveFieldLabel(field));
    }

    return map;
}

/** Lấy tên hiển thị của field: label-text → placeholder → name attribute. */
function _resolveFieldLabel(field) {
    const labelText = field.closest('.form-control')
        ?.querySelector('.label-text')
        ?.textContent.replace(/\s*\*\s*$/, '').trim();
    return labelText || field.placeholder || field.name || 'Trường bắt buộc';
}

/** Chuyển tab qua Alpine reactive data — đồng bộ, không cần event. */
function _switchAlpineTab(wrapper, tabKey) {
    if (!wrapper) return;
    try {
        const data = window.Alpine?.$data(wrapper);
        if (data?.tab !== undefined) data.tab = tabKey;
    } catch { /* Alpine chưa mount — bỏ qua */ }
}

/** Hiện Toast.warning() liệt kê từng tab + field còn thiếu. */
function _toastHiddenErrors(errors) {
    if (!window.Toast) return;

    const lines = Array.from(errors.values(), ({ label, fields }) =>
        `${label}: ${fields.join(', ')}`
    );

    Toast.warning(`Còn thiếu thông tin bắt buộc:\n${lines.join('\n')}`, {
        duration: 5000,
    });
}

// ── Slug auto-fill ─────────────────────────────────────────────────────────

/**
 * Tự động điền slug khi user gõ tên tổ chức.
 * Dừng auto-fill ngay khi user tự chỉnh slug (locked = true).
 * Trên trang edit slug đã có giá trị → locked từ đầu.
 */
function _setupSlugAutoFill(form) {
    const nameInput = form.querySelector('[name="name"]');
    const slugInput = form.querySelector('[name="slug"]');
    if (!nameInput || !slugInput) return;

    let locked = slugInput.value.trim() !== '';

    // User tự sửa slug → khoá, không auto-fill nữa
    slugInput.addEventListener('input', () => {
        locked = slugInput.value.trim() !== '';
    }, { passive: true });

    // Nếu user xoá hết slug → mở khoá lại
    slugInput.addEventListener('change', () => {
        if (!slugInput.value.trim()) locked = false;
    }, { passive: true });

    nameInput.addEventListener('input', () => {
        if (locked) return;
        slugInput.value = _toSlug(nameInput.value);
    }, { passive: true });
}

/**
 * Chuyển chuỗi tiếng Việt sang slug URL-safe.
 * Pipeline: lowercase → map VI chars → strip non-alphanumeric → trim → spaces→hyphens → dedupe hyphens
 */
function _toSlug(str) {
    let out = '';
    for (const ch of str.toLowerCase()) {
        out += VI_MAP[ch] ?? ch;
    }
    return out
        .replace(/[^a-z0-9\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-')
        .replace(/-{2,}/g, '-');
}

// ── Jodit ──────────────────────────────────────────────────────────────────

function _initJodit(form) {
    if (form.querySelector('.jodit-editor') && typeof initJoditAll === 'function') {
        initJoditAll('.jodit-editor');
    }
}
