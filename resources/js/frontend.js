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
 * frontendNav — trạng thái tương tác dùng chung bởi site-header
 * (resources/views/layouts/partials/frontend-header.blade.php).
 *
 * Mục menu (MenuItem) KHÔNG nằm trong state này — chúng được render
 * server-side bằng @foreach (Blade), không phải Alpine x-for, để nội
 * dung nav luôn có mặt trong HTML gửi về (SEO/crawler) thay vì phải đợi
 * JS chạy xong mới thấy. Alpine ở đây chỉ quản lý phần "mở/đóng" (UI).
 *
 * Cấu trúc/CSS site-header copy 1:1 từ spec/header.html + spec/main.css —
 * dropdown cấp 2 (.nav-sub) trên desktop mở bằng CSS :hover thuần (không
 * cần Alpine), mobile hiện .nav-sub như 1 danh sách lồng bình thường
 * (không có accordion) — nên chỉ còn 3 state cần JS:
 *   - search:        topbar/toolbar toggle ô tìm kiếm (.is-active theo spec/main.css)
 *   - mobileNavOpen: toolbar .btn-expand bật/tắt bảng .nav toàn màn hình dưới 594px
 *     (spec/main.css chỉ có phần CSS phản ứng .is-open, JS gắn class của site gốc
 *     không có sẵn — xem resources/css/frontend.css).
 *   - pinned:        sticky header khi cuộn — logic port 1:1 từ windowScroll()
 *     (spec/app.js), viết lại bằng Alpine thay vì jQuery/window.onscroll trực
 *     tiếp thao tác DOM.
 */
document.addEventListener('alpine:init', () => {
    Alpine.data('frontendNav', () => ({
        search: false,
        mobileNavOpen: false,

        pinned: false,
        headerHeight: 0,

        /**
         * spec/app.js windowScroll() — pin #site-header khi cuộn quá chính chiều
         * cao của nó (đo lại mỗi lần cuộn, giống bản gốc `$("#site-header")
         * .outerHeight()`; khi đã pinned, chiều cao được khoá qua :style nên đo
         * lại vẫn ra cùng 1 giá trị — tự ổn định, không cần cache riêng).
         * $el truyền vào từ x-init="initHeaderPin($el)" trên chính <header>.
         */
        initHeaderPin(el) {
            const evaluate = () => {
                this.headerHeight = el.offsetHeight;

                const scrollTop = window.scrollY
                    ?? document.documentElement.scrollTop
                    ?? document.body.scrollTop;

                this.pinned = scrollTop > this.headerHeight;
            };

            window.addEventListener('scroll', evaluate, { passive: true });
            evaluate();
        },
    }));
});

document.addEventListener('DOMContentLoaded', () => {
    Alpine.start();
});
