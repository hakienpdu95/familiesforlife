import { t } from '../../../../../../resources/js/shared/i18n.js';

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
        title: t('Tiêu đề'), field: 'headline', minWidth: 240, sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<span class="badge badge-error badge-sm mr-1.5">' + esc(d.badge_label) + '</span>';
            html += '<span class="text-sm">' + esc(d.headline) + '</span>';
            if (d.has_override) html += ('<p class="text-xs text-base-content/40">' + t('Gốc:') + ' ') + esc(d.article_title) + '</p>';
            return html;
        },
    },
    {
        title: t('Lịch hiển thị'), field: 'starts_at', width: 220, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (!d.starts_at && !d.ends_at) return ('<span class="text-base-content/30 text-xs">' + t('Không giới hạn') + '</span>');
            return '<span class="text-xs">' + esc(d.starts_at || '—') + ' → ' + esc(d.ends_at || '—') + '</span>';
        },
    },
    {
        title: t('Trạng thái'), field: 'is_active', width: 130, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (d.is_running) return ('<span class="badge badge-success badge-sm">' + t('Đang chạy') + '</span>');
            if (!d.is_active) return ('<span class="badge badge-neutral badge-sm">' + t('Đã tắt') + '</span>');
            return ('<span class="badge badge-ghost badge-sm">' + t('Ngoài lịch') + '</span>');
        },
    },
    {
        title: '', field: 'id', width: 110, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<div class="flex items-center justify-center gap-1">';

            if (d.can_update) {
                html += '<a href="' + esc(d.edit_url) + ('" class="btn btn-ghost btn-xs" title="' + t('Sửa') + '">' + t('Sửa') + '</a>');
            }
            if (d.can_delete) {
                html += ('<button class="btn btn-ghost btn-xs text-error" title="' + t('Xoá') + '"')
                    + ' data-url="' + esc(d.destroy_url) + '"'
                    + (' onclick="window.breakingNewsDeleteConfirm(this.dataset.url)">' + t('Xoá') + '</button>');
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
        this.textContent = t('Đang xoá...');

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
                alert(data.message || t('Xoá thất bại. Vui lòng thử lại.'));
            }
        } catch (e) {
            console.error('[breaking-news] delete failed', e);
            alert(t('Lỗi kết nối. Vui lòng thử lại.'));
        } finally {
            this.disabled    = false;
            this.textContent = t('Xoá');
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
                                page_size: t('Dòng/trang'), page_title: t('Trang'),
                                first: '«', last: '»', prev: '‹', next: '›',
                                first_title: t('Trang đầu'), last_title: t('Trang cuối'),
                                prev_title: t('Trang trước'), next_title: t('Trang sau'),
                                counter: { showing: '', of: t('trong'), rows: t('dòng'), pages: t('trang') },
                            },
                        },
                    },

                    columns: COLUMNS,
                    placeholder: '<div class="py-16 text-center opacity-40">'
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>'
                        + ('<p class="text-sm">' + t('Chưa có tin nóng nào') + '</p></div>'),
                });

                window.breakingNewsTable = tableInst;
            },
        };
    });
});
