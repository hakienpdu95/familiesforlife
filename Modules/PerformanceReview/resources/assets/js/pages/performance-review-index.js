/**
 * pages/performance-review-index.js
 * Alpine + Tabulator + TomSelect + Flatpickr cho trang danh sách đánh giá hiệu suất.
 */

// ── Utilities ─────────────────────────────────────────────────────────────────

function prEsc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function prIsoDate(d) {
    return d.getFullYear() + '-'
        + String(d.getMonth() + 1).padStart(2, '0') + '-'
        + String(d.getDate()).padStart(2, '0');
}

function prDisplayDate(iso) {
    if (!iso) return '';
    const p = iso.split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
}

function prPresetRange(preset) {
    const now = new Date(), y = now.getFullYear(), m = now.getMonth(), d = now.getDate();
    if (preset === 'today') return [new Date(y, m, d), new Date(y, m, d)];
    if (preset === 'month') return [new Date(y, m, 1), new Date(y, m + 1, 0)];
    if (preset === 'year')  return [new Date(y, 0, 1), new Date(y, 11, 31)];
    return [null, null];
}

// ── Status badge map ──────────────────────────────────────────────────────────

const PR_BADGE = {
    draft:        'badge-ghost',
    submitted:    'badge-info',
    acknowledged: 'badge-warning',
    finalized:    'badge-success',
    cancelled:    'badge-error',
};

const RATING_BADGE = {
    excellent:     'badge-success',
    good:          'badge-info',
    average:       'badge-warning',
    below_average: 'badge-error',
    poor:          'badge-error',
};

// ── Tabulator columns ─────────────────────────────────────────────────────────

