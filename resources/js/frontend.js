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

    /**
     * newsletterSignup — khối "Đăng ký nhận bản tin" trang chủ
     * (resources/views/components/frontend/newsletter-signup.blade.php). Submit qua fetch()
     * JSON tới Modules\Newsletter (route newsletter.public.subscribe,
     * PublicSubscriptionController::subscribe()) — controller đã có nhánh trả JSON riêng khi
     * request wantsJson() (Accept: application/json ở đây), lỗi validate (422) Laravel tự trả
     * JSON {message, errors} mặc định nên không cần xử lý gì thêm ngoài đọc data.errors.
     *
     * config.endpoint truyền từ Blade (route() không gọi được trong file JS tĩnh) — cùng cách
     * loadMoreArticles/loadMoreEvents nhận config ở trên.
     *
     * Form chỉ có 1 ô email (đúng spec/content.md) nhưng SubscribeData của Modules\Newsletter
     * bắt buộc full_name (quyết định đã chốt riêng của module, không đổi ở đây) — tự suy ra 1
     * giá trị từ phần trước "@" của email lúc submit thay vì hỏi thêm người dùng.
     */
    Alpine.data('newsletterSignup', (config) => ({
        endpoint: config.endpoint,
        email: '',
        agreed: false,
        loading: false,
        success: false,
        errorMessage: '',

        get canSubmit() {
            return this.email !== '' && this.agreed;
        },

        async submit() {
            if (!this.canSubmit || this.loading) {
                return;
            }

            this.loading = true;
            this.errorMessage = '';

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
                const derivedFullName = this.email.split('@')[0] || 'Subscriber';

                const response = await fetch(this.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ full_name: derivedFullName, email: this.email }),
                });

                const data = await response.json().catch(() => ({}));

                if (response.ok) {
                    this.success = true;
                    return;
                }

                if (response.status === 422 && data.errors) {
                    this.errorMessage = Object.values(data.errors).flat()[0] ?? 'Vui lòng kiểm tra lại thông tin đã nhập.';
                } else if (response.status === 429) {
                    this.errorMessage = 'Bạn thao tác quá nhanh, vui lòng thử lại sau ít phút.';
                } else {
                    this.errorMessage = 'Có lỗi xảy ra, vui lòng thử lại sau.';
                }
            } catch (error) {
                this.errorMessage = 'Lỗi kết nối. Vui lòng thử lại.';
            } finally {
                this.loading = false;
            }
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

    // Băng chuyển liên tục kiểu nhandan.vn (khối ".feature .news" trang chủ — xem
    // main.min-*.js: new Swiper(".feature .news", {slidesPerView:'auto', spaceBetween:20,
    // speed:1500, loop:true, autoplay:{delay:2500}})). MỖI tiêu đề là 1 slide riêng (không gom
    // nhóm), slidesPerGroup mặc định = 1 nên mỗi lượt autoplay chỉ trượt sang ĐÚNG 1 tiêu đề.
    // Tiêu đề mới luôn vào từ phải, tiêu đề cũ trôi dần sang trái và ra khỏi khung — phần bị
    // cắt ở mép phải được che bằng gradient mờ dần (CSS #breaking-news-ticker .swiper::after
    // trong resources/css/frontend.css), y hệt cách nhandan.vn dùng ".news:before" — thay vì
    // cố canh "sát mép trái tuyệt đối".
    //
    // loop:true (không dùng rewind) — yêu cầu trượt LIỀN MẠCH đúng thứ tự 1-2-3-4-1-2-3...,
    // không "giật lùi" về tin đầu như hiệu ứng rewind của Swiper (rewind trượt NGƯỢC chiều để
    // quay về slide đầu, trông như bị reset chứ không phải trượt tiếp). loop:true mới cho hiệu
    // ứng trượt tiếp đúng 1 chiều, NHƯNG cần đủ số lượng slide để Swiper dựng vùng đệm — tin
    // nóng thực tế có lúc chỉ 3-4 tin (ít hơn nhiều so với danh sách luôn dồi dào của
    // nhandan.vn), không đủ đệm thì Swiper tự tắt loop (đã gặp thực tế — trượt tới tin cuối rồi
    // dừng hẳn). Khắc phục bằng cách lặp lại mảng tin cho đủ MIN_LOOP_SLIDES bản ghi DOM trước
    // khi đưa vào Swiper — chỉ ảnh hưởng dữ liệu hiển thị (các tin gốc lặp lại vài vòng trong
    // danh sách slide), không đổi thứ tự hay nội dung, đủ để loop luôn hoạt động bất kể có bao
    // nhiêu tin đang hiệu lực.
    const MIN_LOOP_SLIDES = 12;
    const padForLoop = (items) => {
        if (items.length <= 1) return items;
        const out = [];
        while (out.length < MIN_LOOP_SLIDES) out.push(...items);
        return out;
    };

    const itemLink = (item) => `
        <div class="swiper-slide !w-auto">
            <a href="${esc(item.url)}" class="flex items-center gap-2 py-2 whitespace-nowrap hover:underline">
                <svg class="w-4 h-4 text-warning shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.958a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.447a1 1 0 00-.363 1.118l1.287 3.957c.3.922-.755 1.688-1.538 1.118l-3.367-2.446a1 1 0 00-1.176 0l-3.367 2.446c-.783.57-1.838-.196-1.538-1.118l1.286-3.957a1 1 0 00-.363-1.118L2.062 9.385c-.783-.57-.38-1.81.588-1.81h4.163a1 1 0 00.95-.69l1.286-3.958z"/>
                </svg>
                <span class="text-sm font-medium">${esc(item.headline)}</span>
            </a>
        </div>`;

    wrapper.innerHTML = padForLoop(config.items).map(itemLink).join('');

    const swiper = window.initSwiper(el.querySelector('.breaking-news-swiper'), {
        direction:       'horizontal',
        slidesPerView:   'auto',
        spaceBetween:    20,
        loop:            config.items.length > 1,
        speed:           1500,
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
            swiper.appendSlide(padForLoop(data.items).map(itemLink));

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
