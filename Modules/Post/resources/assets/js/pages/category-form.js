/**
 * pages/category-form.js
 *
 * Responsibilities:
 *   1. Inline validation — delegate to global initFormValidation
 *   2. TomSelect          — auto-init select.ts-init (danh mục cha)
 *   3. Color picker sync  — color input ↔ hex text input
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-post-category-form]';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    initAllTomSelects(form);
    _syncColorPicker(form);
});

function _syncColorPicker(form) {
    const picker = form.querySelector('#colorPicker');
    const text   = form.querySelector('#colorText');
    if (!picker || !text) return;

    picker.addEventListener('input',  () => { text.value = picker.value; }, { passive: true });
    picker.addEventListener('change', () => { text.value = picker.value; }, { passive: true });

    text.addEventListener('input', () => {
        if (/^#[0-9a-fA-F]{6}$/.test(text.value)) picker.value = text.value;
    }, { passive: true });
}
