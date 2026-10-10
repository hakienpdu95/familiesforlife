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
        title: 'Thời gian', field: 'created_at', width: 140, hozAlign: 'center', sorter: 'string',
        formatter(cell) {
            return '<span class="text-xs text-base-content/60">' + esc(cell.getValue()) + '</span>';
        },
    },
    {
        title: 'Nội dung', field: 'entity_label', minWidth: 260, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (!d.entity_label) return '<span class="text-base-content/30 italic text-sm">Entity đã bị xoá</span>';
            let html = d.entity_url
                ? '<a href="' + esc(d.entity_url) + '" class="font-medium text-sm link link-hover">' + esc(d.entity_label) + '</a>'
                : '<span class="font-medium text-sm">' + esc(d.entity_label) + '</span>';
            if (d.entity_name) html += '<span class="text-sm text-base-content/60"> — ' + esc(d.entity_name) + '</span>';
            return html;
        },
    },
    {
        title: 'Loại', field: 'type_label', width: 180, headerSort: false,
        formatter(cell) {
            const v = cell.getValue();
            return v ? '<span class="badge badge-sm badge-ghost">' + esc(v) + '</span>' : EMPTY;
        },
    },
    {
        title: 'Hành động', field: 'action_label', width: 170, headerSort: false,
    },
    {
        title: 'Chuyển trạng thái', field: 'to_status_label', width: 210, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '';
            if (d.from_status_label) html += '<span class="badge badge-ghost badge-sm">' + esc(d.from_status_label) + '</span> → ';
            html += '<span class="badge badge-sm ' + esc(d.to_status_badge) + '">' + esc(d.to_status_label) + '</span>';
            return html;
        },
    },
    {
        title: 'Người thực hiện', field: 'performed_by_name', width: 170, headerSort: false,
        formatter(cell) {
            return esc(cell.getValue()) || '<span class="text-base-content/50 text-xs">Hệ thống (job/command)</span>';
        },
    },
    {
        title: 'Lý do', field: 'reason', minWidth: 200, headerSort: false, tooltip: true,
        formatter(cell) {
            return esc(cell.getValue()) || EMPTY;
        },
    },
];

document.addEventListener('alpine:init', () => {
    Alpine.data('approvalHistoryPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        let tableInst     = null;
        let tsSubjectType = null;
        let tsAction      = null;

        const optText = (ts, v) => ts?.options?.[v]?.text ?? v;

        return {
            filters: { subject_type: '', action: '' },

            get hasFilters() {
                return !!(this.filters.subject_type || this.filters.action);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.subject_type) chips.push({ key: 'subject_type', label: optText(tsSubjectType, f.subject_type) });
                if (f.action)       chips.push({ key: 'action',       label: optText(tsAction, f.action) });
                return chips;
            },

            init() {
                this.loadState();
                this.$nextTick(() => { this._setup(); this._initTomSelects(); });
            },

            _initTomSelects() {
                const typeEl   = document.getElementById('ts-history-subject-type');
                const actionEl = document.getElementById('ts-history-action');
                if (!typeEl || !actionEl) return;

                tsSubjectType = createTs(typeEl, {
                    placeholder: 'Tất cả loại',
                    onChange() { typeEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });

                tsAction = createTs(actionEl, {
                    placeholder: 'Tất cả hành động',
                    onChange() { actionEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#approval-history-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.subject_type) p.subject_type = f.subject_type;
                        if (f.action)       p.action       = f.action;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[approval-history] API error', error),

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
                        + '<p class="text-sm">Chưa có lịch sử duyệt nào</p></div>',
                });

                window.approvalHistoryTable = tableInst;
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('subject_type')) this.filters.subject_type = p.get('subject_type');
                if (p.has('action'))       this.filters.action       = p.get('action');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.subject_type) p.set('subject_type', f.subject_type);
                if (f.action)       p.set('action', f.action);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },

            removeChip(key) {
                if (key === 'subject_type') { this.filters.subject_type = ''; tsSubjectType?.setValue('', true); }
                if (key === 'action')       { this.filters.action = ''; tsAction?.setValue('', true); }
                this.onFilterChange();
            },

            reset() {
                this.filters = { subject_type: '', action: '' };
                tsSubjectType?.setValue('', true);
                tsAction?.setValue('', true);
                history.replaceState(null, '', location.pathname);
                this.refresh();
            },
        };
    });
});
