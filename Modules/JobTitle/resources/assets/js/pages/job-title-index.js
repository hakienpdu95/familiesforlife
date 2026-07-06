/**
 * pages/job-title-index.js
 * Alpine component + Tabulator + TomSelect cho index.blade.php chức danh.
 *
 * Server data truyền vào qua x-data="jobTitleListPage({{ Js::from([...]) }})".
 * Globals: window.Alpine, window.TomSelect, window.Tabulator
 */

// ── Utilities ─────────────────────────────────────────────────────────────────

function jtEsc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ── Tabulator columns factory ─────────────────────────────────────────────────

function buildJobTitleColumns(canDelete) {
    return [
        {
            title: 'Chức danh', field: 'name', minWidth: 220, sorter: 'string', frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<div>'
                    + '<a href="' + jtEsc(d.show_url) + '" class="font-semibold text-sm hover:text-primary transition-colors">' + jtEsc(d.name) + '</a>'
                    + '<p class="text-xs text-base-content/40 font-mono mt-0.5">' + jtEsc(d.code) + '</p>'
                    + '</div>';
            },
        },
        {
            title: 'Nhóm', field: 'category', width: 140, hozAlign: 'center', sorter: 'string',
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="badge badge-sm badge-soft ' + jtEsc(d.category_badge) + '">' + jtEsc(d.category_label) + '</span>';
            },
        },
        {
            title: 'Cấp bậc', field: 'level', width: 110, hozAlign: 'center', sorter: 'number',
            formatter(cell) {
                const v = cell.getValue();
                return '<div class="flex items-center justify-center gap-1.5">'
                    + '<span class="badge badge-ghost badge-sm font-mono font-bold">' + v + '</span>'
                    + '<div class="w-12 h-1.5 bg-base-200 rounded-full overflow-hidden">'
                    + '<div class="h-full bg-primary rounded-full" style="width:' + Math.round(v / 20 * 100) + '%"></div>'
                    + '</div>'
                    + '</div>';
            },
        },
        {
            title: 'Trạng thái', field: 'is_active', width: 120, hozAlign: 'center',
            formatter(cell) {
                return cell.getValue()
                    ? '<span class="badge badge-success badge-sm">Đang dùng</span>'
                    : '<span class="badge badge-ghost badge-sm">Vô hiệu</span>';
            },
        },
        {
            title: 'Hệ thống', field: 'is_system', width: 100, hozAlign: 'center', headerSort: false,
            formatter(cell) {
                return cell.getValue()
                    ? '<span class="badge badge-info badge-sm badge-soft">Hệ thống</span>'
                    : '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Khóa', field: 'is_locked', width: 80, hozAlign: 'center', headerSort: false,
            formatter(cell) {
                return cell.getValue()
                    ? '<svg class="w-4 h-4 text-warning mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>'
                    : '<span class="text-base-content/25 text-xs">—</span>';
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

                html += '<a href="' + jtEsc(d.show_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-info" title="Xem">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>'
                    + '</a>';

                if (d.can_edit) {
                    html += '<a href="' + jtEsc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">'
                        + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                        + '</a>';
                }

                if (canDelete && d.can_delete) {
                    html += '<button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa"'
                        + ' data-url="' + jtEsc(d.delete_url) + '" data-name="' + jtEsc(d.name) + '"'
                        + ' onclick="window.jobTitleDeleteConfirm(this.dataset.url,this.dataset.name)">'
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

let jtPendingDeleteUrl = null;

window.jobTitleDeleteConfirm = function (url, name) {
    jtPendingDeleteUrl = url;
    const nameEl = document.getElementById('jobTitleDeleteName');
    if (nameEl) nameEl.textContent = '"' + name + '"';
    document.getElementById('jobTitleDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('jobTitleDeleteConfirmBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async function () {
        if (!jtPendingDeleteUrl) return;

        const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        this.disabled    = true;
        this.textContent = 'Đang xóa...';

        try {
            const res = await fetch(jtPendingDeleteUrl, {
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
                document.getElementById('jobTitleDeleteModal')?.close();
                window.jobTitleTable?.replaceData();
            } else {
                alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[job-title] delete failed', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled    = false;
            this.textContent = 'Xóa';
            jtPendingDeleteUrl = null;
        }
    });
});

// ── Alpine component ──────────────────────────────────────────────────────────

const JT_LS_COLS = 'job-title-list-hidden-cols';

document.addEventListener('alpine:init', () => {

    Alpine.data('jobTitleListPage', (serverData = {}) => {
        const {
            apiUrl     = '',
            categories = [],
            statuses   = [],
            canDelete  = false,
        } = serverData;

        const COLUMNS = buildJobTitleColumns(canDelete);

        // Lib instances (non-reactive)
        let tableInst       = null;
        let categoryTsInst  = null;
        let statusTsInst    = null;

        return {
            filters: {
                search: '', category: '', is_active: '',
            },
            hiddenCols: [],

            get toggleableCols() {
                return COLUMNS
                    .filter(c => c.field !== 'id' && c.field !== 'name')
                    .map(c => ({ field: c.field, title: c.title }));
            },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.category || f.is_active);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.category) {
                    const c = categories.find(x => x.value === f.category);
                    chips.push({ key: 'category', label: c ? c.text : f.category });
                }
                if (f.is_active !== '') {
                    const s = statuses.find(x => x.value === f.is_active);
                    chips.push({ key: 'status', label: s ? s.text : f.is_active });
                }
                return chips;
            },

            // ── Lifecycle ──────────────────────────────────────────────────
            init() {
                this.loadState();
                try { this.hiddenCols = JSON.parse(localStorage.getItem(JT_LS_COLS) || '[]'); } catch (_) {}
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                // ── Tabulator ──────────────────────────────────────────────
                tableInst = new window.Tabulator('#job-title-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)    p.search    = f.search;
                        if (f.category)  p.category  = f.category;
                        if (f.is_active !== '') p.is_active = f.is_active;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[job-title] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'level', dir: 'asc' }],

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'
                        + '<p class="text-sm">Không có chức danh nào</p></div>',
                });

                window.jobTitleTable = tableInst;
                self.hiddenCols.forEach(field => tableInst.hideColumn(field));

                // ── Category TomSelect ─────────────────────────────────────
                categoryTsInst = new window.TomSelect('#filter-category', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả nhóm...',
                    plugins:        ['clear_button'],
                    options:        categories,
                    items:          self.filters.category ? [self.filters.category] : [],
                    onChange(val) {
                        self.filters.category = val || '';
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
                    items:          self.filters.is_active !== '' ? [self.filters.is_active] : [],
                    onChange(val) {
                        self.filters.is_active = val ?? '';
                        self.saveState();
                        self.refresh();
                    },
                });
            },

            // ── URL state persistence ──────────────────────────────────────
            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))   this.filters.search    = p.get('q');
                if (p.has('cat')) this.filters.category  = p.get('cat');
                if (p.has('st'))  this.filters.is_active = p.get('st');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)       p.set('q',   f.search);
                if (f.category)     p.set('cat', f.category);
                if (f.is_active !== '') p.set('st',  f.is_active);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            // ── Actions ───────────────────────────────────────────────────
            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            removeChip(key) {
                if (key === 'search')   this.filters.search = '';
                if (key === 'category') { this.filters.category = '';  categoryTsInst?.clear(true); }
                if (key === 'status')   { this.filters.is_active = ''; statusTsInst?.clear(true); }
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', category: '', is_active: '' };
                categoryTsInst?.clear(true);
                statusTsInst?.clear(true);
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
                try { localStorage.setItem(JT_LS_COLS, JSON.stringify(this.hiddenCols)); } catch (_) {}
            },
        };
    });
});
