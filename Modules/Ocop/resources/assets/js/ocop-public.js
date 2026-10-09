/**
 * Modules/Ocop/resources/assets/js/ocop-public.js
 * Trang công khai /ocop — TomSelect cho bộ lọc "Danh mục" (docs/form-ui-spec.md §22.3 createTs).
 * Build: vite.config.frontend.js → public/build/frontend/
 *
 * Blade (thứ tự §22.8 — tom-select.js TRƯỚC file này):
 *   @vite(['resources/scss/tom-select-frontend.scss'], 'build/frontend')                       — styles
 *   @vite(['resources/js/modules/tom-select.js', 'Modules/Ocop/resources/assets/js/ocop-public.js'], 'build/frontend')
 *
 * Danh mục 3 cấp (Sản phẩm → Nhóm → Phân nhóm), mỗi <option> mang data-depth — render thụt lề
 * theo cấp, giữ nguyên thứ tự cây. onchange="this.form.submit()" gốc trên <select> tự bắt sự
 * kiện change TomSelect phát ra (chọn / bấm xoá) → lọc ngay, không cần gán lại thủ công.
 */

import { createTs } from '@shared/tom-select-factory.js';

const NBSP_PREFIX = /^ +/;

function esc(v) {
    return String(v).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
}

const label = (data) => String(data.text).replace(NBSP_PREFIX, '');

document.addEventListener('DOMContentLoaded', () => {
    const el = document.getElementById('ts-category');
    if (!el || el.tomselect) return;

    createTs(el, {
        allowEmptyOption: true,
        maxOptions:       null,
        placeholder:      el.dataset.tsPlaceholder ?? 'Tất cả danh mục',
        searchField:      ['text'],
        // Giữ thứ tự cây (pre-order); khi gõ tìm thì ưu tiên độ khớp trước.
        sortField:        [{ field: '$score' }, { field: '$order' }],
        render: {
            // Thụt lề ở phần tử CON — phần tử gốc trả về chính là .ts-option, theme _tom-select.scss
            // đặt padding !important cho nó nên padding-left theo cấp đặt ở đây sẽ bị đè mất.
            option(data) {
                const depth = Number(data.depth || 0);
                if (data.value === '') {
                    return `<div><span style="font-weight:600">${esc(label(data))}</span></div>`;
                }
                const style = [
                    'display:flex', 'align-items:baseline', 'gap:.35rem',
                    `margin-left:${depth * 1.25}rem`,
                    depth === 0 ? 'font-weight:600' : '',
                    depth >= 2 ? 'opacity:.85' : '',
                ].filter(Boolean).join(';');
                const branch = depth > 0 ? '<span aria-hidden="true" style="opacity:.4">└</span>' : '';
                return `<div><span style="${style}">${branch}<span>${esc(label(data))}</span></span></div>`;
            },
            item(data) {
                return `<div class="item" title="${esc(label(data))}">${esc(label(data))}</div>`;
            },
            no_results: () => '<div class="no-results">Không tìm thấy danh mục</div>',
        },
    });
});
