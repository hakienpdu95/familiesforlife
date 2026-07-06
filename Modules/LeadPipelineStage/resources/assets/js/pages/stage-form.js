/**
 * pages/stage-form.js
 *
 * Responsibilities:
 *   1. Inline validation — delegate to global initFormValidation
 *   2. TomSelect init    — organization_id (ts-init selects via initAllTomSelects)
 *   3. Color picker sync — color input ↔ hex text input
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

// ── Constants ──────────────────────────────────────────────────────────────

const FORM_SEL = '[data-pipeline-stage-form]';

// ── Entry point ────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    initAllTomSelects(form);
    _syncColorPicker(form);
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
