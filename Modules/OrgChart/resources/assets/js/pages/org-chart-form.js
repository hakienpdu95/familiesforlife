/**
 * Modules/OrgChart/resources/assets/js/pages/org-chart-form.js
 *
 * Responsibilities:
 *   1. Inline validation — delegate to global initFormValidation
 *   2. TomSelect — auto-init mọi select.ts-init trong form + sidebar
 *
 * Requires globals (core): initFormValidation, window.Toast
 * Requires globals (lazy): window.TomSelect (tom-select.js)
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-org-chart-form]';

// ── Entry point ────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    initAllTomSelects(form);
    _initOrgCascades(form);
});

// ── Org → branch cascade ───────────────────────────────────────────────────

function _initOrgCascades(form) {
    const orgEl = form.querySelector('#ts-organization');
    if (!orgEl) return; // orgLocked — static options, no cascade needed

    const deps = [...form.querySelectorAll('[data-org-api]')].map(el => ({
        el,
        ts:      el.tomselect,
        api:     el.dataset.orgApi,
        pending: el.dataset.selectedValue || '',
    })).filter(d => d.ts && d.api);

    if (!deps.length) return;

    const initialOrgId = orgEl.tomselect?.getValue() ?? '';
    if (initialOrgId) {
        deps.forEach(d => _loadOrgOptions(d.api, d.ts, initialOrgId, d.pending));
    } else {
        deps.forEach(d => d.ts.disable());
    }

    orgEl.tomselect?.on('change', (orgId) => {
        deps.forEach(d => { d.ts.clear(true); d.ts.clearOptions(); });
        if (!orgId) { deps.forEach(d => d.ts.disable()); return; }
        deps.forEach(d => _loadOrgOptions(d.api, d.ts, orgId, ''));
    });
}

function _loadOrgOptions(apiUrl, ts, orgId, pending) {
    ts.disable();
    fetch(`${apiUrl}?organization_id=${encodeURIComponent(orgId)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    })
        .then(r => r.ok ? r.json() : [])
        .then(items => {
            ts.addOptions(items.map(b => ({ value: String(b.id), text: b.text })));
            ts.enable();
            if (pending) ts.setValue(String(pending), true);
        })
        .catch(() => ts.enable());
}
