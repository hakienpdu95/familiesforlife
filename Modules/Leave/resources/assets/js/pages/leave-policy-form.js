/**
 * pages/leave-policy-form.js
 * JS controller cho policies/create.blade.php + policies/edit.blade.php
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-leave-policy-form]';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    window.initAllDatePickers?.(form);
    initAllTomSelects(form);
});
