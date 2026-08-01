/**
 * pages/article-index.js
 * Alpine component + Tabulator cho dashboard/posts/articles — cùng pattern
 * Modules/Organization/resources/assets/js/pages/organization-index.js (tham chiếu theo
 * yêu cầu): remote pagination/sort/filter qua API, xoá qua AJAX + modal xác nhận.
 *
 * Server data truyền vào qua x-data="articleListPage({{ Js::from([...]) }})".
 * Globals: window.Alpine, window.Tabulator, meta[name=csrf-token]
 */

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ── Tabulator columns ────────────────────────────────────────────────────────

const COLUMNS = [
    {
        title: 'Tiêu đề', field: 'title', minWidth: 240, sorter: 'string', frozen: true,
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<a href="' + esc(d.show_url) + '" class="font-medium text-sm link link-hover">' + esc(d.title) + '</a>';
        },
    },
    {
        title: 'Danh mục', field: 'category', minWidth: 160, headerSort: false,
        formatter(cell) {
            return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
        },
    },
    {
        title: 'Định dạng', field: 'format_label', width: 160, sorter: 'string',
    },
    {
        title: 'Trạng thái', field: 'status_value', width: 150, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = d.status_label
                ? '<span class="badge badge-sm ' + esc(d.status_badge) + '">' + esc(d.status_label) + '</span>'
                : '<span class="badge badge-sm badge-ghost">—</span>';
            if (d.is_sponsored) {
                html += ' <span class="badge badge-sm ' + esc(d.sponsor_badge) + '" title="Bài viết tài trợ">🏷</span>';
            }
            return html;
        },
    },
    {
        title: 'Người tạo', field: 'created_by_name', width: 150, headerSort: false,
        formatter(cell) {
            return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
        },
    },
    {
        title: 'Ngày tạo', field: 'created_at', width: 110, hozAlign: 'center', sorter: 'string',
    },
    {
        // spec/ga-dashboard-statistics.md §2.2 — đọc từ ga_views_30d đã đồng bộ sẵn
        // (SyncGoogleAnalyticsStatsCommand), null khi bài chưa từng sync/ngoài top 1000 GA —
        // hiển thị "—" chứ không phải "0" để phân biệt "chưa có dữ liệu" với "có dữ liệu, bằng 0".
        title: 'Lượt xem GA', field: 'ga_views_30d', width: 110, hozAlign: 'center', sorter: 'number',
        formatter(cell) {
            const v = cell.getValue();
            return v == null ? '<span class="text-base-content/25 text-xs">—</span>' : esc(v);
        },
    },
    {
        title: '', field: 'id', width: 110, hozAlign: 'center', headerSort: false, frozen: true,
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<div class="flex items-center justify-center gap-1">';

            if (d.is_redirect && d.clicks_url) {
                html += '<a href="' + esc(d.clicks_url) + '" class="btn btn-ghost btn-xs btn-square" title="Thống kê click">'
                    + '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>'
                    + '</a>';
            }

            if (d.can_manage) {
                html += '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square" title="Sửa">'
                    + '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                    + '</a>';

                html += '<button class="btn btn-ghost btn-xs btn-square text-error" title="Xoá"'
                    + ' data-url="' + esc(d.destroy_url) + '" data-title="' + esc(d.title) + '"'
                    + ' onclick="window.articleDeleteConfirm(this.dataset.url, this.dataset.title)">'
                    + '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>'
                    + '</button>';
            }

            html += '</div>';
            return html;
        },
    },
];

// ── AJAX delete ──────────────────────────────────────────────────────────────

let pendingDeleteUrl = null;

window.articleDeleteConfirm = function (url, title) {
    pendingDeleteUrl = url;
    const titleEl = document.getElementById('deleteItemTitle');
    if (titleEl) titleEl.textContent = '"' + title + '"';
    document.getElementById('deleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async function () {
        if (!pendingDeleteUrl) return;

        const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        this.disabled    = true;
        this.textContent = 'Đang xoá...';

        try {
            const res = await fetch(pendingDeleteUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':     csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept':           'application/json',
                    'Content-Type':     'application/x-www-form-urlencoded',
                },
                body: '_method=DELETE',
            });

            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                document.getElementById('deleteModal')?.close();
                window.articleTable?.replaceData();
            } else {
                alert(data.message || 'Xoá thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[post-article] delete failed', e);
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
    Alpine.data('articleListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        let tableInst = null;

        return {
            filters: { search: '', category_id: '', format: '', status: '' },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.category_id || f.format || f.status);
            },

            init() {
                this.loadState();
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#article-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)      p.search      = f.search;
                        if (f.category_id) p.category_id = f.category_id;
                        if (f.format)      p.format      = f.format;
                        if (f.status)      p.status      = f.status;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[post-article] API error', error),

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'
                        + '<p class="text-sm">Không có bài viết nào</p></div>',
                });

                window.articleTable = tableInst;
            },

            // ── URL state persistence ──────────────────────────────────────
            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))  this.filters.search      = p.get('q');
                if (p.has('cat')) this.filters.category_id = p.get('cat');
                if (p.has('fmt')) this.filters.format      = p.get('fmt');
                if (p.has('st'))  this.filters.status      = p.get('st');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)      p.set('q',   f.search);
                if (f.category_id) p.set('cat', f.category_id);
                if (f.format)      p.set('fmt', f.format);
                if (f.status)      p.set('st',  f.status);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            reset() {
                this.filters = { search: '', category_id: '', format: '', status: '' };
                history.replaceState(null, '', location.pathname);
                this.refresh();
            },
        };
    });
});
