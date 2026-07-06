/**
 * interview-form.js
 *
 * Flat form: schedule interview
 * Trách nhiệm:
 *   1. Inline validation — delegate to global initFormValidation
 *   2. TomSelect — auto-init mọi select.ts-init
 *   3. Flatpickr — auto-init datetime picker (scheduled_at)
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-interview-form]';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    initAllTomSelects(form);
    window.initAllDatePickers?.(form);
});
