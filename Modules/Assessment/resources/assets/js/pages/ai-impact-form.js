import { initAllTomSelects } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-ai-impact-form]';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initAllTomSelects(form);
    window.initAllDatePickers?.(form);
    _setupOrgValidation(form);
});

// Validate #ts-organization_id trước submit (super-admin create).
// Field này luôn visible (không ẩn trong x-show) nên Tab Guard bỏ qua —
// cần guard riêng.
function _setupOrgValidation(form) {
    const orgEl = form.querySelector('#ts-organization_id');
    if (!orgEl) return;

    form.addEventListener('submit', (e) => {
        if (orgEl.value.trim()) return;
        e.preventDefault();
        if (window.Toast) {
            Toast.warning('Vui lòng chọn tổ chức.', { duration: 4000 });
        }
    }, true);
}

export function aiImpactForm() {
    return {
        baselineVal:  0,
        achievedVal:  0,
        investCost:   0,
        benefitVal:   0,

        get previewVisible() {
            return this.baselineVal > 0 && this.achievedVal > 0
        },
        get roiVisible() {
            return this.investCost > 0 && this.benefitVal > 0
        },
        get improvementLabel() {
            if (!this.baselineVal) return '—'
            const pct = ((this.achievedVal - this.baselineVal) / Math.abs(this.baselineVal)) * 100
            return (pct >= 0 ? '+' : '') + pct.toFixed(1) + '%'
        },
        get roiLabel() {
            if (!this.investCost) return '—'
            const roi = ((this.benefitVal - this.investCost) / this.investCost) * 100
            return roi.toFixed(1) + '% (' + (roi / 100).toFixed(2) + 'x)'
        },
    }
}
