/**
 * Modules/PromptFrameworkStudio/resources/assets/js/prompt-framework-studio.js
 * Entry point JS module PromptFrameworkStudio.
 * Build: vite.config.backend.js → public/build/backend/assets/modules/prompt-framework-studio.[hash].js
 */

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ── create.blade.php / edit.blade.php — form field động theo framework đã chọn ──────────────
// spec/PromptFrameworkStudio_Technical_Specification.md §4.2. `initialKey`/`initialValues`:
// null ở trang create (trừ khi đến từ nút "Dùng mẫu này" ở Thư viện — §7 v1.3, khi đó chỉ
// `initialKey` có giá trị, `initialValues` vẫn null); ở trang edit truyền sẵn CẢ framework_key
// lẫn field_values đã lưu (framework không đổi được sau khi tạo — §5.3, nên trang edit KHÔNG
// render lại bước 1 chọn framework).
document.addEventListener('alpine:init', () => {
    Alpine.data('promptGenerator', (frameworks, initialKey = null, initialValues = null) => ({
        frameworks,
        selectedKey: initialKey,
        values: initialValues ?? {},
        // §7 (v1.3) — cho phép "Đổi mẫu khác" ở trang create mà không cần rời trang; mặc định ẩn
        // lưới chọn khi đã có sẵn selectedKey (đến từ nút "Dùng mẫu này") để không bắt người dùng
        // chọn lại thứ họ vừa chọn ở trang Thư viện.
        showFrameworkPicker: false,

        init() {
            // Đảm bảo mọi field của framework đã chọn đều có key trong `values` (kể cả field
            // optional chưa từng điền trước đây, hoặc field mới được thêm vào framework sau khi
            // bản ghi này đã tạo) — tránh x-model bind vào key "undefined".
            if (this.selectedKey && this.frameworks[this.selectedKey]) {
                for (const field of this.frameworks[this.selectedKey].fields) {
                    if (!(field.key in this.values)) this.values[field.key] = '';
                }
            }
        },

        select(key) {
            this.selectedKey = key;
            this.values = Object.fromEntries(this.frameworks[key].fields.map((f) => [f.key, '']));
        },

        get selectedFramework() {
            return this.selectedKey ? this.frameworks[this.selectedKey] : null;
        },
    }));
});

// ── show.blade.php — copy prompt vào clipboard (3 trạng thái nút, cùng UX content-outlines.js) ──
window.promptStudioCopyPrompt = async function (elId, btnEl) {
    const el = document.getElementById(elId);
    if (!el) return;

    const idleHtml = btnEl ? btnEl.innerHTML : null;

    if (btnEl) {
        btnEl.disabled = true;
        btnEl.innerHTML = '<span class="loading loading-spinner loading-xs"></span><span>Đang copy...</span>';
    }

    try {
        await navigator.clipboard.writeText(el.value);
        window.Toast?.success('Đã copy prompt vào clipboard.');

        if (btnEl) {
            btnEl.classList.remove('btn-primary');
            btnEl.classList.add('btn-success');
            btnEl.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span>Đã copy!</span>';

            setTimeout(() => {
                btnEl.classList.remove('btn-success');
                btnEl.classList.add('btn-primary');
                btnEl.innerHTML = idleHtml;
                btnEl.disabled = false;
            }, 1500);
        }
    } catch (e) {
        console.error('[prompt-framework-studio] copy failed', e);
        el.select();

        if (btnEl) {
            btnEl.innerHTML = idleHtml;
            btnEl.disabled = false;
        }
    }
};

// §5.3 — confirm trước khi "Sinh lại" (GHI ĐÈ rendered_prompt cũ, không versioning/không thể
// khôi phục) — form gắn data-confirm-regenerate ở edit.blade.php.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-confirm-regenerate]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (!window.confirm('Sinh lại sẽ GHI ĐÈ prompt hiện tại bằng prompt mới — KHÔNG thể khôi phục lại prompt cũ. Tiếp tục?')) {
                e.preventDefault();
            }
        });
    });
});

// ── prompts/index.blade.php — Tabulator + delete confirm ─────────────────────────────────────
const COLUMNS = [
    {
        title: 'Tên prompt', field: 'label', minWidth: 220, sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<a href="' + esc(d.show_url) + '" class="link link-hover text-sm font-medium">' + esc(d.label) + '</a>';
            if (d.is_orphaned) html += ' <span class="badge badge-xs badge-warning" title="Framework đã bị gỡ khỏi hệ thống">Đã gỡ</span>';

            return html;
        },
    },
    {
        title: 'Framework', field: 'framework_name', width: 140,
        formatter: (cell) => '<code class="text-xs">' + esc(cell.getValue()) + '</code>',
    },
    { title: 'Người tạo', field: 'created_by_name', width: 140, formatter: (cell) => esc(cell.getValue() || '—') },
    { title: 'Cập nhật lần cuối', field: 'updated_at', width: 160, sorter: 'string' },
    {
        title: '', field: 'uuid', width: 140, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '';
            if (d.edit_url) {
                html += '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs" title="Sửa">Sửa</a> ';
            }
            html += '<button class="btn btn-ghost btn-xs text-error" title="Xoá"'
                + ' data-url="' + esc(d.delete_url) + '"'
                + ' data-label="' + esc(d.label) + '"'
                + ' onclick="window.promptStudioDeleteConfirm(this.dataset.url, this.dataset.label)">Xoá</button>';

            return html;
        },
    },
];

let pendingDeleteUrl = null;

window.promptStudioDeleteConfirm = function (url, label) {
    pendingDeleteUrl = url;

    const labelEl = document.getElementById('promptStudioDeleteLabel');
    if (labelEl) labelEl.textContent = '"' + (label || '') + '"';

    document.getElementById('promptStudioDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('promptStudioConfirmDeleteBtn');
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
            console.error('[prompt-framework-studio] delete failed', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled = false;
            this.textContent = 'Xoá';
            pendingDeleteUrl = null;
        }
    });
});

document.addEventListener('alpine:init', () => {
    Alpine.data('promptStudioListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        let tableInst = null;

        return {
            filters: { search: '' },

            get hasFilters() {
                return !!this.filters.search;
            },

            init() {
                this.$watch('filters.search', () => tableInst?.replaceData());
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#prompt-studio-table', {
                    ajaxURL: apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {};
                        if (self.filters.search) p.search = self.filters.search;

                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[prompt-framework-studio] API error', error),

                    pagination: true,
                    paginationMode: 'remote',
                    paginationSize: 20,
                    paginationSizeSelector: [10, 20, 50, 100],
                    paginationCounter: 'rows',
                    sortMode: 'remote',
                    initialSort: [{ column: 'updated_at', dir: 'desc' }],

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'
                        + '<p class="text-sm">Chưa có prompt nào</p></div>',
                });

                window.promptStudioTable = tableInst;
            },

            reset() {
                this.filters.search = '';
                tableInst?.replaceData();
            },
        };
    });
});
