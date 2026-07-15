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

    /**
     * loadMoreArticles — nút "Xem thêm bài viết" (khối lưới cuối trang chủ, trước footer —
     * Modules/Post/resources/views/public/home.blade.php). Nội dung mục menu/bài viết vẫn
     * render server-side ở lần tải đầu (SEO); mỗi lần bấm chỉ fetch JSON (html đã render sẵn +
     * has_more + next_cursor) từ PublicCategoryController::loadMore() rồi nối thêm vào cuối
     * lưới — không điều hướng trang, không dùng Previous/Next nữa.
     *
     * Hiệu năng:
     *   - Cursor (afterPublishedAt/afterId) thay offset/exclude phình dần — xem
     *     LoadMoreArticlesQuery (docblock PHP) để biết lý do (tránh OFFSET quét bỏ N dòng đầu
     *     và whereNotIn với mảng ngày càng lớn).
     *   - maxTotal chặn cứng ở CẢ 2 phía: client dừng gọi thêm khi đã đạt (đỡ hẳn request thừa),
     *     server (loadMore()) vẫn tự chặn lại nếu client bị sửa/bypass.
     *   - AbortController: bấm nhanh nhiều lần (hoặc rời trang giữa chừng) huỷ request cũ thay
     *     vì để nó âm thầm resolve muộn và ghi đè lên cursor/hasMore mới hơn.
     *   - appendCards(): chèn 1 lần bằng <template>/DocumentFragment (1 lần reflow) thay vì
     *     insertAdjacentHTML lặp lại nhiều lần; thẻ mới fade+slide-in nhẹ qua Web Animations API
     *     (rẻ hơn CSS animation toàn cục, không cần thêm class vào article-card.blade.php) để
     *     cảm giác mượt thay vì bài mới "đập" thẳng vào khung hình.
     */
    Alpine.data('loadMoreArticles', (config) => ({
        endpoint: config.endpoint,
        exclude: config.exclude,
        afterPublishedAt: config.afterPublishedAt,
        afterId: config.afterId,
        loaded: config.loaded,
        maxTotal: config.maxTotal,
        hasMore: config.hasMore,
        loading: false,
        abortController: null,

        async loadMore() {
            if (this.loading || !this.hasMore || this.loaded >= this.maxTotal) {
                return;
            }

            this.loading = true;
            this.abortController?.abort();
            this.abortController = new AbortController();

            try {
                const url = new URL(this.endpoint, window.location.origin);
                url.searchParams.set('loaded', this.loaded);
                url.searchParams.set('exclude', this.exclude);
                if (this.afterPublishedAt) url.searchParams.set('after_published_at', this.afterPublishedAt);
                if (this.afterId) url.searchParams.set('after_id', this.afterId);

                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                    signal: this.abortController.signal,
                });

                if (! response.ok) {
                    throw new Error(`load-more failed: ${response.status}`);
                }

                const data = await response.json();

                this.appendCards(data.html);

                this.loaded += data.count ?? 0;
                if (data.next_cursor) {
                    this.afterPublishedAt = data.next_cursor.published_at;
                    this.afterId = data.next_cursor.id;
                }
                this.hasMore = data.has_more && this.loaded < this.maxTotal;
            } catch (error) {
                if (error.name !== 'AbortError') {
                    // Dừng hẳn thay vì lặp lỗi vô hạn nếu người dùng bấm lại — họ vẫn xem được
                    // các bài đã tải trước đó, chỉ mất nút "Xem thêm".
                    this.hasMore = false;
                }
            } finally {
                this.loading = false;
            }
        },

        appendCards(html) {
            const template = document.createElement('template');
            template.innerHTML = html.trim();
            const nodes = Array.from(template.content.children);

            nodes.forEach((node) => { node.style.opacity = '0'; });
            this.$refs.grid.append(template.content);

            requestAnimationFrame(() => {
                nodes.forEach((node, i) => {
                    node.animate(
                        [
                            { opacity: 0, transform: 'translateY(8px)' },
                            { opacity: 1, transform: 'translateY(0)' },
                        ],
                        { duration: 220, delay: i * 30, easing: 'ease-out', fill: 'forwards' },
                    );
                });
            });
        },
    }));
});

document.addEventListener('DOMContentLoaded', () => {
    Alpine.start();
});
