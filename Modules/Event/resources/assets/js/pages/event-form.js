/**
 * pages/event-form.js
 *
 * Responsibilities:
 *   1. Inline validation — initFormValidation (data-req, data-val-*)
 *   2. TomSelect          — auto-init select.ts-init (danh mục, tỉnh/phường)
 *   3. Flatpickr          — auto-init input.fp-init (ngày bắt đầu/kết thúc)
 *
 * Globals (lazy bundles): window.TomSelect (tom-select.js), window.initAllDatePickers (flatpickr.js)
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-event-form]';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    initAllTomSelects(form);
    window.initAllDatePickers?.(form);
});
