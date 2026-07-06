/**
 * pages/project-index.js
 * Alpine component + Tabulator + TomSelect + Flatpickr cho trang danh sách dự án.
 */

// ── Utilities ─────────────────────────────────────────────────────────────────

function prjEsc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function prjIsoDate(d) {
    return d.getFullYear() + '-'
        + String(d.getMonth() + 1).padStart(2, '0') + '-'
        + String(d.getDate()).padStart(2, '0');
}

function prjDisplayDate(iso) {
    if (!iso) return '';
    const p = iso.split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
}

function prjPresetRange(preset) {
    const now = new Date(), y = now.getFullYear(), m = now.getMonth(), d = now.getDate();
    if (preset === 'month')   return [new Date(y, m, 1), new Date(y, m + 1, 0)];
    if (preset === 'quarter') {
        const q = Math.floor(m / 3);
        return [new Date(y, q * 3, 1), new Date(y, q * 3 + 3, 0)];
    }
    if (preset === 'year')    return [new Date(y, 0, 1), new Date(y, 11, 31)];
    return [null, null];
}

// ── Priority color map ────────────────────────────────────────────────────────

const PRJ_PRIORITY_DOT = {
    low:      'bg-base-content/20',
    medium:   'bg-info',
    high:     'bg-warning',
    critical: 'bg-error',
};

// ── Tabulator columns ─────────────────────────────────────────────────────────

