/**
 * Modules/Task/resources/assets/js/task.js
 * Alpine component + Tabulator + TomSelect cho trang danh sách công việc.
 */

import './pages/task-form.js';

// ── Utilities ─────────────────────────────────────────────────────────────────

function tskEsc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function tskIsoDate(d) {
    return d.getFullYear() + '-'
        + String(d.getMonth() + 1).padStart(2, '0') + '-'
        + String(d.getDate()).padStart(2, '0');
}

function tskDisplayDate(iso) {
    if (!iso) return '';
    const p = iso.split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
}

// ── Priority color map ────────────────────────────────────────────────────────

const TSK_PRIORITY_DOT = {
    none:     'bg-base-content/15',
    low:      'bg-base-content/30',
    medium:   'bg-info',
    high:     'bg-warning',
    critical: 'bg-error',
};

// ── Tabulator columns ─────────────────────────────────────────────────────────

function buildTaskColumns(canDelete) {
    return [
        {
            title: 'Công việc', field: 'title', minWidth: 280, sorter: 'string', frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                const dot = '<span class="w-2 h-2 rounded-full shrink-0 ' + tskEsc(TSK_PRIORITY_DOT[d.priority] || '') + '"></span>';
                const typeIcon = '<span class="text-xs text-base-content/40">' + tskEsc(d.task_type_icon || '') + '</span>';
                return '<div class="flex items-center gap-2.5">'
                    + dot
                    + '<div class="min-w-0">'
                    + '<div class="flex items-center gap-1.5">'
                    + typeIcon
                    + '<a href="' + tskEsc(d.show_url) + '" class="font-medium text-sm hover:text-primary transition-colors truncate max-w-xs">' + tskEsc(d.title) + '</a>'
                    + '</div>'
                    + '<p class="text-xs text-base-content/40">' + tskEsc(d.project_name || '') + (d.project_code ? ' · ' + tskEsc(d.project_code) : '') + '</p>'
                    + '</div></div>';
            },
        },
        {
            title: 'Loại', field: 'task_type', width: 120, hozAlign: 'center',
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="badge badge-sm badge-soft ' + tskEsc(d.task_type_badge) + '">' + tskEsc(d.task_type_label) + '</span>';
            },
        },
        {
            title: 'Trạng thái', field: 'status', width: 130, hozAlign: 'center',
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="badge badge-sm badge-soft ' + tskEsc(d.status_badge) + '">' + tskEsc(d.status_label) + '</span>';
            },
        },
        {
            title: 'Ưu tiên', field: 'priority', width: 110, hozAlign: 'center',
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="badge badge-sm badge-soft ' + tskEsc(d.priority_badge) + '">' + tskEsc(d.priority_label) + '</span>';
            },
        },
        {
            title: 'Người thực hiện', field: 'employee_name', minWidth: 160,
            formatter(cell) {
                return tskEsc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Hạn hoàn thành', field: 'due_date', width: 130, hozAlign: 'center',
            formatter(cell) {
                return tskEsc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Tiến độ', field: 'progress_pct', width: 100, hozAlign: 'center', sorter: 'number',
            formatter(cell) {
                const v = cell.getValue() || 0;
                const color = v >= 100 ? 'progress-success' : v >= 50 ? 'progress-info' : 'progress-warning';
                return '<div class="flex items-center gap-1.5">'
                    + '<progress class="progress ' + color + ' w-14 h-1.5" value="' + v + '" max="100"></progress>'
                    + '<span class="text-xs">' + v + '%</span>'
                    + '</div>';
            },
        },
        {
            title: 'Dự án', field: 'project_name', minWidth: 160,
            formatter(cell) {
                const d = cell.getRow().getData();
                if (!d.project_name) return '<span class="text-base-content/25 text-xs">—</span>';
                return tskEsc(d.project_name)
                    + (d.project_code ? ' <span class="text-xs text-base-content/40 font-mono">(' + tskEsc(d.project_code) + ')</span>' : '');
            },
        },
        {
            title: 'Ngày tạo', field: 'created_at', width: 110, hozAlign: 'center',
            formatter(cell) {
                return tskEsc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Thao tác', field: 'id', width: 100, hozAlign: 'center', headerSort: false, frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<div class="flex items-center justify-center gap-1">';

                html += '<a href="' + tskEsc(d.show_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-info" title="Xem">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>'
                    + '</a>';

                html += '<a href="' + tskEsc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                    + '</a>';

                if (canDelete) {
                    html += '<button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa"'
                        + ' data-url="' + tskEsc(d.delete_url) + '" data-name="' + tskEsc(d.title) + '"'
                        + ' onclick="window.taskDeleteConfirm(this.dataset.url,this.dataset.name)">'
                        + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>'
                        + '</button>';
                }

                html += '</div>';
                return html;
            },
        },
    ];
}

// ── AJAX Delete ───────────────────────────────────────────────────────────────

let tskPendingDeleteUrl = null;

window.taskDeleteConfirm = function (url, name) {
    tskPendingDeleteUrl = url;
    const nameEl = document.getElementById('taskDeleteName');
    if (nameEl) nameEl.textContent = '"' + name + '"';
    document.getElementById('taskDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('taskDeleteConfirmBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async function () {
        if (!tskPendingDeleteUrl) return;

        const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        this.disabled    = true;
        this.textContent = 'Đang xóa...';

        try {
            const res = await fetch(tskPendingDeleteUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':     csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept':           'application/json',
                    'Content-Type':     'application/x-www-form-urlencoded',
                },
                body: '_method=DELETE',
            });

            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                document.getElementById('taskDeleteModal')?.close();
                window.taskTable?.replaceData();
            } else {
                alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[task] delete failed', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled    = false;
            this.textContent = 'Xóa';
            tskPendingDeleteUrl = null;
        }
    });
});

