/**
 * pages/n8n-connections-index.js
 * Alpine component + Tabulator cho dashboard/n8n/connections — cùng pattern
 * Modules/Video/resources/assets/js/pages/video-index.js: remote pagination/sort/filter qua
 * API, xoá/khôi phục qua AJAX + modal xác nhận.
 *
 * Server data truyền vào qua x-data="n8nConnectionsListPage({{ Js::from([...]) }})".
 */

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function statusBadge(enabled, label) {
    const cls = enabled ? 'badge-success' : 'badge-ghost';
    return '<span class="badge badge-xs ' + cls + '">' + label + '</span>';
}

const COLUMNS = [
    {
        title: 'Kết nối', field: 'name', minWidth: 220, sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<span class="text-sm font-medium">' + esc(d.name) + '</span>';
            if (d.deleted_at) html += ' <span class="badge badge-xs badge-error ml-1">Đã xoá</span>';
            if (d.purpose_note) html += '<p class="text-xs text-base-content/40 truncate max-w-xs">' + esc(d.purpose_note) + '</p>';
            return html;
        },
    },
    {
        title: 'Chiều', field: 'inbound_enabled', width: 170, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<div class="flex flex-col gap-0.5">';
            html += statusBadge(d.inbound_enabled, 'Inbound') + ' ';
            html += statusBadge(d.outbound_enabled, 'Outbound');
            if (d.sends_unsigned_outbound) html += ' <span class="badge badge-xs badge-warning" title="Outbound gửi không ký">chưa ký</span>';
            html += '</div>';
            return html;
        },
    },
    {
        title: 'Inbound token', field: 'inbound_token_masked', minWidth: 150, headerSort: false,
        formatter: (cell) => '<code class="text-xs">' + esc(cell.getValue() || '—') + '</code>',
    },
    {
        title: 'Lần nhận cuối', field: 'last_inbound_at', width: 140, sorter: 'string',
        formatter: (cell) => esc(cell.getValue() || '—'),
    },
    {
        title: 'Lần gọi ra cuối', field: 'last_outbound_at', width: 140, sorter: 'string',
        formatter: (cell) => esc(cell.getValue() || '—'),
    },
    {
        title: '', field: 'id', width: 130, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<div class="flex items-center justify-center gap-1">';

            if (d.edit_url) {
                html += '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs" title="Sửa">Sửa</a>';
            }
            if (d.delete_url) {
                html += '<button class="btn btn-ghost btn-xs text-error" title="Xoá"'
                    + ' data-url="' + esc(d.delete_url) + '"'
                    + ' onclick="window.n8nConnectionDeleteConfirm(this.dataset.url)">Xoá</button>';
            }
            if (d.restore_url) {
                html += '<button class="btn btn-ghost btn-xs text-success" title="Khôi phục"'
                    + ' data-url="' + esc(d.restore_url) + '"'
                    + ' onclick="window.n8nConnectionRestore(this)">Khôi phục</button>';
            }

            html += '</div>';
            return html;
        },
    },
];

// ── AJAX restore ───────────────────────────────────────────────────────────
window.n8nConnectionRestore = async function (btn) {
    const url = btn.dataset.url;
    if (!url) return;

    const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
    btn.disabled = true;

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
        });

        if (res.ok || res.redirected) {
            location.reload();
        } else {
            alert('Khôi phục thất bại. Vui lòng thử lại.');
            btn.disabled = false;
        }
    } catch (e) {
        console.error('[n8n] restore failed', e);
        alert('Lỗi kết nối. Vui lòng thử lại.');
        btn.disabled = false;
    }
};

// ── AJAX delete (soft) ──────────────────────────────────────────────────────
let pendingDeleteUrl = null;

window.n8nConnectionDeleteConfirm = function (url) {
    pendingDeleteUrl = url;
    document.getElementById('n8nConnectionDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('n8nConnectionConfirmDeleteBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async function () {
        if (!pendingDeleteUrl) return;

        const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        this.disabled = true;
        this.textContent = 'Đang xoá...';

        try {
            const res = await fetch(pendingDeleteUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: '_method=DELETE',
            });

            if (res.ok || res.redirected) {
                location.reload();
            } else {
                alert('Xoá thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[n8n] delete failed', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled = false;
            this.textContent = 'Xoá';
            pendingDeleteUrl = null;
        }
    });
});

// ── Alpine component ──────────────────────────────────────────────────────────
document.addEventListener('alpine:init', () => {
    Alpine.data('n8nConnectionsListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        let tableInst = null;
        let reloadChain = Promise.resolve();

        function queueReload() {
            reloadChain = reloadChain
                .catch(() => {})
                .then(() => tableInst?.replaceData())
                .catch((e) => console.error('[n8n] reload failed', e));
        }

        return {
            filters: { search: '', include_trashed: false },

            get hasFilters() {
                return !!(this.filters.search || this.filters.include_trashed);
            },

            init() {
                this.$watch('filters.search', () => queueReload());
                this.$watch('filters.include_trashed', () => queueReload());
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#n8n-connections-table', {
                    ajaxURL: apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search) p.search = f.search;
                        if (f.include_trashed) p.include_trashed = 1;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[n8n] API error', error),

                    pagination: true,
                    paginationMode: 'remote',
                    paginationSize: 20,
                    paginationSizeSelector: [10, 20, 50, 100],
                    paginationCounter: 'rows',
                    sortMode: 'remote',
                    initialSort: [{ column: 'created_at', dir: 'desc' }],

                    layout: 'fitColumns',
                    responsiveLayout: 'collapse',
                    movableColumns: true,

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
                        + '<p class="text-sm">Chưa có kết nối n8n nào</p></div>',
                });

                window.n8nConnectionsTable = tableInst;
            },

            reset() {
                this.filters.search = '';
                this.filters.include_trashed = false;
                queueReload();
            },
        };
    });
});
