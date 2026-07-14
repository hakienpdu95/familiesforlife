/**
 * pages/category-form.js
 *
 * Responsibilities:
 *   1. Inline validation — delegate to global initFormValidation
 *   2. TomSelect          — auto-init select.ts-init (danh mục cha)
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-event-category-form]';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    initAllTomSelects(form);
});
