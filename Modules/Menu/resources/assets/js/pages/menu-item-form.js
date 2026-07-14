/**
 * pages/menu-item-form.js
 *
 * Responsibilities:
 *   1. Inline validation — delegate to global initFormValidation
 *   2. TomSelect          — auto-init select.ts-init (vị trí, mục cha, danh mục)
 *   3. Toggle field theo link_type — chỉ hiện category_id khi link_type=category,
 *      chỉ hiện url/open_in_new_tab khi link_type=url (spec/Menu_Navigation_Technical_Specification.md §5.1)
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-menu-item-form]';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    initAllTomSelects(form);
    _bindLinkTypeToggle(form);
});

function _bindLinkTypeToggle(form) {
    const radios = form.querySelectorAll('[data-link-type-radio]');
    if (!radios.length) return;

    const apply = () => {
        const checked = form.querySelector('[data-link-type-radio]:checked')?.value;
        form.querySelectorAll('[data-link-target]').forEach((el) => {
            el.classList.toggle('hidden', el.dataset.linkTarget !== checked);
        });
    };

    radios.forEach((radio) => radio.addEventListener('change', apply));
    apply();
}
