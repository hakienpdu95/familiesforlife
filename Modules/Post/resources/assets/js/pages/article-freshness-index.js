import { t } from '../../../../../../resources/js/shared/i18n.js';

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const COLUMNS = [
    {
        title: t('Bài viết'), field: 'title', minWidth: 260, sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<div class="py-0.5"><span class="font-medium text-sm">' + esc(d.title) + '</span>'
                + '<div class="text-xs text-base-content/40">' + esc(d.locale) + '</div></div>';
        },
    },
    {
        title: t('Lượt xem'), field: 'view_count', width: 130, hozAlign: 'center', sorter: 'number',
        formatter(cell) {
            return Number(cell.getValue() ?? 0).toLocaleString('vi-VN');
        },
    },
    {
        title: t('Cập nhật lần cuối'), field: 'updated_at', width: 180, hozAlign: 'center', sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<span class="text-xs text-base-content/60">' + esc(d.updated_at_label) + '</span>'
                + '<div><span class="badge badge-sm badge-warning mt-0.5">' + esc(d.updated_at_human) + '</span></div>';
        },
    },
    {
        title: '', field: 'edit_url', width: 120, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            return '<a href="' + esc(cell.getValue()) + ('" class="btn btn-sm btn-primary">' + t('Rà soát') + '</a>');
        },
    },
];

document.addEventListener('alpine:init', () => {
    Alpine.data('articleFreshnessPage', (serverData = {}) => {
        const { rows = [] } = serverData;

        return {
            init() {
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const el = document.getElementById('freshness-table');
                if (!el || !window.Tabulator) return;

                const table = new window.Tabulator(el, {
                    data:    rows,
                    columns: COLUMNS,

                    pagination:             true,
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    initialSort:            [{ column: 'view_count', dir: 'desc' }],

                    layout:           'fitColumns',
                    responsiveLayout: 'collapse',

                    locale: 'vi-VN',
                    langs: {
                        'vi-VN': {
                            pagination: {
                                page_size: t('Dòng/trang'), page_title: t('Trang'),
                                first: '«', last: '»', prev: '‹', next: '›',
                                first_title: t('Trang đầu'), last_title: t('Trang cuối'),
                                prev_title: t('Trang trước'), next_title: t('Trang sau'),
                                counter: { showing: '', of: t('trong'), rows: t('dòng'), pages: t('trang') },
                            },
                        },
                    },

                    placeholder: '<div class="py-16 text-center opacity-40">'
                        + ('<p class="text-sm">' + t('Không có bài viết nào cần rà soát — mọi bài xem nhiều đều đã cập nhật trong 90 ngày qua.') + '</p></div>'),
                });

                window.enhancePageSizeSelect?.(table);
            },
        };
    });
});
