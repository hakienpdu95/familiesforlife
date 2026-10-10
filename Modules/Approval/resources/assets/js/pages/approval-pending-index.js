import { createTs } from '@shared/tom-select-factory.js';

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const EMPTY = '<span class="text-base-content/25 text-xs">—</span>';

const COLUMNS = [
    {
        title: 'Nội dung', field: 'entity_label', minWidth: 260, sorter: 'string', frozen: true,
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<span class="font-medium text-sm">' + esc(d.entity_label) + '</span>';
            if (d.name) html += '<span class="text-sm text-base-content/60"> — ' + esc(d.name) + '</span>';
            return html;
        },
    },
    {
        title: 'Loại', field: 'type_label', width: 200, sorter: 'string',
        formatter(cell) {
            return '<span class="badge badge-sm badge-ghost">' + esc(cell.getValue()) + '</span>';
        },
    },
    {
        title: 'Tổ chức', field: 'organization_name', minWidth: 180, sorter: 'string',
        formatter(cell) {
            return esc(cell.getValue()) || EMPTY;
        },
    },
    {
        title: 'Gửi lúc', field: 'submitted_at', width: 170, hozAlign: 'center', sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<span title="' + esc(d.submitted_at_label) + '">' + esc(d.submitted_ago) + '</span>';
        },
    },
    {
        title: '', field: 'review_url', width: 130, hozAlign: 'center', headerSort: false, frozen: true,
        formatter(cell) {
            const url = cell.getValue();
            return url
                ? '<a href="' + esc(url) + '" class="btn btn-primary btn-xs">Xem &amp; duyệt</a>'
                : EMPTY;
        },
    },
];

document.addEventListener('alpine:init', () => {
    Alpine.data('approvalPendingPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        let tableInst     = null;
        let tsSubjectType = null;

        return {
            filters: { search: '', subject_type: '' },

            get hasFilters() {
                return !!(this.filters.search || this.filters.subject_type);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search)       chips.push({ key: 'search',       label: 'Tìm: ' + f.search });
                if (f.subject_type) chips.push({ key: 'subject_type', label: tsSubjectType?.options?.[f.subject_type]?.text ?? f.subject_type });
                return chips;
            },

            init() {
                this.loadState();
                this.$nextTick(() => { this._setup(); this._initTomSelects(); });
            },

            _initTomSelects() {
                const el = document.getElementById('ts-subject-type');
                if (!el) return;

                tsSubjectType = createTs(el, {
                    placeholder: 'Tất cả loại',
                    onChange() { el.dispatchEvent(new Event('change', { bubbles: true })); },
                });
            },

            _setup() {
                tableInst = new window.Tabulator('#approval-pending-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxResponse: (_u, _p, res) => res.data ?? [],
                    ajaxError: (error) => console.error('[approval] API error', error),

                    pagination:             true,
                    paginationMode:         'local',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    initialSort:            [{ column: 'submitted_at', dir: 'desc' }],

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/></svg>'
                        + '<p class="text-sm">Không có nội dung nào đang chờ bạn duyệt</p></div>',
                });

                tableInst.on('tableBuilt', () => this.applyFilters());
                window.approvalPendingTable = tableInst;
            },

            applyFilters() {
                if (!tableInst) return;
                const term = this.filters.search.trim().toLowerCase();
                const type = this.filters.subject_type;

                if (!term && !type) {
                    tableInst.clearFilter();
                    return;
                }

                tableInst.setFilter((row) => {
                    if (type && row.subject_type !== type) return false;
                    if (!term) return true;
                    return [row.entity_label, row.name, row.organization_name, row.type_label]
                        .some((v) => v && String(v).toLowerCase().includes(term));
                });
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))    this.filters.search       = p.get('q');
                if (p.has('type')) this.filters.subject_type = p.get('type');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)       p.set('q', f.search);
                if (f.subject_type) p.set('type', f.subject_type);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            onFilterChange() { this.saveState(); this.applyFilters(); },
            clearSearch()    { this.filters.search = ''; this.onFilterChange(); },

            removeChip(key) {
                if (key === 'search') this.filters.search = '';
                if (key === 'subject_type') { this.filters.subject_type = ''; tsSubjectType?.setValue('', true); }
                this.onFilterChange();
            },

            reset() {
                this.filters = { search: '', subject_type: '' };
                tsSubjectType?.setValue('', true);
                history.replaceState(null, '', location.pathname);
                this.applyFilters();
            },
        };
    });
});
