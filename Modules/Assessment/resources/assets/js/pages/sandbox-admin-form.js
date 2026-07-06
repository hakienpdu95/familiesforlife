import { createTs, initAllTomSelects } from '@shared/tom-select-factory.js';

document.addEventListener('DOMContentLoaded', () => {
    const envForm = document.querySelector('[data-sandbox-env-form]');
    if (envForm) {
        initAllTomSelects(envForm);
        _setupScopeOrgSelect(envForm);
        _setupScopeOrgValidation(envForm);
    }
});

// Khởi động TomSelect cho #ts-organization_id khi nó ở trong x-show ẩn.
// ts-init bị bỏ qua trên element này vì display:none lúc khởi tạo gây lỗi width=0.
function _setupScopeOrgSelect(form) {
    const orgEl = form.querySelector('#ts-organization_id');
    if (!orgEl) return;

    const scopeWrapper = orgEl.closest('[x-show]');
    if (scopeWrapper && window.getComputedStyle(scopeWrapper).display !== 'none') {
        // Đã visible (scope='org' từ old()) — init sau khi Alpine render xong
        requestAnimationFrame(() => { if (!orgEl.tomselect) createTs(orgEl); });
        return;
    }

    form.querySelectorAll('[name="scope"]').forEach(radio => {
        radio.addEventListener('change', () => {
            if (radio.value === 'org' && !orgEl.tomselect) {
                requestAnimationFrame(() => createTs(orgEl));
            }
        });
    });
}

// Guard: bắt submit khi scope='org' nhưng organization_id trống.
// Cần guard riêng vì field nằm trong x-show visible → Tab Guard bỏ qua.
function _setupScopeOrgValidation(form) {
    const orgEl = form.querySelector('#ts-organization_id');
    if (!orgEl) return;

    form.addEventListener('submit', (e) => {
        const scopeEl = form.querySelector('[name="scope"]:checked')
            ?? form.querySelector('[name="scope"][type="hidden"]');
        if (scopeEl?.value !== 'org') return;
        if (orgEl.value.trim()) return;

        e.preventDefault();
        if (window.Toast) {
            Toast.warning('Vui lòng chọn tổ chức khi phạm vi là "Riêng tổ chức cụ thể".', { duration: 4000 });
        }
    }, true);
}
