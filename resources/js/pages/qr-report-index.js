/**
 * pages/qr-report-index.js
 * Tabulator cho dashboard/qr-reports — cùng pattern Modules/Post article-index.js:
 * remote pagination/sort/filter qua API, trạng thái lọc lưu trên URL, xử lý phản ánh qua AJAX.
 */

import { createTs } from '@shared/tom-select-factory.js';

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const EMPTY = '<span class="text-base-content/25 text-xs">—</span>';

const REASON_BADGE = {
    suspected_fake: 'badge-error',
    wrong_info: 'badge-warning',
    expired: 'badge-warning',
    other: 'badge-ghost',
};

const COLUMNS = [
    {
        title: 'Thời gian', field: 'created_at', width: 140, sorter: 'string',
        formatter: (cell) => '<div class="text-xs whitespace-nowrap leading-tight"><span class="font-mono font-semibold">' + esc(cell.getRow().getData().ref) + '</span><br><span class="text-base-content/50">' + esc(cell.getValue()) + '</span></div>',
    },
    {
        title: 'Mã QR', field: 'qr_code', width: 110, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            return d.qr_code
                ? '<a href="' + esc(d.qr_url) + '" target="_blank" rel="noopener" class="font-mono text-sm font-semibold link link-primary">' + esc(d.qr_code) + '</a>'
                : EMPTY;
        },
    },
    {
        title: 'Đối tượng', field: 'target_title', minWidth: 200, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<div class="text-xs text-base-content/50">' + esc(d.target_kind) + '</div>'
                + '<div class="text-sm font-medium">' + esc(d.target_title)
                + (d.target_trashed ? ' <span class="badge badge-ghost badge-xs">đã xoá</span>' : '') + '</div>';
        },
    },
    {
        title: 'Lý do', field: 'reason', width: 190, sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<span class="badge badge-sm whitespace-nowrap ' + (REASON_BADGE[d.reason] || 'badge-ghost') + '">' + esc(d.reason_label) + '</span>';
        },
    },
    {
        title: 'Nội dung', field: 'content', minWidth: 260, headerSort: false, formatter: 'textarea',
    },
    {
        title: 'Liên hệ', field: 'contact', width: 150, headerSort: false,
        formatter: (cell) => cell.getValue() ? '<span class="text-sm">' + esc(cell.getValue()) + '</span>' : EMPTY,
    },
    {
        title: 'Xử lý', field: 'resolved_at', width: 170, hozAlign: 'center', sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            if (d.resolve_url) {
                return '<button type="button" class="btn btn-success btn-xs"'
                    + ' data-resolve-url="' + esc(d.resolve_url) + '" data-resolve-title="' + esc(d.ref) + '">Đã xử lý</button>';
            }
            return '<div class="text-xs leading-tight"><span class="badge badge-success badge-xs mb-0.5">Đã xử lý</span><br>'
                + esc(d.resolved_by || '—') + '<br><span class="text-base-content/50">' + esc(d.resolved_at || '') + '</span></div>';
        },
    },
];

// ── AJAX resolve ─────────────────────────────────────────────────────────────

let pendingResolveUrl = null;

document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-resolve-url]');
    const modal = document.getElementById('resolveModal');
    if (!btn || !modal) return;
    pendingResolveUrl = btn.dataset.resolveUrl;
    modal.querySelector('[data-resolve-title]').textContent = btn.dataset.resolveTitle;
    modal.showModal();
});

document.addEventListener('click', async (e) => {
    const confirmBtn = e.target.closest('[data-resolve-confirm]');
    if (!confirmBtn || !pendingResolveUrl) return;

    const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
    confirmBtn.disabled = true;

    try {
        const res = await fetch(pendingResolveUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || 'Cập nhật thất bại.');
        document.getElementById('resolveModal')?.close();
        window.qrReportTable?.replaceData();
        window.Toast?.success?.(data.message || 'Đã xử lý phản ánh.');
    } catch (err) {
        alert(err.message || 'Lỗi kết nối. Vui lòng thử lại.');
    } finally {
        confirmBtn.disabled = false;
        pendingResolveUrl = null;
    }
});

// ── Alpine component ──────────────────────────────────────────────────────────

document.addEventListener('alpine:init', () => {
    Alpine.data('qrReportListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;
        let tableInst = null;
        let tsReason = null;

        return {
            filters: { status: 'new', reason: '', search: '' },
            counts: { new: 0, resolved: 0 },

            get hasFilters() {
                return !!(this.filters.reason || this.filters.search);
            },

            init() {
                this.loadState();
                this.$nextTick(() => { this._setup(); this._initTomSelects(); });
            },

            _initTomSelects() {
                const el = document.getElementById('ts-reason');
                if (!el) return;
                tsReason = createTs(el, {
                    placeholder: 'Tất cả lý do',
                    onChange() { el.dispatchEvent(new Event('change', { bubbles: true })); },
                });
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#qr-report-table', {
                    ajaxURL: apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.status) p.status = f.status;
                        if (f.reason) p.reason = f.reason;
                        if (f.search) p.search = f.search;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => {
                        self.counts = { new: res.counts?.new ?? 0, resolved: res.counts?.resolved ?? 0 };
                        return res;
                    },
                    ajaxError: (error) => console.error('[qr-report] API error', error),

                    pagination: true,
                    paginationMode: 'remote',
                    paginationSize: 25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter: 'rows',
                    sortMode: 'remote',
                    initialSort: [{ column: 'created_at', dir: 'desc' }],

                    layout: 'fitColumns',
                    responsiveLayout: 'collapse',
                    height: '68vh',

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
                    placeholder: '<div class="py-16 text-center opacity-40"><p class="text-sm">Không có phản ánh nào</p></div>',
                });

                window.qrReportTable = tableInst;
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('st')) this.filters.status = p.get('st') === 'all' ? '' : p.get('st');
                if (p.has('reason')) this.filters.reason = p.get('reason');
                if (p.has('q')) this.filters.search = p.get('q');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.status !== 'new') p.set('st', f.status || 'all');
                if (f.reason) p.set('reason', f.reason);
                if (f.search) p.set('q', f.search);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh() { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            setStatus(status) { this.filters.status = status; this.onFilterChange(); },
            clearSearch() { this.filters.search = ''; this.onFilterChange(); },

            reset() {
                this.filters.reason = '';
                this.filters.search = '';
                tsReason?.setValue('', true);
                this.onFilterChange();
            },
        };
    });
});
