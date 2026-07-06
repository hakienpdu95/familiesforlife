/**
 * resources/js/modules/tabulator.js
 * Tabulator v6 wrapper
 *
 * Blade:  @vite(['resources/js/modules/tabulator.js'])
 * Use:    initTabulator('#myTable', columns, data, options)
 */
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator.min.css';

const DEFAULTS = {
    layout:          'fitColumns',
    responsiveLayout:'collapse',
    pagination:      true,
    paginationSize:  25,
    paginationSizeSelector: [10, 25, 50, 100],
    movableColumns:  true,
    resizableRows:   false,
    tooltips:        true,
    locale:          'vi-VN',
    langs: {
        'vi-VN': {
            pagination: {
                page_size: 'Dòng/trang',
                page_title: 'Trang',
                first: '«', last: '»', prev: '‹', next: '›',
                first_title: 'Trang đầu', last_title: 'Trang cuối',
                prev_title:  'Trang trước', next_title: 'Trang sau',
            },
        },
    },
};

const TabulatorInstances = new Map();
window.TabulatorInstances = TabulatorInstances;

function initTabulator(selector, columns, data = [], options = {}) {
    const el = typeof selector === 'string'
        ? document.querySelector(selector)
        : selector;
    if (!el) { console.warn('[Tabulator] Element not found:', selector); return null; }

    const instance = new Tabulator(el, { ...DEFAULTS, ...options, columns, data });
    TabulatorInstances.set(typeof selector === 'string' ? selector : el.id, instance);
    return instance;
}

window.initTabulator = initTabulator;
window.Tabulator = Tabulator;
export { initTabulator, TabulatorInstances };
export default Tabulator;
