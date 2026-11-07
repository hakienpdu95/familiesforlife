<!DOCTYPE html>
<html lang="vi" data-theme="portal" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>{{ $title }} — {{ $heading }} — {{ $brand }}</title>
    @include('layouts.partials.font-preload', ['fonts' => ['roboto'], 'build' => 'build/frontend'])
    @vite(['resources/css/icon.css', 'resources/css/frontend.css'], 'build/frontend')
</head>
<body class="bg-base-200 text-base-content antialiased">
    <main class="max-w-md mx-auto min-h-screen bg-base-100 pb-32 shadow-sm">
        <header class="flex items-center justify-between gap-3 px-4 py-3 bg-primary text-primary-content">
            <a href="{{ route('post.public.home') }}" class="font-bold text-sm truncate">{{ $brand }}</a>
            <span class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide shrink-0">
                <i class="fa-solid fa-shield" aria-hidden="true"></i>
                Hệ thống truy xuất
            </span>
        </header>

        @include('traceability.partials.verify-card')

        @if ($notice)
            <div role="status" class="mx-4 mt-3 rounded-lg border border-amber-300 bg-amber-50 text-amber-800 text-sm px-3 py-2.5">
                <i class="fa-solid fa-circle-info mr-1" aria-hidden="true"></i>
                {{ $notice }}
            </div>
        @endif

        @yield('content')

        @include('traceability.partials.report')

        <footer class="px-4 mt-6 pt-4 border-t border-base-200 text-center text-xs text-base-content/50 space-y-1">
            <p>Thông tin do <strong class="text-base-content/70">{{ $brand }}</strong> phát hành và quản lý.</p>
            <p>Mã truy xuất <span class="font-mono font-semibold text-base-content/70">{{ $qr_code }}</span> · {{ url()->current() }}</p>
        </footer>
    </main>

    <div class="fixed bottom-0 inset-x-0 z-10 bg-base-100/95 backdrop-blur border-t border-base-300 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
        <div class="max-w-md mx-auto flex gap-2">
            @if ($cta_secondary)
                <a href="{{ $cta_secondary['url'] }}" @if (! empty($cta_secondary['external'])) target="_blank" rel="noopener nofollow sponsored" @endif
                   class="btn btn-outline btn-primary flex-1 gap-2">
                    @if (! empty($cta_secondary['icon']))<i class="fa-solid {{ $cta_secondary['icon'] }}" aria-hidden="true"></i>@endif
                    {{ $cta_secondary['label'] }}
                </a>
            @endif
            <a href="{{ $cta['url'] }}" @if (! empty($cta['external'])) target="_blank" rel="noopener nofollow sponsored" @endif
               class="btn btn-primary flex-1 gap-2">
                @if (! empty($cta['icon']))<i class="fa-solid {{ $cta['icon'] }}" aria-hidden="true"></i>@endif
                {{ $cta['label'] }}
            </a>
        </div>
    </div>
</body>
</html>
