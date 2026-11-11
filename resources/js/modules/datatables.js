/**
 * resources/js/modules/datatables.js
 * DataTables.net v2 wrapper — jQuery required (already global via app.js)
 *
 * Blade:  @vite(['resources/js/modules/datatables.js'])
 * Use:    initDataTable('#myTable', { ...options })
 *         window.DataTableInstances.get('#myTable').reload()
 */
import DataTable from 'datatables.net';
import { t } from '../shared/i18n.js';
import 'datatables.net-dt';      // default CSS theme

// Default config áp dụng cho mọi bảng
const DEFAULTS = {
    language: {
        url: null, // hoặc đường dẫn đến file lang JSON
        search:          t('Tìm kiếm:'),
        lengthMenu:      t('Hiển thị _MENU_ dòng'),
        info:            t('Hiển thị _START_ đến _END_ / _TOTAL_ dòng'),
        infoEmpty:       t('Không có dữ liệu'),
        infoFiltered:    t('(lọc từ _MAX_ dòng)'),
        zeroRecords:     t('Không tìm thấy kết quả'),
        paginate: {
            first: '«', last: '»', next: '›', previous: '‹',
        },
    },
    pageLength:    25,
    responsive:    true,
    autoWidth:     false,
    stateSave:     false,
    dom: "<'flex flex-wrap items-center justify-between gap-3 mb-4'lf>" +
         "<'overflow-x-auto'tr>" +
         "<'flex flex-wrap items-center justify-between gap-3 mt-4'ip>",
};

/** Map lưu tất cả DataTable instances để dễ access từ bên ngoài */
const DataTableInstances = new Map();
window.DataTableInstances = DataTableInstances;

/**
 * Khởi tạo DataTable
 * @param {string|HTMLElement} selector  CSS selector hoặc element
 * @param {object}             options   Tuỳ chọn DataTables (merge với DEFAULTS)
 * @returns {DataTable}                  DataTable instance
 */
function initDataTable(selector, options = {}) {
    const mergedOpts = {
        ...DEFAULTS,
        ...options,
        language: { ...DEFAULTS.language, ...(options.language ?? {}) },
    };
    const dt = new DataTable(selector, mergedOpts);
    DataTableInstances.set(
        typeof selector === 'string' ? selector : selector.id,
        dt
    );
    return dt;
}

window.initDataTable = initDataTable;
export { initDataTable, DataTableInstances };
export default DataTable;
