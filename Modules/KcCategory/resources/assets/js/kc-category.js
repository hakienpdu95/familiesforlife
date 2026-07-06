/**
 * Modules/KcCategory/resources/assets/js/kc-category.js
 * Entry point JS module KcCategory.
 * Build: vite.config.backend.js → public/build/backend/assets/modules/kc-category.[hash].js
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';
import './pages/kc-category-form.js';
import './pages/kc-category-index.js';

const FORM_SEL = '[data-kc-category-form]';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    initAllTomSelects(form);

    if (form.querySelector('.jodit-editor') && typeof initJoditAll === 'function') {
        initJoditAll('.jodit-editor');
    }
});
