/**
 * pages/event-category-index.js
 * Alpine component + Tabulator (dataTree) cho dashboard/events/categories — cùng pattern
 * Modules/Post/resources/assets/js/pages/category-index.js: không phân trang remote, cây danh
 * mục luôn tải toàn bộ 1 lượt.
 *
 * Server data truyền vào qua x-data="eventCategoryListPage({{ Js::from([...]) }})".
 */

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const COLUMNS = [
    {
        title: 'Tên danh mục', field: 'name', minWidth: 260,
        formatter(cell) {
            const d = cell.getRow().getData();
            const dot = '<span class="inline-block size-2.5 rounded-full mr-1.5 align-middle" style="background:' + esc(d.color_hex || '#94a3b8') + '"></span>';
            return '<div>' + dot + '<span class="font-medium text-sm">' + esc(d.name) + '</span>'
                + '<div class="text-xs text-base-content/40 font-mono ml-4">' + esc(d.slug) + '</div></div>';
        },
    },
    {
        title: 'Danh mục cha', field: 'parent_name', width: 160, headerSort: false,
        formatter(cell) {
            return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
        },
    },
    {
        title: 'Số sự kiện', field: 'events_count', width: 120, hozAlign: 'center', sorter: 'number',
    },
    {
        title: 'Trạng thái', field: 'is_active', width: 110, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const active = cell.getValue();
            return '<span class="badge badge-sm ' + (active ? 'badge-success' : 'badge-ghost') + '">' + (active ? 'Hiện' : 'Ẩn') + '</span>';
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
                    + ' onclick="window.eventCategoryDeleteConfirm(this.dataset.url, this.dataset.name)">'
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

window.eventCategoryDeleteConfirm = function (url, name) {
    pendingDeleteUrl = url;
    const nameEl = document.getElementById('eventCategoryDeleteItemName');
    if (nameEl) nameEl.textContent = '"' + name + '"';
    document.getElementById('eventCategoryDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('eventCategoryConfirmDeleteBtn');
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
                document.getElementById('eventCategoryDeleteModal')?.close();
                window.eventCategoryTable?.replaceData();
            } else {
                alert(data.message || 'Xoá thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[event-category] delete failed', e);
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
    Alpine.data('eventCategoryListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        let tableInst = null;

        return {
            filters: { search: '' },

            init() {
                const p = new URLSearchParams(location.search);
                if (p.has('q')) this.filters.search = p.get('q');
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#event-category-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {};
                        if (self.filters.search) p.search = self.filters.search;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res.data,
                    ajaxError: (error) => console.error('[event-category] API error', error),

                    dataTree:              true,
                    dataTreeChildField:    'children',
                    dataTreeStartExpanded: true,
                    dataTreeChildIndent:   20,

                    // Phân trang cục bộ (toàn bộ cây đã tải 1 lượt) — cùng lý do đã áp dụng
                    // cho dashboard/menu/items.
                    pagination:             true,
                    paginationSize:         20,
                    paginationSizeSelector: [10, 20, 50, 100],
                    paginationCounter:      'rows',

                    layout:           'fitColumns',
                    responsiveLayout: 'collapse',

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'
                        + '<p class="text-sm">Chưa có danh mục nào</p></div>',
                });

                window.eventCategoryTable = tableInst;
            },

            onFilterChange() {
                const p = new URLSearchParams();
                if (this.filters.search) p.set('q', this.filters.search);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
                tableInst?.replaceData();
            },

            clearSearch() {
                this.filters.search = '';
                this.onFilterChange();
            },
        };
    });
});
