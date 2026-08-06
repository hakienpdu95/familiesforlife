/**
 * Modules/ContentOutlines/resources/assets/js/content-outlines.js
 * Entry point JS module ContentOutlines.
 * Build: vite.config.backend.js → public/build/backend/assets/modules/content-outlines.[hash].js
 */

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/** Cùng cách đếm với BuildContentOutlinePromptAction::estimateWordCount() (PHP) — tách khoảng
 *  trắng Unicode, KHÔNG dùng regex ASCII-only, để ước lượng tiếng Việt có dấu không bị lệch. */
function countWords(text) {
    const trimmed = (text || '').trim();
    if (!trimmed) return 0;

    return trimmed.split(/\s+/).length;
}

// ── show.blade.php — copy/download prompt, xem trước Markdown collapsible ──
// Hiệu ứng 3 trạng thái nút: idle ("Copy prompt") → đang copy ("Đang copy prompt...", disabled,
// spinner) → thành công ("Đã copy!", màu success, tự trở lại idle sau 1.5s). `btnEl` truyền qua
// onclick="...(this)" ở blade — fallback lấy theo id nếu gọi tay (VD từ console) không có `this`.
window.contentOutlineCopyPrompt = async function (btnEl) {
    const el = document.getElementById('content-outline-prompt');
    if (!el) return;

    const btn = btnEl || document.getElementById('content-outline-copy-btn');
    const idleHtml = btn ? btn.innerHTML : null;

    if (btn) {
        btn.disabled = true;
        btn.classList.add('scale-95');
        btn.innerHTML = '<span class="loading loading-spinner loading-xs"></span><span>Đang copy prompt...</span>';
    }

    try {
        await navigator.clipboard.writeText(el.value);
        window.Toast?.success('Đã copy prompt vào clipboard.');

        if (btn) {
            btn.classList.remove('btn-primary', 'scale-95');
            btn.classList.add('btn-success', 'scale-105');
            btn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span>Đã copy!</span>';

            setTimeout(() => {
                btn.classList.remove('btn-success', 'scale-105');
                btn.classList.add('btn-primary');
                btn.innerHTML = idleHtml;
                btn.disabled = false;
            }, 1500);
        }
    } catch (e) {
        console.error('[content-outlines] copy failed', e);
        el.select();

        if (btn) {
            btn.classList.remove('scale-95');
            btn.innerHTML = idleHtml;
            btn.disabled = false;
        }
    }
};

// §4.5 (v1.1) — download .md client-side, không round-trip server (nội dung đã có sẵn trong DOM).
window.contentOutlineDownloadPrompt = function (filename) {
    const el = document.getElementById('content-outline-prompt');
    if (!el) return;

    const blob = new Blob([el.value], { type: 'text/markdown;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename || 'content-outline.md';
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
};

// §4.5 (v1.1) — gom mỗi <h2> (Str::markdown() render CommonMark chuẩn, phẳng) + các node theo
// sau nó vào 1 <details><summary> — chỉ chạy 1 LẦN (kiểm tra data-collapsible-applied) vì tab
// "Xem trước" có thể được click lại nhiều lần trong 1 session xem trang.
window.contentOutlineMakeCollapsible = function () {
    const container = document.getElementById('content-outline-preview');
    if (!container || container.dataset.collapsibleApplied) return;

    const children = Array.from(container.children);
    const fragment = document.createDocumentFragment();
    let currentDetails = null;
    let currentBody = null;

    children.forEach((node) => {
        if (node.tagName === 'H2') {
            currentDetails = document.createElement('details');
            currentDetails.open = true;
            currentDetails.className = 'border border-base-200 rounded-lg mb-2 px-3';

            const summary = document.createElement('summary');
            summary.className = 'cursor-pointer font-semibold py-2';
            summary.textContent = node.textContent;
            currentDetails.appendChild(summary);

            currentBody = document.createElement('div');
            currentBody.className = 'pb-2';
            currentDetails.appendChild(currentBody);

            fragment.appendChild(currentDetails);
        } else if (currentBody) {
            currentBody.appendChild(node);
        } else {
            // Nội dung TRƯỚC h2 đầu tiên (persona mở đầu) — hiện thẳng, không bọc collapsible.
            fragment.appendChild(node);
        }
    });

    container.innerHTML = '';
    container.appendChild(fragment);
    container.dataset.collapsibleApplied = '1';
};

// §4.2 (v1.1) — confirm trước khi "Sinh lại" (GHI ĐÈ prompt cũ, không thể khôi phục) — form gắn
// data-confirm-regenerate="1" ở edit.blade.php, KHÔNG áp cho form tạo mới (create.blade.php).
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form[data-confirm-regenerate]');
    if (!form) return;

    form.addEventListener('submit', (e) => {
        if (!window.confirm('Sinh lại sẽ GHI ĐÈ prompt hiện tại bằng prompt mới — KHÔNG thể khôi phục lại prompt cũ (không versioning). Tiếp tục?')) {
            e.preventDefault();
        }
    });
});

