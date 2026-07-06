/**
 * ai-agent-form.js
 *
 * 1. Validation          — delegate tới global initFormValidation (blur + submit)
 * 2. Tab-aware guard     — phát hiện required field trống ở tab ẩn → chuyển tab + Toast
 * 3. TomSelect auto-init — mọi select.ts-init trong form (provider, task_type)
 * 4. Provider → Model cascade — rebuild model options & TomSelect khi provider thay đổi
 *
 * Requires globals: initFormValidation, window.Alpine, window.Toast, window.TomSelect
 * Requires: tom-select.js load trước module JS
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

// ── Constants ──────────────────────────────────────────────────────────────

const FORM_SEL     = '[data-ai-agent-form]';
const RE_TAB_XSHOW = /tab\s*===\s*['"](\w+)['"]/;

// ── Entry point ────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    _setupTabGuard(form);
    initAllTomSelects(form);    // provider (ts-init), task_type (ts-init)
    _initModelCascade(form);    // model select — managed manually (cascade)
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
    const lines = Array.from(errors.values(), ({ label, fields }) =>
        `${label}: ${fields.join(', ')}`
    );
    Toast.warning(`Còn thiếu thông tin bắt buộc:\n${lines.join('\n')}`, { duration: 5000 });
}

// ── Provider → Model cascade ───────────────────────────────────────────────

/**
 * Quản lý model select: khi provider thay đổi, rebuild options và reinit TomSelect.
 * Provider select dùng ts-init (static options).
 * Model select KHÔNG dùng ts-init — JS quản lý lifecycle hoàn toàn.
 */
function _initModelCascade(form) {
    // Chỉ áp dụng cho non-system agent (system agent dùng hidden inputs, không có select)
    const providerEl = form.querySelector('select[name="provider"]');
    const modelEl    = form.querySelector('#ts-model');
    if (!providerEl || !modelEl) return;

    const modelsByProvider = window._aiAgentModels ?? {};
    const currentModel     = modelEl.dataset.currentModel ?? '';

    function _rebuildModelTs(provider) {
        const models = modelsByProvider[provider] ?? [];

        if (modelEl.tomselect) modelEl.tomselect.destroy();

        modelEl.innerHTML = '<option value="">— Chọn model —</option>' +
            models.map(m =>
                `<option value="${m}"${m === currentModel ? ' selected' : ''}>${m}</option>`
            ).join('');

        if (window.TomSelect) {
            new window.TomSelect(modelEl, {
                placeholder: '— Chọn model —',
                allowEmptyOption: true,
            });
        }
    }

    // TomSelect fires native change event — listen trên native select element
    providerEl.addEventListener('change', e => _rebuildModelTs(e.target.value));

    // Init on load với provider đang được chọn
    _rebuildModelTs(providerEl.value || 'claude');
}
