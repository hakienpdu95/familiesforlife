/**
 * pages/leave-request-form.js
 * JS controller cho requests/create.blade.php
 */

import { createTs, initAllTomSelects, destroyTs } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-leave-request-form]';

let tsEmployee = null;

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    window.initAllDatePickers?.(form);
    _initJodit(form);

    const orgLocked = form.dataset.orgLocked === '1';

    // Employee TomSelect — handled manually (not via ts-init) so we can replace options
    tsEmployee = createTs('#ts-employee_id', { placeholder: '— Chọn nhân viên —' });

    if (!orgLocked) {
        // Init org TomSelect manually to attach onChange
        const orgTs = createTs('#ts-organization_id', { placeholder: '— Chọn tổ chức —' });
        if (orgTs) {
            orgTs.on('change', (val) => {
                if (val) _loadEmployees(val);
                else _clearEmployees();
            });
            // Pre-load if old() has an org value (e.g. after validation error)
            const existingOrgId = orgTs.getValue();
            if (existingOrgId) _loadEmployees(existingOrgId);
        }
    } else {
        // Org is locked — employees already rendered from server, tsEmployee is ready
    }

    // Init remaining ts-init selects (leave_type, etc.), skips already-init'd ones
    initAllTomSelects(form);
});

async function _loadEmployees(orgId) {
    destroyTs(tsEmployee);
    tsEmployee = null;

    const empEl = document.getElementById('ts-employee_id');
    if (!empEl) return;

    const oldEmpId = String(empEl.dataset.oldValue || '');
    empEl.innerHTML = '<option value="">— Chọn nhân viên —</option>';

    try {
        const data = await fetch(
            `/dashboard/leave/api/employees?org_id=${encodeURIComponent(orgId)}`
        ).then(r => r.json());

        data.forEach(emp => {
            const opt = document.createElement('option');
            opt.value = emp.id;
            opt.textContent = emp.text;
            if (oldEmpId && String(emp.id) === oldEmpId) opt.selected = true;
            empEl.appendChild(opt);
        });
    } catch (e) {
        console.error('[Leave] Không thể tải danh sách nhân viên:', e);
    }

    tsEmployee = createTs(empEl, { placeholder: '— Chọn nhân viên —' });
}

function _clearEmployees() {
    destroyTs(tsEmployee);
    tsEmployee = null;

    const empEl = document.getElementById('ts-employee_id');
    if (!empEl) return;

    empEl.innerHTML = '<option value="">— Chọn nhân viên —</option>';
    tsEmployee = createTs(empEl, { placeholder: '— Chọn nhân viên —' });
}

function _initJodit(form) {
    if (form.querySelector('.jodit-editor') && typeof initJoditAll === 'function') {
        initJoditAll('.jodit-editor');
    }
}
