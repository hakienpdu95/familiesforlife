/**
 * resources/js/shared/wizard-controller.js
 * ─────────────────────────────────────────────────────────────────────
 * Mixin cho Alpine component của multi-step wizard form.
 * Import và spread vào Alpine.data object cùng với makeFormController.
 *
 * Cách dùng:
 *   import { makeWizardController } from '@shared/wizard-controller.js';
 *   import { makeFormController }   from '@shared/form-controller.js';
 *
 *   Alpine.data('leadWizard', (serverData) => ({
 *       ...makeFormController(serverData, { rules, requiredFields }),
 *       ...makeWizardController({
 *           steps:         ['Khách hàng', 'Cơ hội', 'Tags & Ghi chú'],
 *           validators:    [validateStep1, validateStep2, null],
 *           onStepChange:  (step) => { if (step === 3) updateSummary(); },
 *           initialStep:   serverData.errorStep ?? 1,
 *       }),
 *       // state bổ sung...
 *   }));
 * ─────────────────────────────────────────────────────────────────────
 */

/**
 * @param {Object}   opts
 * @param {string[]} opts.steps        - tên các bước
 * @param {Array}    opts.validators   - hàm validate tương ứng mỗi bước, null = skip
 * @param {Function} opts.onStepChange - callback(newStep: number)
 * @param {number}   opts.initialStep  - bước khởi đầu (1-based, default: 1)
 */
export function makeWizardController(opts = {}) {
    const {
        steps        = [],
        validators   = [],
        onStepChange = null,
        initialStep  = 1,
    } = opts;

    return {
        // ── State ──────────────────────────────────────────────────────
        steps,
        currentStep:  initialStep,
        totalSteps:   steps.length,

        // ── Navigation ─────────────────────────────────────────────────
        async nextStep() {
            const validate = validators[this.currentStep - 1];
            if (validate && !(await validate.call(this))) return;
            if (this.currentStep < this.totalSteps) {
                this.currentStep++;
                this._onStepChange(this.currentStep, onStepChange);
            }
        },

        prevStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
                this._onStepChange(this.currentStep, onStepChange);
            }
        },

        goToStep(n) {
            if (n >= 1 && n <= this.totalSteps) {
                this.currentStep = n;
                this._onStepChange(this.currentStep, onStepChange);
            }
        },

        // ── Computed helpers dùng trong blade :class ───────────────────
        stepDotClass(idx) {   // idx = 0-based
            const step = idx + 1;
            if (this.currentStep === step) return 'wizard-step-dot--active';
            if (this.currentStep > step)  return 'wizard-step-dot--done';
            return 'wizard-step-dot--pending';
        },

        stepLineClass(idx) {  // idx = 0-based (line after dot idx)
            return this.currentStep > idx + 1 ? 'wizard-step-line--done' : '';
        },

        isLastStep()  { return this.currentStep === this.totalSteps; },
        isFirstStep() { return this.currentStep === 1; },

        // ── Internal ───────────────────────────────────────────────────
        _onStepChange(step, cb) {
            if (cb) cb.call(this, step);
        },
    };
}

export default makeWizardController;
