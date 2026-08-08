/**
 * pages/markdown-preview.js
 * spec/Markdown_Content_Negotiation_Technical_Specification.md — trang admin "Xem trước
 * Markdown" (Modules\Post\Features\MarkdownPreview, không thuộc spec, bổ sung theo yêu cầu QA):
 * TomSelect remote (chọn bài viết đã publish) — chọn xong tự submit form GET, load lại trang
 * với ?translation_id=... để Controller render sẵn bên server (không cần AJAX riêng cho preview).
 *
 * preload:true — cùng lý do breaking-news-form.js: mặc định TomSelect chỉ load() khi gõ ký tự,
 * mở dropdown mà chưa gõ gì sẽ thấy trống hoàn toàn dù đã có rất nhiều bài xuất bản.
 */
import { createTsRemote } from '@shared/tom-select-factory.js';

document.addEventListener('DOMContentLoaded', () => {
    const picker = document.getElementById('markdown-preview-picker');
    if (!picker) return;

    const form = picker.closest('form');

    createTsRemote(picker, {
        url:         picker.dataset.tsRemoteUrl,
        valueField:  'id',
        labelField:  'text',
        searchField: ['text'],
        placeholder: 'Tìm bài viết theo tiêu đề...',
        preload:     true,
        render: {
            option: (data) => `
                <div class="py-1">
                    <div class="text-sm font-medium">${data.text}</div>
                    ${data.published_at ? `<div class="text-xs opacity-60">${data.published_at}</div>` : ''}
                </div>`,
            no_results: () => `<div class="no-results" style="padding:.75rem;font-size:.875rem;color:#94a3b8;text-align:center">Không tìm thấy bài viết</div>`,
        },
        onChange(value) {
            if (value) form?.submit();
        },
    });
});
