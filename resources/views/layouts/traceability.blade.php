<!DOCTYPE html>
<html lang="vi" data-theme="portal" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <meta name="theme-color" content="#ffffff">
    <title>{{ $title }} — {{ $brand }}</title>
    @php($metaDescription = \Illuminate\Support\Str::limit(strip_tags((string) ($summary ?? $intro ?? $subtitle ?? $kind)), 160))
    <meta name="description" content="{{ $metaDescription }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $brand }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if (! empty($images[0]))<meta property="og:image" content="{{ $images[0] }}">@endif
    @include('layouts.partials.font-preload', ['fonts' => ['roboto', 'merriweather'], 'build' => 'build/frontend'])
    @vite(['resources/css/frontend.css'], 'build/frontend')
</head>
<body class="bg-base-200 text-base-content antialiased">
    <div @class(['tx-scope max-w-xl mx-auto min-h-screen bg-base-100 sm:border-x sm:border-base-300 sm:shadow-sm', 'pb-24' => $cta_sticky])>
        @php($tagline = trim(\Illuminate\Support\Str::before((string) config('app.site_description'), '—')))
        <header class="sticky top-0 z-20 bg-base-100/95 backdrop-blur border-b border-base-300">
            <div class="flex items-center gap-3 px-5 h-14">
                <a href="{{ route('post.public.home') }}" class="flex items-center gap-2.5 min-w-0 flex-1" aria-label="{{ $brand }} — về trang chủ">
                    <img src="{{ asset('images/logo.png') }}" alt="" width="36" height="36" class="w-9 h-9 shrink-0">
                    <span class="min-w-0 leading-tight">
                        <span class="block tx-serif font-bold text-[15px] truncate">{{ $brand }}</span>
                        @if ($tagline)<span class="block text-[11px] text-base-content/60 truncate">{{ $tagline }}</span>@endif
                    </span>
                </a>
                <a href="{{ route('post.public.home') }}" class="shrink-0 text-[13px] font-medium text-base-content/70 hover:text-primary">Trang chủ ›</a>
            </div>
        </header>

        @include('traceability.partials.status-banner')

        @if ($notice)
            <p class="px-5 py-3 text-sm text-base-content/70 border-b border-base-300 bg-base-200/50">{{ $notice }}</p>
        @endif

        @yield('content')

        @include('traceability.partials.report')
    </div>

    @if ($cta_sticky)
        <div class="fixed bottom-0 inset-x-0 z-10 bg-base-100 border-t border-base-300 pb-[env(safe-area-inset-bottom)]">
            <div class="max-w-xl mx-auto px-5 py-3">
                <a href="{{ $cta['url'] }}" @if (! empty($cta['external'])) target="_blank" rel="noopener nofollow sponsored" @endif
                   class="btn btn-primary w-full h-12 rounded-lg text-[15px] font-semibold shadow-sm">{{ $cta['label'] }}</a>
            </div>
        </div>
    @endif
</body>
</html>
