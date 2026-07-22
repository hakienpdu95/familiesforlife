/**
 * pages/page-index.js
 * Alpine component + Tabulator cho dashboard/pages/items — cùng pattern
 * Modules/Post/resources/assets/js/pages/article-index.js. Publish/Gỡ xuất bản/Xoá đều qua
 * AJAX (không reload trang) — is_system chặn nút Xoá ngay ở đây (server đã tự chặn ở
 * DeletePageAction, đây chỉ là UX, không phải lớp bảo vệ duy nhất).
 *
 * Server data truyền vào qua x-data="pageListPage({{ Js::from([...]) }})".
 */

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function csrf() {
    return document.querySelector('meta[name=csrf-token]')?.content ?? '';
}

const COLUMNS = [
    {
        title: 'Tiêu đề', field: 'title', minWidth: 220, sorter: 'string', frozen: true,
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<span class="font-medium text-sm">' + esc(d.title) + '</span>';
            if (d.is_system) html += ' <span class="badge badge-ghost badge-xs" title="Trang hệ thống — không thể xoá">Hệ thống</span>';
            return html;
        },
    },
    {
        title: 'Đường dẫn', field: 'slug', minWidth: 180, sorter: 'string',
        formatter(cell) { return '<span class="text-xs text-base-content/50">/' + esc(cell.getValue()) + '</span>'; },
    },
    {
        title: 'Trạng thái', field: 'status_value', width: 130, hozAlign: 'center', sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<span class="badge badge-sm ' + (d.is_published ? 'badge-success' : 'badge-ghost') + '">' + esc(d.status_label) + '</span>';
        },
    },
    {
        title: 'Cập nhật', field: 'updated_at', width: 140, hozAlign: 'center', sorter: 'string',
    },
    {
        title: '', field: 'id', width: 210, hozAlign: 'right', headerSort: false, frozen: true,
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<div class="flex items-center justify-end gap-1">';

            if (d.can_update) {
                html += d.is_published
                    ? '<button class="btn btn-ghost btn-xs" title="Chuyển về Nháp" data-url="' + esc(d.unpublish_url) + '" onclick="window.pageTogglePublish(this.dataset.url)">Gỡ xuất bản</button>'
                    : '<button class="btn btn-ghost btn-xs text-success" title="Xuất bản" data-url="' + esc(d.publish_url) + '" onclick="window.pageTogglePublish(this.dataset.url)">Xuất bản</button>';
                html += '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs">Sửa</a>';
            }
            if (d.can_delete) {
                html += '<button class="btn btn-ghost btn-xs text-error" title="Xoá"'
                    + ' data-url="' + esc(d.destroy_url) + '" data-title="' + esc(d.title) + '"'
                    + ' onclick="window.pageDeleteConfirm(this.dataset.url, this.dataset.title)">Xoá</button>';
            }

            html += '</div>';
            return html;
        },
    },
];

// ── AJAX publish/unpublish ────────────────────────────────────────────────────

window.pageTogglePublish = async function (url) {
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: '_method=PATCH',
        });

        const data = await res.json().catch(() => ({}));

        if (res.ok) {
            window.pageTable?.replaceData();
        } else {
            alert(data.message || 'Cập nhật trạng thái thất bại. Vui lòng thử lại.');
        }
    } catch (e) {
        console.error('[page] publish toggle failed', e);
        alert('Lỗi kết nối. Vui lòng thử lại.');
    }
};

// ── AJAX delete ──────────────────────────────────────────────────────────────

let pendingDeleteUrl = null;

window.pageDeleteConfirm = function (url, title) {
    pendingDeleteUrl = url;
    const titleEl = document.getElementById('pageDeleteItemTitle');
    if (titleEl) titleEl.textContent = '"' + title + '"';
    document.getElementById('pageDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('pageConfirmDeleteBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async function () {
        if (!pendingDeleteUrl) return;

        this.disabled    = true;
        this.textContent = 'Đang xoá...';

        try {
            const res = await fetch(pendingDeleteUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: '_method=DELETE',
            });

            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                document.getElementById('pageDeleteModal')?.close();
                window.pageTable?.replaceData();
            } else {
                alert(data.message || 'Xoá thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[page] delete failed', e);
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
    Alpine.data('pageListPage', (serverData = {}) => {
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

                tableInst = new window.Tabulator('#page-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search) p.search = f.search;
                        if (f.status) p.status = f.status;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[page] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         20,
                    paginationSizeSelector: [10, 20, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'updated_at', dir: 'desc' }],

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'
                        + '<p class="text-sm">Chưa có trang tĩnh nào</p></div>',
                });

                window.pageTable = tableInst;
            },

            onFilterChange() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search) p.set('q',  f.search);
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
