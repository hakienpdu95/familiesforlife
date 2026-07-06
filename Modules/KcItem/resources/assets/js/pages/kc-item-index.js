/**
 * pages/kc-item-index.js
 * Alpine component + Tabulator + TomSelect cho index Kho tri thức.
 */

// ── HTML escape ───────────────────────────────────────────────────────────────

function kcItemEsc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ── Status badge ──────────────────────────────────────────────────────────────

function kcItemStatusBadge(status, label) {
    var map = {
        draft:          'badge-ghost',
        pending_review: 'badge-warning',
        approved:       'badge-success',
        rejected:       'badge-error',
        archived:       'badge-ghost',
    };
    var cls = map[status] || 'badge-ghost';
    return '<span class="badge ' + cls + ' badge-sm">' + kcItemEsc(label) + '</span>';
}

// ── Tabulator columns ─────────────────────────────────────────────────────────

function buildKcItemColumns(canDelete) {
    return [
        {
            title: 'Tiêu đề', field: 'title', minWidth: 280, sorter: 'string', frozen: true,
            formatter: function (cell) {
                var d = cell.getRow().getData();
                return '<div class="flex items-start gap-2.5">'
                    + '<div class="min-w-0">'
                    + '<a href="' + kcItemEsc(d.show_url) + '" class="font-semibold text-sm hover:text-primary transition-colors block truncate">' + kcItemEsc(d.title) + '</a>'
                    + '<p class="text-xs text-base-content/40 font-mono mt-0.5 truncate">' + kcItemEsc(d.slug) + '</p>'
                    + '</div></div>';
            },
        },
        {
            title: 'Loại', field: 'type', width: 130, hozAlign: 'center',
            formatter: function (cell) {
                var d = cell.getRow().getData();
                return '<span class="badge badge-outline badge-xs">' + kcItemEsc(d.type_label) + '</span>';
            },
        },
        {
            title: 'Trạng thái', field: 'status', width: 140, hozAlign: 'center',
            formatter: function (cell) {
                var d = cell.getRow().getData();
                return kcItemStatusBadge(d.status, d.status_label);
            },
        },
        {
            title: 'Danh mục', field: 'category', width: 160, sorter: false,
            formatter: function (cell) {
                var cat = cell.getValue();
                if (!cat) return '<span class="opacity-30 text-xs">—</span>';
                var color = cat.color_hex || '#6366f1';
                return '<div class="flex items-center gap-1.5">'
                    + '<span class="w-2 h-2 rounded-full shrink-0" style="background:' + kcItemEsc(color) + '"></span>'
                    + '<span class="text-sm truncate">' + kcItemEsc(cat.name) + '</span>'
                    + '</div>';
            },
        },
        {
            title: 'Phạm vi', field: 'visibility', width: 110, hozAlign: 'center',
            formatter: function (cell) {
                var v = cell.getValue();
                var map = { public: 'Công khai', internal: 'Nội bộ', restricted: 'Hạn chế', private: 'Riêng tư' };
                return '<span class="text-xs text-base-content/60">' + kcItemEsc(map[v] || v) + '</span>';
            },
        },
        {
            title: 'Lượt xem', field: 'view_count', width: 100, hozAlign: 'center', sorter: 'number',
            formatter: function (cell) {
                var v = cell.getValue();
                return v > 0
                    ? '<span class="badge badge-info badge-xs badge-soft">' + v + '</span>'
                    : '<span class="opacity-30 text-xs">0</span>';
            },
        },
        {
            title: 'Version', field: 'version', width: 80, hozAlign: 'center', sorter: 'number',
            formatter: function (cell) {
                return '<span class="badge badge-ghost badge-xs font-mono">v' + cell.getValue() + '</span>';
            },
        },
        {
            title: 'Người tạo', field: 'owner', width: 140, sorter: false,
            formatter: function (cell) {
                var o = cell.getValue();
                return o ? '<span class="text-sm">' + kcItemEsc(o.name) + '</span>'
                         : '<span class="opacity-30 text-xs">—</span>';
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

                html += '<a href="' + kcItemEsc(d.show_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-info" title="Xem">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>'
                    + '</a>';

                if (d.status === 'draft' || d.status === 'rejected') {
                    html += '<a href="' + kcItemEsc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">'
                        + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                        + '</a>';
                }

                if (canDelete) {
                    html += '<button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa"'
                        + ' data-url="' + kcItemEsc(d.delete_url) + '" data-title="' + kcItemEsc(d.title) + '"'
                        + ' onclick="window.kcItemDeleteConfirm(this.dataset.url,this.dataset.title)">'
                        + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>'
                        + '</button>';
                }

                html += '</div>';
                return html;
            },
        },
    ];
}

// ── Delete ────────────────────────────────────────────────────────────────────

var kcItemPendingDeleteUrl = null;

window.kcItemDeleteConfirm = function (url, title) {
    kcItemPendingDeleteUrl = url;
    var el = document.getElementById('kcItemDeleteTitle');
    if (el) el.textContent = '"' + title + '"';
    document.getElementById('kcItemDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('kcItemDeleteConfirmBtn');
    if (!btn) return;

    btn.addEventListener('click', async function () {
        if (!kcItemPendingDeleteUrl) return;

        var csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        this.disabled    = true;
        this.textContent = 'Đang xóa...';

        try {
            var res  = await fetch(kcItemPendingDeleteUrl, {
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    '_method=DELETE',
            });
            var data = await res.json().catch(function () { return {}; });

            if (res.ok) {
                document.getElementById('kcItemDeleteModal')?.close();
                window.kcItemTable?.replaceData();
            } else {
                alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled    = false;
            this.textContent = 'Xóa';
            kcItemPendingDeleteUrl = null;
        }
    });
});

// ── Alpine component ──────────────────────────────────────────────────────────

var KC_ITEM_LS_COLS = 'kc-item-list-hidden-cols';

document.addEventListener('alpine:init', function () {

    Alpine.data('kcItemListPage', function (serverData) {
        serverData   = serverData || {};
        var apiUrl      = serverData.apiUrl      || '';
        var statuses    = serverData.statuses    || [];
        var types       = serverData.types       || [];
        var categories  = serverData.categories  || [];
        var tagsApiUrl  = serverData.tagsApiUrl  || '';
        var canDelete   = serverData.canDelete   || false;

        var COLUMNS = buildKcItemColumns(canDelete);

        // Lib instances (non-reactive)
        var tableInst      = null;
        var statusTsInst   = null;
        var typeTsInst     = null;
        var categoryTsInst = null;
        var tagTsInst      = null;

        return {
            filters:    { search: '', status: '', type: '', category_id: '', tag_id: '' },
            hiddenCols: [],

            get toggleableCols() {
                return COLUMNS
                    .filter(function (c) { return c.field !== 'id' && c.field !== 'title'; })
                    .map(function (c) { return { field: c.field, title: c.title }; });
            },

            get hasFilters() {
                var f = this.filters;
                return !!(f.search || f.status || f.type || f.category_id || f.tag_id);
            },

            get activeChips() {
                var chips = [], f = this.filters;
                if (f.search)      chips.push({ key: 'search',   label: 'Tìm: ' + f.search });
                if (f.type) {
                    var t = types.find(function (x) { return x.value === f.type; });
                    chips.push({ key: 'type', label: 'Loại: ' + (t ? t.text : f.type) });
                }
                if (f.status) {
                    var s = statuses.find(function (x) { return x.value === f.status; });
                    chips.push({ key: 'status', label: s ? s.text : f.status });
                }
                if (f.category_id) {
                    var cat = categories.find(function (x) { return String(x.value) === String(f.category_id); });
                    chips.push({ key: 'category', label: 'Danh mục: ' + (cat ? cat.text : f.category_id) });
                }
                if (f.tag_id) {
                    chips.push({ key: 'tag', label: 'Tag: ' + f.tag_id });
                }
                return chips;
            },

            init: function () {
                this.loadState();
                try { this.hiddenCols = JSON.parse(localStorage.getItem(KC_ITEM_LS_COLS) || '[]'); } catch (_) {}
                var self = this;
                document.addEventListener('DOMContentLoaded', function () { self._setup(); }, { once: true });
            },

            _setup: function () {
                var self = this;

                // ── Tabulator ─────────────────────────────────────────────
                tableInst = new window.Tabulator('#kc-item-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams: function () {
                        var p = {}, f = self.filters;
                        if (f.search)      p.search      = f.search;
                        if (f.status)      p.status      = f.status;
                        if (f.type)        p.type        = f.type;
                        if (f.category_id) p.category_id = f.category_id;
                        if (f.tag_id)      p.tag_id      = f.tag_id;
                        return p;
                    },
                    ajaxResponse: function (_u, _p, res) { return res; },

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

                    columns:     COLUMNS,
                    placeholder: '<div class="py-16 text-center opacity-40">'
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'
                        + '<p class="text-sm">Chưa có tài liệu nào</p></div>',
                });

                window.kcItemTable = tableInst;
                self.hiddenCols.forEach(function (f) { tableInst.hideColumn(f); });

                // ── TomSelects ────────────────────────────────────────────
                typeTsInst = new window.TomSelect('#filter-type', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả loại...',
                    plugins:        ['clear_button'],
                    options:        types,
                    items:          self.filters.type ? [self.filters.type] : [],
                    onChange: function (val) {
                        self.filters.type = val || '';
                        self.saveState();
                        self.refresh();
                    },
                });

                statusTsInst = new window.TomSelect('#filter-status', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả trạng thái...',
                    plugins:        ['clear_button'],
                    options:        statuses,
                    items:          self.filters.status ? [self.filters.status] : [],
                    onChange: function (val) {
                        self.filters.status = val || '';
                        self.saveState();
                        self.refresh();
                    },
                });

                categoryTsInst = new window.TomSelect('#filter-category', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả danh mục...',
                    plugins:        ['clear_button'],
                    options:        categories.map(function (c) { return { value: String(c.value), text: c.text }; }),
                    items:          self.filters.category_id ? [String(self.filters.category_id)] : [],
                    onChange: function (val) {
                        self.filters.category_id = val || '';
                        self.saveState();
                        self.refresh();
                    },
                });

                var tagSelectEl = document.getElementById('filter-tag');
                if (tagSelectEl && tagsApiUrl) {
                    tagTsInst = new window.TomSelect('#filter-tag', {
                        dropdownParent: 'body',
                        placeholder:    'Lọc theo tag...',
                        plugins:        ['clear_button'],
                        valueField:     'value',
                        labelField:     'text',
                        searchField:    ['text'],
                        preload:        true,
                        load: function (query, callback) {
                            var url = tagsApiUrl + (query ? '?q=' + encodeURIComponent(query) : '');
                            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                                .then(function (r) { return r.json(); })
                                .then(function (data) { callback(Array.isArray(data) ? data : []); })
                                .catch(function () { callback([]); });
                        },
                        onChange: function (val) {
                            self.filters.tag_id = val || '';
                            self.saveState();
                            self.refresh();
                        },
                    });
                }
            },

            // ── State persistence ─────────────────────────────────────────
            loadState: function () {
                var p = new URLSearchParams(location.search);
                if (p.has('q'))    this.filters.search      = p.get('q');
                if (p.has('st'))   this.filters.status      = p.get('st');
                if (p.has('tp'))   this.filters.type        = p.get('tp');
                if (p.has('cat'))  this.filters.category_id = p.get('cat');
                if (p.has('tag'))  this.filters.tag_id      = p.get('tag');
            },

            saveState: function () {
                var p = new URLSearchParams(), f = this.filters;
                if (f.search)      p.set('q',   f.search);
                if (f.status)      p.set('st',  f.status);
                if (f.type)        p.set('tp',  f.type);
                if (f.category_id) p.set('cat', f.category_id);
                if (f.tag_id)      p.set('tag', f.tag_id);
                var qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            // ── Actions ───────────────────────────────────────────────────
            refresh:        function () { if (tableInst) tableInst.replaceData(); },
            onFilterChange: function () { this.saveState(); this.refresh(); },
            clearSearch:    function () { this.filters.search = ''; this.saveState(); this.refresh(); },

            removeChip: function (key) {
                var self = this;
                var actions = {
                    search:   function () { self.filters.search = ''; },
                    type:     function () { self.filters.type = '';        if (typeTsInst)     typeTsInst.clear(true); },
                    status:   function () { self.filters.status = '';      if (statusTsInst)   statusTsInst.clear(true); },
                    category: function () { self.filters.category_id = ''; if (categoryTsInst) categoryTsInst.clear(true); },
                    tag:      function () { self.filters.tag_id = '';      if (tagTsInst)      tagTsInst.clear(true); },
                };
                if (actions[key]) actions[key]();
                this.saveState();
                this.refresh();
            },

            reset: function () {
                this.filters = { search: '', status: '', type: '', category_id: '', tag_id: '' };
                if (typeTsInst)     typeTsInst.clear(true);
                if (statusTsInst)   statusTsInst.clear(true);
                if (categoryTsInst) categoryTsInst.clear(true);
                if (tagTsInst)      tagTsInst.clear(true);
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
                try { localStorage.setItem(KC_ITEM_LS_COLS, JSON.stringify(this.hiddenCols)); } catch (_) {}
            },
        };
    });
});
