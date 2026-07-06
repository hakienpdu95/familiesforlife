/**
 * pages/job-title-form.js
 * JS controller cho create.blade.php + edit.blade.php của module JobTitle.
 *
 * JobTitle là flat form (≤10 trường, 1 nhóm) — không cần tab guard hay slug auto-fill.
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

// ── Constants ──────────────────────────────────────────────────────────────
const FORM_SEL = '[data-job-title-form]';

// ── Entry point ────────────────────────────────────────────────────────────
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
