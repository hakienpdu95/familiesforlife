import { createTs, initAllTomSelects } from '@shared/tom-select-factory.js';

document.addEventListener('DOMContentLoaded', () => {
    const defForm = document.querySelector('[data-cert-def-form]');
    if (defForm) {
        initAllTomSelects(defForm);
        _setupTabGuard(defForm);
        _setupScopeOrgSelect(defForm);
        _setupScopeOrgValidation(defForm);
    }

    const issueForm = document.querySelector('[data-cert-issue-form]');
    if (issueForm) {
        initAllTomSelects(issueForm);
    }
});

// Guard: nếu field bắt buộc nằm trong panel ẩn (x-show) mà bị bỏ trống,
// chuyển tab đến panel đó và toast cảnh báo thay vì submit im lặng.
function _setupTabGuard(form) {
    form.addEventListener('submit', (e) => {
        const panels  = Array.from(form.querySelectorAll('[data-tab-label]'));
        const tabBtns = Array.from(form.querySelectorAll('[role="tab"]'));

        for (let i = 0; i < panels.length; i++) {
            const panel = panels[i];
            if (window.getComputedStyle(panel).display !== 'none') continue;

            const invalid = Array.from(panel.querySelectorAll('[name]'))
                .filter(el => !el.validity.valid);

            if (invalid.length) {
                e.preventDefault();
                tabBtns[i]?.click();

                const label = panel.getAttribute('data-tab-label');
                if (window.Toast) {
                    Toast.warning(`Tab "${label}" có trường bắt buộc chưa điền.`, { duration: 4000 });
                }
                return;
            }
        }
    }, true);
}

// Khởi động TomSelect cho #ts-organization_id khi nằm trong x-show ẩn.
// ts-init bị bỏ qua trên element này vì display:none lúc khởi tạo gây lỗi width=0.
function _setupScopeOrgSelect(form) {
    const orgEl = form.querySelector('#ts-organization_id');
    if (!orgEl) return;

    const scopeWrapper = orgEl.closest('[x-show]');
    if (scopeWrapper && window.getComputedStyle(scopeWrapper).display !== 'none') {
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
