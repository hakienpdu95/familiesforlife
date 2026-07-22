/**
 * pages/event-index.js
 * Alpine component + Tabulator cho dashboard/events — cùng pattern article-index.js.
 * Duyệt/Xuất bản/Lưu trữ gọi AJAX trực tiếp; Từ chối cần thu lý do nên dùng 1 modal dùng
 * chung cho mọi hàng (đổi action qua JS thay vì Alpine per-row) — cùng cách tag-index.js xử
 * lý modal "Gộp tag".
 *
 * Server data truyền vào qua x-data="eventListPage({{ Js::from([...]) }})".
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
        title: 'Sự kiện', field: 'title', minWidth: 220, sorter: 'string', frozen: true,
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<span class="font-medium text-sm">' + esc(d.title) + '</span>'
                + '<div class="text-xs text-base-content/40 font-mono">' + esc(d.slug) + '</div>';
        },
    },
    {
        title: 'Danh mục', field: 'category_name', minWidth: 150, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (!d.category_name) return '<span class="text-base-content/25 text-xs">—</span>';
            const dot = '<span class="inline-block size-2.5 rounded-full mr-1.5 align-middle" style="background:' + esc(d.category_color || '#94a3b8') + '"></span>';
            return dot + '<span class="text-sm">' + esc(d.category_name) + '</span>';
        },
    },
    {
        title: 'Thời gian', field: 'start_date', width: 170, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<span class="text-sm">' + esc(d.start_date) + ' – ' + esc(d.end_date) + '</span>';
        },
    },
    {
        title: 'Trạng thái', field: 'status_value', width: 130, hozAlign: 'center', sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<span class="badge badge-sm ' + esc(d.status_badge) + '">' + esc(d.status_label) + '</span>';
        },
    },
    {
        title: '', field: 'id', minWidth: 300, hozAlign: 'right', headerSort: false, frozen: true,
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<div class="flex items-center justify-end gap-1.5">';

            if (d.can_approve) {
                html += '<button class="btn btn-success btn-xs" data-url="' + esc(d.approve_url) + '" onclick="window.eventAction(this.dataset.url, \'Đã duyệt sự kiện.\')">Duyệt</button>';
            }
            if (d.can_reject) {
                html += '<button class="btn btn-error btn-outline btn-xs" data-url="' + esc(d.reject_url) + '" data-title="' + esc(d.title) + '" onclick="window.eventRejectConfirm(this.dataset.url, this.dataset.title)">Từ chối</button>';
            }
            if (d.can_publish) {
                html += '<button class="btn btn-primary btn-xs" data-url="' + esc(d.publish_url) + '" onclick="window.eventAction(this.dataset.url, \'Đã xuất bản sự kiện.\')">Xuất bản</button>';
            }
            if (d.can_archive) {
                html += '<button class="btn btn-ghost btn-xs" data-url="' + esc(d.archive_url) + '" data-title="' + esc(d.title) + '" onclick="window.eventArchiveConfirm(this.dataset.url, this.dataset.title)">Lưu trữ</button>';
            }
            if (d.can_update) {
                html += '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square" title="Sửa">'
                    + '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                    + '</a>';
            }

            html += '</div>';
            return html;
        },
    },
];

// ── AJAX action đơn giản (Duyệt/Xuất bản) ─────────────────────────────────────

window.eventAction = async function (url, successMessage) {
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: '',
        });
        const data = await res.json().catch(() => ({}));

        if (res.ok) {
            window.eventTable?.replaceData();
        } else {
            alert(data.message || 'Thao tác thất bại. Vui lòng thử lại.');
        }
    } catch (e) {
        console.error('[event] action failed', e);
        alert('Lỗi kết nối. Vui lòng thử lại.');
    }
};

// ── Từ chối (cần lý do) ────────────────────────────────────────────────────────

let pendingRejectUrl = null;

window.eventRejectConfirm = function (url, title) {
    pendingRejectUrl = url;
    const titleEl = document.getElementById('eventRejectTitle');
    if (titleEl) titleEl.textContent = '"' + title + '"';
    const reasonEl = document.getElementById('eventRejectReason');
    if (reasonEl) reasonEl.value = '';
    document.getElementById('eventRejectModal')?.showModal();
};

// ── Lưu trữ (confirm đơn giản, dùng modal chung thay vì window.confirm) ────────

let pendingArchiveUrl = null;

window.eventArchiveConfirm = function (url, title) {
    pendingArchiveUrl = url;
    const titleEl = document.getElementById('eventArchiveTitle');
    if (titleEl) titleEl.textContent = '"' + title + '"';
    document.getElementById('eventArchiveModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmRejectBtn = document.getElementById('eventConfirmRejectBtn');
    if (confirmRejectBtn) {
        confirmRejectBtn.addEventListener('click', async function () {
            if (!pendingRejectUrl) return;
            const reason = document.getElementById('eventRejectReason')?.value?.trim();
            if (!reason) { alert('Vui lòng nhập lý do từ chối.'); return; }

            this.disabled = true;
            this.textContent = 'Đang xử lý...';

            try {
                const res = await fetch(pendingRejectUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'rejected_reason=' + encodeURIComponent(reason),
                });
                const data = await res.json().catch(() => ({}));

                if (res.ok) {
                    document.getElementById('eventRejectModal')?.close();
                    window.eventTable?.replaceData();
                } else {
                    alert(data.message || 'Từ chối thất bại. Vui lòng thử lại.');
                }
            } catch (e) {
                console.error('[event] reject failed', e);
                alert('Lỗi kết nối. Vui lòng thử lại.');
            } finally {
                this.disabled = false;
                this.textContent = 'Từ chối sự kiện';
                pendingRejectUrl = null;
            }
        });
    }

    const confirmArchiveBtn = document.getElementById('eventConfirmArchiveBtn');
    if (confirmArchiveBtn) {
        confirmArchiveBtn.addEventListener('click', async function () {
            if (!pendingArchiveUrl) return;

            this.disabled = true;
            this.textContent = 'Đang xử lý...';

            try {
                const res = await fetch(pendingArchiveUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: '',
                });
                const data = await res.json().catch(() => ({}));

                if (res.ok) {
                    document.getElementById('eventArchiveModal')?.close();
                    window.eventTable?.replaceData();
                } else {
                    alert(data.message || 'Lưu trữ thất bại. Vui lòng thử lại.');
                }
            } catch (e) {
                console.error('[event] archive failed', e);
                alert('Lỗi kết nối. Vui lòng thử lại.');
            } finally {
                this.disabled = false;
                this.textContent = 'Lưu trữ sự kiện';
                pendingArchiveUrl = null;
            }
        });
    }
});

// ── Alpine component ──────────────────────────────────────────────────────────

document.addEventListener('alpine:init', () => {
    Alpine.data('eventListPage', (serverData = {}) => {
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

                tableInst = new window.Tabulator('#event-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search) p.search = f.search;
                        if (f.status) p.status = f.status;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[event] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         20,
                    paginationSizeSelector: [10, 20, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'created_at', dir: 'desc' }],

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>'
                        + '<p class="text-sm">Chưa có sự kiện nào</p></div>',
                });

                window.eventTable = tableInst;
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
