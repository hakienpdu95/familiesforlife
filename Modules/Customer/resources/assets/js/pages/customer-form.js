/**
 * customer-form.js
 *
 * Responsibilities:
 *   1. Inline validation    — initFormValidation (data-req, data-val-*)
 *   2. Tab-aware guard      — detect required field empty on hidden tab → Toast + switch tab
 *   3. Widget init          — TomSelect (stage, source, assignee, tags) + Flatpickr (dates)
 *   4. Province/Ward cascade — TomSelect + async ward fetch + hidden name fields
 *
 * Globals (core bundle):   initFormValidation, window.Alpine, window.Toast
 * Globals (lazy bundles):  window.TomSelect (tom-select.js), initAllDatePickers (flatpickr.js)
 */

import { createTs, initAllTomSelects } from '@shared/tom-select-factory.js';

// ── Constants ──────────────────────────────────────────────────────────────

const FORM_SEL     = '[data-customer-form]';
const RE_TAB_XSHOW = /tab\s*===\s*['"](\w+)['"]/;

// Ward TomSelect instance — module-level vì cascade cần destroy/recreate
let _tsWard = null;

// ── Entry point ────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    window.initAllDatePickers?.(form);
    _setupTabGuard(form);
    initAllTomSelects(form);
    _initWidgets(form);
    _initOrgCascades(form);
    _initProvinceWard(form);
});

// ── Tab-aware submit guard ─────────────────────────────────────────────────

/**
 * Returns true if el is inside an x-show element that is currently display:none
 * AND that element is NOT a tab panel (tab panels have data-tab-label).
 * Used to suspend data-req validation for type-toggle sections (individual/company).
 */
function _isHiddenByXShow(el) {
    let node = el.parentElement;
    while (node && node !== document.body) {
        if (node.hasAttribute('x-show') && !node.hasAttribute('data-tab-label')
            && node.style.display === 'none') return true;
        node = node.parentElement;
    }
    return false;
}

function _resetSubmitting(wrapper) {
    if (!wrapper) return;
    try {
        const data = window.Alpine?.$data(wrapper);
        if (data?.submitting !== undefined) data.submitting = false;
    } catch { /* Alpine not ready */ }
}

function _setupTabGuard(form) {
    let wrapper = null;

    // 1. Suspend data-req on fields hidden by x-show type-toggle (not tab panels)
    //    so initFormValidation skips them (they're irrelevant in current type mode).
    form.addEventListener('submit', () => {
        form.querySelectorAll('[data-req]').forEach(f => {
            if (_isHiddenByXShow(f)) {
                f.dataset.reqSuspended = f.dataset.req;
                delete f.dataset.req;
            }
        });
    }, /* capture */ true);

    // 2. Toast + tab switch for required fields on hidden tab panels.
    form.addEventListener('submit', (e) => {
        const errors = _collectHiddenErrors(form);
        if (!errors.size) return;

        e.preventDefault();
        wrapper ??= form.closest('[x-data]') ?? document.querySelector('[x-data]');
        _switchAlpineTab(wrapper, errors.keys().next().value);
        _toastHiddenErrors(errors);
    }, /* capture */ true);

    // 3. After all sync handlers: restore suspended fields + reset Alpine if prevented.
    form.addEventListener('submit', (e) => {
        setTimeout(() => {
            form.querySelectorAll('[data-req-suspended]').forEach(f => {
                f.dataset.req = f.dataset.reqSuspended;
                delete f.dataset.reqSuspended;
            });
            if (!e.defaultPrevented) return;
            wrapper ??= form.closest('[x-data]') ?? document.querySelector('[x-data]');
            _resetSubmitting(wrapper);
        }, 0);
    });
}

