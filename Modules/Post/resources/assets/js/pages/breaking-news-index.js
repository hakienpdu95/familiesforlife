/**
 * pages/breaking-news-index.js
 * Alpine component + Tabulator cho dashboard/breaking-news/items — cùng pattern
 * Modules/Banner/resources/assets/js/pages/banner-index.js.
 *
 * Server data truyền vào qua x-data="breakingNewsListPage({{ Js::from([...]) }})".
 */

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const COLUMNS = [
    {
        title: 'Tiêu đề', field: 'headline', minWidth: 240, sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<span class="badge badge-error badge-sm mr-1.5">' + esc(d.badge_label) + '</span>';
            html += '<span class="text-sm">' + esc(d.headline) + '</span>';
            if (d.has_override) html += '<p class="text-xs text-base-content/40">Gốc: ' + esc(d.article_title) + '</p>';
            return html;
        },
    },
    {
        title: 'Lịch hiển thị', field: 'starts_at', width: 220, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (!d.starts_at && !d.ends_at) return '<span class="text-base-content/30 text-xs">Không giới hạn</span>';
            return '<span class="text-xs">' + esc(d.starts_at || '—') + ' → ' + esc(d.ends_at || '—') + '</span>';
        },
    },
    {
        title: 'Trạng thái', field: 'is_active', width: 130, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (d.is_running) return '<span class="badge badge-success badge-sm">Đang chạy</span>';
            if (!d.is_active) return '<span class="badge badge-neutral badge-sm">Đã tắt</span>';
            return '<span class="badge badge-ghost badge-sm">Ngoài lịch</span>';
        },
    },
    {
        title: '', field: 'id', width: 110, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<div class="flex items-center justify-center gap-1">';

            if (d.can_update) {
                html += '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs" title="Sửa">Sửa</a>';
            }
            if (d.can_delete) {
                html += '<button class="btn btn-ghost btn-xs text-error" title="Xoá"'
                    + ' data-url="' + esc(d.destroy_url) + '"'
                    + ' onclick="window.breakingNewsDeleteConfirm(this.dataset.url)">Xoá</button>';
            }

            html += '</div>';
            return html;
        },
    },
];

// ── AJAX delete ──────────────────────────────────────────────────────────────

let pendingDeleteUrl = null;

window.breakingNewsDeleteConfirm = function (url) {
    pendingDeleteUrl = url;
    document.getElementById('breakingNewsDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('breakingNewsConfirmDeleteBtn');
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
                document.getElementById('breakingNewsDeleteModal')?.close();
                window.breakingNewsTable?.replaceData();
            } else {
                alert(data.message || 'Xoá thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[breaking-news] delete failed', e);
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
    Alpine.data('breakingNewsListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        let tableInst = null;

        return {
            init() {
                this.$nextTick(() => this._setup());
            },

            _setup() {
                tableInst = new window.Tabulator('#breaking-news-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[breaking-news] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         20,
                    paginationSizeSelector: [10, 20, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'sort_order', dir: 'asc' }],

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>'
                        + '<p class="text-sm">Chưa có tin nóng nào</p></div>',
                });

                window.breakingNewsTable = tableInst;
            },
        };
    });
});
