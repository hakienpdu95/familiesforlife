/**
 * pages/department-index.js
 * Alpine component + Tabulator + TomSelect + Flatpickr cho index.blade.php phòng ban.
 *
 * Server data truyền vào qua x-data="deptListPage({{ Js::from([...]) }})".
 * Globals: window.Alpine, window.TomSelect, window.Tabulator,
 *          window.initDateRangePicker
 */

// ── Utilities ─────────────────────────────────────────────────────────────────

function dEsc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function dIsoDate(d) {
    return d.getFullYear() + '-'
        + String(d.getMonth() + 1).padStart(2, '0') + '-'
        + String(d.getDate()).padStart(2, '0');
}

function dDisplayDate(iso) {
    if (!iso) return '';
    const p = iso.split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
}

function dPresetRange(preset) {
    const now = new Date(), y = now.getFullYear(), m = now.getMonth(), d = now.getDate();
    if (preset === 'today')  return [new Date(y, m, d), new Date(y, m, d)];
    if (preset === 'week') {
        const dow = now.getDay() === 0 ? 6 : now.getDay() - 1;
        return [new Date(y, m, d - dow), new Date(y, m, d - dow + 6)];
    }
    if (preset === 'month') return [new Date(y, m, 1), new Date(y, m + 1, 0)];
    if (preset === 'year')  return [new Date(y, 0, 1), new Date(y, 11, 31)];
    return [null, null];
}

// ── Tabulator columns factory ─────────────────────────────────────────────────

