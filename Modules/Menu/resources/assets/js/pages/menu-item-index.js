/**
 * pages/menu-item-index.js
 * Alpine component + Tabulator (dataTree) cho dashboard/menu/items — cùng pattern
 * Modules/Post/resources/assets/js/pages/category-index.js. Nút Lên/Xuống hoán đổi sort_order
 * với sibling liền trước/sau (đúng cặp id/sort_order MenuItemApiController đã tính sẵn ở
 * prev_id/prev_sort_order/next_id/next_sort_order), gọi AJAX thay vì submit form reload trang.
 *
 * Server data truyền vào qua x-data="menuItemListPage({{ Js::from([...]) }})".
 */

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const COLUMNS = [
    {
        title: 'Nhãn', field: 'label', minWidth: 220,
        formatter(cell) {
            const d = cell.getRow().getData();
            const icon = d.icon ? '<i class="' + esc(d.icon) + ' mr-1.5 text-base-content/50"></i>' : '';
            return icon + '<span class="font-medium text-sm">' + esc(d.label) + '</span>';
        },
    },
    {
        title: 'Vị trí', field: 'location_label', width: 150, hozAlign: 'center', headerSort: false,
        formatter(cell) { return '<span class="badge badge-sm badge-ghost">' + esc(cell.getValue()) + '</span>'; },
    },
    {
        title: 'Đích liên kết', field: 'link_target', minWidth: 200, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (d.link_type === 'category') return '<span class="text-sm">' + esc(d.link_target) + '</span>';
            if (d.link_type === 'url') {
                let html = '<span class="font-mono text-xs">' + esc((d.link_target || '').slice(0, 40)) + '</span>';
                if (d.open_in_new_tab) html += ' <span class="badge badge-xs badge-ghost">tab mới</span>';
                return html;
            }
            return '<span class="text-base-content/30 text-sm">— chỉ mở submenu —</span>';
        },
    },
    {
        title: 'Thứ tự', field: 'sort_order', width: 100, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<div class="flex items-center justify-center gap-0.5">';
            html += '<button class="btn btn-ghost btn-xs btn-square" title="Lên" ' + (d.prev_id ? '' : 'disabled')
                + ' onclick="window.menuItemReorder(' + d.id + ',' + d.sort_order + ',' + (d.prev_id ?? 'null') + ',' + d.prev_sort_order + ')">'
                + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>'
                + '</button>';
            html += '<button class="btn btn-ghost btn-xs btn-square" title="Xuống" ' + (d.next_id ? '' : 'disabled')
                + ' onclick="window.menuItemReorder(' + d.id + ',' + d.sort_order + ',' + (d.next_id ?? 'null') + ',' + d.next_sort_order + ')">'
                + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>'
                + '</button>';
            html += '</div>';
            return html;
        },
    },
    {
        title: 'Trạng thái', field: 'is_active', width: 100, hozAlign: 'center', headerSort: false,
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
                    + ' data-url="' + esc(d.destroy_url) + '" data-label="' + esc(d.label) + '"'
                    + ' onclick="window.menuItemDeleteConfirm(this.dataset.url, this.dataset.label)">'
                    + '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>'
                    + '</button>';
            }

            html += '</div>';
            return html;
        },
    },
];

function csrf() {
    return document.querySelector('meta[name=csrf-token]')?.content ?? '';
}

// ── AJAX reorder ─────────────────────────────────────────────────────────────

window.menuItemReorder = async function (itemId, itemSortOrder, otherId, otherSortOrder) {
    if (!otherId) return;

    try {
        const res = await fetch(window.menuItemReorderUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'order[' + itemId + ']=' + otherSortOrder + '&order[' + otherId + ']=' + itemSortOrder,
        });

        if (res.ok) {
            window.menuItemTable?.replaceData();
        } else {
            const data = await res.json().catch(() => ({}));
            alert(data.message || 'Cập nhật thứ tự thất bại. Vui lòng thử lại.');
        }
    } catch (e) {
        console.error('[menu-item] reorder failed', e);
        alert('Lỗi kết nối. Vui lòng thử lại.');
    }
};

// ── AJAX delete ──────────────────────────────────────────────────────────────

let pendingDeleteUrl = null;

window.menuItemDeleteConfirm = function (url, label) {
    pendingDeleteUrl = url;
    const labelEl = document.getElementById('menuItemDeleteItemLabel');
    if (labelEl) labelEl.textContent = '"' + label + '"';
    document.getElementById('menuItemDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('menuItemConfirmDeleteBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async function () {
        if (!pendingDeleteUrl) return;

        this.disabled    = true;
        this.textContent = 'Đang xoá...';

        try {
            const res = await fetch(pendingDeleteUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: '_method=DELETE',
            });

            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                document.getElementById('menuItemDeleteModal')?.close();
                window.menuItemTable?.replaceData();
            } else {
                alert(data.message || 'Xoá thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[menu-item] delete failed', e);
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
    Alpine.data('menuItemListPage', (serverData = {}) => {
        const { apiUrl = '', reorderUrl = '' } = serverData;
        window.menuItemReorderUrl = reorderUrl;

        let tableInst = null;

        return {
            filters: { search: '', location: '' },

            get hasFilters() {
                return !!(this.filters.search || this.filters.location);
            },

            init() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))   this.filters.search   = p.get('q');
                if (p.has('loc')) this.filters.location = p.get('loc');
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#menu-item-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)   p.search   = f.search;
                        if (f.location) p.location = f.location;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res.data,
                    ajaxError: (error) => console.error('[menu-item] API error', error),

                    dataTree:              true,
                    dataTreeChildField:    'children',
                    dataTreeStartExpanded: true,
                    dataTreeChildIndent:   20,

                    // Phân trang CỤC BỘ (không remote — toàn bộ cây đã tải 1 lượt qua ajaxURL ở
                    // trên). Tabulator phân trang theo hàng GỐC, mục con luôn đi kèm mục cha
                    // trên cùng trang (không bị tách rời sang trang khác).
                    pagination:             true,
                    paginationSize:         20,
                    paginationSizeSelector: [10, 20, 50, 100],
                    paginationCounter:      'rows',
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

                    layout:           'fitColumns',
                    responsiveLayout: 'collapse',

                    columns: COLUMNS,
                    placeholder: '<div class="py-16 text-center opacity-40">'
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h7"/></svg>'
                        + '<p class="text-sm">Chưa có mục menu nào</p></div>',
                });

                window.menuItemTable = tableInst;
            },

            onFilterChange() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)   p.set('q',   f.search);
                if (f.location) p.set('loc', f.location);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
                tableInst?.replaceData();
            },

            clearSearch() { this.filters.search = ''; this.onFilterChange(); },

            reset() {
                this.filters = { search: '', location: '' };
                history.replaceState(null, '', location.pathname);
                tableInst?.replaceData();
            },
        };
    });
});
