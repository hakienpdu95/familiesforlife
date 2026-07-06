/**
 * pages/source-form.js
 *
 * Responsibilities:
 *   1. Inline validation — delegate to global initFormValidation
 *   2. TomSelect init    — organization_id (ts-init selects via initAllTomSelects)
 *   3. Color picker sync — color input ↔ hex text input
 *   4. Icon preview — live Iconify preview khi user gõ icon name
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

// ── Constants ──────────────────────────────────────────────────────────────

const FORM_SEL      = '[data-lead-source-form]';
const ICON_FALLBACK = 'mdi:help-circle';

// ── Entry point ────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    initAllTomSelects(form);
    _syncColorPicker(form);
    _setupIconPreview(form);
});

// ── Color picker sync ──────────────────────────────────────────────────────

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

// ── Icon preview ───────────────────────────────────────────────────────────

function _setupIconPreview(form) {
    const iconInput   = form.querySelector('#iconInput');
    const iconPreview = form.querySelector('#iconPreview');
    if (!iconInput || !iconPreview) return;

    const span = iconPreview.querySelector('.iconify');
    if (!span) return;

    iconInput.addEventListener('input', () => {
        span.setAttribute('data-icon', iconInput.value.trim() || ICON_FALLBACK);
        window.Iconify?.scan(iconPreview);
    }, { passive: true });
}
