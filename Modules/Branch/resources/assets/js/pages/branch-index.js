/**
 * pages/branch-index.js
 * Alpine component + Tabulator + TomSelect + Flatpickr cho index.blade.php chi nhánh.
 *
 * Server data truyền vào qua x-data="branchListPage({{ Js::from([...]) }})".
 * Globals: window.Alpine, window.TomSelect, window.Tabulator,
 *          window.initDateRangePicker
 */

// ── Utilities ─────────────────────────────────────────────────────────────────

function bEsc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function bIsoDate(d) {
    return d.getFullYear() + '-'
        + String(d.getMonth() + 1).padStart(2, '0') + '-'
        + String(d.getDate()).padStart(2, '0');
}

function bDisplayDate(iso) {
    if (!iso) return '';
    const p = iso.split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
}

function bPresetRange(preset) {
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

function bTsSetPlaceholder(ts, text) {
    ts.settings.placeholder = text;
    if (ts.control_input) ts.control_input.setAttribute('placeholder', text);
}

// ── Tabulator columns factory ─────────────────────────────────────────────────

function buildBranchColumns(canDelete) {
    return [
        {
            title: 'Chi nhánh', field: 'name', minWidth: 240, sorter: 'string', frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                const indent = '&nbsp;'.repeat(d.depth * 4);
                return '<div>'
                    + indent
                    + (d.depth > 0 ? '<span class="text-base-content/25 mr-1">└</span>' : '')
                    + '<a href="' + bEsc(d.show_url) + '" class="font-semibold text-sm hover:text-primary transition-colors">' + bEsc(d.name) + '</a>'
                    + '<p class="text-xs text-base-content/40 font-mono mt-0.5">' + bEsc(d.code) + '</p>'
                    + '</div>';
            },
        },
        {
            title: 'Loại', field: 'type', width: 140, hozAlign: 'center', sorter: 'string',
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="badge badge-sm badge-soft ' + bEsc(d.type_badge) + '">' + bEsc(d.type_label) + '</span>';
            },
        },
        {
            title: 'Trạng thái', field: 'status', width: 120, hozAlign: 'center', sorter: 'string',
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="badge badge-sm badge-soft ' + bEsc(d.status_badge) + '">' + bEsc(d.status_label) + '</span>';
            },
        },
        {
            title: 'Chi nhánh cha', field: 'parent_name', minWidth: 160, sorter: 'string',
            formatter(cell) {
                return bEsc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Tỉnh/Thành', field: 'province_name', width: 160, sorter: 'string',
            formatter(cell) {
                return bEsc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Điện thoại', field: 'phone', width: 140, headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                const parts = [];
                if (d.phone) parts.push('<p class="text-sm">' + bEsc(d.phone) + '</p>');
                if (d.email) parts.push('<p class="text-xs text-base-content/50">' + bEsc(d.email) + '</p>');
                return parts.length ? parts.join('') : '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'CN con', field: 'children_count', width: 80, hozAlign: 'center', sorter: 'number',
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

                html += '<a href="' + bEsc(d.show_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-info" title="Xem">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>'
                    + '</a>';

                html += '<a href="' + bEsc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                    + '</a>';

                if (canDelete && d.can_delete) {
                    html += '<button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa"'
                        + ' data-url="' + bEsc(d.delete_url) + '" data-name="' + bEsc(d.name) + '"'
                        + ' onclick="window.branchDeleteConfirm(this.dataset.url,this.dataset.name)">'
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

let bPendingDeleteUrl = null;

window.branchDeleteConfirm = function (url, name) {
    bPendingDeleteUrl = url;
    const nameEl = document.getElementById('branchDeleteName');
    if (nameEl) nameEl.textContent = '"' + name + '"';
    document.getElementById('branchDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('branchDeleteConfirmBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async function () {
        if (!bPendingDeleteUrl) return;

        const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        this.disabled    = true;
        this.textContent = 'Đang xóa...';

        try {
            const res = await fetch(bPendingDeleteUrl, {
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
                document.getElementById('branchDeleteModal')?.close();
                window.branchTable?.replaceData();
            } else {
                alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[branch] delete failed', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled    = false;
            this.textContent = 'Xóa';
            bPendingDeleteUrl = null;
        }
    });
});

// ── Alpine component ──────────────────────────────────────────────────────────

const B_LS_COLS = 'branch-list-hidden-cols';

document.addEventListener('alpine:init', () => {

    Alpine.data('branchListPage', (serverData = {}) => {
        const {
            apiUrl        = '',
            wardsApi      = '',
            provinces     = [],
            types         = [],
            statuses      = [],
            parentOptions = [],
            canDelete     = false,
        } = serverData;

        const COLUMNS = buildBranchColumns(canDelete);

        // Lib instances (non-reactive)
        let tableInst    = null;
        let typeTsInst   = null;
        let statusTsInst = null;
        let provTsInst   = null;
        let dateFpInst   = null;
        let settingPreset = false;

        return {
            filters: {
                search: '', type: '', status: '', province_code: '',
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
                return !!(f.search || f.type || f.status || f.province_code || f.date_from);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.type) {
                    const t = types.find(x => x.value === f.type);
                    chips.push({ key: 'type', label: t ? t.text : f.type });
                }
                if (f.status) {
                    const s = statuses.find(x => x.value === f.status);
                    chips.push({ key: 'status', label: s ? s.text : f.status });
                }
                if (f.province_code) {
                    const p = provinces.find(x => x.province_code === f.province_code);
                    chips.push({ key: 'province', label: p ? p.name : f.province_code });
                }
                if (f.date_from && f.date_to)
                    chips.push({ key: 'date', label: bDisplayDate(f.date_from) + ' — ' + bDisplayDate(f.date_to) });
                return chips;
            },

            // ── Lifecycle ──────────────────────────────────────────────────
            init() {
                this.loadState();
                try { this.hiddenCols = JSON.parse(localStorage.getItem(B_LS_COLS) || '[]'); } catch (_) {}
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                // ── Tabulator ──────────────────────────────────────────────
                tableInst = new window.Tabulator('#branch-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)        p.search        = f.search;
                        if (f.type)          p.type          = f.type;
                        if (f.status)        p.status        = f.status;
                        if (f.province_code) p.province_code = f.province_code;
                        if (f.date_from)     p.date_from     = f.date_from;
                        if (f.date_to)       p.date_to       = f.date_to;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[branch] API error', error),

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
                        + '<p class="text-sm">Không có chi nhánh nào</p></div>',
                });

                window.branchTable = tableInst;
                self.hiddenCols.forEach(field => tableInst.hideColumn(field));

                // ── Type TomSelect ─────────────────────────────────────────
                typeTsInst = new window.TomSelect('#filter-type', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả loại...',
                    plugins:        ['clear_button'],
                    options:        types,
                    items:          self.filters.type ? [self.filters.type] : [],
                    onChange(val) {
                        self.filters.type = val || '';
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

                // ── Province TomSelect ─────────────────────────────────────
                provTsInst = new window.TomSelect('#filter-province', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả tỉnh/thành...',
                    maxOptions:     null,
                    searchField:    ['text'],
                    plugins:        ['clear_button'],
                    options:        provinces.map(p => ({ value: p.province_code, text: p.name })),
                    items:          self.filters.province_code ? [self.filters.province_code] : [],
                    onChange(val) {
                        self.filters.province_code = val || '';
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
                            self.filters.date_from = bIsoDate(dates[0]);
                            self.filters.date_to   = bIsoDate(dates[1]);
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
                if (p.has('q'))    this.filters.search        = p.get('q');
                if (p.has('typ'))  this.filters.type          = p.get('typ');
                if (p.has('st'))   this.filters.status        = p.get('st');
                if (p.has('prov')) this.filters.province_code = p.get('prov');
                if (p.has('from')) this.filters.date_from     = p.get('from');
                if (p.has('to'))   this.filters.date_to       = p.get('to');
                if (p.has('dpre')) this.activeDatePreset      = p.get('dpre');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)        p.set('q',    f.search);
                if (f.type)          p.set('typ',  f.type);
                if (f.status)        p.set('st',   f.status);
                if (f.province_code) p.set('prov', f.province_code);
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
                const range = bPresetRange(preset);
                if (!range[0]) return;
                settingPreset         = true;
                this.activeDatePreset = preset;
                this.filters.date_from = bIsoDate(range[0]);
                this.filters.date_to   = bIsoDate(range[1]);
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
                if (key === 'type')   { this.filters.type = '';          typeTsInst?.clear(true); }
                if (key === 'status') { this.filters.status = '';        statusTsInst?.clear(true); }
                if (key === 'province') { this.filters.province_code = ''; provTsInst?.clear(true); }
                if (key === 'date')   { this.clearDate(); return; }
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', type: '', status: '', province_code: '', date_from: '', date_to: '' };
                this.activeDatePreset = '';
                typeTsInst?.clear(true);
                statusTsInst?.clear(true);
                provTsInst?.clear(true);
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
                try { localStorage.setItem(B_LS_COLS, JSON.stringify(this.hiddenCols)); } catch (_) {}
            },
        };
    });
});
