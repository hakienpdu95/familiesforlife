/**
 * pages/org-chart-index.js
 * Alpine component + Tabulator + TomSelect cho trang danh sách cấu hình sơ đồ tổ chức.
 */

// ── Utilities ─────────────────────────────────────────────────────────────────

function occEsc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ── Delete handler ────────────────────────────────────────────────────────────

window.occConfirmDelete = function (url, name) {
    const modal     = document.getElementById('occDeleteModal');
    const nameEl    = document.getElementById('occDeleteName');
    const confirmBtn = document.getElementById('occDeleteConfirmBtn');
    if (!modal || !nameEl || !confirmBtn) return;

    nameEl.textContent = name;
    modal.showModal();

    const handler = function () {
        confirmBtn.removeEventListener('click', handler);
        modal.close();
        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN':    document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                'Accept':          'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then(r => r.json())
            .then(() => { if (window._occTableInst) window._occTableInst.replaceData(); })
            .catch(() => alert('Xóa thất bại.'));
    };
    confirmBtn.addEventListener('click', handler);
};

// ── Alpine component ──────────────────────────────────────────────────────────

document.addEventListener('alpine:init', function () {
    var tableInst      = null;
    var viewTypeTsInst = null;
    var groupByTsInst  = null;
    var LS_COLS        = 'occ-list-hidden-cols';

    Alpine.data('orgChartListPage', function (opts) {
        var API_URL    = opts.apiUrl;
        var VIEW_TYPES = opts.viewTypes || [];
        var GROUP_BYS  = opts.groupBys  || [];
        var CAN_DELETE = opts.canDelete  || false;

        // Tabulator columns
        var COLUMNS = [
            {
                title: 'Tên cấu hình', field: 'name', minWidth: 240, sorter: 'string', frozen: true,
                formatter: function (cell) {
                    var d = cell.getRow().getData();
                    var badge = d.is_default
                        ? ' <span class="badge badge-primary badge-xs ml-1">Mặc định</span>'
                        : '';
                    return '<a href="' + occEsc(d.show_url) + '" class="font-semibold text-sm hover:text-primary transition-colors">'
                        + occEsc(d.name) + '</a>' + badge;
                },
            },
            {
                title: 'Kiểu view', field: 'view_type', width: 150, hozAlign: 'center',
                formatter: function (cell) {
                    var d = cell.getRow().getData();
                    return '<span class="badge badge-sm badge-soft badge-info">' + occEsc(d.view_type_label) + '</span>';
                },
            },
            {
                title: 'Nhóm theo', field: 'group_by', width: 140, hozAlign: 'center',
                formatter: function (cell) {
                    var d = cell.getRow().getData();
                    return '<span class="badge badge-sm badge-soft badge-ghost">' + occEsc(d.group_by_label) + '</span>';
                },
            },
            {
                title: 'Chi nhánh', field: 'scope_branch', minWidth: 160,
                formatter: function (cell) {
                    return occEsc(cell.getValue()) || '<span class="text-base-content/25 text-xs">Toàn tổ chức</span>';
                },
            },
            {
                title: 'Số cấp', field: 'max_depth', width: 90, hozAlign: 'center', sorter: 'number',
                formatter: function (cell) {
                    return '<span class="font-mono text-sm">' + occEsc(cell.getValue()) + '</span>';
                },
            },
            {
                title: 'Tạo lúc', field: 'created_at', width: 120, hozAlign: 'center',
                formatter: function (cell) {
                    return '<span class="text-xs text-base-content/50">' + occEsc(cell.getValue()) + '</span>';
                },
            },
            {
                title: 'Thao tác', field: 'id', width: 120, hozAlign: 'center',
                headerSort: false, frozen: true,
                formatter: function (cell) {
                    var d = cell.getRow().getData();
                    var btns = '<div class="flex gap-1 justify-center">'
                        + '<a href="' + occEsc(d.show_url) + '" class="btn btn-ghost btn-xs btn-square" title="Xem sơ đồ">'
                        + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>'
                        + '</a>'
                        + '<a href="' + occEsc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square" title="Chỉnh sửa">'
                        + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                        + '</a>';

                    if (CAN_DELETE) {
                        btns += '<button class="btn btn-ghost btn-xs btn-square text-error" title="Xóa"'
                            + ' data-url="' + occEsc(d.delete_url) + '" data-name="' + occEsc(d.name) + '"'
                            + ' onclick="window.occConfirmDelete(this.dataset.url, this.dataset.name)">'
                            + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>'
                            + '</button>';
                    }

                    return btns + '</div>';
                },
            },
        ];

        return {
            filters: { search: '', view_type: '', group_by: '' },
            hiddenCols: [],
            activeDatePreset: '',

            get hasFilters() {
                var f = this.filters;
                return f.search !== '' || f.view_type !== '' || f.group_by !== '';
            },

            get activeChips() {
                var chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.view_type) {
                    var vt = VIEW_TYPES.find(function (v) { return v.value === f.view_type; });
                    chips.push({ key: 'view_type', label: 'Kiểu: ' + (vt ? vt.text : f.view_type) });
                }
                if (f.group_by) {
                    var gb = GROUP_BYS.find(function (g) { return g.value === f.group_by; });
                    chips.push({ key: 'group_by', label: 'Nhóm: ' + (gb ? gb.text : f.group_by) });
                }
                return chips;
            },

            get toggleableCols() {
                return COLUMNS.filter(function (c) { return !c.frozen && c.field !== 'id'; })
                    .map(function (c) { return { field: c.field, title: c.title }; });
            },

            init: function () {
                var self = this;
                self.loadState();
                try { self.hiddenCols = JSON.parse(localStorage.getItem(LS_COLS) || '[]'); } catch (e) {}
                document.addEventListener('DOMContentLoaded', function () { self._setup(); }, { once: true });
            },

            _setup: function () {
                var self = this;

                // TomSelect — view type
                viewTypeTsInst = new window.TomSelect('#occ-view-type', {
                    dropdownParent: 'body', placeholder: 'Tất cả kiểu...', maxOptions: null,
                    plugins: ['clear_button'],
                    options: VIEW_TYPES.map(function (v) { return { value: v.value, text: v.text }; }),
                    items: self.filters.view_type ? [self.filters.view_type] : [],
                    onChange: function (val) {
                        self.filters.view_type = val || '';
                        self.saveState(); self.refresh();
                    },
                });

                // TomSelect — group by
                groupByTsInst = new window.TomSelect('#occ-group-by', {
                    dropdownParent: 'body', placeholder: 'Tất cả nhóm...', maxOptions: null,
                    plugins: ['clear_button'],
                    options: GROUP_BYS.map(function (g) { return { value: g.value, text: g.text }; }),
                    items: self.filters.group_by ? [self.filters.group_by] : [],
                    onChange: function (val) {
                        self.filters.group_by = val || '';
                        self.saveState(); self.refresh();
                    },
                });

                // Tabulator
                tableInst = new window.Tabulator('#occ-table', {
                    ajaxURL:    API_URL,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams: function () {
                        var p = {}, f = self.filters;
                        if (f.search)     p.search     = f.search;
                        if (f.view_type)  p.view_type  = f.view_type;
                        if (f.group_by)   p.group_by   = f.group_by;
                        return p;
                    },
                    ajaxResponse: function (_url, _params, res) { return res; },
                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'is_default', dir: 'desc' }],
                    layout:                 'fitColumns',
                    responsiveLayout:       'collapse',
                    movableColumns:         true,
                    height:                 '65vh',
                    locale:                 'vi-VN',
                    langs: {
                        'vi-VN': {
                            pagination: {
                                page_size: 'Dòng/trang', page_title: 'Trang',
                                first: '«', last: '»', prev: '‹', next: '›',
                                counter: { showing: '', of: 'trong', rows: 'dòng', pages: 'trang' },
                            },
                        },
                    },
                    columns: COLUMNS,
                });

                window._occTableInst = tableInst;

                self.hiddenCols.forEach(function (field) { tableInst.hideColumn(field); });
            },

            loadState: function () {
                var p = new URLSearchParams(location.search);
                if (p.has('q'))  this.filters.search    = p.get('q');
                if (p.has('vt')) this.filters.view_type = p.get('vt');
                if (p.has('gb')) this.filters.group_by  = p.get('gb');
            },

            saveState: function () {
                var p = new URLSearchParams(), f = this.filters;
                if (f.search)    p.set('q',  f.search);
                if (f.view_type) p.set('vt', f.view_type);
                if (f.group_by)  p.set('gb', f.group_by);
                var qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh: function () { if (tableInst) tableInst.replaceData(); },

            onFilterChange: function () { this.saveState(); this.refresh(); },

            clearSearch: function () {
                this.filters.search = '';
                document.getElementById('occ-search').value = '';
                this.saveState(); this.refresh();
            },

            reset: function () {
                this.filters = { search: '', view_type: '', group_by: '' };
                document.getElementById('occ-search').value = '';
                if (viewTypeTsInst) viewTypeTsInst.clear(true);
                if (groupByTsInst)  groupByTsInst.clear(true);
                history.replaceState(null, '', location.pathname);
                this.refresh();
            },

            removeChip: function (key) {
                var self = this;
                if (key === 'search')    { self.filters.search = ''; document.getElementById('occ-search').value = ''; }
                if (key === 'view_type') { self.filters.view_type = ''; if (viewTypeTsInst) viewTypeTsInst.clear(true); }
                if (key === 'group_by')  { self.filters.group_by  = ''; if (groupByTsInst)  groupByTsInst.clear(true);  }
                self.saveState(); self.refresh();
            },

            toggleCol: function (field) {
                if (this.hiddenCols.includes(field)) {
                    this.hiddenCols = this.hiddenCols.filter(function (f) { return f !== field; });
                    if (tableInst) tableInst.showColumn(field);
                } else {
                    this.hiddenCols.push(field);
                    if (tableInst) tableInst.hideColumn(field);
                }
                try { localStorage.setItem(LS_COLS, JSON.stringify(this.hiddenCols)); } catch (e) {}
            },
        };
    });
});