// ── Alpine component ──────────────────────────────────────────────────────────

const TSK_LS_COLS = 'task-list-hidden-cols';

document.addEventListener('alpine:init', () => {

    Alpine.data('taskListPage', (serverData = {}) => {
        const {
            apiUrl    = '',
            statuses  = [],
            priorities= [],
            taskTypes = [],
            projects  = [],
            employees = [],
            canDelete = false,
        } = serverData;

        const COLUMNS = buildTaskColumns(canDelete);

        let tableInst      = null;
        let statusTsInst   = null;
        let priorityTsInst = null;
        let taskTypeTsInst = null;
        let projectTsInst  = null;
        let employeeTsInst = null;
        let dateFromInst   = null;
        let dateToInst     = null;

        return {
            filters: {
                search: '', project_id: '', status: '', priority: '',
                task_type: '', employee_id: '', date_from: '', date_to: '',
            },
            hiddenCols: [],

            get toggleableCols() {
                return COLUMNS
                    .filter(c => c.field !== 'id' && c.field !== 'title')
                    .map(c => ({ field: c.field, title: c.title }));
            },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.project_id || f.status || f.priority || f.task_type || f.employee_id || f.date_from);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.project_id) {
                    const p = projects.find(x => String(x.value) === String(f.project_id));
                    chips.push({ key: 'project', label: 'DA: ' + (p ? p.text : f.project_id) });
                }
                if (f.status) {
                    const s = statuses.find(x => x.value === f.status);
                    chips.push({ key: 'status', label: s ? s.text : f.status });
                }
                if (f.priority) {
                    const p = priorities.find(x => x.value === f.priority);
                    chips.push({ key: 'priority', label: 'Ưu tiên: ' + (p ? p.text : f.priority) });
                }
                if (f.task_type) {
                    const t = taskTypes.find(x => x.value === f.task_type);
                    chips.push({ key: 'task_type', label: 'Loại: ' + (t ? t.text : f.task_type) });
                }
                if (f.employee_id) {
                    const e = employees.find(x => String(x.value) === String(f.employee_id));
                    chips.push({ key: 'employee', label: 'NV: ' + (e ? e.text : f.employee_id) });
                }
                if (f.date_from && f.date_to)
                    chips.push({ key: 'date', label: tskDisplayDate(f.date_from) + ' — ' + tskDisplayDate(f.date_to) });
                return chips;
            },

            // ── Lifecycle ──────────────────────────────────────────────────
            init() {
                this.loadState();
                try { this.hiddenCols = JSON.parse(localStorage.getItem(TSK_LS_COLS) || '[]'); } catch (_) {}
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#task-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)      p.search      = f.search;
                        if (f.project_id)  p.project_id  = f.project_id;
                        if (f.status)      p.status      = f.status;
                        if (f.priority)    p.priority    = f.priority;
                        if (f.task_type)   p.task_type   = f.task_type;
                        if (f.employee_id) p.employee_id = f.employee_id;
                        if (f.date_from)   p.date_from   = f.date_from;
                        if (f.date_to)     p.date_to     = f.date_to;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[task] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'created_at', dir: 'desc' }],

                    layout:           'fitColumns',
                    responsiveLayout: 'collapse',
                    movableColumns:   true,
                    height:           '68vh',

                    locale: 'vi-VN',
                    langs: {
                        'vi-VN': {
                            pagination: {
                                page_size: 'Dòng/trang', page_title: 'Trang',
                                first: '«', last: '»', prev: '‹', next: '›',
                                first_title: 'Trang đầu', last_title: 'Trang cuối',
                                prev_title: 'Trang trước', next_title: 'Trang sau',
                                counter: { showing: '', of: 'trong', rows: 'dòng', pages: 'trang' },
                            },
                        },
                    },

                    columns: COLUMNS,
                    placeholder: '<div class="py-16 text-center opacity-40">'
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>'
                        + '<p class="text-sm">Không có công việc nào</p></div>',
                });

                window.taskTable = tableInst;
                self.hiddenCols.forEach(field => tableInst.hideColumn(field));

                statusTsInst = new window.TomSelect('#filter-status', {
                    dropdownParent: 'body', placeholder: 'Tất cả trạng thái...',
                    plugins: ['clear_button'], options: statuses,
                    items: self.filters.status ? [self.filters.status] : [],
                    onChange(val) { self.filters.status = val || ''; self.saveState(); self.refresh(); },
                });

                priorityTsInst = new window.TomSelect('#filter-priority', {
                    dropdownParent: 'body', placeholder: 'Tất cả mức ưu tiên...',
                    plugins: ['clear_button'], options: priorities,
                    items: self.filters.priority ? [self.filters.priority] : [],
                    onChange(val) { self.filters.priority = val || ''; self.saveState(); self.refresh(); },
                });

                taskTypeTsInst = new window.TomSelect('#filter-task-type', {
                    dropdownParent: 'body', placeholder: 'Tất cả loại...',
                    plugins: ['clear_button'], options: taskTypes,
                    items: self.filters.task_type ? [self.filters.task_type] : [],
                    onChange(val) { self.filters.task_type = val || ''; self.saveState(); self.refresh(); },
                });

                projectTsInst = new window.TomSelect('#filter-project', {
                    dropdownParent: 'body', placeholder: 'Tất cả dự án...',
                    maxOptions: null, searchField: ['text'],
                    plugins: ['clear_button'], options: projects,
                    valueField: 'value', labelField: 'text',
                    items: self.filters.project_id ? [self.filters.project_id] : [],
                    onChange(val) { self.filters.project_id = val || ''; self.saveState(); self.refresh(); },
                    render: { no_results: () => '<div class="no-results p-3 text-sm opacity-50">Không tìm thấy</div>' },
                });

                employeeTsInst = new window.TomSelect('#filter-employee', {
                    dropdownParent: 'body', placeholder: 'Tất cả nhân viên...',
                    maxOptions: null, searchField: ['text'],
                    plugins: ['clear_button'], options: employees,
                    valueField: 'value', labelField: 'text',
                    items: self.filters.employee_id ? [self.filters.employee_id] : [],
                    onChange(val) { self.filters.employee_id = val || ''; self.saveState(); self.refresh(); },
                    render: { no_results: () => '<div class="no-results p-3 text-sm opacity-50">Không tìm thấy</div>' },
                });
            },

            // ── URL state ─────────────────────────────────────────────────
            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))    this.filters.search      = p.get('q');
                if (p.has('prj'))  this.filters.project_id  = p.get('prj');
                if (p.has('st'))   this.filters.status      = p.get('st');
                if (p.has('pri'))  this.filters.priority    = p.get('pri');
                if (p.has('typ'))  this.filters.task_type   = p.get('typ');
                if (p.has('emp'))  this.filters.employee_id = p.get('emp');
                if (p.has('from')) this.filters.date_from   = p.get('from');
                if (p.has('to'))   this.filters.date_to     = p.get('to');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)      p.set('q',    f.search);
                if (f.project_id)  p.set('prj',  f.project_id);
                if (f.status)      p.set('st',   f.status);
                if (f.priority)    p.set('pri',  f.priority);
                if (f.task_type)   p.set('typ',  f.task_type);
                if (f.employee_id) p.set('emp',  f.employee_id);
                if (f.date_from)   p.set('from', f.date_from);
                if (f.date_to)     p.set('to',   f.date_to);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            removeChip(key) {
                if (key === 'search')    this.filters.search = '';
                if (key === 'project')  { this.filters.project_id = '';  projectTsInst?.clear(true); }
                if (key === 'status')   { this.filters.status = '';      statusTsInst?.clear(true); }
                if (key === 'priority') { this.filters.priority = '';    priorityTsInst?.clear(true); }
                if (key === 'task_type'){ this.filters.task_type = '';   taskTypeTsInst?.clear(true); }
                if (key === 'employee') { this.filters.employee_id = ''; employeeTsInst?.clear(true); }
                if (key === 'date') {
                    this.filters.date_from = '';
                    this.filters.date_to   = '';
                }
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', project_id: '', status: '', priority: '', task_type: '', employee_id: '', date_from: '', date_to: '' };
                statusTsInst?.clear(true);
                priorityTsInst?.clear(true);
                taskTypeTsInst?.clear(true);
                projectTsInst?.clear(true);
                employeeTsInst?.clear(true);
                history.replaceState(null, '', location.pathname);
                this.refresh();
            },

            toggleCol(field) {
                if (this.hiddenCols.includes(field)) {
                    this.hiddenCols = this.hiddenCols.filter(f => f !== field);
                    tableInst?.showColumn(field);
                } else {
                    this.hiddenCols.push(field);
                    tableInst?.hideColumn(field);
                }
                try { localStorage.setItem(TSK_LS_COLS, JSON.stringify(this.hiddenCols)); } catch (_) {}
            },
        };
    });
});
