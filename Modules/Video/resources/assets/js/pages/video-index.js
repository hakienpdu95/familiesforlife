/**
 * pages/video-index.js
 * Alpine component + Tabulator cho dashboard/videos/items — cùng pattern
 * Modules/Banner/resources/assets/js/pages/banner-index.js: remote pagination/sort/filter qua
 * API, xoá qua AJAX + modal xác nhận, toggle is_active qua AJAX trực tiếp trên bảng.
 *
 * Server data truyền vào qua x-data="videoListPage({{ Js::from([...]) }})".
 */

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const COLUMNS = [
    {
        // src ban đầu LUÔN là thumbnail_url (hqdefault, đảm bảo tồn tại) — window.videoUpgradeThumbnails()
        // (gọi ở hook renderComplete bên dưới, video.js) tự dò và nâng lên thumbnail_hd_url nếu video có bản Full HD thật.
        title: 'Ảnh', field: 'thumbnail_url', width: 110, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (!d.thumbnail_url) return '';
            return '<img src="' + esc(d.thumbnail_url) + '" data-thumb-hd="' + esc(d.thumbnail_hd_url) + '" alt="" class="h-12 w-auto rounded border border-base-300 object-cover">';
        },
    },
    {
        title: 'Tên', field: 'name', minWidth: 220, sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<span class="text-sm font-medium">' + esc(d.name) + '</span>';
            if (d.video_url) {
                html += '<p class="text-xs text-base-content/40 truncate max-w-xs">' + esc(d.video_url) + '</p>';
            }
            return html;
        },
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
                + ' onchange="window.videoToggleActive(this)">';
        },
    },
    {
        title: 'Ngày tạo', field: 'created_at', width: 140, sorter: 'string',
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
                    + ' data-url="' + esc(d.delete_url) + '"'
                    + ' onclick="window.videoDeleteConfirm(this.dataset.url)">Xoá</button>';
            }

            html += '</div>';
            return html;
        },
    },
];

// ── Toggle is_active (AJAX trực tiếp, không cần reload bảng) ──────────────────

window.videoToggleActive = async function (el) {
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
        console.error('[video] toggle-active failed', e);
        el.checked = !wasChecked;
        alert('Lỗi kết nối. Vui lòng thử lại.');
    } finally {
        el.disabled = false;
    }
};

// ── AJAX delete ──────────────────────────────────────────────────────────────

let pendingDeleteUrl = null;

window.videoDeleteConfirm = function (url) {
    pendingDeleteUrl = url;
    document.getElementById('videoDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('videoConfirmDeleteBtn');
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
                document.getElementById('videoDeleteModal')?.close();
                window.videoTable?.replaceData();
            } else {
                alert(data.message || 'Xoá thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[video] delete failed', e);
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
    Alpine.data('videoListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        let tableInst = null;
        let reloadChain = Promise.resolve();

        /**
         * Xếp hàng các lần reload thay vì gọi tableInst.replaceData() trực tiếp — tránh lỗi
         * Tabulator "Data Load Response Blocked: An active data load request was blocked by an
         * attempt to change table data while the request was being made" khi 1 lần reload mới
         * bị gọi trong lúc lần trước còn đang chờ response. ajaxParams() luôn đọc `filters` MỚI
         * NHẤT tại đúng thời điểm Tabulator gửi request, nên xếp hàng tuần tự (không huỷ request
         * cũ) vẫn luôn cho kết quả đúng với trạng thái filter hiện tại.
         */
        function queueReload() {
            reloadChain = reloadChain
                .catch(() => {})
                .then(() => tableInst?.replaceData())
                .catch((e) => console.error('[video] reload failed', e));
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

                /**
                 * $watch chỉ phản ứng với thay đổi SAU khi đăng ký (không tự fire cho 2 dòng gán
                 * từ query string ở trên — Tabulator tự load đúng filters đó ở lần đầu, xem
                 * _setup()). Gắn watch cho CẢ 2 field ở đây thay vì @input/@change trực tiếp
                 * trong Blade — trước đây input tìm kiếm dùng "x-model.debounce.400ms" (delay
                 * gán giá trị) NHƯNG lại kèm "@input=onFilterChange()" gọi ngay lập tức trên MỖI
                 * keystroke (không đợi debounce) → gọi replaceData() dồn dập → Tabulator tự chặn
                 * (lỗi trên) → lần reload cuối cùng (lúc xoá hết chữ) có thể bị rớt, để bảng kẹt
                 * ở kết quả lọc cũ dù ô tìm kiếm đã trống. Nay chỉ còn 1 đường kích hoạt reload
                 * duy nhất — sau khi giá trị ĐÃ debounce xong và thực sự đổi.
                 */
                this.$watch('filters.search', () => this.onFilterChange());
                this.$watch('filters.is_active', () => this.onFilterChange());

                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#video-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)             p.search    = f.search;
                        if (f.is_active !== '')   p.is_active = f.is_active;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[video] API error', error),

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>'
                        + '<p class="text-sm">Chưa có video nào</p></div>',
                });

                // Mỗi lần Tabulator render xong 1 trang dữ liệu (load lần đầu, đổi trang, đổi
                // filter...) — nâng cấp ảnh đại diện vừa render lên Full HD nếu có (§ video.js).
                tableInst.on('renderComplete', () => window.videoUpgradeThumbnails?.());

                window.videoTable = tableInst;
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
