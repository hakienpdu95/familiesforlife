/**
 * subscription-index.js
 *
 * Responsibilities:
 *   1. TomSelect — filter bar (plan, status) + modal selects (plan, feature_slug)
 *   2. Flatpickr — date inputs trong modal (start_date, end_date, ends_at, expires_at)
 *   3. Modal open event — cập nhật giá trị TomSelect + flatpickr khi mở modal cho org mới
 *
 * Requires globals (core bundle):   window.Alpine, initFormValidation
 * Requires globals (lazy bundle):   window.TomSelect (tom-select.js), window.initAllDatePickers (flatpickr.js)
 */

import { createTs } from '@shared/tom-select-factory.js';

// ── Constants ──────────────────────────────────────────────────────────────

const PAGE_SEL = '[data-subscription-index]';

// ── Entry point ────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector(PAGE_SEL);
    if (!page) return;

    _initFilterSelects(page);
    _initModalSelects(page);
    _initModalDatePickers(page);
    _initModalForms(page);
    _handleModalOpen(page);
});

// ── Filter bar TomSelect ───────────────────────────────────────────────────

function _initFilterSelects(page) {
    const filterForm = page.querySelector('[data-filter-form]');
    if (!filterForm || !window.TomSelect) return;

    for (const el of filterForm.querySelectorAll('select.ts-init')) {
        if (!el.tomselect) createTs(el, { placeholder: el.dataset.tsPlaceholder || '— Tất cả —' });
    }
}

// ── Modal TomSelect ────────────────────────────────────────────────────────

function _initModalSelects(page) {
    if (!window.TomSelect) return;

    const planEl = page.querySelector('[data-modal-assign] [name="plan_id"]');
    if (planEl && !planEl.tomselect) {
        createTs(planEl, { placeholder: '— Chọn plan —' });
    }

    const featureEl = page.querySelector('[data-modal-override] [name="feature_slug"]');
    if (featureEl && !featureEl.tomselect) {
        createTs(featureEl, { placeholder: '— Chọn tính năng —', maxOptions: 50 });
    }
}

// ── Modal flatpickr ────────────────────────────────────────────────────────

function _initModalDatePickers(page) {
    if (!window.initAllDatePickers) return;
    const modal = page.querySelector('[data-modal]');
    if (modal) window.initAllDatePickers(modal);
}

// ── Form validation ────────────────────────────────────────────────────────

function _initModalForms(page) {
    if (typeof initFormValidation !== 'function') return;
    initFormValidation('[data-assign-form]');
    initFormValidation('[data-extend-form]');
    initFormValidation('[data-override-form]');
}

// ── Modal open → sync field values ────────────────────────────────────────

/**
 * Khi modal mở cho một org, cập nhật:
 *   - Plan TomSelect → pre-select plan hiện tại
 *   - ends_at flatpickr → pre-fill ngày hết hạn hiện tại
 */
function _handleModalOpen(page) {
    window.addEventListener('subscription:modal-open', (e) => {
        const data = e.detail;

        // Plan select (assign tab) — set current plan as default
        const planEl = page.querySelector('[data-modal-assign] [name="plan_id"]');
        if (planEl?.tomselect) {
            planEl.tomselect.setValue(data.currentPlanId ? String(data.currentPlanId) : '', true);
        }

        // Gia hạn date (extend tab) — pre-fill ends_at hiện tại
        const extendsEl = page.querySelector('[data-modal-extend] [name="ends_at"]');
        if (extendsEl?._flatpickr) {
            extendsEl._flatpickr.setDate(data.endsAt || '', true);
        }

        // Reset override form
        const featureEl = page.querySelector('[data-modal-override] [name="feature_slug"]');
        if (featureEl?.tomselect) featureEl.tomselect.setValue('', true);
        const valueEl = page.querySelector('[data-modal-override] [name="value"]');
        if (valueEl) valueEl.value = '';
        const expiresEl = page.querySelector('[data-modal-override] [name="expires_at"]');
        if (expiresEl?._flatpickr) expiresEl._flatpickr.clear();
    });
}
