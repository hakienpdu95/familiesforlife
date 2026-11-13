/**
 * pages/category-form.js
 *
 * Responsibilities:
 *   1. Inline validation — delegate to global initFormValidation
 *   2. TomSelect          — danh mục cha dạng cây (createTsTree)
 */

import { createTsTree, initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-event-category-form]';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    createTsTree(form.querySelector('#ts-parent'));
    initAllTomSelects(form);
});
