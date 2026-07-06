/**
 * pages/kc-category-index.js
 * Alpine component + Tabulator + TomSelect cho index danh mục KC.
 *
 * Server data truyền vào qua x-data="kcCategoryListPage({{ Js::from([...]) }})".
 * Globals: window.Alpine, window.TomSelect, window.Tabulator
 */

// ── HTML escape ───────────────────────────────────────────────────────────────

function kcCatEsc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ── Tabulator columns ─────────────────────────────────────────────────────────

function buildKcCategoryColumns(canDelete) {
    return [
        {
            title: 'Tên danh mục', field: 'name', minWidth: 240, sorter: 'string', frozen: true,
            formatter: function (cell) {
                var d = cell.getRow().getData();
                var color = d.color_hex || '#6366f1';
                var icon  = d.icon
                    ? '<i class="' + kcCatEsc(d.icon) + ' w-3.5 h-3.5"></i>'
                    : '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>';

                return '<div class="flex items-center gap-2.5">'
                    + '<span class="w-7 h-7 rounded-lg flex items-center justify-center text-white shrink-0" style="background:' + kcCatEsc(color) + '">' + icon + '</span>'
                    + '<div>'
                    + '<a href="' + kcCatEsc(d.show_url) + '" class="font-semibold text-sm hover:text-primary transition-colors">' + kcCatEsc(d.name) + '</a>'
                    + '<p class="text-xs text-base-content/40 font-mono mt-0.5">' + kcCatEsc(d.slug) + '</p>'
                    + '</div></div>';
            },
        },
        {
            title: 'Danh mục cha', field: 'parent', width: 180, sorter: false,
            formatter: function (cell) {
                var p = cell.getValue();
                if (!p) return '<span class="badge badge-ghost badge-xs">Gốc</span>';
                return '<span class="text-sm">' + kcCatEsc(p.name) + '</span>';
            },
        },
        {
            title: 'Danh mục con', field: 'children_count', width: 120, hozAlign: 'center', sorter: 'number',
            formatter: function (cell) {
                var v = cell.getValue();
                return v > 0
                    ? '<span class="badge badge-info badge-sm badge-soft">' + v + '</span>'
                    : '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Thứ tự', field: 'sort_order', width: 90, hozAlign: 'center', sorter: 'number',
            formatter: function (cell) {
                return '<span class="badge badge-ghost badge-sm font-mono">' + cell.getValue() + '</span>';
            },
        },
        {
            title: 'Trạng thái', field: 'is_active', width: 130, hozAlign: 'center',
            formatter: function (cell) {
                return cell.getValue()
                    ? '<span class="badge badge-success badge-sm">Hiển thị</span>'
                    : '<span class="badge badge-ghost badge-sm">Ẩn</span>';
            },
        },
        {
            title: 'Ngày tạo', field: 'created_at', width: 110, hozAlign: 'center', sorter: 'string',
        },
        {
            title: 'Thao tác', field: 'id', width: 110, hozAlign: 'center', headerSort: false, frozen: true,
            formatter: function (cell) {
                var d   = cell.getRow().getData();
                var html = '<div class="flex items-center justify-center gap-1">';

                html += '<a href="' + kcCatEsc(d.show_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-info" title="Xem">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>'
                    + '</a>';

                html += '<a href="' + kcCatEsc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                    + '</a>';

                if (canDelete && d.can_delete) {
                    html += '<button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa"'
                        + ' data-url="' + kcCatEsc(d.delete_url) + '" data-name="' + kcCatEsc(d.name) + '"'
                        + ' onclick="window.kcCatDeleteConfirm(this.dataset.url,this.dataset.name)">'
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

var kcCatPendingDeleteUrl = null;

window.kcCatDeleteConfirm = function (url, name) {
    kcCatPendingDeleteUrl = url;
    var el = document.getElementById('kcCatDeleteName');
    if (el) el.textContent = '"' + name + '"';
    document.getElementById('kcCatDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('kcCatDeleteConfirmBtn');
    if (!btn) return;

    btn.addEventListener('click', async function () {
        if (!kcCatPendingDeleteUrl) return;

        var csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        this.disabled    = true;
        this.textContent = 'Đang xóa...';

        try {
            var res  = await fetch(kcCatPendingDeleteUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':     csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept':           'application/json',
                    'Content-Type':     'application/x-www-form-urlencoded',
                },
                body: '_method=DELETE',
            });

            var data = await res.json().catch(function () { return {}; });

            if (res.ok) {
                document.getElementById('kcCatDeleteModal')?.close();
                window.kcCategoryTable?.replaceData();
            } else {
                alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[kc-category] delete error', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled    = false;
            this.textContent = 'Xóa';
            kcCatPendingDeleteUrl = null;
        }
    });
});

// ── Alpine component ──────────────────────────────────────────────────────────

var KC_CAT_LS_COLS = 'kc-category-list-hidden-cols';

document.addEventListener('alpine:init', function () {

    Alpine.data('kcCategoryListPage', function (serverData) {
        serverData = serverData || {};
        var apiUrl    = serverData.apiUrl    || '';
        var statuses  = serverData.statuses  || [];
        var canDelete = serverData.canDelete || false;

        var COLUMNS = buildKcCategoryColumns(canDelete);

        // Lib instances (non-reactive)
        var tableInst    = null;
        var statusTsInst = null;

        return {
            filters:    { search: '', is_active: '' },
            hiddenCols: [],

            get toggleableCols() {
                return COLUMNS
                    .filter(function (c) { return c.field !== 'id' && c.field !== 'name'; })
                    .map(function (c) { return { field: c.field, title: c.title }; });
            },

            get hasFilters() {
                return !!(this.filters.search || this.filters.is_active !== '');
            },

            get activeChips() {
                var chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.is_active !== '') {
                    var st = statuses.find(function (s) { return s.value === f.is_active; });
                    chips.push({ key: 'status', label: st ? st.text : f.is_active });
                }
                return chips;
            },

            init: function () {
                this.loadState();
                try { this.hiddenCols = JSON.parse(localStorage.getItem(KC_CAT_LS_COLS) || '[]'); } catch (_) {}
                var self = this;
                document.addEventListener('DOMContentLoaded', function () { self._setup(); }, { once: true });
            },

            _setup: function () {
                var self = this;

                // ── Tabulator ─────────────────────────────────────────────
                tableInst = new window.Tabulator('#kc-category-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams: function () {
                        var p = {}, f = self.filters;
                        if (f.search)            p.search    = f.search;
                        if (f.is_active !== '')  p.is_active = f.is_active;
                        return p;
                    },
                    ajaxResponse: function (_u, _p, res) { return res; },

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'sort_order', dir: 'asc' }],

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

                    columns:     COLUMNS,
                    placeholder: '<div class="py-16 text-center opacity-40">'
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>'
                        + '<p class="text-sm">Chưa có danh mục nào</p></div>',
                });

                window.kcCategoryTable = tableInst;
                self.hiddenCols.forEach(function (f) { tableInst.hideColumn(f); });

                // ── Status TomSelect ──────────────────────────────────────
                statusTsInst = new window.TomSelect('#filter-status', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả trạng thái...',
                    plugins:        ['clear_button'],
                    options:        statuses,
                    items:          self.filters.is_active !== '' ? [self.filters.is_active] : [],
                    onChange: function (val) {
                        self.filters.is_active = val !== undefined ? val : '';
                        self.saveState();
                        self.refresh();
                    },
                });
            },

            // ── State persistence ─────────────────────────────────────────
            loadState: function () {
                var p = new URLSearchParams(location.search);
                if (p.has('q'))  this.filters.search    = p.get('q');
                if (p.has('st')) this.filters.is_active = p.get('st');
            },

            saveState: function () {
                var p = new URLSearchParams(), f = this.filters;
                if (f.search)           p.set('q',  f.search);
                if (f.is_active !== '') p.set('st', f.is_active);
                var qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            // ── Actions ───────────────────────────────────────────────────
            refresh:         function () { if (tableInst) tableInst.replaceData(); },
            onFilterChange:  function () { this.saveState(); this.refresh(); },
            clearSearch:     function () { this.filters.search = ''; this.saveState(); this.refresh(); },

            removeChip: function (key) {
                if (key === 'search') this.filters.search = '';
                if (key === 'status') { this.filters.is_active = ''; if (statusTsInst) statusTsInst.clear(true); }
                this.saveState();
                this.refresh();
            },

            reset: function () {
                this.filters = { search: '', is_active: '' };
                if (statusTsInst) statusTsInst.clear(true);
                history.replaceState(null, '', location.pathname);
                this.refresh();
            },

            toggleCol: function (field) {
                if (this.hiddenCols.includes(field)) {
                    this.hiddenCols = this.hiddenCols.filter(function (f) { return f !== field; });
                    if (tableInst) tableInst.showColumn(field);
                } else {
                    this.hiddenCols.push(field);
                    if (tableInst) tableInst.hideColumn(field);
                }
                try { localStorage.setItem(KC_CAT_LS_COLS, JSON.stringify(this.hiddenCols)); } catch (_) {}
            },
        };
    });
});
