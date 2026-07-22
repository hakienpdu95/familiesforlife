/**
 * pages/ocop-product-index.js
 * Alpine component + Tabulator cho dashboard/ocop/products — cùng pattern
 * Modules/Organization/resources/assets/js/pages/organization-index.js (tham chiếu theo
 * yêu cầu): remote pagination/sort/filter qua API, xoá qua AJAX + modal xác nhận.
 *
 * Server data truyền vào qua x-data="ocopProductListPage({{ Js::from([...]) }})".
 */

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const COLUMNS = [
    {
        title: 'Ảnh', field: 'image_url', width: 70, headerSort: false,
        formatter(cell) {
            const v = cell.getValue();
            return v
                ? '<img src="' + esc(v) + '" alt="" class="h-10 w-10 rounded border border-base-300 object-cover">'
                : '<div class="h-10 w-10 rounded border border-base-300 bg-base-200"></div>';
        },
    },
    {
        title: 'Sản phẩm', field: 'name', minWidth: 220, sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<span class="font-medium text-sm">' + esc(d.name) + '</span>';
            if (d.is_featured) html += ' <span class="badge badge-warning badge-xs">Nổi bật</span>';
            return html;
        },
    },
    {
        title: 'Danh mục', field: 'category', minWidth: 160, headerSort: false,
        formatter(cell) {
            return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
        },
    },
    {
        title: 'Hạng sao', field: 'star_rating', width: 110, hozAlign: 'center', sorter: 'number',
        formatter(cell) { return cell.getValue() + ' ★'; },
    },
    {
        title: 'Tỉnh/thành', field: 'province_name', minWidth: 150, headerSort: false,
        formatter(cell) {
            return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
        },
    },
    {
        title: 'Trạng thái', field: 'status_value', width: 130, hozAlign: 'center', sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<span class="badge badge-sm ' + (d.status_value === 'published' ? 'badge-success' : 'badge-ghost') + '">' + esc(d.status_label) + '</span>';
        },
    },
    {
        title: '', field: 'id', width: 90, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<div class="flex items-center justify-center gap-1">';

            if (d.can_update) {
                html += '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square" title="Sửa">'
                    + '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                    + '</a>';
            }
            if (d.can_delete) {
                html += '<button class="btn btn-ghost btn-xs btn-square text-error" title="Xoá"'
                    + ' data-url="' + esc(d.destroy_url) + '" data-name="' + esc(d.name) + '"'
                    + ' onclick="window.ocopProductDeleteConfirm(this.dataset.url, this.dataset.name)">'
                    + '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>'
                    + '</button>';
            }

            html += '</div>';
            return html;
        },
    },
];

// ── AJAX delete ──────────────────────────────────────────────────────────────

let pendingDeleteUrl = null;

window.ocopProductDeleteConfirm = function (url, name) {
    pendingDeleteUrl = url;
    const nameEl = document.getElementById('ocopProductDeleteItemName');
    if (nameEl) nameEl.textContent = '"' + name + '"';
    document.getElementById('ocopProductDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('ocopProductConfirmDeleteBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async function () {
        if (!pendingDeleteUrl) return;

        const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        this.disabled    = true;
        this.textContent = 'Đang xoá...';

        try {
            const res = await fetch(pendingDeleteUrl, {
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
                document.getElementById('ocopProductDeleteModal')?.close();
                window.ocopProductTable?.replaceData();
            } else {
                alert(data.message || 'Xoá thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[ocop-product] delete failed', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled    = false;
            this.textContent = 'Xoá';
            pendingDeleteUrl = null;
        }
    });
});

// ── Alpine component ──────────────────────────────────────────────────────────

document.addEventListener('alpine:init', () => {
    Alpine.data('ocopProductListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        let tableInst = null;

        return {
            filters: { search: '', category_id: '', status: '' },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.category_id || f.status);
            },

            init() {
                this.loadState();
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#ocop-product-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)      p.search      = f.search;
                        if (f.category_id) p.category_id = f.category_id;
                        if (f.status)      p.status      = f.status;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[ocop-product] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         20,
                    paginationSizeSelector: [10, 20, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'created_at', dir: 'desc' }],

                    layout:           'fitColumns',
                    responsiveLayout: 'collapse',
                    movableColumns:   true,

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>'
                        + '<p class="text-sm">Chưa có sản phẩm nào</p></div>',
                });

                window.ocopProductTable = tableInst;
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))   this.filters.search      = p.get('q');
                if (p.has('cat')) this.filters.category_id = p.get('cat');
                if (p.has('st'))  this.filters.status      = p.get('st');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)      p.set('q',   f.search);
                if (f.category_id) p.set('cat', f.category_id);
                if (f.status)      p.set('st',  f.status);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            reset() {
                this.filters = { search: '', category_id: '', status: '' };
                history.replaceState(null, '', location.pathname);
                this.refresh();
            },
        };
    });
});
