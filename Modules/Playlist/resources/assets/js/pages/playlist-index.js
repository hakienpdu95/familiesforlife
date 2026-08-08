/**
 * pages/playlist-index.js
 * Alpine component + Tabulator cho dashboard/playlists/items — cùng pattern
 * Modules/Video/resources/assets/js/pages/video-index.js: remote pagination/sort/filter qua
 * API, xoá qua AJAX + modal xác nhận, toggle is_active qua AJAX trực tiếp trên bảng.
 *
 * Server data truyền vào qua x-data="playlistListPage({{ Js::from([...]) }})".
 */

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const COLUMNS = [
    {
        title: 'Ảnh', field: 'thumbnail_url', width: 110, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (!d.thumbnail_url) return '';
            return '<img src="' + esc(d.thumbnail_url) + '" alt="" class="h-12 w-auto rounded border border-base-300 object-cover">';
        },
    },
    {
        title: 'Tên', field: 'name', minWidth: 220, sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<a href="' + esc(d.edit_url) + '" class="text-sm font-medium hover:underline">' + esc(d.name) + '</a>';
        },
    },
    {
        title: 'Số nội dung', field: 'items_count', width: 110, hozAlign: 'center', sorter: 'number', headerSort: false,
    },
    {
        title: 'Thứ tự', field: 'sort_order', width: 90, hozAlign: 'center', sorter: 'number',
    },
    {
        title: 'Trạng thái', field: 'is_active', width: 110, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            const checked = d.is_active ? 'checked' : '';
            return '<input type="checkbox" class="toggle toggle-success toggle-sm" ' + checked
                + ' data-url="' + esc(d.toggle_active_url) + '"'
                + ' onchange="window.playlistToggleActive(this)">';
        },
    },
    {
        title: 'Ngày tạo', field: 'created_at', width: 140, sorter: 'string',
    },
    {
        title: '', field: 'id', width: 140, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<div class="flex items-center justify-center gap-1">';

            html += '<a href="' + esc(d.public_url) + '" target="_blank" class="btn btn-ghost btn-xs" title="Xem trang công khai">Xem</a>';

            if (d.can_update) {
                html += '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs" title="Sửa">Sửa</a>';
            }
            if (d.can_delete) {
                html += '<button class="btn btn-ghost btn-xs text-error" title="Xoá"'
                    + ' data-url="' + esc(d.delete_url) + '"'
                    + ' onclick="window.playlistDeleteConfirm(this.dataset.url)">Xoá</button>';
            }

            html += '</div>';
            return html;
        },
    },
];

// ── Toggle is_active (AJAX trực tiếp, không cần reload bảng) ──────────────────

window.playlistToggleActive = async function (el) {
    const url = el.dataset.url;
    if (!url) return;

    const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
    const wasChecked = el.checked;
    el.disabled = true;

    try {
        const res = await fetch(url, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
            el.checked = !wasChecked;
            alert(data.message || 'Cập nhật trạng thái thất bại. Vui lòng thử lại.');
        }
    } catch (e) {
        console.error('[playlist] toggle-active failed', e);
        el.checked = !wasChecked;
        alert('Lỗi kết nối. Vui lòng thử lại.');
    } finally {
        el.disabled = false;
    }
};

// ── AJAX delete ──────────────────────────────────────────────────────────────

let pendingDeleteUrl = null;

window.playlistDeleteConfirm = function (url) {
    pendingDeleteUrl = url;
    document.getElementById('playlistDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('playlistConfirmDeleteBtn');
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
                document.getElementById('playlistDeleteModal')?.close();
                window.playlistTable?.replaceData();
            } else {
                alert(data.message || 'Xoá thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[playlist] delete failed', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled    = false;
            this.textContent = 'Xoá';
            pendingDeleteUrl = null;
        }
    });
});

// ── Alpine component (trang danh sách /dashboard/playlists/items) ────────────────────────

document.addEventListener('alpine:init', () => {
    Alpine.data('playlistListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        let tableInst = null;
        let reloadChain = Promise.resolve();

        // Xếp hàng các lần reload thay vì gọi tableInst.replaceData() trực tiếp — tránh lỗi
        // Tabulator "Data Load Response Blocked" khi 1 lần reload mới bị gọi trong lúc lần
        // trước còn đang chờ response (cùng lý do đã ghi ở video-index.js).
        function queueReload() {
            reloadChain = reloadChain
                .catch(() => {})
                .then(() => tableInst?.replaceData())
                .catch((e) => console.error('[playlist] reload failed', e));
        }

        return {
            filters: { search: '', is_active: '' },

            get hasFilters() {
                return !!(this.filters.search || this.filters.is_active !== '');
            },

            init() {
                const p = new URLSearchParams(location.search);
                if (p.has('q')) this.filters.search = p.get('q');
                if (p.has('active')) this.filters.is_active = p.get('active');

                this.$watch('filters.search', () => this.onFilterChange());
                this.$watch('filters.is_active', () => this.onFilterChange());

                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#playlist-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)             p.search    = f.search;
                        if (f.is_active !== '')   p.is_active = f.is_active;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[playlist] API error', error),

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
                        + '<p class="text-sm">Chưa có playlist nào</p></div>',
                });

                window.playlistTable = tableInst;
            },

            onFilterChange() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)           p.set('q', f.search);
                if (f.is_active !== '') p.set('active', f.is_active);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
                queueReload();
            },

            reset() {
                this.filters.search = '';
                this.filters.is_active = '';
                history.replaceState(null, '', location.pathname);
                queueReload();
            },
        };
    });
});