function buildPrColumns(canDelete) {
    return [
        {
            title: 'Nhân viên', field: 'employee_name', minWidth: 220, sorter: 'string', frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<a href="' + prEsc(d.show_url) + '" class="font-semibold text-sm hover:text-primary transition-colors">'
                    + prEsc(d.employee_name) + '</a>'
                    + '<p class="text-xs font-mono text-base-content/40">' + prEsc(d.employee_code) + '</p>'
                    + (d.employee_dept ? '<p class="text-xs text-base-content/30">' + prEsc(d.employee_dept) + '</p>' : '');
            },
        },
        {
            title: 'Kỳ', field: 'period', width: 110, hozAlign: 'center',
            formatter(cell) {
                return '<span class="font-mono text-xs">' + prEsc(cell.getValue()) + '</span>';
            },
        },
        {
            title: 'Trạng thái', field: 'status', width: 140, hozAlign: 'center',
            formatter(cell) {
                const d = cell.getRow().getData();
                const cls = PR_BADGE[d.status] || 'badge-ghost';
                return '<span class="badge badge-sm badge-soft ' + cls + '">' + prEsc(d.status_label) + '</span>';
            },
        },
        {
            title: 'Điểm', field: 'overall_score', width: 80, hozAlign: 'center',
            formatter(cell) {
                const v = cell.getValue();
                if (v == null) return '<span class="text-base-content/25 text-xs">—</span>';
                return '<span class="font-bold text-primary">' + parseFloat(v).toFixed(1) + '</span>';
            },
        },
        {
            title: 'Xếp loại', field: 'overall_rating', width: 140, hozAlign: 'center',
            formatter(cell) {
                const d = cell.getRow().getData();
                if (!d.overall_rating) return '<span class="text-base-content/25 text-xs">—</span>';
                const cls = RATING_BADGE[d.overall_rating] || 'badge-ghost';
                return '<span class="badge badge-sm ' + cls + '">' + prEsc(d.overall_rating_label) + '</span>';
            },
        },
        {
            title: 'Người đánh giá', field: 'reviewer_name', minWidth: 160,
            formatter(cell) {
                const d = cell.getRow().getData();
                return prEsc(d.reviewer_name) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Mẫu', field: 'template_name', minWidth: 160,
            formatter(cell) {
                return prEsc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Chức danh (snapshot)', field: 'snap_job_title', minWidth: 160,
            formatter(cell) {
                return prEsc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Ngày tạo', field: 'created_at', width: 110, hozAlign: 'center',
            formatter(cell) {
                return prEsc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Thao tác', field: 'id', width: 100, hozAlign: 'center', headerSort: false, frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<div class="flex items-center justify-center gap-1">';
                html += '<a href="' + prEsc(d.show_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-info" title="Xem">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>';
                html += '<a href="' + prEsc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>';
                if (canDelete) {
                    html += '<button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa"'
                        + ' data-url="' + prEsc(d.delete_url) + '" data-name="' + prEsc(d.employee_name) + '"'
                        + ' onclick="window.prDeleteConfirm(this.dataset.url,this.dataset.name)">'
                        + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>';
                }
                html += '</div>';
                return html;
            },
        },
    ];
}

// ── AJAX Delete ───────────────────────────────────────────────────────────────

let prPendingDeleteUrl = null;

window.prDeleteConfirm = function (url, name) {
    prPendingDeleteUrl = url;
    const nameEl = document.getElementById('prDeleteName');
    if (nameEl) nameEl.textContent = '"' + name + '"';
    document.getElementById('prDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('prDeleteConfirmBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async function () {
        if (!prPendingDeleteUrl) return;

        const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        this.disabled    = true;
        this.textContent = 'Đang xóa...';

        try {
            const res = await fetch(prPendingDeleteUrl, {
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
                document.getElementById('prDeleteModal')?.close();
                window.prTable?.replaceData();
            } else {
                alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[pr] delete failed', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled    = false;
            this.textContent = 'Xóa';
            prPendingDeleteUrl = null;
        }
    });
});

// ── Alpine component ──────────────────────────────────────────────────────────

const PR_LS_COLS = 'pr-list-hidden-cols';

document.addEventListener('alpine:init', () => {

    Alpine.data('performanceReviewListPage', (serverData = {}) => {
        const {
            apiUrl    = '',
            statuses  = [],
            templates = [],
            canDelete = false,
        } = serverData;

        const COLUMNS = buildPrColumns(canDelete);

        let tableInst     = null;
        let statusTsInst  = null;
        let tplTsInst     = null;
        let dateFpInst    = null;
        let settingPreset = false;

        return {
            filters: {
                search: '', status: '', template_id: '',
                period: '', date_from: '', date_to: '',
            },
            activeDatePreset: '',
            hiddenCols: [],

            get toggleableCols() {
                return COLUMNS
                    .filter(c => c.field !== 'id' && c.field !== 'employee_name')
                    .map(c => ({ field: c.field, title: c.title }));
            },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.status || f.template_id || f.period || f.date_from);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.status) {
                    const s = statuses.find(x => x.value === f.status);
                    chips.push({ key: 'status', label: s ? s.text : f.status });
                }
                if (f.template_id) {
                    const t = templates.find(x => String(x.value) === String(f.template_id));
                    chips.push({ key: 'template', label: 'Mẫu: ' + (t ? t.text : f.template_id) });
                }
                if (f.period) chips.push({ key: 'period', label: 'Kỳ: ' + f.period });
                if (f.date_from && f.date_to)
                    chips.push({ key: 'date', label: prDisplayDate(f.date_from) + ' — ' + prDisplayDate(f.date_to) });
                return chips;
            },

            // ── Lifecycle ────────────────────────────────────────────────────
            init() {
                this.loadState();
                try { this.hiddenCols = JSON.parse(localStorage.getItem(PR_LS_COLS) || '[]'); } catch (_) {}
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#pr-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)      p.search      = f.search;
                        if (f.status)      p.status      = f.status;
                        if (f.template_id) p.template_id = f.template_id;
                        if (f.period)      p.period      = f.period;
                        if (f.date_from)   p.date_from   = f.date_from;
                        if (f.date_to)     p.date_to     = f.date_to;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[pr] API error', error),

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>'
                        + '<p class="text-sm">Không có đánh giá nào</p></div>',
                });

                window.prTable = tableInst;
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

                // Template TomSelect
                tplTsInst = new window.TomSelect('#filter-template', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả mẫu...',
                    maxOptions:     null,
                    searchField:    ['text'],
                    plugins:        ['clear_button'],
                    options:        templates,
                    valueField:     'value',
                    labelField:     'text',
                    items:          self.filters.template_id ? [self.filters.template_id] : [],
                    onChange(val) {
                        self.filters.template_id = val || '';
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
                            self.filters.date_from = prIsoDate(dates[0]);
                            self.filters.date_to   = prIsoDate(dates[1]);
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

            // ── URL state ────────────────────────────────────────────────────
            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))    this.filters.search      = p.get('q');
                if (p.has('st'))   this.filters.status      = p.get('st');
                if (p.has('tpl'))  this.filters.template_id = p.get('tpl');
                if (p.has('per'))  this.filters.period      = p.get('per');
                if (p.has('from')) this.filters.date_from   = p.get('from');
                if (p.has('to'))   this.filters.date_to     = p.get('to');
                if (p.has('dpre')) this.activeDatePreset    = p.get('dpre');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)      p.set('q',    f.search);
                if (f.status)      p.set('st',   f.status);
                if (f.template_id) p.set('tpl',  f.template_id);
                if (f.period)      p.set('per',  f.period);
                if (f.date_from)   p.set('from', f.date_from);
                if (f.date_to)     p.set('to',   f.date_to);
                if (this.activeDatePreset) p.set('dpre', this.activeDatePreset);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            // ── Actions ──────────────────────────────────────────────────────
            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            setDatePreset(preset) {
                const range = prPresetRange(preset);
                if (!range[0]) return;
                settingPreset         = true;
                this.activeDatePreset = preset;
                this.filters.date_from = prIsoDate(range[0]);
                this.filters.date_to   = prIsoDate(range[1]);
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
                if (key === 'status') { this.filters.status = ''; statusTsInst?.clear(true); }
                if (key === 'template') { this.filters.template_id = ''; tplTsInst?.clear(true); }
                if (key === 'period')   this.filters.period = '';
                if (key === 'date')   { this.clearDate(); return; }
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', status: '', template_id: '', period: '', date_from: '', date_to: '' };
                this.activeDatePreset = '';
                statusTsInst?.clear(true);
                tplTsInst?.clear(true);
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
                try { localStorage.setItem(PR_LS_COLS, JSON.stringify(this.hiddenCols)); } catch (_) {}
            },
        };
    });
});
