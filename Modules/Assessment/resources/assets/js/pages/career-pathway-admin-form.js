import { createTs, initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL     = '[data-career-pathway-admin-form]';
const RE_TAB_XSHOW = /tab\s*===\s*['"](\w+)['"]/;

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    _setupTabGuard(form);
    _setupScopeOrgValidation(form);
    initAllTomSelects(form);
    _setupScopeOrgSelect(form);
});

// ── Scope org: validate organization_id trước khi submit ──────────────────────
// Tab Guard chỉ bắt field trong tab ẩn. org_id nằm trong x-show="scope==='org'"
// nên khi scope=org field đó visible → bị Guard bỏ qua → cần guard riêng.

function _setupScopeOrgValidation(form) {
    const orgEl = form.querySelector('#ts-organization_id');
    if (!orgEl) return;

    form.addEventListener('submit', (e) => {
        const scopeEl = form.querySelector('[name="scope"]:checked')
            ?? form.querySelector('[name="scope"][type="hidden"]');
        if (scopeEl?.value !== 'org') return;
        if (orgEl.value.trim()) return;

        e.preventDefault();

        const wrapper = form.closest('[x-data]') ?? document.querySelector('[x-data]');
        _switchAlpineTab(wrapper, 'basic');

        if (window.Toast) {
            Toast.warning('Vui lòng chọn tổ chức khi phạm vi là "Riêng tổ chức cụ thể".', { duration: 4000 });
        }
    }, true);
}

// ── Scope radio → TomSelect org (conditionally shown) ─────────────────────────
// #ts-organization_id nằm trong x-show="scope === 'org'" nên KHÔNG dùng ts-init.
// Init thủ công sau khi Alpine reveal để TomSelect đo được width đúng.

function _setupScopeOrgSelect(form) {
    const orgEl = form.querySelector('#ts-organization_id');
    if (!orgEl) return; // không phải super-admin create → bỏ qua

    const scopeWrapper = orgEl.closest('[x-show]');

    // Nếu old('scope') = 'org' (validation lỗi redirect back), element đã visible
    if (scopeWrapper && scopeWrapper.style.display !== 'none') {
        requestAnimationFrame(() => { if (!orgEl.tomselect) createTs(orgEl); });
        return;
    }

    // Lắng nghe scope radio change
    form.querySelectorAll('[name="scope"]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.value === 'org' && !orgEl.tomselect) {
                requestAnimationFrame(() => createTs(orgEl));
            }
        });
    });
}

// ── Tab-Aware Submit Guard ─────────────────────────────────────────────────────

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