function buildProjectColumns(canDelete) {
    return [
        {
            title: 'Dự án', field: 'name', minWidth: 260, sorter: 'string', frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                const dot = '<span class="w-2 h-2 rounded-full shrink-0 ' + prjEsc(PRJ_PRIORITY_DOT[d.priority] || '') + '"></span>';
                return '<div class="flex items-center gap-2.5">'
                    + dot
                    + '<div>'
                    + '<a href="' + prjEsc(d.show_url) + '" class="font-semibold text-sm hover:text-primary transition-colors">' + prjEsc(d.name) + '</a>'
                    + '<p class="text-xs font-mono text-base-content/40">' + prjEsc(d.code) + '</p>'
                    + '</div></div>';
            },
        },
        {
            title: 'Trạng thái', field: 'status', width: 140, hozAlign: 'center',
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="badge badge-sm badge-soft ' + prjEsc(d.status_badge) + '">' + prjEsc(d.status_label) + '</span>';
            },
        },
        {
            title: 'Ưu tiên', field: 'priority', width: 110, hozAlign: 'center',
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="badge badge-sm badge-soft ' + prjEsc(d.priority_badge) + '">' + prjEsc(d.priority_label) + '</span>';
            },
        },
        {
            title: 'Chi nhánh', field: 'branch_name', minWidth: 150,
            formatter(cell) {
                return prjEsc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Phòng ban', field: 'dept_name', minWidth: 160,
            formatter(cell) {
                return prjEsc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Người phụ trách', field: 'owner_name', minWidth: 160,
            formatter(cell) {
                const d = cell.getRow().getData();
                if (!d.owner_name) return '<span class="text-base-content/25 text-xs">—</span>';
                return prjEsc(d.owner_name)
                    + (d.owner_code ? ' <span class="text-xs text-base-content/40 font-mono">(' + prjEsc(d.owner_code) + ')</span>' : '');
            },
        },
        {
            title: 'Thành viên', field: 'active_members_count', width: 100, hozAlign: 'center', sorter: 'number',
            formatter(cell) {
                const v = cell.getValue() || 0;
                return '<span class="badge badge-ghost badge-sm">' + v + ' người</span>';
            },
        },
        {
            title: 'Bắt đầu', field: 'start_date', width: 110, hozAlign: 'center',
            formatter(cell) {
                return prjEsc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Kết thúc', field: 'end_date', width: 110, hozAlign: 'center',
            formatter(cell) {
                return prjEsc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Phân loại', field: 'category', width: 120, headerSort: false,
            formatter(cell) {
                const v = cell.getValue();
                return v ? '<span class="badge badge-xs badge-ghost">' + prjEsc(v) + '</span>'
                         : '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Thao tác', field: 'id', width: 100, hozAlign: 'center', headerSort: false, frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<div class="flex items-center justify-center gap-1">';

                html += '<a href="' + prjEsc(d.show_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-info" title="Xem">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>'
                    + '</a>';

                html += '<a href="' + prjEsc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                    + '</a>';

                if (canDelete) {
                    html += '<button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa"'
                        + ' data-url="' + prjEsc(d.delete_url) + '" data-name="' + prjEsc(d.name) + '"'
                        + ' onclick="window.projectDeleteConfirm(this.dataset.url,this.dataset.name)">'
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

let prjPendingDeleteUrl = null;

window.projectDeleteConfirm = function (url, name) {
    prjPendingDeleteUrl = url;
    const nameEl = document.getElementById('projectDeleteName');
    if (nameEl) nameEl.textContent = '"' + name + '"';
    document.getElementById('projectDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('projectDeleteConfirmBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async function () {
        if (!prjPendingDeleteUrl) return;

        const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        this.disabled    = true;
        this.textContent = 'Đang xóa...';

        try {
            const res = await fetch(prjPendingDeleteUrl, {
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
                document.getElementById('projectDeleteModal')?.close();
                window.projectTable?.replaceData();
            } else {
                alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[project] delete failed', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled    = false;
            this.textContent = 'Xóa';
            prjPendingDeleteUrl = null;
        }
    });
});

// ── Alpine component ──────────────────────────────────────────────────────────

const PRJ_LS_COLS = 'project-list-hidden-cols';

document.addEventListener('alpine:init', () => {

    Alpine.data('projectListPage', (serverData = {}) => {
        const {
            apiUrl      = '',
            statuses    = [],
            priorities  = [],
            branches    = [],
            departments = [],
            canDelete   = false,
        } = serverData;

        const COLUMNS = buildProjectColumns(canDelete);

        let tableInst     = null;
        let statusTsInst  = null;
        let priorityTsInst= null;
        let branchTsInst  = null;
        let deptTsInst    = null;
        let dateFpInst    = null;
        let settingPreset = false;

        return {
            filters: {
                search: '', status: '', priority: '',
                branch_id: '', department_id: '',
                date_from: '', date_to: '',
            },
            activeDatePreset: '',
            hiddenCols:       [],

            get toggleableCols() {
                return COLUMNS
                    .filter(c => c.field !== 'id' && c.field !== 'name')
                    .map(c => ({ field: c.field, title: c.title }));
            },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.status || f.priority || f.branch_id || f.department_id || f.date_from);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.status) {
                    const s = statuses.find(x => x.value === f.status);
                    chips.push({ key: 'status', label: s ? s.text : f.status });
                }
                if (f.priority) {
                    const p = priorities.find(x => x.value === f.priority);
                    chips.push({ key: 'priority', label: 'Ưu tiên: ' + (p ? p.text : f.priority) });
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
                    chips.push({ key: 'date', label: prjDisplayDate(f.date_from) + ' — ' + prjDisplayDate(f.date_to) });
                return chips;
            },

            // ── Lifecycle ──────────────────────────────────────────────────
            init() {
                this.loadState();
                try { this.hiddenCols = JSON.parse(localStorage.getItem(PRJ_LS_COLS) || '[]'); } catch (_) {}
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#project-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)        p.search        = f.search;
                        if (f.status)        p.status        = f.status;
                        if (f.priority)      p.priority      = f.priority;
                        if (f.branch_id)     p.branch_id     = f.branch_id;
                        if (f.department_id) p.department_id = f.department_id;
                        if (f.date_from)     p.date_from     = f.date_from;
                        if (f.date_to)       p.date_to       = f.date_to;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[project] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'name', dir: 'asc' }],

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
                        + '<p class="text-sm">Không có dự án nào</p></div>',
                });

                window.projectTable = tableInst;
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

                // Priority TomSelect
                priorityTsInst = new window.TomSelect('#filter-priority', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả mức ưu tiên...',
                    plugins:        ['clear_button'],
                    options:        priorities,
                    items:          self.filters.priority ? [self.filters.priority] : [],
                    onChange(val) {
                        self.filters.priority = val || '';
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
                            self.filters.date_from = prjIsoDate(dates[0]);
                            self.filters.date_to   = prjIsoDate(dates[1]);
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
                if (p.has('q'))    this.filters.search        = p.get('q');
                if (p.has('st'))   this.filters.status        = p.get('st');
                if (p.has('pri'))  this.filters.priority      = p.get('pri');
                if (p.has('br'))   this.filters.branch_id     = p.get('br');
                if (p.has('dp'))   this.filters.department_id = p.get('dp');
                if (p.has('from')) this.filters.date_from     = p.get('from');
                if (p.has('to'))   this.filters.date_to       = p.get('to');
                if (p.has('dpre')) this.activeDatePreset      = p.get('dpre');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)        p.set('q',    f.search);
                if (f.status)        p.set('st',   f.status);
                if (f.priority)      p.set('pri',  f.priority);
                if (f.branch_id)     p.set('br',   f.branch_id);
                if (f.department_id) p.set('dp',   f.department_id);
                if (f.date_from)     p.set('from', f.date_from);
                if (f.date_to)       p.set('to',   f.date_to);
                if (this.activeDatePreset) p.set('dpre', this.activeDatePreset);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            // ── Actions ───────────────────────────────────────────────────
            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            setDatePreset(preset) {
                const range = prjPresetRange(preset);
                if (!range[0]) return;
                settingPreset         = true;
                this.activeDatePreset = preset;
                this.filters.date_from = prjIsoDate(range[0]);
                this.filters.date_to   = prjIsoDate(range[1]);
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
                if (key === 'search')     this.filters.search = '';
                if (key === 'status')   { this.filters.status = '';        statusTsInst?.clear(true); }
                if (key === 'priority') { this.filters.priority = '';      priorityTsInst?.clear(true); }
                if (key === 'branch')   { this.filters.branch_id = '';     branchTsInst?.clear(true); }
                if (key === 'department') { this.filters.department_id = ''; deptTsInst?.clear(true); }
                if (key === 'date')     { this.clearDate(); return; }
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', status: '', priority: '', branch_id: '', department_id: '', date_from: '', date_to: '' };
                this.activeDatePreset = '';
                statusTsInst?.clear(true);
                priorityTsInst?.clear(true);
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
                try { localStorage.setItem(PRJ_LS_COLS, JSON.stringify(this.hiddenCols)); } catch (_) {}
            },
        };
    });
});
