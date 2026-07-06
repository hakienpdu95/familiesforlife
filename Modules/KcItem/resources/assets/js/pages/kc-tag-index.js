/**
 * pages/kc-tag-index.js
 * Alpine + Tabulator cho trang danh sách Tags KC.
 * pages/kc-tag-form.js — Alpine component cho form create/edit tag.
 */

// ── Escape helper ─────────────────────────────────────────────────────────────

function kcTagEsc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ── Slugify ───────────────────────────────────────────────────────────────────

function kcTagSlugify(text) {
    return text
        .toString().toLowerCase()
        .normalize('NFD').replace(/[̀-ͯ]/g, '')
        .replace(/[đĐ]/g, 'd')
        .replace(/[^a-z0-9\s-]/g, '')
        .trim().replace(/\s+/g, '-').replace(/-+/g, '-');
}

// ── Tag form Alpine component ─────────────────────────────────────────────────

document.addEventListener('alpine:init', function () {
    Alpine.data('kcTagForm', function () {
        return {
            slug:        '',
            color:       '#6366f1',
            namePreview: '',
            _slugEdited: false,

            init: function () {
                var slugEl = document.querySelector('[name="slug"]');
                if (slugEl && slugEl.value) {
                    this.slug        = slugEl.value;
                    this._slugEdited = true;
                }
                var colorEl = document.querySelector('[name="color_hex"]');
                if (colorEl && colorEl.value) {
                    this.color = colorEl.value;
                }
                var nameEl = document.querySelector('[name="name"]');
                if (nameEl && nameEl.value) {
                    this.namePreview = nameEl.value;
                }
            },

            onNameInput: function (val) {
                this.namePreview = val;
                if (!this._slugEdited) {
                    this.slug = kcTagSlugify(val);
                }
            },

            syncColor: function (val) {
                if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                    this.color = val;
                }
            },
        };
    });
});

// ── Delete ────────────────────────────────────────────────────────────────────

var kcTagPendingDeleteUrl = null;

window.kcTagDeleteConfirm = function (url, name) {
    kcTagPendingDeleteUrl = url;
    var el = document.getElementById('kcTagDeleteName');
    if (el) el.textContent = '"' + name + '"';
    document.getElementById('kcTagDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('kcTagDeleteConfirmBtn');
    if (!btn) return;

    btn.addEventListener('click', async function () {
        if (!kcTagPendingDeleteUrl) return;

        var csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        this.disabled    = true;
        this.textContent = 'Đang xóa...';

        try {
            var res  = await fetch(kcTagPendingDeleteUrl, {
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    '_method=DELETE',
            });
            var data = await res.json().catch(function () { return {}; });

            if (res.ok) {
                document.getElementById('kcTagDeleteModal')?.close();
                window.kcTagTable?.replaceData();
            } else {
                alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled    = false;
            this.textContent = 'Xóa';
            kcTagPendingDeleteUrl = null;
        }
    });
});

// ── Alpine component — list page ─────────────────────────────────────────────

document.addEventListener('alpine:init', function () {
    Alpine.data('kcTagListPage', function (serverData) {
        serverData = serverData || {};
        var apiUrl    = serverData.apiUrl    || '';
        var canDelete = serverData.canDelete || false;

        var tableInst = null;

        return {
            filters:  { search: '' },

            get hasFilters() { return !!this.filters.search; },

            init: function () {
                var self = this;
                document.addEventListener('DOMContentLoaded', function () { self._setup(); }, { once: true });
            },

            _setup: function () {
                var self = this;

                tableInst = new window.Tabulator('#kc-tag-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams: function () {
                        var p = {}, f = self.filters;
                        if (f.search) p.search = f.search;
                        return p;
                    },
                    ajaxResponse: function (_u, _p, res) { return res; },

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         50,
                    paginationSizeSelector: [25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'name', dir: 'asc' }],

                    layout: 'fitColumns',
                    height: '65vh',

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

                    columns: [
                        {
                            title: 'Tag', field: 'name', minWidth: 200, sorter: 'string',
                            formatter: function (cell) {
                                var d = cell.getRow().getData();
                                var color = d.color_hex || '#6366f1';
                                return '<div class="flex items-center gap-2">'
                                    + '<span class="w-3 h-3 rounded-full shrink-0" style="background:' + kcTagEsc(color) + '"></span>'
                                    + '<a href="' + kcTagEsc(d.edit_url) + '" class="font-medium text-sm hover:text-primary transition-colors">' + kcTagEsc(d.name) + '</a>'
                                    + '</div>';
                            },
                        },
                        {
                            title: 'Slug', field: 'slug', width: 200,
                            formatter: function (cell) {
                                return '<span class="font-mono text-xs text-base-content/50">' + kcTagEsc(cell.getValue()) + '</span>';
                            },
                        },
                        {
                            title: 'Màu', field: 'color_hex', width: 100, hozAlign: 'center', headerSort: false,
                            formatter: function (cell) {
                                var v = cell.getValue();
                                if (!v) return '<span class="opacity-30 text-xs">—</span>';
                                return '<span class="badge badge-sm font-mono" style="background:' + kcTagEsc(v) + ';color:#fff;">' + kcTagEsc(v) + '</span>';
                            },
                        },
                        {
                            title: 'Tài liệu', field: 'items_count', width: 90, hozAlign: 'center', sorter: 'number',
                            formatter: function (cell) {
                                var v = cell.getValue();
                                return v > 0
                                    ? '<span class="badge badge-info badge-xs badge-soft">' + v + '</span>'
                                    : '<span class="opacity-30 text-xs">0</span>';
                            },
                        },
                        {
                            title: 'Ngày tạo', field: 'created_at', width: 110, hozAlign: 'center', sorter: 'string',
                        },
                        {
                            title: 'Thao tác', field: 'id', width: 100, hozAlign: 'center', headerSort: false, frozen: true,
                            formatter: function (cell) {
                                var d   = cell.getRow().getData();
                                var html = '<div class="flex items-center justify-center gap-1">';

                                html += '<a href="' + kcTagEsc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">'
                                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                                    + '</a>';

                                if (canDelete) {
                                    html += '<button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa"'
                                        + ' data-url="' + kcTagEsc(d.delete_url) + '" data-name="' + kcTagEsc(d.name) + '"'
                                        + ' onclick="window.kcTagDeleteConfirm(this.dataset.url,this.dataset.name)">'
                                        + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>'
                                        + '</button>';
                                }

                                html += '</div>';
                                return html;
                            },
                        },
                    ],

                    placeholder: '<div class="py-16 text-center opacity-40"><p class="text-sm">Chưa có tag nào</p></div>',
                });

                window.kcTagTable = tableInst;
            },

            refresh:        function () { if (tableInst) tableInst.replaceData(); },
            onFilterChange: function () { this.refresh(); },
            clearSearch:    function () { this.filters.search = ''; this.refresh(); },
            reset:          function () { this.filters = { search: '' }; this.refresh(); },
        };
    });
});
