/**
 * resources/js/shared/form-controller.js
 * ─────────────────────────────────────────────────────────────────────
 * Factory tạo Alpine data object cho form create/edit.
 * Mọi module dùng làm base, sau đó spread thêm state/logic riêng.
 *
 * Cách dùng:
 *   import { makeFormController } from '@shared/form-controller.js';
 *
 *   Alpine.data('leadForm', (serverData) => ({
 *       ...makeFormController(serverData, {
 *           rules: {
 *               contact_name: v => !v.trim() ? 'Họ tên là bắt buộc' : null,
 *               contact_email: v => v && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)
 *                                    ? 'Email không đúng định dạng' : null,
 *           },
 *           requiredFields: ['contact_name', 'stage_id'],
 *       }),
 *       // state bổ sung của module
 *       title: serverData.title ?? '',
 *   }));
 * ─────────────────────────────────────────────────────────────────────
 */

/**
 * @param {Object} serverData  - dữ liệu từ Blade (old values, errors, model)
 * @param {Object} opts
 * @param {Object} opts.rules           - { fieldName: (value) => string|null }
 * @param {string[]} opts.requiredFields - danh sách field bắt buộc
 */
export function makeFormController(serverData = {}, opts = {}) {
    const { rules = {}, requiredFields = [] } = opts;

    return {
        // ── Trạng thái validation ──────────────────────────────────────
        _touched:   {},
        _attempted: serverData.hasErrors ?? false,
        submitting: false,

        // ── API public: đánh dấu field đã tương tác ───────────────────
        touch(field) {
            this._touched[field] = true;
        },

        // ── Tính toán lỗi theo rules + required ───────────────────────
        _getErrors() {
            const errs = {};

            // required
            requiredFields.forEach(field => {
                const val = String(this[field] ?? '').trim();
                if (!val) errs[field] = errs[field] || 'Trường này là bắt buộc';
            });

            // custom rules
            Object.entries(rules).forEach(([field, fn]) => {
                if (errs[field]) return;   // already has required error
                const msg = fn.call(this, this[field] ?? '');
                if (msg) errs[field] = msg;
            });

            return errs;
        },

        // ── Helpers dùng trong blade x-bind ───────────────────────────
        showErr(field) {
            const errs = this._getErrors();
            return (this._touched[field] || this._attempted) && !!errs[field];
        },

        errMsg(field) {
            return this._getErrors()[field] ?? '';
        },

        showOk(field) {
            const val = String(this[field] ?? '').trim();
            return this._touched[field] && !this._getErrors()[field] && !!val;
        },

        /** Trả về object class cho :class binding trên input */
        fieldCls(field) {
            return {
                'input-error':   this.showErr(field),
                'input-success': this.showOk(field),
            };
        },

        /** Kiểm tra toàn bộ form hợp lệ */
        get isValid() {
            return Object.keys(this._getErrors()).length === 0;
        },

        // ── Submit handler ─────────────────────────────────────────────
        handleSubmit(event) {
            this._attempted = true;

            // Đánh dấu tất cả field là đã touch để hiện lỗi
            const allFields = [
                ...requiredFields,
                ...Object.keys(rules),
            ];
            allFields.forEach(f => (this._touched[f] = true));

            if (!this.isValid) {
                event.preventDefault();
                this.$nextTick(() => {
                    const errEl = document.querySelector('.input-error, .select-error, .textarea-error');
                    errEl?.closest('.form-control')?.scrollIntoView({
                        behavior: 'smooth',
                        block:    'center',
                    });
                });
                return false;
            }

            this.submitting = true;
            return true;
        },
    };
}

export default makeFormController;
