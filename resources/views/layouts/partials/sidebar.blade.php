<aside class="sidebar" id="sidebar">
<script>try{if(localStorage.getItem('ap_sidebar_collapsed')==='1')document.currentScript.parentElement.classList.add('collapsed')}catch(e){}</script>

    <div class="brand">
        <div class="brand-logo">
            <img src="{{ asset('logo.png') }}" alt="{{ config('app.name') }}" width="143" height="36" class="brand-logo-img">
        </div>
        {{-- brand-name ẩn: tên đã có trong logo image --}}
    </div>

    <nav class="nav-wrap">

        <x-sidebar-section title="Không gian làm việc" :first="true">
        <div class="nav-group">
            <a href="{{ route('backend.dashboard') }}"
               class="nav-link {{ request()->routeIs('backend.dashboard') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span class="nav-label">Dashboard</span>
            </a>
        </div>

        {{-- @can('viewDashboard') — TÊN GATE thật (ApprovalServiceProvider::boot()), KHÔNG
             phải chuỗi permission "approval.view_dashboard" — content_moderator KHÔNG có
             permission Spatie đó (Gate viewDashboard tự OR thêm isPlatformContentModerator()). --}}
        @can('viewDashboard')
        <div class="nav-group">
            <a href="{{ route('backend.approval.dashboard') }}"
               class="nav-link {{ request()->routeIs('backend.approval.dashboard') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="nav-label">Chờ duyệt của tôi</span>
            </a>
        </div>
        @endcan

        @can('viewApprovalHistory')
        <div class="nav-group">
            <a href="{{ route('backend.approval.history') }}"
               class="nav-link {{ request()->routeIs('backend.approval.history') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="nav-label">Lịch sử duyệt</span>
            </a>
        </div>
        @endcan

        {{-- content_editor/content_head (§18.10) — không có permission post_article.* nào
             (org-less) nên không dùng @can(permission) — check thẳng 2 role qua User helper. --}}
        @if(auth()->user()?->isPlatformContentEditor() || auth()->user()?->isPlatformContentHead())
        <div class="nav-group">
            <a href="{{ route('backend.post.articles.pending-review') }}"
               class="nav-link {{ request()->routeIs('backend.post.articles.pending-review') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l6 6v10a2 2 0 01-2 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 13h6M9 17h6M9 9h1"/></svg>
                <span class="nav-label">Bài viết chờ duyệt</span>
            </a>
        </div>
        @endif

        {{-- spec/ContentCalendar_Technical_Specification.md §6/§13 — content_calendar.view cấp cho
             platform_content_creator/section_editor/content_editor/content_head/viewer
             (Modules\ContentCalendar\Database\Seeders\ContentCalendarPermissionSeeder). --}}
        @can(\App\Enums\PermissionEnum::CONTENT_CALENDAR_VIEW->value)
        <details {{ request()->routeIs('backend.contentcalendar.*') ? 'open' : '' }}>
            <summary class="nav-summary {{ request()->routeIs('backend.contentcalendar.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
                <span class="nav-label">Lịch biên tập</span>
                <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
            </summary>
            <div class="sub-menu">
                <a href="{{ route('backend.contentcalendar.board') }}"
                   class="sub-link {{ request()->routeIs('backend.contentcalendar.board') ? 'active' : '' }}">Board</a>
                <a href="{{ route('backend.contentcalendar.calendar') }}"
                   class="sub-link {{ request()->routeIs('backend.contentcalendar.calendar') ? 'active' : '' }}">Lịch tháng</a>
            </div>
        </details>
        @endcan
        </x-sidebar-section>

        <x-sidebar-section title="Nội dung (CMS)">
        {{-- spec/ga-dashboard-statistics.md §9.9 — mở rộng thêm 'post_analytics.view': không role
             nào trong 5 role được cấp quyền này (platform_ops/viewer/content_editor/content_head/
             section_editor) có sẵn POST_ARTICLE_VIEW (đã kiểm tra trực tiếp), nên nếu chỉ gate
             theo POST_ARTICLE_VIEW như cũ thì cả nhóm menu "Bài viết" sẽ ẩn với họ, khiến link
             "Thống kê traffic" không bao giờ hiển thị được dù đã có quyền xem trang đó. --}}
        @if(auth()->user()?->can(\App\Enums\PermissionEnum::POST_ARTICLE_VIEW->value) || auth()->user()?->can('post_analytics.view'))
        <details {{ request()->routeIs('backend.post.*') ? 'open' : '' }}>
            <summary class="nav-summary {{ request()->routeIs('backend.post.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l6 6v10a2 2 0 01-2 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 13h6M9 17h6M9 9h1"/></svg>
                <span class="nav-label">Bài viết</span>
                <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
            </summary>
            <div class="sub-menu">
                <a href="{{ route('backend.post.articles.index') }}"
                   class="sub-link {{ request()->routeIs('backend.post.articles.*') ? 'active' : '' }}">Danh sách bài viết</a>
                <a href="{{ route('backend.post.articles.needs-freshness-review') }}"
                   class="sub-link {{ request()->routeIs('backend.post.articles.needs-freshness-review') ? 'active' : '' }}">Cần rà soát nội dung</a>
                @can('post_analytics.view')
                <a href="{{ route('backend.post.articles.analytics') }}"
                   class="sub-link {{ request()->routeIs('backend.post.articles.analytics') ? 'active' : '' }}">Thống kê traffic</a>
                @endcan
                @can('create', \Modules\Post\Models\PostArticle::class)
                <a href="{{ route('backend.post.articles.create') }}"
                   class="sub-link {{ request()->routeIs('backend.post.articles.create') ? 'active' : '' }}">Thêm bài viết</a>
                @endcan
                @can(\App\Enums\PermissionEnum::POST_CATEGORY_MANAGE->value)
                <a href="{{ route('backend.post.categories.index') }}"
                   class="sub-link {{ request()->routeIs('backend.post.categories.*') ? 'active' : '' }}">Danh mục bài viết</a>
                @endcan
                @can(\App\Enums\PermissionEnum::POST_TAG_MANAGE->value)
                <a href="{{ route('backend.post.tags.index') }}"
                   class="sub-link {{ request()->routeIs('backend.post.tags.*') ? 'active' : '' }}">Quản lý tag</a>
                @endcan
                {{-- spec/Markdown_Content_Negotiation_Technical_Specification.md — công cụ QA
                     xem trước bản Markdown mà content negotiation trả về, gate riêng
                     POST_ARTICLE_VIEW (không dùng chung điều kiện post_analytics.view của khối
                     @if bên ngoài — người chỉ có quyền xem thống kê traffic không cần thấy mục
                     này). --}}
                @can(\App\Enums\PermissionEnum::POST_ARTICLE_VIEW->value)
                <a href="{{ route('backend.post.markdown-preview.index') }}"
                   class="sub-link {{ request()->routeIs('backend.post.markdown-preview.*') ? 'active' : '' }}">Xem trước Markdown (AI)</a>
                @endcan
            </div>
        </details>
        @endif

        {{-- spec/Breaking_News_Ticker_Technical_Specification.md §6.3 — breaking_news.manage
             cấp cho platform_ops/platform_content_head (Modules\Post\Database\Seeders\BreakingNewsPermissionSeeder). --}}
        @can(\App\Enums\PermissionEnum::BREAKING_NEWS_MANAGE->value)
        <div class="nav-group">
            <a href="{{ route('backend.post.breaking-news.items.index') }}"
               class="nav-link {{ request()->routeIs('backend.post.breaking-news.items.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span class="nav-label">Tin nóng</span>
            </a>
        </div>
        @endcan

        {{-- spec/Event_Management_Technical_Specification.md §9 — event.view cấp cho
             platform_content_editor/head/ops (Modules\Event\Database\Seeders\EventPermissionSeeder). --}}
        @can(\App\Enums\PermissionEnum::EVENT_VIEW->value)
        <details {{ request()->routeIs('backend.event.*') ? 'open' : '' }}>
            <summary class="nav-summary {{ request()->routeIs('backend.event.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3M3 11h18M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z"/></svg>
                <span class="nav-label">Sự kiện</span>
                <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
            </summary>
            <div class="sub-menu">
                <a href="{{ route('backend.event.index') }}"
                   class="sub-link {{ request()->routeIs('backend.event.index') ? 'active' : '' }}">Danh sách sự kiện</a>
                @can('event_category.manage')
                <a href="{{ route('backend.event.categories.index') }}"
                   class="sub-link {{ request()->routeIs('backend.event.categories.*') ? 'active' : '' }}">Danh mục sự kiện</a>
                @endcan
            </div>
        </details>
        @endcan

        {{-- spec/Video_Management_Technical_Specification.md §6.7 — video.manage cấp cho
             platform_ops/platform_content_head (Modules\Video\Database\Seeders\VideoPermissionSeeder). --}}
        @can(\App\Enums\PermissionEnum::VIDEO_MANAGE->value)
        <div class="nav-group">
            <a href="{{ route('backend.video.items.index') }}"
               class="nav-link {{ request()->routeIs('backend.video.items.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                <span class="nav-label">Video</span>
            </a>
        </div>
        @endcan

        {{-- spec/Playlist_Technical_Specification.md §0/§6.5 — playlist.manage RIÊNG (không
             mượn video.manage) cấp cho platform_ops/platform_content_head
             (Modules\Playlist\Database\Seeders\PlaylistPermissionSeeder). --}}
        @can(\App\Enums\PermissionEnum::PLAYLIST_MANAGE->value)
        <div class="nav-group">
            <a href="{{ route('backend.playlist.items.index') }}"
               class="nav-link {{ request()->routeIs('backend.playlist.items.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19V6l12-2v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-2c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-2"/></svg>
                <span class="nav-label">Playlist</span>
            </a>
        </div>
        @endcan

        {{-- spec/Page_Static_Pages_Technical_Specification.md §7 — page.manage cấp cho
             platform_ops/platform_content_head (Modules\Page\Database\Seeders\PagePermissionSeeder). --}}
        @can(\App\Enums\PermissionEnum::PAGE_MANAGE->value)
        <div class="nav-group">
            <a href="{{ route('backend.page.items.index') }}"
               class="nav-link {{ request()->routeIs('backend.page.items.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="nav-label">Trang tĩnh</span>
            </a>
        </div>
        @endcan

        {{-- spec/Banner_Management_Technical_Specification.md §6.3 — banner.manage cấp cho
             platform_ops/platform_content_head (Modules\Banner\Database\Seeders\BannerPermissionSeeder). --}}
        @can(\App\Enums\PermissionEnum::BANNER_MANAGE->value)
        <div class="nav-group">
            <a href="{{ route('backend.banner.items.index') }}"
               class="nav-link {{ request()->routeIs('backend.banner.items.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5h16v10H4z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19h16"/></svg>
                <span class="nav-label">Banner</span>
            </a>
        </div>
        @endcan

        {{-- spec/Menu_Navigation_Technical_Specification.md §1 — MenuItem decoupled khỏi
             PostCategory (điều hướng ≠ phân loại nội dung), nên là mục sidebar riêng,
             không lồng trong "Bài viết". Phase 1: chỉ CRUD quản trị, chưa render công khai. --}}
        @can(\App\Enums\PermissionEnum::MENU_MANAGE->value)
        <div class="nav-group">
            <a href="{{ route('backend.menu.items.index') }}"
               class="nav-link {{ request()->routeIs('backend.menu.items.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h7"/></svg>
                <span class="nav-label">Điều hướng menu</span>
            </a>
        </div>
        @endcan

        {{-- spec/Newsletter_Technical_Specification.md §12 — platform-wide, không permission
             Spatie riêng (§0 mục 10). Dùng @can('viewAny', ...) đi qua NewsletterPolicy/Gate
             (KHÔNG gọi thẳng auth()->user()->isPlatformContentEditor() như mục "Bài viết chờ
             duyệt" ở trên) — vì gọi thẳng method là raw PHP, không đi qua Gate nên KHÔNG tự
             hưởng bypass super-admin của Gate::before() (app/Providers/AppServiceProvider.php),
             khiến chính super-admin lại không thấy menu của module do chính mình tạo. --}}
        @can('viewAny', \Modules\Newsletter\Models\NewsletterSubscriber::class)
        <details {{ request()->routeIs('backend.newsletter.*') ? 'open' : '' }}>
            <summary class="nav-summary {{ request()->routeIs('backend.newsletter.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <span class="nav-label">Bản tin</span>
                <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
            </summary>
            <div class="sub-menu">
                <a href="{{ route('backend.newsletter.subscribers.index') }}"
                   class="sub-link {{ request()->routeIs('backend.newsletter.subscribers.*') ? 'active' : '' }}">Người đăng ký</a>
                @can('sendBroadcast', \Modules\Newsletter\Models\NewsletterBroadcastLog::class)
                <a href="{{ route('backend.newsletter.broadcast.create') }}"
                   class="sub-link {{ request()->routeIs('backend.newsletter.broadcast.create') ? 'active' : '' }}">Soạn bản tin</a>
                @endcan
                <a href="{{ route('backend.newsletter.broadcast.logs') }}"
                   class="sub-link {{ request()->routeIs('backend.newsletter.broadcast.logs') ? 'active' : '' }}">Lịch sử gửi</a>
            </div>
        </details>
        @endcan
        </x-sidebar-section>

        <x-sidebar-section title="Dữ liệu chuyên ngành">
        @can('manageQrReports')
        @php($newQrReports = \App\Shared\Traceability\Models\QrReport::where('status', 'new')->count())
        <div class="nav-group">
            <a href="{{ route('backend.qr-reports.index') }}"
               class="nav-link {{ request()->routeIs('backend.qr-reports.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21V4.5A1.5 1.5 0 014.5 3h11.379a1.5 1.5 0 011.06 2.56L15 7.5l1.94 1.94A1.5 1.5 0 0115.879 12H5"/></svg>
                <span class="nav-label">Phản ánh mã QR</span>
                @if($newQrReports > 0)<span class="badge badge-error badge-xs ml-auto">{{ $newQrReports }}</span>@endif
            </a>
        </div>
        @endcan

        {{-- spec/Province_Showcase_Technical_Specification.md §6.1 — ocop.manage cấp cho
             platform_ops/platform_content_head (Modules\Ocop\Database\Seeders\OcopPermissionSeeder).
             Single nav-link (không dropdown) — quản lý danh mục truy cập từ trong trang sản phẩm. --}}
        @can(\App\Enums\PermissionEnum::OCOP_MANAGE->value)
        <div class="nav-group">
            <a href="{{ route('backend.ocop.products.index') }}"
               class="nav-link {{ request()->routeIs('backend.ocop.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.59 13.41 13.42 20.58a2 2 0 01-2.83 0L3 13V3h10l7.59 7.59a2 2 0 010 2.82z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 7h.01"/></svg>
                <span class="nav-label">OCOP</span>
            </a>
        </div>
        @endcan

        @if(Route::has('backend.ocop-subjects.index'))
        @can(\App\Enums\PermissionEnum::OCOP_SUBJECT_MANAGE->value)
        <div class="nav-group">
            <a href="{{ route('backend.ocop-subjects.index') }}"
               class="nav-link {{ request()->routeIs('backend.ocop-subjects.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6"/></svg>
                <span class="nav-label">Chủ thể OCOP</span>
            </a>
        </div>
        @endcan
        @endif

        @can(\App\Enums\PermissionEnum::PRODUCT_VIEW->value)
        <details {{ request()->routeIs('backend.products.*') ? 'open' : '' }}>
            <summary class="nav-summary {{ request()->routeIs('backend.products.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25a1.125 1.125 0 001.125-1.125V6a1.125 1.125 0 00-1.125-1.125H3.375A1.125 1.125 0 002.25 6v.375a1.125 1.125 0 001.125 1.125z"/></svg>
                <span class="nav-label">Sản phẩm & Dịch vụ</span>
                <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
            </summary>
            <div class="sub-menu">
                <a href="{{ route('backend.products.index') }}"
                   class="sub-link {{ request()->routeIs('backend.products.index') ? 'active' : '' }}">Danh sách sản phẩm</a>
                @can('create', \Modules\Product\Models\Product::class)
                <a href="{{ route('backend.products.create') }}"
                   class="sub-link {{ request()->routeIs('backend.products.create') ? 'active' : '' }}">Thêm sản phẩm</a>
                @endcan
                @can(\App\Enums\PermissionEnum::PRODUCT_CATEGORY_MANAGE->value)
                <a href="{{ route('backend.products.categories.index') }}"
                   class="sub-link {{ request()->routeIs('backend.products.categories.*') ? 'active' : '' }}">Danh mục sản phẩm</a>
                @endcan
            </div>
        </details>
        @endcan

        {{-- spec/RealEstateForSale_Technical_Specification.md §6 — real_estate.view cấp cho
             CEO/Sales/Ops/Marketing (config/permissions.php). --}}
        @can(\App\Enums\PermissionEnum::REAL_ESTATE_VIEW->value)
        <details {{ request()->routeIs('backend.real-estate.*') ? 'open' : '' }}>
            <summary class="nav-summary {{ request()->routeIs('backend.real-estate.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span class="nav-label">Bất động sản</span>
                <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
            </summary>
            <div class="sub-menu">
                <a href="{{ route('backend.real-estate.index') }}"
                   class="sub-link {{ request()->routeIs('backend.real-estate.index') ? 'active' : '' }}">Danh sách tin</a>
                @can('create', \Modules\RealEstate\Models\RealEstateListing::class)
                <a href="{{ route('backend.real-estate.create') }}"
                   class="sub-link {{ request()->routeIs('backend.real-estate.create') ? 'active' : '' }}">Đăng tin mới</a>
                @endcan
            </div>
        </details>
        @endcan

        @can(\App\Enums\PermissionEnum::PROVINCE_MANAGE->value)
        <div class="nav-group">
            <a href="{{ route('backend.provinces.index') }}"
               class="nav-link {{ request()->routeIs('backend.provinces.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                <span class="nav-label">Tỉnh thành</span>
            </a>
        </div>
        @endcan

        {{-- spec/Heritage_Technical_Specification.md §4 — heritage.manage cấp cho
             platform_ops/platform_content_head (Modules\Heritage\Database\Seeders\
             HeritagePermissionSeeder). Single nav-link (không dropdown), cùng nguyên tắc OCOP. --}}
        @can(\App\Enums\PermissionEnum::HERITAGE_MANAGE->value)
        <div class="nav-group">
            <a href="{{ route('backend.heritage.sites.index') }}"
               class="nav-link {{ request()->routeIs('backend.heritage.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21h18M5 21V7l8-4v18M13 21V11l6 3v7M9 9v.01M9 12v.01M9 15v.01"/></svg>
                <span class="nav-label">Di tích/Di sản</span>
            </a>
        </div>
        @endcan
        </x-sidebar-section>

        <x-sidebar-section title="AI Studio">
        {{-- spec/CoreIdeaExtractor.md §12 — content_foundation.use cấp cho platform_content_editor/
             platform_content_head/platform_section_editor
             (Modules\ContentFoundation\Database\Seeders\ContentFoundationPermissionSeeder). Trang
             quản lý ngữ cảnh biên tập DÙNG CHUNG bởi CoreIdeaExtractor/VideoIdeaExtractor. --}}
        @can(\App\Enums\PermissionEnum::CONTENT_FOUNDATION_USE->value)
        <div class="nav-group">
            <a href="{{ route('backend.contentfoundation.index') }}"
               class="nav-link {{ request()->routeIs('backend.contentfoundation.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2M5 21H3m4-14h6m-6 4h6m-6 4h4"/></svg>
                <span class="nav-label">Content Foundation</span>
            </a>
        </div>
        @endcan

        {{-- spec/ContentOutlines_Technical_Specification.md §6 — content_outlines.use cấp cho
             platform_content_editor/platform_content_head/platform_section_editor
             (Modules\ContentOutlines\Database\Seeders\ContentOutlinesPermissionSeeder). Soạn
             prompt research/dàn ý SEO để dán sang AI ngoài — KHÔNG gọi AI Provider trong app,
             khác CoreIdeaExtractor/VideoIdeaExtractor. --}}
        @can(\App\Enums\PermissionEnum::CONTENT_OUTLINES_USE->value)
        <div class="nav-group">
            <a href="{{ route('backend.contentoutlines.index') }}"
               class="nav-link {{ request()->routeIs('backend.contentoutlines.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="nav-label">Dàn ý nội dung</span>
            </a>
        </div>
        @endcan

        {{-- spec/CoreIdeaExtractor.md — core_idea_extractor.use cấp cho platform_content_editor/
             platform_content_head (Modules\CoreIdeaExtractor\Database\Seeders\CoreIdeaExtractorPermissionSeeder). --}}
        @can(\App\Enums\PermissionEnum::CORE_IDEA_EXTRACTOR_USE->value)
        <div class="nav-group">
            <a href="{{ route('backend.coreideaextractor.index') }}"
               class="nav-link {{ request()->routeIs('backend.coreideaextractor.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                <span class="nav-label">Trích ý bài viết</span>
            </a>
        </div>
        @endcan

        {{-- video_idea_extractor.use cấp cho platform_content_editor/platform_content_head/
             platform_section_editor (Modules\VideoIdeaExtractor\Database\Seeders\VideoIdeaExtractorPermissionSeeder). --}}
        @can(\App\Enums\PermissionEnum::VIDEO_IDEA_EXTRACTOR_USE->value)
        <div class="nav-group">
            <a href="{{ route('backend.videoideaextractor.index') }}"
               class="nav-link {{ request()->routeIs('backend.videoideaextractor.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                <span class="nav-label">Trích ý video</span>
            </a>
        </div>
        @endcan

        {{-- spec/PromptFrameworkStudio_Technical_Specification.md §6 — prompt_framework_studio.use
             cấp cho platform_content_editor/platform_content_head/platform_section_editor
             (Modules\PromptFrameworkStudio\Database\Seeders\PromptFrameworkStudioPermissionSeeder).
             Thư viện 13 framework prompt engineering + form sinh prompt có cấu trúc — KHÔNG gọi
             AI Provider trong app, cùng nguyên tắc ContentOutlines. --}}
        @can(\App\Enums\PermissionEnum::PROMPT_FRAMEWORK_STUDIO_USE->value)
        <div class="nav-group">
            <a href="{{ route('backend.promptstudio.library') }}"
               class="nav-link {{ request()->routeIs('backend.promptstudio.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                <span class="nav-label">Prompt Framework Studio</span>
            </a>
        </div>
        @endcan

        {{-- spec/BulkIdeationPromptStudio.md — bulk_ideation_prompt_studio.use cấp cho
             platform_content_editor/platform_content_head/platform_section_editor
             (Modules\BulkIdeationPromptStudio\Database\Seeders\BulkIdeationPromptStudioPermissionSeeder).
             Ghép prompt "nhào nặn" ý tưởng bài viết từ 1 mớ từ khóa/câu hỏi/ý tưởng vụn vặt — KHÔNG
             gọi AI Provider trong app, cùng nguyên tắc PromptFrameworkStudio/VideoSeriesPromptStudio. --}}
        @can(\App\Enums\PermissionEnum::BULK_IDEATION_PROMPT_STUDIO_USE->value)
        <div class="nav-group">
            <a href="{{ route('backend.bulkideationpromptstudio.index') }}"
               class="nav-link {{ request()->routeIs('backend.bulkideationpromptstudio.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                <span class="nav-label">Bulk Ideation Prompt Studio</span>
            </a>
        </div>
        @endcan

        {{-- spec/AIVideoStudioTemplate_Technical_Specification.md §9/§13 — ai_video_studio_template.use
             cấp cho platform_content_editor/platform_content_head/platform_section_editor
             (Modules\AIVideoStudioTemplate\Database\Seeders\AIVideoStudioTemplatePermissionSeeder).
             Quản lý Director Prompt Template (Project → nhiều Shot) — KHÔNG gọi AI Provider trong
             app, cùng nguyên tắc ContentOutlines/PromptFrameworkStudio. v1.23 — đổi sang dropdown
             (cùng pattern dropdown Lịch biên tập) để thêm trang "Sinh Master Prompt kịch bản" mà không tách
             route/permission riêng — vẫn gate phẳng bằng 1 permission như trước. --}}
        @can(\App\Enums\PermissionEnum::AI_VIDEO_STUDIO_TEMPLATE_USE->value)
        <details {{ request()->routeIs('backend.aivideostudiotemplate.*') ? 'open' : '' }}>
            <summary class="nav-summary {{ request()->routeIs('backend.aivideostudiotemplate.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                <span class="nav-label">AI Video Studio</span>
                <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
            </summary>
            <div class="sub-menu">
                <a href="{{ route('backend.aivideostudiotemplate.index') }}"
                   class="sub-link {{ request()->routeIs('backend.aivideostudiotemplate.index') || request()->routeIs('backend.aivideostudiotemplate.create') || request()->routeIs('backend.aivideostudiotemplate.show') || request()->routeIs('backend.aivideostudiotemplate.edit') ? 'active' : '' }}">Danh sách project</a>
                <a href="{{ route('backend.aivideostudiotemplate.formula-advisor') }}"
                   class="sub-link {{ request()->routeIs('backend.aivideostudiotemplate.formula-advisor') ? 'active' : '' }}">Sinh Master Prompt</a>
            </div>
        </details>
        @endcan

        {{-- video_series_prompt_studio.use cấp cho platform_content_editor/platform_content_head/
             platform_section_editor (Modules\VideoSeriesPromptStudio\Database\Seeders\
             VideoSeriesPromptStudioPermissionSeeder). Thiết kế kiến trúc cho CẢ CHUỖI video nhiều
             tập (Series Bible) — KHÔNG gọi AI Provider trong app, cùng nguyên tắc
             PromptFrameworkStudio/ContentOutlines. --}}
        @can(\App\Enums\PermissionEnum::VIDEO_SERIES_PROMPT_STUDIO_USE->value)
        <div class="nav-group">
            <a href="{{ route('backend.videoseriespromptstudio.index') }}"
               class="nav-link {{ request()->routeIs('backend.videoseriespromptstudio.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 16h4m10 0h4M3 4h18v16H3V4z"/></svg>
                <span class="nav-label">Video Series Prompt Studio</span>
            </a>
        </div>
        @endcan

        @canany(['aicem.view', 'aicem.config_prompt'])
        <details {{ request()->routeIs('backend.aicem.*') ? 'open' : '' }}>
            <summary class="nav-summary {{ request()->routeIs('backend.aicem.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                <span class="nav-label">AICEM (Trợ lý AI)</span>
                <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
            </summary>
            <div class="sub-menu">
                <a href="{{ route('backend.aicem.dashboard') }}"
                   class="sub-link {{ request()->routeIs('backend.aicem.dashboard') ? 'active' : '' }}">Tổng quan</a>
                <a href="{{ route('backend.aicem.knowledge-documents.index') }}"
                   class="sub-link {{ request()->routeIs('backend.aicem.knowledge-documents.*') ? 'active' : '' }}">Kho tri thức</a>
                @can('create', \Modules\Aicem\Models\AicemKnowledgeDocument::class)
                <a href="{{ route('backend.aicem.knowledge-documents.create') }}"
                   class="sub-link {{ request()->routeIs('backend.aicem.knowledge-documents.create') ? 'active' : '' }}">Thêm tri thức</a>
                @endcan
                @can('aicem.config_prompt')
                <a href="{{ route('backend.aicem.example-candidates.index') }}"
                   class="sub-link {{ request()->routeIs('backend.aicem.example-candidates.*') ? 'active' : '' }}">Duyệt ví dụ mẫu</a>
                @endcan
                @can('aicem.config')
                <a href="{{ route('backend.aicem.settings') }}"
                   class="sub-link {{ request()->routeIs('backend.aicem.settings*') ? 'active' : '' }}">Cấu hình</a>
                @endcan
            </div>
        </details>
        @endcanany
        </x-sidebar-section>

        <x-sidebar-section title="Hệ thống">
        @can('viewAny', \App\Models\User::class)
        <div class="nav-group">
            <a href="{{ route('backend.users.index') }}"
               class="nav-link {{ request()->routeIs('backend.users.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="nav-label">Tài khoản</span>
            </a>
        </div>
        @endcan

        @can('viewSystemMonitor')
        <div class="nav-group">
            <a href="{{ route('backend.system-monitor') }}"
               class="nav-link {{ request()->routeIs('backend.system-monitor') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span class="nav-label">Giám sát hệ thống</span>
            </a>
        </div>
        @endcan

        <div class="nav-group">
        <a href="{{ route('backend.notifications.index') }}"
           class="nav-link {{ request()->routeIs('backend.notifications.*') ? 'active' : '' }}">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <span class="nav-label">Thông báo</span>
        </a>
        </div>

        <div class="nav-group">
        @can('activitylog.view')
        <details {{ request()->routeIs('activitylog.*') ? 'open' : '' }}>
            <summary class="nav-summary {{ request()->routeIs('activitylog.*') ? 'active' : '' }}">
                <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span class="nav-label">Activity Log</span>
                <svg class="nav-arrow" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m9 18 6-6-6-6"/></svg>
            </summary>
            <div class="sub-menu">
                <a href="{{ route('activitylog.index') }}"
                   class="sub-link {{ request()->routeIs('activitylog.index') ? 'active' : '' }}">
                   Danh sách log
                </a>
            </div>
        </details>
        @endcan

        @can('manageSystemSettings')
        <a href="{{ route('backend.settings.index') }}"
           class="nav-link {{ request()->routeIs('backend.settings.*') ? 'active' : '' }}">
            <svg class="nav-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span class="nav-label">{{ __('Cài đặt hệ thống') }}</span>
        </a>
        @endcan
        </div>
        </x-sidebar-section>

    </nav>
</aside>
