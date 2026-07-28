<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale ?? app()->getLocale()) }}" data-theme="portal">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@hasSection('title')@yield('title') — @endif{{ config('app.site_name') }}</title>
    @hasSection('meta_description')
    <meta name="description" content="@yield('meta_description')">
    @endif
    @stack('meta')

    {{-- GEO/AEO (spec/blog.md, nhiều nguồn) — Organization/WebSite JSON-LD PHẢI xuất hiện trên
         MỌI trang công khai (không chỉ trang bài viết), khác `ArticleStructuredDataBuilder`
         (module Post) vốn chỉ nhúng 1 bản Organization rút gọn LÀM publisher bên trong Article
         schema, chỉ có ở trang bài viết. Đây là node Organization/WebSite gốc, đầy đủ hơn (có
         logo/sameAs khi đã cấu hình) — giúp AI/search engine xác định rõ danh tính thương hiệu
         dù người dùng vào từ trang chủ/danh mục/tác giả, không chỉ từ 1 bài viết cụ thể. --}}
    @php
        // Viết "@context" ngoài khối PHP thô này sẽ bị Blade compile nhầm thành directive
        // Context của framework, ra HTML rác — bên trong khối PHP thô thì an toàn.
        // route('post.public.home') (không phải url('/')) — khớp đúng entity URL + endpoint
        // SearchAction mà PublicCategoryController::index() đang đọc ($request->string('q')),
        // xem home.blade.php trước bản gộp này.
        $homeUrl = route('post.public.home');

        $organizationNode = array_filter([
            '@type'       => 'Organization',
            '@id'         => $homeUrl . '#organization',
            'name'        => config('app.site_name'),
            'url'         => $homeUrl,
            'description' => config('app.site_description') ?: null,
            'logo'        => config('app.site_logo_url') ?: null,
            'sameAs'      => config('app.site_social_links') ?: null,
        ]);

        $websiteNode = [
            '@type'     => 'WebSite',
            '@id'       => $homeUrl . '#website',
            'name'      => config('app.site_name'),
            'url'       => $homeUrl,
            'publisher' => ['@id' => $homeUrl . '#organization'],
            'potentialAction' => [
                '@type'  => 'SearchAction',
                'target' => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => $homeUrl . '?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];

        // spec/Menu_Navigation_Technical_Specification.md §7.2.1 — chỉ khai báo cấp 1, KHÔNG
        // lặp lại toàn bộ 3 cấp mega-menu vào JSON-LD (crawler tự theo <a href> thật, không cần
        // structured data lặp lại từng flyout item).
        $graph = [$organizationNode, $websiteNode];

        if (($menuTree ?? collect())->isNotEmpty()) {
            $graph = [...$graph, ...$menuTree->map(fn ($item) => [
                '@type' => 'SiteNavigationElement',
                'name'  => $item->label,
                'url'   => $item->resolveUrl() ?? url('/'),
            ])->values()->all()];
        }

        $siteJsonLd = json_encode([
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    @endphp
    <script type="application/ld+json">{!! $siteJsonLd !!}</script>

    @vite(['resources/css/frontend.css', 'resources/js/frontend.js'], 'build/frontend')
    @stack('styles')
</head>
<body class="bg-base-100 text-base-content">

<div x-data="frontendNav" class="flex flex-col min-h-screen">
    @include('layouts.partials.frontend-header')

    {{-- .site-content — spec/main.css bù margin-top khi .site-header.is-pinned (mobile, header
         rời khỏi flow do position:fixed — xem ".site-header.is-pinned+.site-content" trong
         resources/css/frontend.css). --}}
    <main class="site-content flex-1">
        @yield('content')
    </main>

    @include('layouts.partials.frontend-footer')
</div>

@stack('scripts')
</body>
</html>
