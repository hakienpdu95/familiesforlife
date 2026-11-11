/**
 * ocop-product-form.js
 *
 * Responsibilities:
 *   1. Inline validation — delegate to global initFormValidation (blur + format + submit)
 *   2. Tab-aware submit guard — phát hiện required field trống ở tab ẩn,
 *      chuyển tab, hiện Toast trước khi initFormValidation validate inline
 *   3. TomSelect — auto-init mọi select.ts-init (category_id, star_rating, status...)
 *
 * Requires globals (core bundle): initFormValidation, window.Alpine, window.Toast
 * Requires globals (lazy bundle):  window.TomSelect (tom-select.js)
 */

import { createTs, initAllTomSelects } from '@shared/tom-select-factory.js';
import { renderCategoryTreeOption } from '@shared/ts-tree-option.js';

import { t } from '../../../../../../resources/js/shared/i18n.js';
// ── Constants & lookup tables ──────────────────────────────────────────────

const FORM_SEL = '[data-ocop-product-form]';

/** Trích tên tab từ x-show="tab === 'basic'" — compile 1 lần, dùng mãi. */
const RE_TAB_XSHOW = /tab\s*===\s*['"](\w+)['"]/;

// ── Entry point ────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    _setupTabGuard(form);
    _initCategorySelect(form);
    _setupAffiliateLinks(form);
    _setupDocumentUploads(form);
    initAllTomSelects(form);
});

function _setupDocumentUploads(form) {
    const boxes = form.querySelectorAll('[data-doc-uploads]');
    if (!boxes.length || !window.initFilePondUpload) return;

    for (const box of boxes) {
        const collection = box.dataset.collection;
        const inputs = new Map();

        initFilePondUpload(box.querySelector('[data-doc-pond]'), {
            collection,
            maxFiles: Number(box.dataset.maxFiles) || 10,
            credits: false,
            labelIdle: (t('Kéo thả tệp hồ sơ vào đây hoặc') + ' <span class="filepond--label-action">' + t('Chọn tệp') + '</span>'),
            onUploaded(uuid) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `document_uuids[${collection}][]`;
                input.value = uuid;
                box.appendChild(input);
                inputs.set(uuid, input);
            },
            onReverted(uuid) {
                inputs.get(uuid)?.remove();
                inputs.delete(uuid);
            },
        });
    }

    form.addEventListener('submit', (e) => {
        const busy = [...(window.FilePondInstances?.values() ?? [])].some((pond) =>
            pond.getFiles().some((f) => [
                window.FilePond.FileStatus.PROCESSING,
                window.FilePond.FileStatus.PROCESSING_QUEUED,
                window.FilePond.FileStatus.IDLE,
            ].includes(f.status)),
        );
        if (!busy) return;

        e.preventDefault();
        e.stopImmediatePropagation();
        const msg = t('Tệp đang được tải lên, vui lòng chờ hoàn tất rồi bấm Lưu.');
        window.Toast ? Toast.warning(msg, { duration: 5000 }) : alert(msg);
    }, true);
}

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

function _initCategorySelect(form) {
    const el = form.querySelector('#ts-category_id');
    if (!el || el.tomselect) return;
    createTs(el, {
        placeholder: el.dataset.tsPlaceholder || t('— Chọn danh mục —'),
        maxOptions:  null,
        render:      { option: renderCategoryTreeOption },
    });
}

// ── Tab-aware submit guard ─────────────────────────────────────────────────

function _setupTabGuard(form) {
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
    return labelText || field.placeholder || field.name || t('Trường bắt buộc');
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

    Toast.warning(`${t('Còn thiếu thông tin bắt buộc:\\n:n', { n: (lines.join('\n')) })}`, {
        duration: 5000,
    });
}
