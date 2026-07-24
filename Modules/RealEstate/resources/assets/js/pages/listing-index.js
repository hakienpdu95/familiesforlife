/**
 * pages/listing-index.js
 * Alpine component + Tabulator cho dashboard/real-estate — cùng pattern
 * Modules/Product/resources/assets/js/pages/product-index.js (tham chiếu theo yêu cầu):
 * remote pagination/sort/filter qua API, xoá qua AJAX + modal xác nhận.
 *
 * Server data truyền vào qua x-data="realEstateListPage({{ Js::from([...]) }})".
 */

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const COLUMNS = [
    {
        title: 'Tiêu đề', field: 'title', minWidth: 240, sorter: 'string', frozen: true,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (!d.can_update) return '<span class="text-sm">' + esc(d.title) + '</span>';
            return '<a href="' + esc(d.edit_url) + '" class="font-medium text-sm link link-hover">' + esc(d.title) + '</a>';
        },
    },
    {
        title: 'Loại tin / Loại hình', field: 'listing_type_label', minWidth: 190, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            return esc(d.listing_type_label) + ' · ' + esc(d.property_type_label);
        },
    },
    {
        title: 'Giá', field: 'display_price', width: 140, hozAlign: 'right', headerSort: false,
        formatter(cell) {
            return '<span class="font-mono text-sm">' + esc(cell.getValue()) + '</span>';
        },
    },
    {
        title: 'Trạng thái duyệt', field: 'approval_label', width: 150, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (!d.approval_label) return '<span class="text-base-content/30 text-xs">—</span>';
            return '<span class="badge badge-sm ' + esc(d.approval_badge) + '">' + esc(d.approval_label) + '</span>';
        },
    },
    {
        title: 'Ngày tạo', field: 'created_at', width: 110, hozAlign: 'center', sorter: 'string',
    },
    {
        title: '', field: 'id', width: 90, hozAlign: 'center', headerSort: false, frozen: true,
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
                    + ' data-url="' + esc(d.destroy_url) + '" data-title="' + esc(d.title) + '"'
                    + ' onclick="window.realEstateDeleteConfirm(this.dataset.url, this.dataset.title)">'
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

window.realEstateDeleteConfirm = function (url, title) {
    pendingDeleteUrl = url;
    const titleEl = document.getElementById('realEstateDeleteItemTitle');
    if (titleEl) titleEl.textContent = '"' + title + '"';
    document.getElementById('realEstateDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('realEstateConfirmDeleteBtn');
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
                document.getElementById('realEstateDeleteModal')?.close();
                window.realEstateTable?.replaceData();
            } else {
                alert(data.message || 'Xoá thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[real-estate] delete failed', e);
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
    Alpine.data('realEstateListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        let tableInst = null;

        return {
            filters: { search: '', listing_type: '', property_type: '', approval_status: '' },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.listing_type || f.property_type || f.approval_status);
            },

            init() {
                this.loadState();
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#real-estate-listing-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)          p.search          = f.search;
                        if (f.listing_type)    p.listing_type    = f.listing_type;
                        if (f.property_type)   p.property_type   = f.property_type;
                        if (f.approval_status) p.approval_status = f.approval_status;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[real-estate] API error', error),

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 21h18M5 21V7l7-4 7 4v14M9 9h1m4 0h1m-6 4h1m4 0h1m-6 4h1m4 0h1"/></svg>'
                        + '<p class="text-sm">Chưa có tin nào</p></div>',
                });

                window.realEstateTable = tableInst;
            },

            // ── URL state persistence ──────────────────────────────────────
            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))  this.filters.search          = p.get('q');
                if (p.has('lt')) this.filters.listing_type    = p.get('lt');
                if (p.has('pt')) this.filters.property_type   = p.get('pt');
                if (p.has('st')) this.filters.approval_status = p.get('st');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)          p.set('q',  f.search);
                if (f.listing_type)    p.set('lt', f.listing_type);
                if (f.property_type)   p.set('pt', f.property_type);
                if (f.approval_status) p.set('st', f.approval_status);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            reset() {
                this.filters = { search: '', listing_type: '', property_type: '', approval_status: '' };
                history.replaceState(null, '', location.pathname);
                this.refresh();
            },
        };
    });
});
