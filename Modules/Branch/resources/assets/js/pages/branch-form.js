/**
 * pages/branch-form.js
 * JS controller cho create.blade.php + edit.blade.php của module Branch.
 *
 * Xử lý:
 *  - initFormValidation  (global từ app.js)
 *  - Tab-Aware Submit Guard (Section 17 spec)
 *  - TomSelect cho mọi <select> (Section 22 spec)
 *  - Province → Ward cascade tích hợp TomSelect
 */

import { createTs, initAllTomSelects } from '@shared/tom-select-factory.js';

// ── Constants ──────────────────────────────────────────────────────────────
const FORM_SEL = '[data-branch-form]';

const RE_TAB_XSHOW = /tab\s*===\s*['"](\w+)['"]/;

// Ward TomSelect instance — module-level vì cascade cần destroy/recreate
let _tsWard = null;

// ── Entry point ────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    window.initAllDatePickers?.(form);   // init mọi input.fp-init (opened_at, closed_at)
    _setupTabGuard(form);
    initAllTomSelects(form);             // init mọi select.ts-init
    _initProvinceWard(form);             // cascade: province (onChange) + ward (dynamic)
    _initOrgParentCascade(form);         // cascade: organization (onChange) → parent branch
});

// ── TomSelect: Province → Ward cascade ────────────────────────────────────
function _initProvinceWard(form) {
    const provinceEl = form.querySelector('[name="province_code"]');
    const wardEl     = form.querySelector('[name="ward_code"]');
    if (!provinceEl || !wardEl) return;

    const wardsCache  = {};
    const initialWard = wardEl.dataset.selectedWard || '';

    // Ward: KHÔNG ts-init — JS tự quản lý lifecycle (destroy/recreate)
    _tsWard = createTs(wardEl, {
        placeholder: provinceEl.value ? 'Chọn phường/xã...' : 'Chọn tỉnh trước...',
    });
    if (!provinceEl.value) _tsWard?.disable();

    // Province đã được initAllTomSelects() init qua ts-init → lấy instance, gắn onChange
    const tsProvince = provinceEl.tomselect;
    if (tsProvince) {
        tsProvince.on('change', (val) => {
            _rebuildWardTs(wardEl, { enabled: false, placeholder: 'Chọn tỉnh trước...' });
            if (!val) return;
            _loadWards(val, wardEl, null, wardsCache);
        });
    }

    // Nếu province đã được chọn (old() hoặc model binding), load wards luôn
    if (provinceEl.value) {
        _loadWards(provinceEl.value, wardEl, initialWard, wardsCache);
    }
}

/** Destroy + recreate ward TomSelect với placeholder và trạng thái mới. */
function _rebuildWardTs(wardEl, { enabled, placeholder, options = [] }) {
    if (_tsWard) { _tsWard.destroy(); _tsWard = null; }

    wardEl.innerHTML = `<option value="">${placeholder}</option>`;
    for (const opt of options) {
        const el = document.createElement('option');
        el.value       = opt.value;
        el.textContent = opt.text;
        if (opt.selected) el.selected = true;
        wardEl.appendChild(el);
    }
    wardEl.disabled = !enabled;

    _tsWard = createTs(wardEl, { placeholder });
    if (!enabled) _tsWard?.disable();
}

function _loadWards(code, wardEl, pendingWard, cache) {
    if (cache[code]) {
        _applyWards(cache[code], wardEl, pendingWard);
        return;
    }

    _rebuildWardTs(wardEl, { enabled: false, placeholder: 'Đang tải...' });

    const apiBase = document.querySelector('meta[name="wards-api"]')?.content
        || window.location.origin + '/api/provinces';

    fetch(`${apiBase}/${encodeURIComponent(code)}/wards`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(data => { cache[code] = data; _applyWards(data, wardEl, pendingWard); })
        .catch(() => _rebuildWardTs(wardEl, { enabled: false, placeholder: 'Lỗi tải dữ liệu' }));
}

function _applyWards(wards, wardEl, pendingWard) {
    const options = wards.map(w => ({
        value: w.ward_code,
        text:  w.name,
        selected: pendingWard && w.ward_code === pendingWard,
    }));
    _rebuildWardTs(wardEl, { enabled: true, placeholder: 'Chọn phường/xã...', options });
}

// ── TomSelect: Organization → Parent Branch cascade ───────────────────────
function _initOrgParentCascade(form) {
    const orgEl    = form.querySelector('#ts-organization');
    const parentEl = form.querySelector('#ts-parent');
    if (!orgEl || !parentEl) return;

    const apiUrl = parentEl.dataset.parentApi;
    if (!apiUrl) return;

    const tsOrg    = orgEl.tomselect;
    const tsParent = parentEl.tomselect;
    if (!tsOrg || !tsParent) return;

    // Nếu org đã được chọn sẵn khi load (old() hoặc edit), fetch ngay
    const initialOrgId = tsOrg.getValue();
    if (initialOrgId) {
        _loadParentOptions(apiUrl, tsParent, initialOrgId, parentEl.dataset.selectedParent || '');
    } else {
        tsParent.disable();
    }

    tsOrg.on('change', (orgId) => {
        tsParent.clear(true);
        tsParent.clearOptions();

        if (!orgId) {
            tsParent.disable();
            return;
        }

        _loadParentOptions(apiUrl, tsParent, orgId, '');
    });
}

function _loadParentOptions(apiUrl, tsParent, orgId, pendingValue) {
    tsParent.disable();
    fetch(`${apiUrl}?organization_id=${encodeURIComponent(orgId)}&for_parent=1`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept':           'application/json',
        },
    })
        .then(r => r.ok ? r.json() : [])
        .then(items => {
            tsParent.addOptions(items.map(b => ({ value: String(b.id), text: b.text })));
            tsParent.enable();
            if (pendingValue) tsParent.setValue(String(pendingValue), true);
        })
        .catch(() => tsParent.enable());
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