// ── _form.blade.php — category picker + ước lượng độ dài prompt (§4.1) ─────
document.addEventListener('alpine:init', () => {
    Alpine.data('contentOutlineForm', (serverData = {}) => {
        const { foundationApiUrlTemplate = '', initialCategoryUuid = null, fields = {} } = serverData;

        // Baseline ước lượng (persona + hệ giá trị gia đình + quy trình BOTTOM) theo outline_depth
        // — KHÔNG chính xác tuyệt đối (đó là việc của BuildContentOutlinePromptAction::
        // estimateWordCount() ở server, xem show.blade.php) — chỉ đủ để cảnh báo SỚM trước khi submit.
        const BASELINE_WORDS = { brief: 180, standard: 350, detailed: 480 };
        const FOUNDATION_WORD_CAP = { brief: 60, standard: 150, detailed: 400 }; // ~ char limit / 5

        return {
            categoryUuid: initialCategoryUuid || '',
            foundation: null,
            fields: {
                outline_depth: fields.outline_depth || 'standard',
                secondary_keywords: fields.secondary_keywords || '',
                target_audience: fields.target_audience || '',
                content_goal: fields.content_goal || '',
                tone_style: fields.tone_style || '',
                competitor_urls: fields.competitor_urls || '',
                additional_notes: fields.additional_notes || '',
            },

            get estimatedWordCount() {
                const depth = ['brief', 'standard', 'detailed'].includes(this.fields.outline_depth)
                    ? this.fields.outline_depth
                    : 'standard';

                let total = BASELINE_WORDS[depth];
                total += countWords(this.fields.secondary_keywords);
                total += countWords(this.fields.target_audience);
                total += countWords(this.fields.content_goal);
                total += countWords(this.fields.tone_style);
                total += countWords(this.fields.competitor_urls);
                total += countWords(this.fields.additional_notes);

                if (this.foundation) {
                    const cap = FOUNDATION_WORD_CAP[depth];
                    ['core_focus', 'unique_angle', 'pain_points', 'objections', 'decision_criteria', 'rejected_ideas']
                        .forEach((key) => {
                            if (this.foundation[key]) total += Math.min(countWords(this.foundation[key]), cap);
                        });
                }

                return total;
            },

            init() {
                if (this.categoryUuid) this.onCategoryChange();
            },

            async onCategoryChange() {
                if (!this.categoryUuid) {
                    this.foundation = null;

                    return;
                }

                const url = foundationApiUrlTemplate.replace('__UUID__', this.categoryUuid);

                try {
                    const res = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    });
                    const data = await res.json();
                    this.foundation = data.foundation || null;
                } catch (e) {
                    console.error('[content-outlines] fetch foundation failed', e);
                    this.foundation = null;
                }
            },
        };
    });
});

// ── index.blade.php — Tabulator + delete confirm (§4.3 — hiện rõ label/topic) ──
const COLUMNS = [
    {
        title: 'Dàn ý', field: 'label', minWidth: 220, sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<a href="' + esc(d.show_url) + '" class="link link-hover text-sm font-medium">' + esc(d.label) + '</a>';
            if (d.is_long_prompt) html += ' <span class="badge badge-xs badge-warning" title="Prompt dài, có thể bị AI ngoài cắt">dài</span>';
            html += '<p class="text-xs text-base-content/40 truncate max-w-xs">' + esc(d.topic) + '</p>';

            return html;
        },
    },
    {
        title: 'Từ khoá', field: 'target_keyword', minWidth: 140,
        formatter: (cell) => '<code class="text-xs">' + esc(cell.getValue()) + '</code>',
    },
    { title: 'Chuyên mục', field: 'category_name', minWidth: 140, formatter: (cell) => esc(cell.getValue() || '—') },
    { title: 'Người tạo', field: 'created_by_name', width: 140, formatter: (cell) => esc(cell.getValue() || '—') },
    {
        title: 'Bài viết', field: 'linked_article_title', minWidth: 160, headerSort: false,
        formatter: (cell) => cell.getValue() ? '<span class="badge badge-xs badge-success">Đã gắn</span> ' + esc(cell.getValue()) : '<span class="text-base-content/30">—</span>',
    },
    { title: 'Ngày tạo', field: 'created_at', width: 140, sorter: 'string' },
    {
        title: '', field: 'uuid', width: 90, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();

            return '<button class="btn btn-ghost btn-xs text-error" title="Xoá"'
                + ' data-url="' + esc(d.delete_url) + '"'
                + ' data-label="' + esc(d.label) + '"'
                + ' data-topic="' + esc(d.topic) + '"'
                + ' onclick="window.contentOutlineDeleteConfirm(this.dataset.url, this.dataset.label, this.dataset.topic)">Xoá</button>';
        },
    },
];

let pendingDeleteUrl = null;

window.contentOutlineDeleteConfirm = function (url, label, topic) {
    pendingDeleteUrl = url;

    const labelEl = document.getElementById('contentOutlineDeleteLabel');
    const topicEl = document.getElementById('contentOutlineDeleteTopic');
    if (labelEl) labelEl.textContent = '"' + (label || '') + '"';
    if (topicEl) topicEl.textContent = topic || '';

    document.getElementById('contentOutlineDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('contentOutlineConfirmDeleteBtn');
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
            console.error('[content-outlines] delete failed', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled = false;
            this.textContent = 'Xoá';
            pendingDeleteUrl = null;
        }
    });
});

document.addEventListener('alpine:init', () => {
    Alpine.data('contentOutlinesListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        let tableInst = null;
        let reloadChain = Promise.resolve();

        function queueReload() {
            reloadChain = reloadChain
                .catch(() => {})
                .then(() => tableInst?.replaceData())
                .catch((e) => console.error('[content-outlines] reload failed', e));
        }

        return {
            filters: { search: '' },

            get hasFilters() {
                return !!this.filters.search;
            },

            init() {
                this.$watch('filters.search', () => queueReload());
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#content-outlines-table', {
                    ajaxURL: apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {};
                        if (self.filters.search) p.search = self.filters.search;

                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[content-outlines] API error', error),

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'
                        + '<p class="text-sm">Chưa có dàn ý nào</p></div>',
                });

                window.contentOutlinesTable = tableInst;
            },

            reset() {
                this.filters.search = '';
                queueReload();
            },
        };
    });
});
