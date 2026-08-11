/**
 * pages/heritage-site-form.js
 *
 * Responsibilities:
 *   1. Inline validation — delegate to global initFormValidation (blur + format + submit)
 *   2. TomSelect — auto-init mọi select.ts-init (heritage_type, rank, visiting_status, status...)
 *
 * Requires globals (core bundle): initFormValidation
 * Requires globals (lazy bundle):  window.TomSelect (tom-select.js)
 *
 * Không có tab-aware submit guard như ocop-product-form.js — form Heritage chỉ 1 card duy nhất,
 * không có field required nào ẩn sau tab.
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-heritage-site-form]';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    initAllTomSelects(form);
});
