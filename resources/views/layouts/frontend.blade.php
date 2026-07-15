<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale ?? app()->getLocale()) }}" data-theme="portal">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@hasSection('title')@yield('title') — @endif{{ config('app.name', 'Cổng thông tin') }}</title>
    @hasSection('meta_description')
    <meta name="description" content="@yield('meta_description')">
    @endif
    @stack('meta')

    {{-- spec/Menu_Navigation_Technical_Specification.md §7.2.1 — chỉ khai báo cấp 1, KHÔNG
         lặp lại toàn bộ 3 cấp mega-menu vào JSON-LD (crawler tự theo <a href> thật, không cần
         structured data lặp lại từng flyout item). --}}
    @if(($menuTree ?? collect())->isNotEmpty())
    @php
        // Viết "@context" ngoài khối PHP thô này sẽ bị Blade compile nhầm thành directive
        // Context của framework, ra HTML rác — bên trong khối PHP thô thì an toàn.
        $navJsonLd = json_encode([
            '@context' => 'https://schema.org',
            '@graph'   => $menuTree->map(fn ($item) => [
                '@type' => 'SiteNavigationElement',
                'name'  => $item->label,
                'url'   => $item->resolveUrl() ?? url('/'),
            ])->values(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    @endphp
    <script type="application/ld+json">{!! $navJsonLd !!}</script>
    @endif

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
