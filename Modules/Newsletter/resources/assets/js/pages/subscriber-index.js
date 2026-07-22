/**
 * pages/subscriber-index.js
 * Alpine component + Tabulator cho dashboard/newsletter/subscribers — cùng pattern
 * Modules/Post/resources/assets/js/pages/article-index.js.
 *
 * Server data truyền vào qua x-data="subscriberListPage({{ Js::from([...]) }})".
 */

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const COLUMNS = [
    {
        title: 'Họ tên', field: 'full_name', minWidth: 180, sorter: 'string',
        formatter(cell) {
            return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
        },
    },
    {
        title: 'Email', field: 'email', minWidth: 220, sorter: 'string',
        formatter(cell) { return '<span class="text-xs">' + esc(cell.getValue()) + '</span>'; },
    },
    {
        title: 'Trạng thái', field: 'status_value', width: 200, hozAlign: 'center', sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<span class="badge badge-sm ' + esc(d.status_badge) + '">' + esc(d.status_label) + '</span>';
        },
    },
    {
        title: 'Ngày đăng ký', field: 'subscribed_at', width: 150, hozAlign: 'center', sorter: 'string',
    },
    {
        title: '', field: 'id', width: 80, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (!d.can_remove) return '';
            return '<button class="btn btn-ghost btn-xs text-error" title="Xoá"'
                + ' data-url="' + esc(d.destroy_url) + '" data-email="' + esc(d.email) + '"'
                + ' onclick="window.subscriberDeleteConfirm(this.dataset.url, this.dataset.email)">Xoá</button>';
        },
    },
];

// ── AJAX delete ──────────────────────────────────────────────────────────────

let pendingDeleteUrl = null;

window.subscriberDeleteConfirm = function (url, email) {
    pendingDeleteUrl = url;
    const emailEl = document.getElementById('subscriberDeleteItemEmail');
    if (emailEl) emailEl.textContent = email;
    document.getElementById('subscriberDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('subscriberConfirmDeleteBtn');
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
                document.getElementById('subscriberDeleteModal')?.close();
                window.subscriberTable?.replaceData();
            } else {
                alert(data.message || 'Xoá thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[newsletter-subscriber] delete failed', e);
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
    Alpine.data('subscriberListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        let tableInst = null;

        return {
            filters: { search: '', status: '' },

            get hasFilters() {
                return !!(this.filters.search || this.filters.status);
            },

            init() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))  this.filters.search = p.get('q');
                if (p.has('st')) this.filters.status = p.get('st');
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#subscriber-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search) p.search = f.search;
                        if (f.status) p.status = f.status;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[newsletter-subscriber] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'subscribed_at', dir: 'desc' }],

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
                        + '<p class="text-sm">Chưa có ai đăng ký</p></div>',
                });

                window.subscriberTable = tableInst;
            },

            onFilterChange() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search) p.set('q', f.search);
                if (f.status) p.set('st', f.status);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
                tableInst?.replaceData();
            },

            clearSearch() { this.filters.search = ''; this.onFilterChange(); },

            reset() {
                this.filters = { search: '', status: '' };
                history.replaceState(null, '', location.pathname);
                tableInst?.replaceData();
            },
        };
    });
});
