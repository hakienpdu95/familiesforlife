/**
 * pages/menu-item-form.js
 *
 * Responsibilities:
 *   1. Inline validation — delegate to global initFormValidation
 *   2. TomSelect          — auto-init select.ts-init (vị trí, mục cha, danh mục)
 *   3. Toggle field theo link_type — chỉ hiện category_id khi link_type=category,
 *      chỉ hiện page_id khi link_type=page, chỉ hiện url/open_in_new_tab khi link_type=url
 *      (spec/Menu_Navigation_Technical_Specification.md §5.1)
 *   4. Chọn trang tĩnh khi "Nhãn hiển thị" còn trống → tự điền nhãn = tiêu đề trang
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-menu-item-form]';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    initAllTomSelects(form);
    _bindLinkTypeToggle(form);
    _bindPageLabelAutofill(form);
});

function _bindLinkTypeToggle(form) {
    const radios = form.querySelectorAll('[data-link-type-radio]');
    if (!radios.length) return;

    const apply = () => {
        const checked = form.querySelector('[data-link-type-radio]:checked')?.value;
        form.querySelectorAll('[data-link-target]').forEach((el) => {
            const hidden = el.dataset.linkTarget !== checked;
            el.classList.toggle('hidden', hidden);
            // Field của kiểu đang ẩn không được submit — tránh prohibited_unless khi đổi kiểu liên kết
            el.querySelectorAll('input, select').forEach((field) => {
                field.disabled = hidden;
                if (field.tomselect) hidden ? field.tomselect.disable() : field.tomselect.enable();
            });
        });
    };

    radios.forEach((radio) => radio.addEventListener('change', apply));
    apply();
}

function _bindPageLabelAutofill(form) {
    const pageEl  = form.querySelector('#ts-page');
    const labelEl = form.querySelector('[name="label"]');
    if (!pageEl || !labelEl) return;

    pageEl.addEventListener('change', () => {
        if (labelEl.value.trim()) return;
        const title = pageEl.tomselect?.options?.[pageEl.value]?.title
            ?? pageEl.querySelector(`option[value="${CSS.escape(pageEl.value)}"]`)?.dataset.title;
        if (title) labelEl.value = title;
    });
}