function _collectHiddenErrors(form) {
    const map = new Map();

    for (const field of form.querySelectorAll('[data-req]')) {
        if (field.value.trim()) continue;

        const panel = field.closest('[x-show][data-tab-label]');
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
    // Tags TomSelect multi — không có ts-init vì cần plugin remove_button
    const tagsEl = form.querySelector('#ts-tag-ids');
    if (tagsEl) {
        createTs(tagsEl, {
            plugins:  ['remove_button'],
            maxItems: null,
            placeholder: '— Chọn tags —',
        });
    }
    // #ts-gender, #ts-company-size, #ts-lifecycle-stage, #ts-source-id, #customer-assigned
    // đã có class ts-init → initAllTomSelects() xử lý, không cần gọi lại ở đây
}

// ── Org → dependent selects cascade (giống hệt Employee/Task/Project/OrgChart) ─────
//
// #customer-assigned có data-org-api (chỉ khi !orgLocked, tức super-admin) → ban đầu
// KHÔNG load gì cả (disable), chỉ load khi user chọn tổ chức ở #ts-organization. Nếu
// org đã có sẵn giá trị lúc vào trang (edit form) thì load ngay theo org đó.

function _initOrgCascades(form) {
    const orgEl = form.querySelector('#ts-organization');
    if (!orgEl) return; // orgLocked — org cố định, PHP đã render sẵn options

    const deps = [...form.querySelectorAll('[data-org-api]')].map(el => ({
        el,
        ts:      el.tomselect,
        api:     el.dataset.orgApi,
        extra:   el.dataset.orgApiExtra || '',
        pending: el.dataset.selectedValue || '',
    })).filter(d => d.ts && d.api);

    if (!deps.length) return;

    const initialOrgId = orgEl.tomselect?.getValue() ?? '';
    if (initialOrgId) {
        deps.forEach(d => _loadOrgOptions(d.api, d.ts, initialOrgId, d.extra, d.pending));
    } else {
        deps.forEach(d => d.ts.disable());
    }

    orgEl.tomselect?.on('change', (orgId) => {
        deps.forEach(d => { d.ts.clear(true); d.ts.clearOptions(); });
        if (!orgId) {
            deps.forEach(d => d.ts.disable());
            return;
        }
        deps.forEach(d => _loadOrgOptions(d.api, d.ts, orgId, d.extra, ''));
    });
}

function _loadOrgOptions(apiUrl, ts, orgId, extra, pending) {
    ts.disable();
    fetch(`${apiUrl}?organization_id=${encodeURIComponent(orgId)}${extra}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept':           'application/json',
        },
    })
        .then(r => r.ok ? r.json() : [])
        .then(items => {
            ts.addOptions(items.map(b => ({ value: String(b.id), text: b.text })));
            ts.enable();
            if (pending) ts.setValue(String(pending), true);
        })
        .catch(() => ts.enable());
}

// ── Province / Ward cascade ────────────────────────────────────────────────

function _initProvinceWard(form) {
    const provinceEl = form.querySelector('[name="province_code"]');
    const wardEl     = form.querySelector('[name="ward_code"]');
    const provNameEl = form.querySelector('#customer-province-name');
    const wardNameEl = form.querySelector('#customer-ward-name');

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
            if (provNameEl) provNameEl.value = val ? (tsProvince.options[val]?.text ?? '') : '';
            if (wardNameEl) wardNameEl.value = '';
            _rebuildWardTs(wardEl, wardNameEl, { enabled: false, placeholder: 'Chọn tỉnh trước...' });
            if (!val) return;
            _loadWards(val, wardEl, wardNameEl, null, wardsCache);
        });
    }

    // Nếu province đã có giá trị (old() hoặc model binding), load wards ngay
    if (provinceEl.value) {
        _loadWards(provinceEl.value, wardEl, wardNameEl, initialWard, wardsCache);
    }
}

function _rebuildWardTs(wardEl, wardNameEl, { enabled, placeholder, options = [] }) {
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

    _tsWard = createTs(wardEl, {
        placeholder,
        onChange(val) {
            if (wardNameEl) wardNameEl.value = val ? (_tsWard?.options[val]?.text ?? '') : '';
        },
    });
    if (!enabled) _tsWard?.disable();
}

function _loadWards(code, wardEl, wardNameEl, pendingWard, cache) {
    if (cache[code]) {
        _applyWards(cache[code], wardEl, wardNameEl, pendingWard);
        return;
    }

    _rebuildWardTs(wardEl, wardNameEl, { enabled: false, placeholder: 'Đang tải...' });

    const apiBase = document.querySelector('meta[name="wards-api"]')?.content
        || window.location.origin + '/api/provinces';

    fetch(`${apiBase}/${encodeURIComponent(code)}/wards`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
        .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
        .then(data => { cache[code] = data; _applyWards(data, wardEl, wardNameEl, pendingWard); })
        .catch(() => _rebuildWardTs(wardEl, wardNameEl, { enabled: false, placeholder: 'Lỗi tải dữ liệu' }));
}

function _applyWards(wards, wardEl, wardNameEl, pendingWard) {
    const options = wards.map(w => ({
        value: w.ward_code,
        text:  w.name,
        selected: !!pendingWard && w.ward_code === pendingWard,
    }));
    _rebuildWardTs(wardEl, wardNameEl, { enabled: true, placeholder: 'Chọn phường/xã...', options });
    if (pendingWard && wardNameEl) {
        const match = wards.find(w => w.ward_code === pendingWard);
        if (match) wardNameEl.value = match.name;
    }
}
