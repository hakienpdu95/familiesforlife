/**
 * lead-form.js
 *
 * Responsibilities:
 *   1. Inline validation    — initFormValidation (data-req, data-val-*)
 *   2. Tab-aware guard      — phát hiện required field trống ở tab ẩn → Toast + switch tab
 *   3. Widget init          — TomSelect (stage, source, assignee) + Flatpickr (date)
 *   4. Province/Ward cascade — TomSelect + async ward fetch + hidden name fields
 *   5. Tag checkboxes       — toggle màu khi chọn/bỏ chọn
 *
 * Globals (core bundle):   initFormValidation, window.Alpine, window.Toast
 * Globals (lazy bundles):  window.TomSelect (tom-select.js), initDatePicker (flatpickr.js)
 */

import { createTs, createTsAssignee, initAllTomSelects } from '@shared/tom-select-factory.js';

// ── Constants ──────────────────────────────────────────────────────────────

const FORM_SEL     = '[data-lead-form]';
const RE_TAB_XSHOW = /tab\s*===\s*['"](\w+)['"]/;

// ── Entry point ────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    initAllTomSelects(form);
    _initWidgets(form);
    _initProvinceWard(form);
    _setupTabGuard(form);
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
    return field.closest('.form-control')
        ?.querySelector('.label-text')
        ?.textContent.replace(/\s*\*\s*$/, '').trim()
        ?? field.placeholder
        ?? field.name
        ?? 'Trường bắt buộc';
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

// ── Widgets ────────────────────────────────────────────────────────────────

function _initWidgets(form) {
    // Stage TomSelect (trong sidebar, luôn visible)
    if (form.querySelector('#lead-stage')) {
        createTs('#lead-stage', { placeholder: '— Chọn tình trạng —' });
    }

    // Source TomSelect (trong tab Cơ hội)
    if (form.querySelector('#lead-source')) {
        createTs('#lead-source', { placeholder: '— Chọn nguồn —' });
    }

    // Assignee TomSelect remote (sidebar, conditional)
    const assignedEl = form.querySelector('#lead-assigned');
    if (assignedEl?.dataset.assignableUrl) {
        createTsAssignee(assignedEl, assignedEl.dataset.assignableUrl);
    }

    // Tags TomSelect multi-select
    const tagsEl = form.querySelector('#ts-tag-ids');
    if (tagsEl) {
        createTs(tagsEl, {
            plugins:  ['remove_button'],
            maxItems: null,
            placeholder: '— Chọn tags —',
        });
    }

    // Flatpickr cho ngày chốt
    const dateEl = form.querySelector('#lead-close-date');
    if (dateEl && typeof initDatePicker === 'function') {
        initDatePicker('#lead-close-date');
    }
}

// ── Province / Ward cascade ────────────────────────────────────────────────

function _initProvinceWard(form) {
    const provEl     = form.querySelector('#ts-province');
    const wardEl     = form.querySelector('#ts-ward');
    const provNameEl = form.querySelector('#lead-province-name');
    const wardNameEl = form.querySelector('#lead-ward-name');

    if (!provEl || !wardEl) return;

    // Giá trị cần restore sau khi load wards (old() khi server redirect về)
    const pendingWard     = wardEl.dataset.wardInit     || '';
    const pendingWardName = wardEl.dataset.wardNameInit || '';

    // Ward TomSelect — disabled cho đến khi chọn tỉnh
    const wardTs = createTs(wardEl, {
        placeholder: 'Chọn tỉnh / TP trước',
        onChange(val) {
            wardNameEl.value = val ? (wardTs.options[val]?.text ?? '') : '';
        },
    });
    wardTs.disable();

    // Province TomSelect — onChange fetch wards (không dùng ts-init vì cần onChange cascade)
    const provTs = createTs(provEl, {
        placeholder: 'Tìm tỉnh / thành phố...',
        onChange: async (code) => {
            provNameEl.value = code ? (provTs.options[code]?.text ?? '') : '';
            wardNameEl.value = '';
            wardTs.clear(true);
            wardTs.clearOptions();
            wardTs.disable();

            if (!code) return;

            wardTs.settings.placeholder         = 'Đang tải...';
            wardTs.control_input.placeholder    = 'Đang tải...';

            try {
                const wards = await fetch(`/api/provinces/${code}/wards`).then(r => r.json());
                wards.forEach(w => wardTs.addOption({ value: w.ward_code, text: w.name }));

                wardTs.settings.placeholder         = 'Tìm phường / xã...';
                wardTs.control_input.placeholder    = 'Tìm phường / xã...';
                wardTs.enable();

                // Khôi phục ward đã chọn (sau khi server redirect về do lỗi)
                if (pendingWard && !wardTs.getValue()) {
                    wardTs.setValue(pendingWard, /* silent */ true);
                    if (pendingWardName) wardNameEl.value = pendingWardName;
                }
            } catch {
                wardTs.settings.placeholder         = 'Lỗi tải dữ liệu';
                wardTs.control_input.placeholder    = 'Lỗi tải dữ liệu';
                wardTs.enable();
            }
        },
    });

    // Pre-populate tỉnh nếu old() có giá trị
    const initProv = provEl.dataset.selectedProvince;
    if (initProv) provTs.setValue(initProv, /* silent */ true);
}

