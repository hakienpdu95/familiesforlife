/**
 * pages/tag-index.js
 * Alpine component + Tabulator cho dashboard/posts/tags — cùng pattern article-index.js.
 * Thêm modal "Gộp tag" (đổi từ form POST render sẵn sang AJAX để không phải reload trang khi
 * bảng đang chạy Tabulator remote).
 *
 * Server data truyền vào qua x-data="tagListPage({{ Js::from([...]) }})".
 */

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const COLUMNS = [
    {
        title: 'Tên tag', field: 'name', minWidth: 220, sorter: 'string', frozen: true,
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<span class="font-medium text-sm">' + esc(d.name) + '</span>'
                + '<div class="text-xs text-base-content/40 font-mono">' + esc(d.slug) + '</div>';
        },
    },
    {
        title: 'Số bài viết', field: 'articles_count', width: 130, hozAlign: 'center', sorter: 'number',
    },
    {
        title: '', field: 'id', width: 130, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<div class="flex items-center justify-center gap-1">';

            if (d.can_update) {
                html += '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square" title="Sửa">'
                    + '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                    + '</a>';
            }
            if (d.can_delete) {
                html += '<button class="btn btn-ghost btn-xs btn-square" title="Gộp vào tag khác"'
                    + ' data-url="' + esc(d.merge_url) + '" data-name="' + esc(d.name) + '" data-id="' + d.id + '"'
                    + ' onclick="window.tagMergeConfirm(this.dataset.url, this.dataset.name, this.dataset.id)">'
                    + '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>'
                    + '</button>';

                html += '<button class="btn btn-ghost btn-xs btn-square text-error" title="Xoá"'
                    + ' data-url="' + esc(d.destroy_url) + '" data-name="' + esc(d.name) + '"'
                    + ' onclick="window.tagDeleteConfirm(this.dataset.url, this.dataset.name)">'
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

window.tagDeleteConfirm = function (url, name) {
    pendingDeleteUrl = url;
    const nameEl = document.getElementById('tagDeleteItemName');
    if (nameEl) nameEl.textContent = '"' + name + '"';
    document.getElementById('tagDeleteModal')?.showModal();
};

// ── AJAX merge ───────────────────────────────────────────────────────────────

let pendingMergeUrl = null;

window.tagMergeConfirm = function (url, name, sourceId) {
    pendingMergeUrl = url;
    const nameEl = document.getElementById('tagMergeSourceName');
    if (nameEl) nameEl.textContent = '"' + name + '"';

    const select = document.getElementById('tagMergeTargetSelect');
    if (select) {
        select.value = '';
        Array.from(select.options).forEach((opt) => {
            opt.disabled = String(opt.value) === String(sourceId);
        });
    }

    document.getElementById('tagMergeModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const csrf = () => document.querySelector('meta[name=csrf-token]')?.content ?? '';

    // Delete
    const confirmDeleteBtn = document.getElementById('tagConfirmDeleteBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', async function () {
            if (!pendingDeleteUrl) return;
            this.disabled = true;
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
                    document.getElementById('tagDeleteModal')?.close();
                    window.tagTable?.replaceData();
                } else {
                    alert(data.message || 'Xoá thất bại. Vui lòng thử lại.');
                }
            } catch (e) {
                console.error('[post-tag] delete failed', e);
                alert('Lỗi kết nối. Vui lòng thử lại.');
            } finally {
                this.disabled = false;
                this.textContent = 'Xoá';
                pendingDeleteUrl = null;
            }
        });
    }

    // Merge
    const confirmMergeBtn = document.getElementById('tagConfirmMergeBtn');
    if (confirmMergeBtn) {
        confirmMergeBtn.addEventListener('click', async function () {
            if (!pendingMergeUrl) return;
            const targetId = document.getElementById('tagMergeTargetSelect')?.value;
            if (!targetId) { alert('Vui lòng chọn tag đích.'); return; }

            this.disabled = true;
            this.textContent = 'Đang gộp...';

            try {
                const res = await fetch(pendingMergeUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'target_tag_id=' + encodeURIComponent(targetId),
                });
                const data = await res.json().catch(() => ({}));

                if (res.ok) {
                    document.getElementById('tagMergeModal')?.close();
                    window.tagTable?.replaceData();
                } else {
                    alert(data.message || 'Gộp tag thất bại. Vui lòng thử lại.');
                }
            } catch (e) {
                console.error('[post-tag] merge failed', e);
                alert('Lỗi kết nối. Vui lòng thử lại.');
            } finally {
                this.disabled = false;
                this.textContent = 'Gộp tag';
                pendingMergeUrl = null;
            }
        });
    }
});

// ── Alpine component ──────────────────────────────────────────────────────────

document.addEventListener('alpine:init', () => {
    Alpine.data('tagListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        let tableInst = null;

        return {
            filters: { search: '' },

            init() {
                const p = new URLSearchParams(location.search);
                if (p.has('q')) this.filters.search = p.get('q');
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#tag-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {};
                        if (self.filters.search) p.search = self.filters.search;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[post-tag] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'name', dir: 'asc' }],

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>'
                        + '<p class="text-sm">Chưa có tag nào</p></div>',
                });

                window.tagTable = tableInst;
            },

            onFilterChange() {
                const p = new URLSearchParams();
                if (this.filters.search) p.set('q', this.filters.search);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
                tableInst?.replaceData();
            },

            clearSearch() {
                this.filters.search = '';
                this.onFilterChange();
            },
        };
    });
});
