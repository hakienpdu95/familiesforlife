function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const EMPTY = '<span class="text-base-content/25 text-xs">—</span>';

const ICON_EDIT = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
const ICON_ADD = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>';
const ICON_DELETE = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';

const COLUMNS = [
    {
        title: 'Chủ thể', field: 'name', minWidth: 240, sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<span class="font-medium text-sm">' + esc(d.name) + '</span>';
            if (!d.is_active) html += ' <span class="badge badge-ghost badge-xs">Ngừng hoạt động</span>';
            return html;
        },
    },
    { title: 'Mã số định danh', field: 'tax_code', width: 160, sorter: 'string', formatter: (c) => '<span class="font-mono text-sm">' + esc(c.getValue()) + '</span>' },
    { title: 'Loại hình', field: 'organization_type', width: 140, sorter: 'string', formatter: (c) => esc(c.getValue()) },
    { title: 'Tỉnh/thành', field: 'province_name', minWidth: 140, headerSort: false, formatter: (c) => esc(c.getValue()) || EMPTY },
    { title: 'Hạng sao', field: 'ocop_star', width: 100, hozAlign: 'center', sorter: 'number', formatter: (c) => c.getValue() ? c.getValue() + ' ★' : EMPTY },
    { title: 'Sản phẩm', field: 'products_count', width: 100, hozAlign: 'center', sorter: 'number' },
    {
        title: '', field: 'id', width: 120, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<div class="flex items-center justify-center gap-1">';
            if (d.can_create_product) {
                html += '<a href="' + esc(d.create_product_url) + '" class="btn btn-ghost btn-xs btn-square text-primary" title="Thêm sản phẩm cho chủ thể này">' + ICON_ADD + '</a>';
            }
            if (d.can_update) {
                html += '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square" title="Sửa">' + ICON_EDIT + '</a>';
            }
            if (d.can_delete) {
                html += '<button class="btn btn-ghost btn-xs btn-square text-error" title="Xoá"'
                    + ' data-url="' + esc(d.destroy_url) + '" data-name="' + esc(d.name) + '"'
                    + ' onclick="window.ocopSubjectDeleteConfirm(this.dataset.url, this.dataset.name)">' + ICON_DELETE + '</button>';
            }
            return html + '</div>';
        },
    },
];

let pendingDeleteUrl = null;

window.ocopSubjectDeleteConfirm = function (url, name) {
    pendingDeleteUrl = url;
    const nameEl = document.getElementById('ocopSubjectDeleteItemName');
    if (nameEl) nameEl.textContent = '"' + name + '"';
    document.getElementById('ocopSubjectDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('ocopSubjectConfirmDeleteBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async function () {
        if (!pendingDeleteUrl) return;

        const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        this.disabled = true;
        this.textContent = 'Đang xoá...';

        try {
            const res = await fetch(pendingDeleteUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: '_method=DELETE',
            });
            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                document.getElementById('ocopSubjectDeleteModal')?.close();
                window.ocopSubjectTable?.replaceData();
            } else {
                alert(data.message || 'Xoá thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[ocop-subject] delete failed', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled = false;
            this.textContent = 'Xoá';
            pendingDeleteUrl = null;
        }
    });
});

document.addEventListener('alpine:init', () => {
    Alpine.data('ocopSubjectListPage', ({ apiUrl = '' } = {}) => {
        let tableInst = null;
        const emptyFilters = () => ({ search: '', organization_type: '' });

        return {
            filters: emptyFilters(),

            get hasFilters() {
                return !!(this.filters.search || this.filters.organization_type);
            },

            init() {
                const p = new URLSearchParams(location.search);
                if (p.has('q')) this.filters.search = p.get('q');
                if (p.has('type')) this.filters.organization_type = p.get('type');
                this.$nextTick(() => this.setup());
            },

            setup() {
                const self = this;

                tableInst = new window.Tabulator('#ocop-subject-table', {
                    ajaxURL: apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {};
                        if (self.filters.search) p.search = self.filters.search;
                        if (self.filters.organization_type) p.organization_type = self.filters.organization_type;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[ocop-subject] API error', error),

                    pagination: true,
                    paginationMode: 'remote',
                    paginationSize: 20,
                    paginationSizeSelector: [10, 20, 50, 100],
                    paginationCounter: 'rows',
                    sortMode: 'remote',
                    initialSort: [{ column: 'created_at', dir: 'desc' }],

                    layout: 'fitColumns',
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
                    placeholder: '<div class="py-16 text-center opacity-40"><p class="text-sm">Chưa có chủ thể nào</p></div>',
                });

                window.ocopSubjectTable = tableInst;
            },

            saveState() {
                const p = new URLSearchParams();
                if (this.filters.search) p.set('q', this.filters.search);
                if (this.filters.organization_type) p.set('type', this.filters.organization_type);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            onFilterChange() { this.saveState(); tableInst?.replaceData(); },

            reset() {
                this.filters = emptyFilters();
                this.onFilterChange();
            },
        };
    });
});