function buildDeptColumns(canDelete) {
    return [
        {
            title: 'Phòng ban', field: 'name', minWidth: 240, sorter: 'string', frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                const indent = '&nbsp;'.repeat(d.depth * 4);
                return '<div>'
                    + indent
                    + (d.depth > 0 ? '<span class="text-base-content/25 mr-1">└</span>' : '')
                    + '<a href="' + dEsc(d.show_url) + '" class="font-semibold text-sm hover:text-primary transition-colors">' + dEsc(d.name) + '</a>'
                    + '<p class="text-xs text-base-content/40 font-mono mt-0.5">' + dEsc(d.code) + '</p>'
                    + '</div>';
            },
        },
        {
            title: 'Chức năng', field: 'function', width: 175, hozAlign: 'center', sorter: 'string',
            formatter(cell) {
                const d = cell.getRow().getData();
                if (!d.function) return '<span class="text-base-content/25 text-xs">—</span>';
                return '<span class="badge badge-sm badge-soft ' + dEsc(d.function_badge) + '">' + dEsc(d.function_label) + '</span>';
            },
        },
        {
            title: 'Trạng thái', field: 'status', width: 130, hozAlign: 'center', sorter: 'string',
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="badge badge-sm badge-soft ' + dEsc(d.status_badge) + '">' + dEsc(d.status_label) + '</span>';
            },
        },
        {
            title: 'Chi nhánh', field: 'branch_name', minWidth: 160, sorter: 'string',
            formatter(cell) {
                return dEsc(cell.getValue()) || '<span class="text-base-content/25 text-xs">Toàn tổ chức</span>';
            },
        },
        {
            title: 'Phòng ban cha', field: 'parent_name', minWidth: 160, sorter: 'string',
            formatter(cell) {
                return dEsc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Biên chế', field: 'headcount_limit', width: 100, hozAlign: 'center', sorter: 'number',
            formatter(cell) {
                const v = cell.getValue();
                return v ? '<span class="text-sm font-mono">' + v + '</span>' : '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Con', field: 'children_count', width: 70, hozAlign: 'center', sorter: 'number',
            formatter(cell) {
                const v = cell.getValue();
                return v > 0
                    ? '<span class="badge badge-ghost badge-sm">' + v + '</span>'
                    : '<span class="text-base-content/25 text-xs">0</span>';
            },
        },
        {
            title: 'Ngày tạo', field: 'created_at', width: 110, hozAlign: 'center', sorter: 'string',
        },
        {
            title: 'Thao tác', field: 'id', width: 110, hozAlign: 'center', headerSort: false, frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<div class="flex items-center justify-center gap-1">';

                html += '<a href="' + dEsc(d.show_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-info" title="Xem">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>'
                    + '</a>';

                html += '<a href="' + dEsc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                    + '</a>';

                if (canDelete && d.can_delete) {
                    html += '<button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa"'
                        + ' data-url="' + dEsc(d.delete_url) + '" data-name="' + dEsc(d.name) + '"'
                        + ' onclick="window.deptDeleteConfirm(this.dataset.url,this.dataset.name)">'
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

let dPendingDeleteUrl = null;

window.deptDeleteConfirm = function (url, name) {
    dPendingDeleteUrl = url;
    const nameEl = document.getElementById('deptDeleteName');
    if (nameEl) nameEl.textContent = '"' + name + '"';
    document.getElementById('deptDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('deptDeleteConfirmBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async function () {
        if (!dPendingDeleteUrl) return;

        const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        this.disabled    = true;
        this.textContent = 'Đang xóa...';

        try {
            const res = await fetch(dPendingDeleteUrl, {
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
                document.getElementById('deptDeleteModal')?.close();
                window.deptTable?.replaceData();
            } else {
                alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[dept] delete failed', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled    = false;
            this.textContent = 'Xóa';
            dPendingDeleteUrl = null;
        }
    });
});

// ── Alpine component ──────────────────────────────────────────────────────────

const D_LS_COLS = 'dept-list-hidden-cols';

document.addEventListener('alpine:init', () => {

    Alpine.data('deptListPage', (serverData = {}) => {
        const {
            apiUrl        = '',
            functions     = [],
            statuses      = [],
            branchOptions = [],
            canDelete     = false,
        } = serverData;

        const COLUMNS = buildDeptColumns(canDelete);

        // Lib instances (non-reactive)
        let tableInst      = null;
        let funcTsInst     = null;
        let statusTsInst   = null;
        let branchTsInst   = null;
        let dateFpInst     = null;
        let settingPreset  = false;

        return {
            filters: {
                search: '', function: '', status: '', branch_id: '',
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
                return !!(f.search || f.function || f.status || f.branch_id || f.date_from);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.function) {
                    const fn = functions.find(x => x.value === f.function);
                    chips.push({ key: 'function', label: fn ? fn.text : f.function });
                }
                if (f.status) {
                    const s = statuses.find(x => x.value === f.status);
                    chips.push({ key: 'status', label: s ? s.text : f.status });
                }
                if (f.branch_id) {
                    const b = branchOptions.find(x => String(x.value) === String(f.branch_id));
                    chips.push({ key: 'branch', label: b ? b.text : ('Chi nhánh #' + f.branch_id) });
                }
                if (f.date_from && f.date_to)
                    chips.push({ key: 'date', label: dDisplayDate(f.date_from) + ' — ' + dDisplayDate(f.date_to) });
                return chips;
            },

            // ── Lifecycle ──────────────────────────────────────────────────
            init() {
                this.loadState();
                try { this.hiddenCols = JSON.parse(localStorage.getItem(D_LS_COLS) || '[]'); } catch (_) {}
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                // ── Tabulator ──────────────────────────────────────────────
                tableInst = new window.Tabulator('#dept-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)    p.search    = f.search;
                        if (f.function)  p.function  = f.function;
                        if (f.status)    p.status    = f.status;
                        if (f.branch_id) p.branch_id = f.branch_id;
                        if (f.date_from) p.date_from = f.date_from;
                        if (f.date_to)   p.date_to   = f.date_to;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[dept] API error', error),

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>'
                        + '<p class="text-sm">Không có phòng ban nào</p></div>',
                });

                window.deptTable = tableInst;
                self.hiddenCols.forEach(field => tableInst.hideColumn(field));

                // ── Function TomSelect ─────────────────────────────────────
                funcTsInst = new window.TomSelect('#filter-function', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả chức năng...',
                    plugins:        ['clear_button'],
                    options:        functions,
                    items:          self.filters.function ? [self.filters.function] : [],
                    onChange(val) {
                        self.filters.function = val || '';
                        self.saveState();
                        self.refresh();
                    },
                });

                // ── Status TomSelect ───────────────────────────────────────
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

                // ── Branch TomSelect ───────────────────────────────────────
                branchTsInst = new window.TomSelect('#filter-branch', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả chi nhánh...',
                    maxOptions:     null,
                    searchField:    ['text'],
                    plugins:        ['clear_button'],
                    options:        branchOptions,
                    items:          self.filters.branch_id ? [String(self.filters.branch_id)] : [],
                    onChange(val) {
                        self.filters.branch_id = val || '';
                        self.saveState();
                        self.refresh();
                    },
                    render: { no_results: () => '<div class="no-results p-3 text-sm opacity-50">Không tìm thấy</div>' },
                });

                // ── Flatpickr date range ───────────────────────────────────
                dateFpInst = window.initDateRangePicker('#filter-date', {
                    disableMobile: true,
                    onChange(dates) {
                        if (settingPreset) return;
                        self.activeDatePreset = '';
                        if (dates.length === 2) {
                            self.filters.date_from = dIsoDate(dates[0]);
                            self.filters.date_to   = dIsoDate(dates[1]);
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

            // ── URL state persistence ──────────────────────────────────────
            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))    this.filters.search    = p.get('q');
                if (p.has('fn'))   this.filters.function  = p.get('fn');
                if (p.has('st'))   this.filters.status    = p.get('st');
                if (p.has('br'))   this.filters.branch_id = p.get('br');
                if (p.has('from')) this.filters.date_from = p.get('from');
                if (p.has('to'))   this.filters.date_to   = p.get('to');
                if (p.has('dpre')) this.activeDatePreset  = p.get('dpre');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)    p.set('q',    f.search);
                if (f.function)  p.set('fn',   f.function);
                if (f.status)    p.set('st',   f.status);
                if (f.branch_id) p.set('br',   f.branch_id);
                if (f.date_from) p.set('from', f.date_from);
                if (f.date_to)   p.set('to',   f.date_to);
                if (this.activeDatePreset) p.set('dpre', this.activeDatePreset);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            // ── Actions ───────────────────────────────────────────────────
            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            setDatePreset(preset) {
                const range = dPresetRange(preset);
                if (!range[0]) return;
                settingPreset          = true;
                this.activeDatePreset  = preset;
                this.filters.date_from = dIsoDate(range[0]);
                this.filters.date_to   = dIsoDate(range[1]);
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
                if (key === 'search')   this.filters.search = '';
                if (key === 'function') { this.filters.function  = ''; funcTsInst?.clear(true); }
                if (key === 'status')   { this.filters.status    = ''; statusTsInst?.clear(true); }
                if (key === 'branch')   { this.filters.branch_id = ''; branchTsInst?.clear(true); }
                if (key === 'date')     { this.clearDate(); return; }
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', function: '', status: '', branch_id: '', date_from: '', date_to: '' };
                this.activeDatePreset = '';
                funcTsInst?.clear(true);
                statusTsInst?.clear(true);
                branchTsInst?.clear(true);
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
                try { localStorage.setItem(D_LS_COLS, JSON.stringify(this.hiddenCols)); } catch (_) {}
            },
        };
    });
});
