/**
 * pages/department-form.js
 * JS controller cho create.blade.php + edit.blade.php của module Department.
 *
 * Xử lý:
 *  - initFormValidation  (global từ app.js)
 *  - Tab-Aware Submit Guard (Section 17 spec)
 *  - TomSelect cho mọi <select> (Section 22 spec)
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

// ── Constants ──────────────────────────────────────────────────────────────
const FORM_SEL = '[data-department-form]';

const RE_TAB_XSHOW = /tab\s*===\s*['"](\w+)['"]/;

// ── Entry point ────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    _setupTabGuard(form);
    window.initAllDatePickers?.(form);   // init mọi input.fp-init trong form
    initAllTomSelects(form);             // init mọi select.ts-init trong form
    _initOrgCascades(form);              // cascade: organization → branch + parent department
});

// ── TomSelect: Organization → Branch + Parent Department cascade ───────────
function _initOrgCascades(form) {
    const orgEl    = form.querySelector('#ts-organization');
    const branchEl = form.querySelector('#ts-branch');
    const parentEl = form.querySelector('#ts-parent');

    // orgLocked = true khi không có #ts-organization (readonly input thay thế)
    if (!orgEl) return;

    const tsBranch = branchEl?.tomselect;
    const tsParent = parentEl?.tomselect;

    // Nếu org đã có giá trị khi load (old() hoặc edit), fetch ngay
    const initialOrgId = orgEl.tomselect?.getValue() ?? '';
    if (initialOrgId) {
        if (tsBranch && branchEl.dataset.branchApi) {
            _loadOptions(branchEl.dataset.branchApi, tsBranch, initialOrgId, '', branchEl.dataset.selectedBranch || '');
        }
        if (tsParent && parentEl.dataset.parentApi) {
            _loadOptions(parentEl.dataset.parentApi, tsParent, initialOrgId, '&for_parent=1', parentEl.dataset.selectedParent || '');
        }
    } else {
        tsBranch?.disable();
        tsParent?.disable();
    }

    orgEl.tomselect?.on('change', (orgId) => {
        if (tsBranch) {
            tsBranch.clear(true);
            tsBranch.clearOptions();
        }
        if (tsParent) {
            tsParent.clear(true);
            tsParent.clearOptions();
        }

        if (!orgId) {
            tsBranch?.disable();
            tsParent?.disable();
            return;
        }

        if (tsBranch && branchEl.dataset.branchApi) {
            _loadOptions(branchEl.dataset.branchApi, tsBranch, orgId, '', '');
        }
        if (tsParent && parentEl.dataset.parentApi) {
            _loadOptions(parentEl.dataset.parentApi, tsParent, orgId, '&for_parent=1', '');
        }
    });
}

function _loadOptions(apiUrl, tsInstance, orgId, extraParams, pendingValue) {
    tsInstance.disable();
    fetch(`${apiUrl}?organization_id=${encodeURIComponent(orgId)}${extraParams}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept':           'application/json',
        },
    })
        .then(r => r.ok ? r.json() : [])
        .then(items => {
            tsInstance.addOptions(items.map(b => ({ value: String(b.id), text: b.text })));
            tsInstance.enable();
            if (pendingValue) tsInstance.setValue(String(pendingValue), true);
        })
        .catch(() => tsInstance.enable());
}

// ── Tab-Aware Submit Guard ─────────────────────────────────────────────────
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
