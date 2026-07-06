/**
 * Modules/KcItem/resources/assets/js/kc-item.js
 * Entry point JS module KcItem (bao gồm KcTag).
 */

import './pages/kc-item-form.js';
import './pages/kc-item-index.js';
import './pages/kc-tag-index.js';
import './pages/kc-item-attachment.js';
import './pages/kc-item-show.js';

// Khởi tạo validation + Jodit + Flatpickr cho form create/edit
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-kc-item-form]');
    if (form) {
        initFormValidation('[data-kc-item-form]');
        if (typeof initJoditAll === 'function') {
            initJoditAll('.jodit-editor');
        }
        window.initAllDatePickers?.(form);
    }
});
