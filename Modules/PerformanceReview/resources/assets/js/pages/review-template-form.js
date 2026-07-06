import { initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-review-template-form]';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initAllTomSelects(form);
});
