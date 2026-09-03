/**
 * pages/knowledge-document-index.js
 * Alpine component + Tabulator cho dashboard/aicem/knowledge-documents — cùng pattern
 * Modules/Organization/resources/assets/js/pages/organization-index.js (tham chiếu theo
 * yêu cầu): remote pagination/sort/filter qua API, xoá qua AJAX + modal xác nhận.
 *
 * Server data truyền vào qua x-data="knowledgeDocumentListPage({{ Js::from([...]) }})".
 */

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/**
 * Đối chiếu bài context-engineering (animalz.co) — "freshness": module-level mutable (không phải
 * const trong COLUMNS, vốn được xây dựng 1 lần lúc file load, TRƯỚC khi Alpine.data() factory
 * chạy và biết serverData.staleAfterDays) — cột formatter đọc biến này bằng closure, được gán giá
 * trị thật khi Alpine.data() factory chạy (luôn xảy ra trước khi user thấy được bảng, xem init()).
 * Cùng ngưỡng/công thức tính tuổi với CoreIdeaExtractor::category-foundations.blade.php
 * (foundationAgeDays/formatFoundationAge/isFoundationStale) — 1 khái niệm freshness, không tạo
 * 2 cách tính tuổi khác nhau trong cùng hệ thống context engineering.
 */
let staleAfterDays = 90;

function ageDays(updatedAt) {
    if (!updatedAt) return null;
    return Math.floor((Date.now() - new Date(updatedAt).getTime()) / (1000 * 60 * 60 * 24));
}

function formatAge(updatedAt) {
    const days = ageDays(updatedAt);
    if (days === null) return '<span class="text-base-content/25 text-xs">—</span>';
    if (days < 1) return 'Hôm nay';
    if (days === 1) return '1 ngày trước';
    if (days < 30) return `${days} ngày trước`;
    const months = Math.floor(days / 30);
    if (months < 12) return `${months} tháng trước`;
    return `${Math.floor(months / 12)} năm trước`;
}

function isStale(updatedAt) {
    const days = ageDays(updatedAt);
    return days !== null && days >= staleAfterDays;
}

const COLUMNS = [
    {
        title: 'Tiêu đề', field: 'title', minWidth: 220, sorter: 'string', frozen: true,
        formatter(cell) { return '<span class="font-medium text-sm">' + esc(cell.getValue()) + '</span>'; },
    },
    {
        title: 'Loại', field: 'type', width: 130, sorter: 'string',
        formatter(cell) { return '<span class="badge badge-sm badge-ghost font-mono">' + esc(cell.getValue()) + '</span>'; },
    },
    {
        title: 'Đối tượng', field: 'subject_type', minWidth: 140, headerSort: false,
        formatter(cell) { return esc(cell.getValue()) || 'DNA chung'; },
    },
    {
        title: 'Phạm vi', field: 'scope_count', minWidth: 170, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (d.scope_count == null) return '<span class="text-base-content/30">Mọi bài/sản phẩm</span>';
            const matchLabel = { any: 'khớp 1', all: 'khớp mọi' }[d.scope_match] ?? esc(d.scope_match);
            return '<span class="badge badge-sm badge-info">' + d.scope_count + ' điều kiện (' + matchLabel + ')</span>';
        },
    },
    {
        title: 'Độ ưu tiên', field: 'priority', width: 100, hozAlign: 'center', sorter: 'number',
    },
    {
        title: 'Phiên bản', field: 'current_version', width: 100, hozAlign: 'center', sorter: 'number',
        formatter(cell) { return 'v' + cell.getValue(); },
    },
    {
        title: 'Cập nhật', field: 'updated_at', minWidth: 150, headerSort: false,
        formatter(cell) {
            const updatedAt = cell.getValue();
            const stale = isStale(updatedAt);
            const badgeClass = stale ? 'badge-warning' : 'badge-ghost';
            const tooltip = stale ? `title="Đã hơn ${staleAfterDays} ngày chưa cập nhật — cân nhắc ôn lại tri thức này"` : '';
            return `<span class="badge badge-xs ${badgeClass}" ${tooltip}>${formatAge(updatedAt)}</span>`;
        },
    },
    {
        title: 'Người tạo', field: 'creator_name', minWidth: 140, headerSort: false,
        formatter(cell) {
            return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
        },
    },
    {
        title: '', field: 'id', width: 90, hozAlign: 'center', headerSort: false, frozen: true,
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<div class="flex items-center justify-center gap-1">';

            if (d.can_update) {
                html += '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square" title="Sửa">'
                    + '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                    + '</a>';
            }
            if (d.can_delete) {
                html += '<button class="btn btn-ghost btn-xs btn-square text-error" title="Xoá"'
                    + ' data-url="' + esc(d.destroy_url) + '" data-title="' + esc(d.title) + '"'
                    + ' onclick="window.knowledgeDocumentDeleteConfirm(this.dataset.url, this.dataset.title)">'
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

window.knowledgeDocumentDeleteConfirm = function (url, title) {
    pendingDeleteUrl = url;
    const titleEl = document.getElementById('knowledgeDocumentDeleteItemTitle');
    if (titleEl) titleEl.textContent = '"' + title + '"';
    document.getElementById('knowledgeDocumentDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('knowledgeDocumentConfirmDeleteBtn');
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
                document.getElementById('knowledgeDocumentDeleteModal')?.close();
                window.knowledgeDocumentTable?.replaceData();
            } else {
                alert(data.message || 'Xoá thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[aicem-knowledge-document] delete failed', e);
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
    Alpine.data('knowledgeDocumentListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        // Gán module-level staleAfterDays TRƯỚC khi _setup() dựng bảng (COLUMNS đọc biến này qua
        // closure) — xem docblock chỗ khai báo `let staleAfterDays` ở đầu file.
        staleAfterDays = serverData.staleAfterDays ?? staleAfterDays;

        let tableInst = null;

        return {
            filters: { search: '', type: '', subject_type: '' },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.type || f.subject_type);
            },

            init() {
                this.loadState();
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#knowledge-document-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)       p.search       = f.search;
                        if (f.type)         p.type         = f.type;
                        if (f.subject_type) p.subject_type = f.subject_type;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[aicem-knowledge-document] API error', error),

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>'
                        + '<p class="text-sm">Chưa có tri thức nào trong Knowledge Base</p></div>',
                });

                window.knowledgeDocumentTable = tableInst;
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))   this.filters.search       = p.get('q');
                if (p.has('ty'))  this.filters.type         = p.get('ty');
                if (p.has('sub')) this.filters.subject_type = p.get('sub');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)       p.set('q',   f.search);
                if (f.type)         p.set('ty',  f.type);
                if (f.subject_type) p.set('sub', f.subject_type);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            reset() {
                this.filters = { search: '', type: '', subject_type: '' };
                history.replaceState(null, '', location.pathname);
                this.refresh();
            },
        };
    });
});
