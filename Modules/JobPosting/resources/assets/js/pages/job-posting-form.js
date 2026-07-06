import { initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-job-post-form]';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    initAllTomSelects(form);
    _initJodit(form);
});

function _initJodit(form) {
    if (form.querySelector('.jodit-editor') && typeof initJoditAll === 'function') {
        initJoditAll('.jodit-editor');
    }
}
