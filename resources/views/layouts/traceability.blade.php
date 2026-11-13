<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="portal" class="scroll-smooth scroll-pt-20">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
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
    @include('layouts.partials.font-preload', ['fonts' => ['inter'], 'build' => 'build/frontend'])
    @include('layouts.partials.google-analytics', ['gaContentGroup' => 'qr_trace'])
    @include('layouts.partials.i18n-script')
    @vite(['resources/css/frontend.css'], 'build/frontend')
    @stack('head')
</head>
<body class="bg-[#ebebe8] antialiased"
      {{ \App\Support\Analytics\ClickTracking::context(null, $track_item ?? $title, $qr_code ?? null, $track_category ?? null) }}
      {{ \App\Support\Analytics\ClickTracking::view('qr_view', [
          'scan_id' => $scan['scan_id'] ?? null,
          'scan_source' => $scan['scan_source'] ?? null,
          'page_locale' => $scan['locale'] ?? app()->getLocale(),
          'subject_name' => $subject['name'] ?? null,
          'ocop_stars' => $stars ?? null,
          'verification_status' => $status ?? null,
      ]) }}
      {{ \App\Support\Analytics\ClickTracking::toggle('qr_section_open') }}>
    <div @class(['tx-scope tx-canvas max-w-xl mx-auto min-h-dvh flex flex-col sm:border-x sm:border-[rgb(28_25_23/.08)]', 'pb-[calc(6rem+env(safe-area-inset-bottom))]' => $cta_sticky])>
        @php($tagline = trim(\Illuminate\Support\Str::before((string) config('app.site_description'), '—')))
        <header class="sticky top-0 z-20 bg-base-100/95 backdrop-blur border-b border-[var(--tx-line)] pt-[env(safe-area-inset-top)]">
            <div class="flex items-center gap-3 px-4 h-14">
                <div class="flex items-center gap-2.5 min-w-0 flex-1">
                    <img src="{{ asset('images/logo.png') }}" alt="" width="36" height="36" class="w-9 h-9 shrink-0">
                    <span class="min-w-0 leading-tight">
                        <span class="block text-[15px] font-bold tracking-[-0.01em] truncate">{{ $brand }}</span>
                        @if ($tagline)<span class="block text-[12px] text-[#78716c] truncate">{{ $tagline }}</span>@endif
                    </span>
                </div>
                @include('traceability.partials.locale-switcher')
            </div>
        </header>

        @include('traceability.partials.locale-fallback')

        @include('traceability.partials.status-banner')

        @if ($notice)
            <p class="px-4 py-3 text-[14px] text-[#57534e] bg-base-100 border-b border-[var(--tx-line)]">{{ $notice }}</p>
        @endif

        <main class="flex-1 pb-2">
            @yield('content')
        </main>

        @include('traceability.partials.report')

        @if ($cta_sticky)
            <div class="fixed bottom-0 inset-x-0 z-30 bg-base-100/95 backdrop-blur border-t border-[rgb(28_25_23/.08)] pb-[env(safe-area-inset-bottom)]">
                <div class="max-w-xl mx-auto flex gap-2 px-4 py-3" {{ \App\Support\Analytics\ClickTracking::context('qr_sticky_bar') }}>
                    @php($buy = $channels ?? [])
                    @forelse ($buy as $channel)
                        <a href="{{ $channel['url'] }}" target="_blank" rel="noopener nofollow sponsored" {{ \App\Support\Analytics\ClickTracking::link($channel['platform'] ?? null, null, $channel['name']) }}
                           class="btn flex-1 min-w-0 h-12 min-h-12 px-2 rounded-lg border-0 shadow-none text-white text-[15px] max-[359.98px]:text-[14px] font-semibold gap-2 {{ $channel['button'] }}">
                            @if (! empty($channel['logo']))<span class="w-7 h-7 rounded-md bg-white flex items-center justify-center shrink-0" aria-hidden="true"><img src="{{ $channel['logo'] }}" alt="" width="22" height="22" class="w-[22px] h-[22px] object-contain"></span>@endif
                            <span class="truncate">{{ count($buy) === 1 ? __('Mua trên :channel', ['channel' => $channel['name']]) : ($channel['short'] ?? $channel['name']) }}</span>
                        </a>
                    @empty
                        @if ($cta)
                        <a href="{{ $cta['url'] }}" @if (! empty($cta['external'])) target="_blank" rel="noopener nofollow sponsored" @endif {{ \App\Support\Analytics\ClickTracking::link(null, 'cta_click') }}
                           class="btn btn-primary flex-1 h-12 min-h-12 rounded-lg text-[15px] font-semibold shadow-none border-0">{{ $cta['label'] }}</a>
                        @endif
                    @endforelse
                    <a href="#phan-anh" onclick="document.getElementById('phan-anh').open = true"
                       class="btn w-12 h-12 min-h-12 p-0 rounded-lg bg-base-100 border border-[rgb(28_25_23/.14)] shadow-none text-[#57534e] hover:border-primary hover:text-primary [&>svg]:w-5 [&>svg]:h-5" aria-label="{{ __('Phản ánh thông tin chưa đúng') }}">@include('traceability.partials.icon', ['name' => 'flag'])</a>
                </div>
            </div>
        @endif
    </div>

</body>
</html>
