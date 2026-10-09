/**
 * pages/heritage-site-form.js
 * Tab-based form theo docs/form-ui-spec.md v5.1 (§10, §11, §17, §18).
 *
 * Responsibilities:
 *   1. Inline validation — global initFormValidation (blur + format + submit)
 *   2. Tab-Aware Submit Guard (§17) — lỗi ở tab ẩn → chuyển tab + Toast
 *   3. TomSelect — select.ts-init auto-init
 *   3b. Nút trạng thái (name="status") — xác nhận khi gỡ xuất bản, khoá nút chống submit 2 lần
 *   4. FilePond ảnh bìa — create: bindTo uuid draft; edit: gắn thẳng vào entity
 *   4b. Jodit cho "Nội dung" — giữ ảnh đã upload khi submit (clearJoditDraftTracking)
 *   5. Slug auto-fill có debounce (§18)
 *   6. Tọa độ — dán "lat, lng" vào ô Vĩ độ tự tách; link xem bản đồ
 *   7. Dirty Form Guard (§17.7) — gọi cuối cùng
 *
 * Requires globals: initFormValidation, Toast (toastify.js), TomSelect (tom-select.js),
 *                   initFilePondUpload (filepond.js), initJodit (jodit.js)
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-heritage-site-form]';
const RE_TAB_XSHOW = /tab\s*===\s*['"](\w+)['"]/;
const RE_COORD_PAIR = /^\s*(-?\d+(?:\.\d+)?)\s*[,;\s]\s*(-?\d+(?:\.\d+)?)\s*$/;
const SLUG_DEBOUNCE_MS = 300;

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

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    _setupTabGuard(form);
    initAllTomSelects(form);
    _setupStatusButtons(form);
    _initCoverUpload(form);
    const contentEditor = _initContentEditor(form);
    _setupSlugAutoFill(form);
    _setupCoordinates(form);
    _setupDirtyGuard(form, [contentEditor]);
});

// ── Tab-Aware Submit Guard (§17) ──────────────────────────────────────────────

function _setupTabGuard(form) {
    let wrapper = null;

    form.addEventListener('submit', (e) => {
        const errors = _collectHiddenErrors(form);
        if (!errors.size) return;

        e.preventDefault();
        wrapper ??= form.closest('[x-data]');
        _switchAlpineTab(wrapper, errors.keys().next().value);
        _toastHiddenErrors(errors);
    }, true);
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

// ── Nút trạng thái trong sticky bar ───────────────────────────────────────────

function _setupStatusButtons(form) {
    for (const btn of form.querySelectorAll('button[data-confirm]')) {
        btn.addEventListener('click', (e) => {
            if (!window.confirm(btn.dataset.confirm)) e.preventDefault();
        });
    }

    document.addEventListener('submit', (e) => {
        if (e.target !== form || e.defaultPrevented) return;
        const submitter = e.submitter;
        setTimeout(() => {
            for (const btn of form.querySelectorAll('.submit-actions button[type="submit"]')) btn.disabled = true;
            if (submitter?.classList.contains('btn')) {
                submitter.insertAdjacentHTML('afterbegin', '<span class="loading loading-spinner loading-xs"></span>');
            }
        }, 0);
    });

    window.addEventListener('pageshow', (e) => {
        if (!e.persisted) return;
        for (const btn of form.querySelectorAll('.submit-actions button[type="submit"]')) btn.disabled = false;
        for (const spin of form.querySelectorAll('.submit-actions .loading')) spin.remove();
    });
}

// ── FilePond ảnh bìa ──────────────────────────────────────────────────────────

function _initCoverUpload(form) {
    const el = form.querySelector('#cover-filepond');
    if (!el || !window.initFilePondUpload) return;

    const { contextType, contextId } = el.dataset;
    if (contextType && contextId) {
        initFilePondUpload(el, { collection: 'cover', contextType, contextId, allowRevert: true });
    } else {
        initFilePondUpload(el, { collection: 'cover', bindTo: '#cover-media-uuid' });
    }
}

// ── Jodit "Nội dung" ──────────────────────────────────────────────────────────

function _initContentEditor(form) {
    const el = form.querySelector('#heritage-content');
    if (!el || !window.initJodit) return null;

    const editor = initJodit(el, { showWordsCounter: true, showCharsCounter: true });

    document.addEventListener('submit', (e) => {
        if (e.target === form && !e.defaultPrevented) window.clearJoditDraftTracking?.(el.id);
    });

    return editor;
}

// ── Slug auto-fill (§18) ──────────────────────────────────────────────────────

function _debounce(fn, wait) {
    let timer = null;
    const debounced = (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => { timer = null; fn(...args); }, wait);
    };
    debounced.cancel = () => { clearTimeout(timer); timer = null; };
    debounced.flush  = () => { if (timer) { debounced.cancel(); fn(); } };
    return debounced;
}

function _setupSlugAutoFill(form) {
    const nameInput = form.querySelector('[name="name"]');
    const slugInput = form.querySelector('[name="slug"]');
    if (!nameInput || !slugInput) return;

    let locked = slugInput.value.trim() !== '';

    const fillSlug = _debounce(() => {
        if (!locked) slugInput.value = _toSlug(nameInput.value);
    }, SLUG_DEBOUNCE_MS);

    slugInput.addEventListener('input',  () => { locked = slugInput.value.trim() !== ''; if (locked) fillSlug.cancel(); }, { passive: true });
    slugInput.addEventListener('change', () => { if (!slugInput.value.trim()) locked = false; }, { passive: true });
    nameInput.addEventListener('input',  fillSlug, { passive: true });

    form.addEventListener('submit', () => fillSlug.flush(), true);
}

function _toSlug(str) {
    let out = '';
    for (const ch of str.toLowerCase()) out += VI_MAP[ch] ?? ch;
    return out.replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-').replace(/-{2,}/g, '-');
}

// ── Tọa độ: tách cặp "lat, lng" khi dán + link bản đồ ─────────────────────────

function _setupCoordinates(form) {
    const latInput = form.querySelector('[data-coord="lat"]');
    const lngInput = form.querySelector('[data-coord="lng"]');
    const mapLink  = form.querySelector('[data-coord-map]');
    if (!latInput || !lngInput) return;

    const syncMapLink = () => {
        if (!mapLink) return;
        const lat = parseFloat(latInput.value);
        const lng = parseFloat(lngInput.value);
        const ok = Number.isFinite(lat) && Number.isFinite(lng);
        mapLink.classList.toggle('hidden', !ok);
        if (ok) mapLink.href = `https://www.google.com/maps?q=${lat},${lng}`;
    };

    for (const input of [latInput, lngInput]) {
        input.addEventListener('paste', (e) => {
            const m = RE_COORD_PAIR.exec(e.clipboardData?.getData('text') ?? '');
            if (!m) return;
            e.preventDefault();
            latInput.value = m[1];
            lngInput.value = m[2];
            latInput.dispatchEvent(new Event('input', { bubbles: true }));
            lngInput.dispatchEvent(new Event('input', { bubbles: true }));
        });
        input.addEventListener('input', syncMapLink, { passive: true });
    }

    syncMapLink();
}

// ── Dirty Form Guard (§17.7) ──────────────────────────────────────────────────

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
