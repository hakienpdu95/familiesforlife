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
        // Tuỳ chọn — có khi component được dùng ở trang danh mục (public/category.blade.php)
        // để lọc thêm đúng danh mục đó; undefined/null ở trang chủ → không ảnh hưởng gì (không
        // gửi tham số category_id).
        categoryId: config.categoryId ?? null,
        // Tuỳ chọn — số bài tải mỗi lần bấm "Xem thêm". undefined ở trang chủ → server tự
        // dùng mặc định 8 (giữ nguyên hành vi cũ); category.blade.php truyền 12 (đúng số bài
        // hiển thị ban đầu, xem PublicCategoryController::loadMore()).
        limit: config.limit ?? null,
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
                if (this.limit) url.searchParams.set('limit', this.limit);
                if (this.categoryId) url.searchParams.set('category_id', this.categoryId);

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

    /**
     * loadMoreEvents — "Xem thêm sự kiện" (su-kien, su-kien/danh-muc/{slug}) — cùng cấu trúc
     * loadMoreArticles ở trên, chỉ khác tên cursor (afterStartDate/after_start_date thay
     * afterPublishedAt/after_published_at) vì Event sort ASC theo start_date (sắp diễn ra gần
     * nhất trước), không phải DESC theo published_at như Post.
     */
    Alpine.data('loadMoreEvents', (config) => ({
        endpoint: config.endpoint,
        exclude: config.exclude,
        afterStartDate: config.afterStartDate,
        afterId: config.afterId,
        loaded: config.loaded,
        maxTotal: config.maxTotal,
        hasMore: config.hasMore,
        categoryId: config.categoryId ?? null,
        limit: config.limit ?? null,
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
                if (this.afterStartDate) url.searchParams.set('after_start_date', this.afterStartDate);
                if (this.afterId) url.searchParams.set('after_id', this.afterId);
                if (this.limit) url.searchParams.set('limit', this.limit);
                if (this.categoryId) url.searchParams.set('category_id', this.categoryId);

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
                    this.afterStartDate = data.next_cursor.start_date;
                    this.afterId = data.next_cursor.id;
                }
                this.hasMore = data.has_more && this.loaded < this.maxTotal;
            } catch (error) {
                if (error.name !== 'AbortError') {
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

/**
 * initBreakingNewsTicker — spec/Breaking_News_Ticker_Technical_Specification.md §7.3 — dải
 * ticker "tin nóng" ghim đầu trang chủ. Dùng Swiper (resources/js/modules/swiper.js, đã có sẵn
 * trong dependencies) để có hiệu ứng TRƯỢT NGANG mượt giữa các tiêu đề, thay cho đổi text tức
 * thì của bản Alpine trước đó. Swiper tự quản lý DOM .swiper-slide theo cơ chế riêng (removeAll
 * Slides()/appendSlide()) nên viết bằng vanilla JS thay vì Alpine component — trộn Alpine x-for
 * với vùng DOM Swiper tự control dễ xung đột (2 bên cùng tranh nhau viết lại cùng 1 khúc DOM).
 *
 * Vẫn giữ nguyên polling JSON định kỳ (KHÔNG đổi endpoint/logic) để cảm giác "cập nhật liên
 * tục" mà không cần F5 — chỉ đổi cách áp dữ liệu mới vào DOM (thay slide qua API của Swiper
 * thay vì gán lại 1 biến Alpine reactive).
 */
function initBreakingNewsTicker() {
    const el = document.getElementById('breaking-news-ticker');
    if (!el || !window.initSwiper) {
        return;
    }

    const config  = JSON.parse(el.dataset.config);
    const wrapper = el.querySelector('.swiper-wrapper');

    const esc = (v) => {
        if (v == null) return '';
        return String(v)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    };

    // .swiper-slide chỉ để Swiper quản lý (CSS core của Swiper set display/height cho phần tử
    // này) — layout flex phải nằm ở thẻ lồng bên trong, không đặt trực tiếp trên .swiper-slide,
    // vì vendor-swiper.css load sau frontend.css (do @vite của swiper.js nằm trong @push
    // ('scripts') cuối trang) nên sẽ đè mất display:flex nếu đặt cùng cấp.
    // slidesPerView:'auto' (dưới) — mỗi item rộng theo ĐÚNG độ dài tiêu đề thật (whitespace-
    // nowrap, không truncate/marquee), không chia đều 3 cột bằng nhau nữa; tiêu đề ngắn/dài
    // đứng cạnh nhau linh hoạt, hiển thị TRỌN VẸN, Swiper tự tính có bao nhiêu item vừa 1 màn.
    const slideHtml = (item) => `
        <div class="swiper-slide !w-auto">
            <a href="${esc(item.url)}" class="flex items-center gap-2 py-2 whitespace-nowrap hover:underline">
                <span class="badge badge-sm badge-neutral shrink-0">${esc(item.badge)}</span>
                <span class="text-sm font-medium">${esc(item.headline)}</span>
            </a>
        </div>`;

    wrapper.innerHTML = config.items.map(slideHtml).join('');

    const swiper = window.initSwiper(el.querySelector('.breaking-news-swiper'), {
        direction:       'horizontal',
        slidesPerView:   'auto',
        spaceBetween:    32,
        loop:            true,
        speed:           600,
        navigation:      false,
        pagination:      false,
        allowTouchMove:  false,
        autoplay: config.items.length > 1
            ? { delay: config.rotateMs, disableOnInteraction: false }
            : false,
    });

    setInterval(async () => {
        try {
            const res  = await fetch(config.pollUrl, { headers: { Accept: 'application/json' } });
            const data = await res.json();

            swiper.removeAllSlides();
            swiper.appendSlide(data.items.map(slideHtml));

            // loopDestroy()/loopCreate() bắt buộc SAU KHI đổi slide khi loop:true — Swiper nhân
            // bản slide nội bộ để trượt liền mạch, đổi nội dung mà không dựng lại các bản sao
            // này sẽ để lại slide CŨ trong vòng lặp (hiện lại tin đã hết hạn khi trượt qua).
            swiper.loopDestroy();
            swiper.loopCreate();
            swiper.update();

            if (data.items.length > 1) {
                swiper.autoplay?.start();
            } else {
                swiper.autoplay?.stop();
            }
        } catch (e) {
            // Bỏ qua lỗi mạng tạm thời — vòng poll kế tiếp tự thử lại, không cần báo lỗi cho
            // người đọc (ticker chỉ là nội dung phụ trợ, không phải luồng nghiệp vụ chính).
        }
    }, config.pollMs);
}

document.addEventListener('DOMContentLoaded', initBreakingNewsTicker);
