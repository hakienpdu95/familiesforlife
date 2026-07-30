/**
 * pages/offer-index.js
 * Alpine component + Tabulator cho dashboard/accesstrade/offers — dữ liệu chỉ đọc (đồng bộ từ
 * AccessTrade), không có sửa/xoá tay — cùng pattern Modules/Banner/resources/assets/js/pages/banner-index.js
 * nhưng bỏ hết phần CRUD.
 *
 * Server data truyền vào qua x-data="offerListPage({{ Js::from([...]) }})".
 */

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const COLUMNS = [
    {
        title: 'Ảnh', field: 'image_url', width: 90, headerSort: false,
        formatter(cell) {
            const v = cell.getValue();
            return v ? '<img src="' + esc(v) + '" alt="" class="h-10 w-auto rounded border border-base-300 object-cover">' : '';
        },
    },
    {
        title: 'Tên khuyến mãi', field: 'name', minWidth: 220, sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<a href="' + esc(d.aff_link || d.link || '#') + '" target="_blank" rel="noopener" class="link link-hover text-sm">' + esc(d.name) + '</a>';
        },
    },
    { title: 'Merchant', field: 'merchant', width: 140, sorter: 'string' },
    { title: 'Domain', field: 'domain', width: 160, sorter: 'string' },
    {
        title: 'Coupon', field: 'has_coupon', width: 110, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            return d.has_coupon
                ? '<span class="badge badge-success badge-sm">' + d.coupon_count + ' mã</span>'
                : '<span class="badge badge-ghost badge-sm">Không</span>';
        },
    },
    {
        title: 'Hạn dùng', field: 'end_time', width: 170, headerSort: true,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (!d.start_time && !d.end_time) return '<span class="text-base-content/30 text-xs">Không giới hạn</span>';
            return '<span class="text-xs">' + esc(d.start_time || '—') + ' → ' + esc(d.end_time || '—') + '</span>';
        },
    },
    {
        title: 'Đồng bộ lúc', field: 'last_synced_at', width: 150, sorter: 'string',
        formatter: (cell) => '<span class="text-xs text-base-content/50">' + esc(cell.getValue()) + '</span>',
    },
];

document.addEventListener('alpine:init', () => {
    Alpine.data('offerListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        let tableInst = null;

        return {
            filters: { merchant: '', domain: '', has_coupon: '' },

            get hasFilters() {
                return !!(this.filters.merchant || this.filters.domain || this.filters.has_coupon !== '');
            },

            init() {
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#accesstrade-offer-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.merchant)        p.merchant   = f.merchant;
                        if (f.domain)          p.domain     = f.domain;
                        if (f.has_coupon !== '') p.has_coupon = f.has_coupon;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[accesstrade] offers API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         20,
                    paginationSizeSelector: [10, 20, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'end_time', dir: 'asc' }],

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
                        + '<p class="text-sm">Chưa có offer nào — bấm "Đồng bộ ngay" để lấy dữ liệu từ AccessTrade.</p></div>',
                });

                window.accessTradeOfferTable = tableInst;
            },

            onFilterChange() {
                tableInst?.replaceData();
            },

            reset() {
                this.filters = { merchant: '', domain: '', has_coupon: '' };
                tableInst?.replaceData();
            },
        };
    });
});
