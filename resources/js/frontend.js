/**
 * resources/js/frontend.js
 * ────────────────────────────────────────────────────────────────────
 * Entry point riêng cho CỔNG THÔNG TIN công khai (Post::public.*).
 *
 * Khác app.js (backend): không cần jQuery / Laravel Echo / iconify-icon —
 * portal chỉ dùng Alpine cho drawer di động + dropdown danh mục, giữ
 * bundle tối thiểu cho tốc độ tải trang công khai (SEO/Core Web Vitals).
 *
 * Pattern Alpine giống resources/js/app.js: expose window.Alpine TRƯỚC
 * start() để script inline trong blade có thể Alpine.data()/store() an
 * toàn trước DOMContentLoaded.
 */
import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * frontendNav — trạng thái tương tác dùng chung bởi header/drawer
 * (resources/views/layouts/partials/frontend-*.blade.php).
 *
 * Danh mục (categories) KHÔNG nằm trong state này — chúng được render
 * server-side bằng @foreach (Blade), không phải Alpine x-for, để nội
 * dung nav luôn có mặt trong HTML gửi về (SEO/crawler) thay vì phải đợi
 * JS chạy xong mới thấy. Alpine ở đây chỉ quản lý phần "mở/đóng" (UI).
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('frontendNav', () => ({
        subOpen: null,
        mobileSub: null,
        search: false,
    }));
});

document.addEventListener('DOMContentLoaded', () => {
    Alpine.start();
});
