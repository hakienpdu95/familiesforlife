/**
 * resources/js/modules/form-validation.js
 * Form validation helper — standalone, không phụ thuộc thư viện nào.
 *
 * Tự động nhúng vào global qua app.js (core bundle) → dùng được trên mọi trang.
 *
 * ── Đánh dấu field cần validate bằng data attribute ──────────────────
 *   data-req="Thông báo"           required — bắt buộc có giá trị
 *   data-val-email="Thông báo"     email format (hoặc dùng type="email")
 *   data-val-url="Thông báo"       URL format (hoặc dùng type="url")
 *   data-val-maxlength="255"       max length
 *   data-val-minlength="3"         min length
 *   data-val-pattern="regex"       regex pattern
 *   data-val-pattern-msg="Thông báo"  thông báo khi pattern fail
 *
 * ── Sử dụng ──────────────────────────────────────────────────────────
 *   initFormValidation('[data-my-form]');
 *   // hoặc truyền element trực tiếp:
 *   initFormValidation(document.querySelector('form'));
 */

const SELECTORS = [
    '[data-req]',
    '[data-val-email]',
    '[data-val-url]',
    '[data-val-maxlength]',
    '[data-val-minlength]',
    '[data-val-pattern]',
    'input[type="email"]',
    'input[type="url"]',
].join(',');

function _errorClass(el) {
    const tag = el.tagName.toLowerCase();
    if (tag === 'select')   return 'select-error';
    if (tag === 'textarea') return 'textarea-error';
    return 'input-error';
}

function _getFirstError(field) {
    const val   = field.value;
    const empty = !val.trim();

    // Required
    if ('req' in field.dataset) {
        if (empty) return field.dataset.req || 'Trường này là bắt buộc';
    }

    // Format checks — bỏ qua nếu field rỗng (required đã bắt ở trên)
    if (empty) return null;

    // Email
    if (field.type === 'email' || 'valEmail' in field.dataset) {
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
            return field.dataset.valEmail || 'Email không đúng định dạng';
        }
    }

    // URL
    if (field.type === 'url' || 'valUrl' in field.dataset) {
        try { new URL(val); } catch {
            return field.dataset.valUrl || 'URL không hợp lệ (VD: https://example.com)';
        }
    }

    // Max length
    if ('valMaxlength' in field.dataset) {
        const max = Number(field.dataset.valMaxlength);
        if (val.length > max) return `Tối đa ${max} ký tự (hiện tại: ${val.length})`;
    }

    // Min length
    if ('valMinlength' in field.dataset) {
        const min = Number(field.dataset.valMinlength);
        if (val.length < min) return `Tối thiểu ${min} ký tự`;
    }

    // Pattern
    if ('valPattern' in field.dataset) {
        if (!new RegExp(field.dataset.valPattern).test(val)) {
            return field.dataset.valPatternMsg || 'Giá trị không đúng định dạng';
        }
    }

    return null;
}

function _showError(field, msg) {
    _clearError(field);
    field.classList.add(_errorClass(field));
    const control = field.closest('.form-control');
    if (!control) return;
    const p = Object.assign(document.createElement('p'), {
        className: 'form-val-msg mt-1 text-xs text-error',
        textContent: msg,
    });
    control.appendChild(p);
}

function _clearError(field) {
    field.classList.remove('input-error', 'select-error', 'textarea-error');
    field.closest('.form-control')?.querySelector('.form-val-msg')?.remove();
}

function _validateField(field) {
    const err = _getFirstError(field);
    if (err) { _showError(field, err); return false; }
    _clearError(field);
    return true;
}

function _hasError(field) {
    return !!field.closest('.form-control')?.querySelector('.form-val-msg');
}

function initFormValidation(formSelector) {
    const form = typeof formSelector === 'string'
        ? document.querySelector(formSelector)
        : formSelector;
    if (!form) return;

    const fields = () => form.querySelectorAll(SELECTORS);

    fields().forEach(field => {
        // Validate khi rời field (blur)
        field.addEventListener('blur', () => _validateField(field));

        // Re-validate real-time chỉ khi field đang có lỗi → UX gọn hơn
        field.addEventListener('input',  () => { if (_hasError(field)) _validateField(field); });
        field.addEventListener('change', () => { if (_hasError(field)) _validateField(field); });
    });

    form.addEventListener('submit', e => {
        // Sync tất cả Jodit instance (nếu có) trước khi validate
        window.JoditInstances?.forEach(ed => ed.synchronizeValues?.());

        let invalid = 0;
        fields().forEach(f => { if (!_validateField(f)) invalid++; });

        if (invalid) {
            e.preventDefault();
            form.querySelector('.form-val-msg')
                ?.closest('.form-control')
                ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });
}

window.initFormValidation    = initFormValidation;
window.initOrgFormValidation = initFormValidation; // backward compat

export { initFormValidation };
export default initFormValidation;
