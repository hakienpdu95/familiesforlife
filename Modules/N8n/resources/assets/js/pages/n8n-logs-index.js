/**
 * pages/n8n-logs-index.js
 * Alpine component + 2 Tabulator (inbound/outbound) cho dashboard/n8n/logs — filter theo
 * connection/chiều/trạng thái (spec/N8n_Integration_Technical_Specification.md §7.4).
 */

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function connectionCell(d) {
    if (!d.connection_name) return '<span class="text-base-content/40">—</span>';
    let html = esc(d.connection_name);
    if (d.connection_deleted) html += ' <span class="badge badge-xs badge-ghost">đã xoá</span>';
    return html;
}

const INBOUND_COLUMNS = [
    { title: 'Thời gian', field: 'received_at', width: 150, sorter: 'string' },
    { title: 'Kết nối', field: 'connection_name', minWidth: 160, headerSort: false, formatter: (cell) => connectionCell(cell.getRow().getData()) },
    { title: 'IP', field: 'ip_address', width: 130, formatter: (cell) => esc(cell.getValue() || '—') },
    {
        title: 'Chữ ký', field: 'signature_valid', width: 100, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const v = cell.getValue();
            if (v === null) return '<span class="badge badge-xs badge-ghost">n/a</span>';
            return v ? '<span class="badge badge-xs badge-success">Hợp lệ</span>' : '<span class="badge badge-xs badge-error">Sai</span>';
        },
    },
    {
        title: 'HTTP', field: 'http_status_returned', width: 80, hozAlign: 'center',
        formatter(cell) {
            const v = cell.getValue();
            const cls = v >= 200 && v < 300 ? 'badge-success' : 'badge-error';
            return '<span class="badge badge-xs ' + cls + '">' + esc(v) + '</span>';
        },
    },
    { title: 'event_name', field: 'event_name', minWidth: 140, formatter: (cell) => '<code class="text-xs">' + esc(cell.getValue() || '—') + '</code>' },
    { title: 'Listener', field: 'listener_count', width: 80, hozAlign: 'center' },
    {
        title: 'Chi tiết', field: 'error_message', minWidth: 200, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (d.error_message) return '<span class="text-xs text-error">' + esc(d.error_message) + '</span>';
            if (d.payload_excerpt) return '<code class="text-xs text-base-content/60 truncate block max-w-md" title="' + esc(d.payload_excerpt) + '">' + esc(d.payload_excerpt) + '</code>';
            return '—';
        },
    },
];

const OUTBOUND_COLUMNS = [
    { title: 'Thời gian', field: 'requested_at', width: 150, sorter: 'string' },
    { title: 'Kết nối', field: 'connection_name', minWidth: 160, headerSort: false, formatter: (cell) => connectionCell(cell.getRow().getData()) },
    { title: 'event_name', field: 'event_name', minWidth: 130, formatter: (cell) => '<code class="text-xs">' + esc(cell.getValue() || '—') + '</code>' },
    { title: 'Caller', field: 'caller', minWidth: 160, formatter: (cell) => '<code class="text-xs">' + esc(cell.getValue() || '—') + '</code>' },
    {
        title: 'Kết quả', field: 'success', width: 100, hozAlign: 'center', headerSort: false,
        formatter: (cell) => cell.getValue() ? '<span class="badge badge-xs badge-success">OK</span>' : '<span class="badge badge-xs badge-error">Lỗi</span>',
    },
    { title: 'HTTP', field: 'http_status', width: 80, hozAlign: 'center', formatter: (cell) => esc(cell.getValue() ?? '—') },
    { title: 'Thời gian xử lý', field: 'duration_ms', width: 110, hozAlign: 'center', formatter: (cell) => cell.getValue() != null ? cell.getValue() + ' ms' : '—' },
    {
        title: 'Chi tiết', field: 'error_message', minWidth: 200, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (d.error_message) return '<span class="text-xs text-error">' + esc(d.error_message) + '</span>';
            if (d.payload_excerpt) return '<code class="text-xs text-base-content/60 truncate block max-w-md" title="' + esc(d.payload_excerpt) + '">' + esc(d.payload_excerpt) + '</code>';
            return '—';
        },
    },
];

const TABULATOR_BASE = {
    pagination: true,
    paginationMode: 'remote',
    paginationSize: 20,
    paginationSizeSelector: [10, 20, 50, 100],
    paginationCounter: 'rows',
    sortMode: 'remote',
    layout: 'fitColumns',
    responsiveLayout: 'collapse',
    movableColumns: true,
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
};

document.addEventListener('alpine:init', () => {
    Alpine.data('n8nLogsPage', (serverData = {}) => {
        const { inboundApiUrl = '', outboundApiUrl = '' } = serverData;

        let inboundTable = null;
        let outboundTable = null;

        return {
            direction: 'inbound',
            filters: { connection_id: '', signature_valid: '', success: '' },

            get hasFilters() {
                return !!(this.filters.connection_id || this.filters.signature_valid !== '' || this.filters.success !== '');
            },

            init() {
                const p = new URLSearchParams(location.search);
                if (p.has('direction')) this.direction = p.get('direction') === 'outbound' ? 'outbound' : 'inbound';
                if (p.has('connection_id')) this.filters.connection_id = p.get('connection_id');

                this.$nextTick(() => this._setupTables());
            },

            _setupTables() {
                const self = this;

                inboundTable = new window.Tabulator('#n8n-inbound-logs-table', {
                    ...TABULATOR_BASE,
                    ajaxURL: inboundApiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.connection_id) p.connection_id = f.connection_id;
                        if (f.signature_valid !== '') p.signature_valid = f.signature_valid;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[n8n] inbound logs API error', error),
                    initialSort: [{ column: 'received_at', dir: 'desc' }],
                    columns: INBOUND_COLUMNS,
                    placeholder: '<div class="py-16 text-center opacity-40"><p class="text-sm">Chưa có log inbound nào</p></div>',
                });

                outboundTable = new window.Tabulator('#n8n-outbound-logs-table', {
                    ...TABULATOR_BASE,
                    ajaxURL: outboundApiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.connection_id) p.connection_id = f.connection_id;
                        if (f.success !== '') p.success = f.success;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[n8n] outbound logs API error', error),
                    initialSort: [{ column: 'requested_at', dir: 'desc' }],
                    columns: OUTBOUND_COLUMNS,
                    placeholder: '<div class="py-16 text-center opacity-40"><p class="text-sm">Chưa có log outbound nào</p></div>',
                });

                window.n8nInboundLogsTable = inboundTable;
                window.n8nOutboundLogsTable = outboundTable;
            },

            switchDirection(direction) {
                this.direction = direction;
                this._syncQueryString();
            },

            onFilterChange() {
                this._syncQueryString();
                (this.direction === 'inbound' ? inboundTable : outboundTable)?.replaceData();
            },

            reset() {
                this.filters = { connection_id: '', signature_valid: '', success: '' };
                this._syncQueryString();
                inboundTable?.replaceData();
                outboundTable?.replaceData();
            },

            _syncQueryString() {
                const p = new URLSearchParams();
                if (this.direction !== 'inbound') p.set('direction', this.direction);
                if (this.filters.connection_id) p.set('connection_id', this.filters.connection_id);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },
        };
    });
});
