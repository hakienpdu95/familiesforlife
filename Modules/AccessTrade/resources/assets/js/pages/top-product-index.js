/**
 * pages/top-product-index.js
 * Alpine component + Tabulator cho dashboard/accesstrade/top-products — cùng pattern
 * offer-index.js, chỉ đọc.
 */

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function money(v) {
    const n = Number(v) || 0;
    return n.toLocaleString('vi-VN') + 'đ';
}

const COLUMNS = [
    {
        title: 'Ảnh', field: 'image_url', width: 90, headerSort: false,
        formatter(cell) {
            const v = cell.getValue();
            return v ? '<img src="' + esc(v) + '" alt="" class="h-10 w-10 rounded border border-base-300 object-cover">' : '';
        },
    },
    {
        title: 'Sản phẩm', field: 'name', minWidth: 220, sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<a href="' + esc(d.aff_link || d.link || '#') + '" target="_blank" rel="noopener" class="link link-hover text-sm">' + esc(d.name) + '</a>';
            if (d.category_name) html += '<p class="text-xs text-base-content/40">' + esc(d.category_name) + '</p>';
            return html;
        },
    },
    { title: 'Merchant', field: 'merchant', width: 140, sorter: 'string' },
    { title: 'Thương hiệu', field: 'brand', width: 140, sorter: 'string' },
    {
        title: 'Giá', field: 'price', width: 130, hozAlign: 'right', sorter: 'number',
        formatter: (cell) => '<span class="text-sm">' + money(cell.getValue()) + '</span>',
    },
    {
        title: 'Giảm giá', field: 'discount', width: 130, hozAlign: 'right', sorter: 'number',
        formatter: (cell) => cell.getValue() ? '<span class="text-sm text-error">' + money(cell.getValue()) + '</span>' : '—',
    },
    {
        title: 'Đã bán', field: 'total', width: 100, hozAlign: 'center', sorter: 'number',
        formatter: (cell) => '<span class="badge badge-ghost badge-sm">' + esc(cell.getValue()) + '</span>',
    },
    {
        title: 'Đồng bộ lúc', field: 'last_synced_at', width: 150, sorter: 'string',
        formatter: (cell) => '<span class="text-xs text-base-content/50">' + esc(cell.getValue()) + '</span>',
    },
];

document.addEventListener('alpine:init', () => {
    Alpine.data('topProductListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        let tableInst = null;

        return {
            filters: { merchant: '', brand: '' },

            get hasFilters() {
                return !!(this.filters.merchant || this.filters.brand);
            },

            init() {
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#accesstrade-top-product-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.merchant) p.merchant = f.merchant;
                        if (f.brand)    p.brand    = f.brand;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[accesstrade] top products API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         20,
                    paginationSizeSelector: [10, 20, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'total', dir: 'desc' }],

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
                        + '<p class="text-sm">Chưa có sản phẩm nào — bấm "Đồng bộ ngay" để lấy dữ liệu từ AccessTrade.</p></div>',
                });

                window.accessTradeTopProductTable = tableInst;
            },

            onFilterChange() {
                tableInst?.replaceData();
            },

            reset() {
                this.filters = { merchant: '', brand: '' };
                tableInst?.replaceData();
            },
        };
    });
});
