/**
 * resources/js/modules/flatpickr.js
 * Flatpickr date/time picker wrapper
 *
 * Blade:  @vite(['resources/js/modules/flatpickr.js'])
 * Use:    initDatePicker('#myInput', { ...options })
 *         initDateTimePicker('#myInput')
 *         initDateRangePicker('#myInput')
 */
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

// Vietnamese locale
const VI = {
    firstDayOfWeek: 1,
    weekdays: {
        shorthand: ['CN','T2','T3','T4','T5','T6','T7'],
        longhand:  ['Chủ nhật','Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6','Thứ 7'],
    },
    months: {
        shorthand: ['Th1','Th2','Th3','Th4','Th5','Th6','Th7','Th8','Th9','Th10','Th11','Th12'],
        longhand:  ['Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6','Tháng 7','Tháng 8','Tháng 9','Tháng 10','Tháng 11','Tháng 12'],
    },
};

const BASE = { locale: VI, dateFormat: 'd/m/Y', allowInput: true };

const initDatePicker      = (sel, opts = {}) => flatpickr(sel, { ...BASE, ...opts });
const initDateTimePicker  = (sel, opts = {}) => flatpickr(sel, { ...BASE, enableTime: true, dateFormat: 'd/m/Y H:i', ...opts });
const initDateRangePicker = (sel, opts = {}) => flatpickr(sel, { ...BASE, mode: 'range', ...opts });
const initTimePicker      = (sel, opts = {}) => flatpickr(sel, { ...BASE, noCalendar: true, enableTime: true, dateFormat: 'H:i', ...opts });

/**
 * Auto-init tất cả input.fp-init trong container.
 *
 * Blade: thêm class="... fp-init" + id="fp-[field]" vào <input>
 *        value="{{ old('field', $model->field?->format('Y-m-d') ?? '') }}"
 *
 * - dateFormat:  'Y-m-d'  → submitted value (ISO, tương thích Laravel 'date' validation)
 * - altInput:    true      → Flatpickr tạo input hiển thị riêng
 * - altFormat:   'd/m/Y'  → format hiển thị cho user
 * - data-fp-mode: 'single' | 'range' | 'datetime'  (mặc định 'single')
 *
 * Gọi 1 lần trong page controller:
 *   window.initAllDatePickers?.(form);
 */
function initAllDatePickers(container = document) {
    for (const el of container.querySelectorAll('input.fp-init')) {
        if (el._flatpickr) continue;
        const mode = el.dataset.fpMode ?? 'single';
        const base = {
            locale:        VI,
            dateFormat:    'Y-m-d',
            altInput:      true,
            altFormat:     'd/m/Y',
            allowInput:    false,
            disableMobile: true,
        };
        if (mode === 'datetime') {
            flatpickr(el, { ...base, enableTime: true, altFormat: 'd/m/Y H:i', dateFormat: 'Y-m-d H:i:S' });
        } else if (mode === 'range') {
            flatpickr(el, { ...base, mode: 'range' });
        } else {
            flatpickr(el, base);
        }
    }
}

window.initDatePicker      = initDatePicker;
window.initDateTimePicker  = initDateTimePicker;
window.initDateRangePicker = initDateRangePicker;
window.initTimePicker      = initTimePicker;
window.initAllDatePickers  = initAllDatePickers;
window.flatpickr           = flatpickr;
export { initDatePicker, initDateTimePicker, initDateRangePicker, initTimePicker, initAllDatePickers };
export default flatpickr;
