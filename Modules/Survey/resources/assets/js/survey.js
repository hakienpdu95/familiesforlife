/**
 * survey.js
 *
 * Responsibilities:
 *   1. surveyTake Alpine component — wizard-style section navigation + required validation
 *   2. TomSelect + Flatpickr initialization for the take form
 *   3. initFormValidation + TomSelect for admin/builder forms
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

// ── Constants ──────────────────────────────────────────────────────────────

const FORM_SEL      = '[data-survey-form]';
const TAKE_FORM_SEL = '[data-survey-take-form]';

// ── Alpine: surveyTake ─────────────────────────────────────────────────────

document.addEventListener('alpine:init', () => {
    Alpine.data('surveyTake', (totalSections) => ({
        currentSection: 0,
        totalSections,
        submitting: false,

        progress() {
            if (this.totalSections <= 1) return 100;
            return Math.round(((this.currentSection + 1) / this.totalSections) * 100);
        },

        isFirst() { return this.currentSection === 0; },
        isLast()  { return this.currentSection === this.totalSections - 1; },

        next() {
            const sectionEl = document.querySelector(`[data-section="${this.currentSection}"]`);
            const missing = sectionEl ? _collectMissingRequired(sectionEl) : [];
            if (missing.length > 0) {
                window.Toast?.warning(
                    `Vui lòng trả lời ${missing.length} câu hỏi bắt buộc (đánh dấu *).`,
                    { duration: 4000 }
                );
                missing[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }
            this.currentSection++;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        prev() {
            this.currentSection--;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        handleSubmit(e) {
            this.submitting = true;
            e.target.submit();
        },
    }));
});

// ── Validation helpers ─────────────────────────────────────────────────────

function _collectMissingRequired(sectionEl) {
    const missing = [];
    for (const q of sectionEl.querySelectorAll('.survey-question[data-required="1"]')) {
        if (_isQuestionEmpty(q)) missing.push(q);
    }
    return missing;
}

function _isQuestionEmpty(q) {
    const radios = q.querySelectorAll('input[type="radio"]');
    if (radios.length > 0) return !Array.from(radios).some(r => r.checked);

    const checkboxes = q.querySelectorAll('input[type="checkbox"]');
    if (checkboxes.length > 0) return !Array.from(checkboxes).some(c => c.checked);

    // Hidden inputs for Alpine-driven fields (rating, NPS, boolean)
    const hidden = q.querySelector('input[type="hidden"][data-required-value]');
    if (hidden) return !hidden.value || hidden.value === '' || hidden.value === 'null';

    const input = q.querySelector('input:not([type="hidden"]), textarea, select');
    return !input || !input.value?.trim();
}

// ── Entry point ────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const adminForm = document.querySelector(FORM_SEL);
    if (adminForm) {
        initAllTomSelects(adminForm);
        initFormValidation(FORM_SEL);
    }

    const takeForm = document.querySelector(TAKE_FORM_SEL);
    if (takeForm) {
        initAllTomSelects(takeForm);
        window.initAllDatePickers?.(takeForm);
    }
});
