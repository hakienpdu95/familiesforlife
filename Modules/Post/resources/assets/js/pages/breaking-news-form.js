/**
 * pages/breaking-news-form.js
 * spec/Breaking_News_Ticker_Technical_Specification.md §6.2 — form đánh dấu tin nóng:
 * TomSelect remote (chọn bài viết) + Flatpickr datetime (starts_at/ends_at).
 *
 * Chọn bài viết dùng createTsRemote() TRỰC TIẾP (không qua initAllTomSelects() dùng chung) vì
 * cần 2 tuỳ chỉnh riêng initAllTomSelects() không hỗ trợ:
 *   1. preload:true — mặc định TomSelect CHỈ gọi load() khi người dùng gõ ký tự (shouldLoad
 *      yêu cầu query.length > 0), nên mở dropdown mà chưa gõ gì sẽ thấy TRỐNG HOÀN TOÀN dù hệ
 *      thống có rất nhiều bài đã xuất bản (đã gặp thật — trông như "không chọn được bài viết").
 *      preload:true gọi load('') ngay lúc khởi tạo, hiện sẵn danh sách mặc định (cùng cách
 *      createTsAssignee() trong tom-select-factory.js đã xử lý).
 *   2. render.option tuỳ biến — hiện thêm chuyên mục + ngày đăng dưới tiêu đề, giúp phân biệt
 *      các bài trùng/gần giống tên nhau (chỉ thấy tiêu đề trần rất dễ chọn nhầm bài).
 */
import { createTsRemote } from '@shared/tom-select-factory.js';

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

document.addEventListener('DOMContentLoaded', () => {
    const picker = document.getElementById('article-picker');
    if (!picker) return;

    window.initAllDatePickers?.();

    const activeArticleIds = new Set(
        JSON.parse(picker.dataset.activeIds || '[]').map(Number)
    );
    const warningEl = document.getElementById('article-picker-warning');

    createTsRemote(picker, {
        url:         picker.dataset.tsRemoteUrl,
        valueField:  'id',
        labelField:  'text',
        searchField: ['text'],
        placeholder: picker.dataset.tsPlaceholder || 'Tìm bài viết theo tiêu đề...',
        preload:     true,
        render: {
            option: (data) => `
                <div class="py-1">
                    <div class="text-sm font-medium">${esc(data.text)}</div>
                    <div class="text-xs opacity-60 flex items-center gap-2">
                        ${data.category ? `<span class="badge badge-ghost badge-xs">${esc(data.category)}</span>` : ''}
                        ${data.published_at ? `<span>${esc(data.published_at)}</span>` : ''}
                    </div>
                </div>`,
            no_results: () => `<div class="no-results" style="padding:.75rem;font-size:.875rem;color:#94a3b8;text-align:center">Không tìm thấy bài viết</div>`,
        },
        onChange(value) {
            if (!warningEl) return;

            // Form tạo cho phép chọn nhiều bài (multiple) — value khi đó là mảng id, khác form
            // sửa (value là 1 id đơn). Chuẩn hoá về mảng để dùng chung 1 logic kiểm tra.
            const values = Array.isArray(value) ? value : [value];
            const hasDuplicate = values.some((v) => v && activeArticleIds.has(Number(v)));

            warningEl.classList.toggle('hidden', !hasDuplicate);
        },
    });
});
