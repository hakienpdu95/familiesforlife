/**
 * pages/employee-index.js
 * Alpine component + Tabulator + TomSelect + Flatpickr cho trang danh sách nhân viên.
 */

// ── Utilities ─────────────────────────────────────────────────────────────────

function empEsc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function empIsoDate(d) {
    return d.getFullYear() + '-'
        + String(d.getMonth() + 1).padStart(2, '0') + '-'
        + String(d.getDate()).padStart(2, '0');
}

function empDisplayDate(iso) {
    if (!iso) return '';
    const p = iso.split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
}

function empPresetRange(preset) {
    const now = new Date(), y = now.getFullYear(), m = now.getMonth(), d = now.getDate();
    if (preset === 'today') return [new Date(y, m, d), new Date(y, m, d)];
    if (preset === 'month') return [new Date(y, m, 1), new Date(y, m + 1, 0)];
    if (preset === 'year')  return [new Date(y, 0, 1), new Date(y, 11, 31)];
    return [null, null];
}

// ── Tabulator columns factory ─────────────────────────────────────────────────

function buildEmployeeColumns(canDelete) {
    return [
        {
            title: 'Nhân viên', field: 'full_name', minWidth: 240, sorter: 'string', frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                const avatar = d.avatar_url
                    ? '<img src="' + empEsc(d.avatar_url) + '" class="w-8 h-8 rounded-full object-cover shrink-0"/>'
                    : '<div class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary text-xs font-bold shrink-0">'
                        + empEsc((d.full_name || '?').charAt(0).toUpperCase()) + '</div>';
                return '<div class="flex items-center gap-2.5">'
                    + avatar
                    + '<div><a href="' + empEsc(d.show_url) + '" class="font-semibold text-sm hover:text-primary transition-colors">' + empEsc(d.full_name) + '</a>'
                    + '<p class="text-xs font-mono text-base-content/40">' + empEsc(d.employee_code) + '</p></div>'
                    + '</div>';
            },
        },
        {
            title: 'Trạng thái', field: 'status', width: 130, hozAlign: 'center',
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="badge badge-sm badge-soft ' + empEsc(d.status_badge) + '">' + empEsc(d.status_label) + '</span>';
            },
        },
        {
            title: 'Hợp đồng', field: 'employment_type', width: 130, hozAlign: 'center',
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="badge badge-sm badge-soft ' + empEsc(d.employment_type_badge) + '">' + empEsc(d.employment_type_label) + '</span>';
            },
        },
        {
            title: 'Chi nhánh', field: 'snap_branch_name', minWidth: 160,
            formatter(cell) {
                return empEsc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Phòng ban', field: 'snap_dept_name', minWidth: 160,
            formatter(cell) {
                return empEsc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Chức danh', field: 'snap_job_title', minWidth: 160,
            formatter(cell) {
                const d = cell.getRow().getData();
                if (!d.snap_job_title) return '<span class="text-base-content/25 text-xs">—</span>';
                return empEsc(d.snap_job_title)
                    + (d.snap_job_level ? ' <span class="text-xs text-base-content/40">Lv.' + d.snap_job_level + '</span>' : '');
            },
        },
        {
            title: 'Email', field: 'email', minWidth: 180, headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<a href="mailto:' + empEsc(d.email) + '" class="text-xs text-primary hover:underline">' + empEsc(d.email) + '</a>';
                if (d.phone) html += '<p class="text-xs text-base-content/40">' + empEsc(d.phone) + '</p>';
                return html;
            },
        },
        {
            title: 'Quản lý', field: 'manager_name', width: 160, headerSort: false,
            formatter(cell) {
                return empEsc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Ngày vào làm', field: 'hired_at', width: 120, hozAlign: 'center',
            formatter(cell) {
                return empEsc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Thao tác', field: 'id', width: 100, hozAlign: 'center', headerSort: false, frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<div class="flex items-center justify-center gap-1">';

                html += '<a href="' + empEsc(d.show_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-info" title="Xem">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>'
                    + '</a>';

                html += '<a href="' + empEsc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                    + '</a>';

                if (canDelete) {
                    html += '<button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa"'
                        + ' data-url="' + empEsc(d.delete_url) + '" data-name="' + empEsc(d.full_name) + '"'
                        + ' onclick="window.employeeDeleteConfirm(this.dataset.url,this.dataset.name)">'
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

let empPendingDeleteUrl = null;

window.employeeDeleteConfirm = function (url, name) {
    empPendingDeleteUrl = url;
    const nameEl = document.getElementById('employeeDeleteName');
    if (nameEl) nameEl.textContent = '"' + name + '"';
    document.getElementById('employeeDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('employeeDeleteConfirmBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async function () {
        if (!empPendingDeleteUrl) return;

        const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        this.disabled    = true;
        this.textContent = 'Đang xóa...';

        try {
            const res = await fetch(empPendingDeleteUrl, {
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
                document.getElementById('employeeDeleteModal')?.close();
                window.employeeTable?.replaceData();
            } else {
                alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[employee] delete failed', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled    = false;
            this.textContent = 'Xóa';
            empPendingDeleteUrl = null;
        }
    });
});

// ── Alpine component ──────────────────────────────────────────────────────────

const EMP_LS_COLS = 'employee-list-hidden-cols';

document.addEventListener('alpine:init', () => {

    Alpine.data('employeeListPage', (serverData = {}) => {
        const {
            apiUrl          = '',
            statuses        = [],
            employmentTypes = [],
            branches        = [],
            departments     = [],
            canDelete       = false,
        } = serverData;

        const COLUMNS = buildEmployeeColumns(canDelete);

        let tableInst      = null;
        let statusTsInst   = null;
        let typeTsInst     = null;
        let branchTsInst   = null;
        let deptTsInst     = null;
        let dateFpInst     = null;
        let settingPreset  = false;

        return {
            filters: {
                search: '', status: '', employment_type: '',
                branch_id: '', department_id: '',
                date_from: '', date_to: '',
            },
            activeDatePreset: '',
            hiddenCols:       [],

            get toggleableCols() {
                return COLUMNS
                    .filter(c => c.field !== 'id' && c.field !== 'full_name')
                    .map(c => ({ field: c.field, title: c.title }));
            },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.status || f.employment_type || f.branch_id || f.department_id || f.date_from);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.status) {
                    const s = statuses.find(x => x.value === f.status);
                    chips.push({ key: 'status', label: s ? s.text : f.status });
                }
                if (f.employment_type) {
                    const t = employmentTypes.find(x => x.value === f.employment_type);
                    chips.push({ key: 'employment_type', label: t ? t.text : f.employment_type });
                }
                if (f.branch_id) {
                    const b = branches.find(x => String(x.value) === String(f.branch_id));
                    chips.push({ key: 'branch', label: 'CN: ' + (b ? b.text : f.branch_id) });
                }
                if (f.department_id) {
                    const d = departments.find(x => String(x.value) === String(f.department_id));
                    chips.push({ key: 'department', label: 'PB: ' + (d ? d.text : f.department_id) });
                }
                if (f.date_from && f.date_to)
                    chips.push({ key: 'date', label: empDisplayDate(f.date_from) + ' — ' + empDisplayDate(f.date_to) });
                return chips;
            },

            // ── Lifecycle ──────────────────────────────────────────────────
            init() {
                this.loadState();
                try { this.hiddenCols = JSON.parse(localStorage.getItem(EMP_LS_COLS) || '[]'); } catch (_) {}
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#employee-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)          p.search          = f.search;
                        if (f.status)          p.status          = f.status;
                        if (f.employment_type) p.employment_type = f.employment_type;
                        if (f.branch_id)       p.branch_id       = f.branch_id;
                        if (f.department_id)   p.department_id   = f.department_id;
                        if (f.date_from)       p.date_from       = f.date_from;
                        if (f.date_to)         p.date_to         = f.date_to;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[employee] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'full_name', dir: 'asc' }],

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>'
                        + '<p class="text-sm">Không có nhân viên nào</p></div>',
                });

                window.employeeTable = tableInst;
                self.hiddenCols.forEach(field => tableInst.hideColumn(field));

                // Status TomSelect
                statusTsInst = new window.TomSelect('#filter-status', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả trạng thái...',
                    plugins:        ['clear_button'],
                    options:        statuses,
                    items:          self.filters.status ? [self.filters.status] : [],
                    onChange(val) {
                        self.filters.status = val || '';
                        self.saveState();
                        self.refresh();
                    },
                });

                // Employment type TomSelect
                typeTsInst = new window.TomSelect('#filter-employment-type', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả loại HĐ...',
                    plugins:        ['clear_button'],
                    options:        employmentTypes,
                    items:          self.filters.employment_type ? [self.filters.employment_type] : [],
                    onChange(val) {
                        self.filters.employment_type = val || '';
                        self.saveState();
                        self.refresh();
                    },
                });

                // Branch TomSelect
                branchTsInst = new window.TomSelect('#filter-branch', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả chi nhánh...',
                    maxOptions:     null,
                    searchField:    ['text'],
                    plugins:        ['clear_button'],
                    options:        branches,
                    valueField:     'value',
                    labelField:     'text',
                    items:          self.filters.branch_id ? [self.filters.branch_id] : [],
                    onChange(val) {
                        self.filters.branch_id = val || '';
                        self.saveState();
                        self.refresh();
                    },
                    render: { no_results: () => '<div class="no-results p-3 text-sm opacity-50">Không tìm thấy</div>' },
                });

                // Department TomSelect
                deptTsInst = new window.TomSelect('#filter-department', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả phòng ban...',
                    maxOptions:     null,
                    searchField:    ['text'],
                    plugins:        ['clear_button'],
                    options:        departments,
                    valueField:     'value',
                    labelField:     'text',
                    items:          self.filters.department_id ? [self.filters.department_id] : [],
                    onChange(val) {
                        self.filters.department_id = val || '';
                        self.saveState();
                        self.refresh();
                    },
                    render: { no_results: () => '<div class="no-results p-3 text-sm opacity-50">Không tìm thấy</div>' },
                });

                // Flatpickr date range
                dateFpInst = window.initDateRangePicker('#filter-date', {
                    disableMobile: true,
                    onChange(dates) {
                        if (settingPreset) return;
                        self.activeDatePreset = '';
                        if (dates.length === 2) {
                            self.filters.date_from = empIsoDate(dates[0]);
                            self.filters.date_to   = empIsoDate(dates[1]);
                        } else {
                            self.filters.date_from = '';
                            self.filters.date_to   = '';
                        }
                        self.saveState();
                        self.refresh();
                    },
                });

                if (self.filters.date_from && self.filters.date_to) {
                    dateFpInst.setDate([self.filters.date_from, self.filters.date_to], false);
                }
            },

            // ── URL state ─────────────────────────────────────────────────
            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))    this.filters.search          = p.get('q');
                if (p.has('st'))   this.filters.status          = p.get('st');
                if (p.has('typ'))  this.filters.employment_type = p.get('typ');
                if (p.has('br'))   this.filters.branch_id       = p.get('br');
                if (p.has('dp'))   this.filters.department_id   = p.get('dp');
                if (p.has('from')) this.filters.date_from       = p.get('from');
                if (p.has('to'))   this.filters.date_to         = p.get('to');
                if (p.has('dpre')) this.activeDatePreset        = p.get('dpre');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)          p.set('q',    f.search);
                if (f.status)          p.set('st',   f.status);
                if (f.employment_type) p.set('typ',  f.employment_type);
                if (f.branch_id)       p.set('br',   f.branch_id);
                if (f.department_id)   p.set('dp',   f.department_id);
                if (f.date_from)       p.set('from', f.date_from);
                if (f.date_to)         p.set('to',   f.date_to);
                if (this.activeDatePreset) p.set('dpre', this.activeDatePreset);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            // ── Actions ───────────────────────────────────────────────────
            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            setDatePreset(preset) {
                const range = empPresetRange(preset);
                if (!range[0]) return;
                settingPreset         = true;
                this.activeDatePreset = preset;
                this.filters.date_from = empIsoDate(range[0]);
                this.filters.date_to   = empIsoDate(range[1]);
                dateFpInst?.setDate([range[0], range[1]], false);
                settingPreset = false;
                this.saveState();
                this.refresh();
            },

            clearDate() {
                this.activeDatePreset  = '';
                this.filters.date_from = '';
                this.filters.date_to   = '';
                dateFpInst?.clear(false);
                this.saveState();
                this.refresh();
            },

            removeChip(key) {
                if (key === 'search')          this.filters.search = '';
                if (key === 'status')        { this.filters.status = '';          statusTsInst?.clear(true); }
                if (key === 'employment_type') { this.filters.employment_type = ''; typeTsInst?.clear(true); }
                if (key === 'branch')        { this.filters.branch_id = '';       branchTsInst?.clear(true); }
                if (key === 'department')    { this.filters.department_id = '';   deptTsInst?.clear(true); }
                if (key === 'date')          { this.clearDate(); return; }
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', status: '', employment_type: '', branch_id: '', department_id: '', date_from: '', date_to: '' };
                this.activeDatePreset = '';
                statusTsInst?.clear(true);
                typeTsInst?.clear(true);
                branchTsInst?.clear(true);
                deptTsInst?.clear(true);
                dateFpInst?.clear(false);
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
                try { localStorage.setItem(EMP_LS_COLS, JSON.stringify(this.hiddenCols)); } catch (_) {}
            },
        };
    });
});
