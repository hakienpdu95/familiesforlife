/**
 * pages/broadcast-log-index.js
 * Alpine component + Tabulator cho dashboard/newsletter/broadcast/logs — bảng chỉ đọc
 * (append-only, không có hành động xoá/sửa), cùng pattern article-index.js.
 *
 * Server data truyền vào qua x-data="broadcastLogListPage({{ Js::from([...]) }})".
 */

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const COLUMNS = [
    {
        title: 'Chủ đề', field: 'subject', minWidth: 240, sorter: 'string',
        formatter(cell) { return '<span class="font-medium text-sm">' + esc(cell.getValue()) + '</span>'; },
    },
    {
        title: 'Thời điểm gửi/lên lịch', field: 'created_at', width: 220, sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            return d.scheduled_at
                ? '<span class="text-xs">Lên lịch: ' + esc(d.scheduled_at) + '</span>'
                : '<span class="text-xs">' + esc(d.created_at) + '</span>';
        },
    },
    {
        title: 'Người gửi', field: 'sent_by_name', width: 160, headerSort: false,
        formatter(cell) {
            return '<span class="text-xs">' + (esc(cell.getValue()) || 'Hệ thống') + '</span>';
        },
    },
    {
        title: 'Resend Broadcast ID', field: 'resend_broadcast_id', minWidth: 200, headerSort: false,
        formatter(cell) {
            const v = cell.getValue();
            return v ? '<span class="text-xs text-base-content/40 font-mono">' + esc(v) + '</span>' : '<span class="text-base-content/25 text-xs">—</span>';
        },
    },
];

document.addEventListener('alpine:init', () => {
    Alpine.data('broadcastLogListPage', (serverData = {}) => {
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

                tableInst = new window.Tabulator('#broadcast-log-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {};
                        if (self.filters.search) p.search = self.filters.search;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[newsletter-broadcast-log] API error', error),

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>'
                        + '<p class="text-sm">Chưa gửi bản tin nào</p></div>',
                });

                window.broadcastLogTable = tableInst;
            },

            onFilterChange() {
                const p = new URLSearchParams();
                if (this.filters.search) p.set('q', this.filters.search);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
                tableInst?.replaceData();
            },

            clearSearch() { this.filters.search = ''; this.onFilterChange(); },
        };
    });
});
