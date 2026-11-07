@extends('layouts.traceability')

@section('content')
    @php($kicker = array_filter([$is_ranked ? $rank : null, $type]))
    <div class="tx-hero">
        @if ($images)
            <figure class="relative">
                <img src="{{ $images[0] }}" alt="{{ $title }}" width="960" height="1100" @if (! empty($image_srcsets[0])) srcset="{{ $image_srcsets[0] }}" sizes="{{ \App\Shared\Traceability\TraceabilityService::RESPONSIVE_SIZES }}" @endif fetchpriority="high" decoding="async"
                     class="w-full aspect-[4/5] max-h-[62vh] object-cover bg-[#e5e5e2]">
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/15 to-transparent"></div>
                <figcaption class="absolute inset-x-0 bottom-0 px-4 pb-5 text-white">
                    @if ($kicker)<p class="text-[12.5px] font-semibold text-white/90">{{ implode(' · ', $kicker) }}</p>@endif
                    <h1 class="tx-display !text-white text-[28px] mt-1.5 [text-shadow:0_1px_12px_rgb(0_0_0/0.35)]">{{ $title }}</h1>
                    @if ($era)<p class="mt-1.5 text-[15px] text-white/85">{{ $era }}</p>@endif
                </figcaption>
            </figure>
        @else
            <div class="px-4 pt-5">
                @if ($kicker)<p class="tx-eyebrow">{{ implode(' · ', $kicker) }}</p>@endif
                <h1 class="tx-display text-[26px] mt-1.5">{{ $title }}</h1>
                @if ($era)<p class="mt-1.5 text-[15px] text-[#78716c]">{{ $era }}</p>@endif
            </div>
        @endif

        @if ($visiting || $map_url)
            <div class="flex flex-wrap items-center gap-2 px-4 py-4">
                @if ($visiting)
                    <p @class([
                        'inline-flex items-center gap-2 rounded-md px-2.5 h-8 text-[13.5px] font-semibold',
                        'bg-[#e8f5ec] text-[#166534]' => $visiting['tone'] === 'success',
                        'bg-[#fdf6e3] text-[#854d0e]' => $visiting['tone'] === 'warning',
                        'bg-[#fdecec] text-[#991b1b]' => $visiting['tone'] === 'error',
                    ])>
                        <span @class(['w-2 h-2 rounded-full', 'bg-success' => $visiting['tone'] === 'success', 'bg-warning' => $visiting['tone'] === 'warning', 'bg-error' => $visiting['tone'] === 'error'])></span>
                        {{ $visiting['label'] }}
                    </p>
                @endif
                @if ($map_url)
                    <a href="{{ $map_url }}" target="_blank" rel="noopener" class="ml-auto inline-flex items-center gap-1.5 h-9 px-3 rounded-lg bg-primary text-primary-content text-[13.5px] font-semibold [&>svg]:w-4 [&>svg]:h-4">@include('traceability.partials.icon', ['name' => 'map']) Chỉ đường</a>
                @endif
            </div>
        @else
            <div class="h-4"></div>
        @endif
    </div>

    <section class="tx-section">
        <h2 class="tx-section-title">Thông tin di tích</h2>
        @include('traceability.partials.spec', ['rows' => [
            $rank ? ['label' => 'Xếp hạng', 'value' => $rank] : null,
            $type ? ['label' => 'Loại hình', 'value' => $type] : null,
            $era ? ['label' => 'Niên đại', 'value' => $era] : null,
            $address ? ['label' => 'Địa chỉ', 'value' => $address] : null,
            $coordinates ? ['label' => 'Toạ độ', 'value' => $coordinates] : null,
        ]])
    </section>

    @if ($intro || filled($content_html))
        <section class="tx-section">
            <h2 class="tx-section-title">Lịch sử và giá trị</h2>
            <div class="tx-card px-4 py-5">
                @if ($intro)
                    <p class="text-[17px] leading-[1.7] font-medium text-[#44403c]">{{ $intro }}</p>
                @endif
                @if (filled($content_html))
                    <div @class(['tx-prose', 'mt-5 pt-5 border-t border-[var(--tx-line)]' => $intro])>{!! $content_html !!}</div>
                @endif
                @if ($detail_url && ($cta['url'] ?? null) !== $detail_url)
                    <a href="{{ $detail_url }}" class="mt-4 inline-flex items-center gap-1 text-[14px] font-medium text-primary">Xem trang giới thiệu đầy đủ <span class="w-4 h-4 [&>svg]:w-4 [&>svg]:h-4">@include('traceability.partials.icon', ['name' => 'chevron'])</span></a>
                @endif
            </div>
        </section>
    @elseif ($detail_url && ($cta['url'] ?? null) !== $detail_url)
        <div class="tx-section">
            <a href="{{ $detail_url }}" class="tx-card tx-row group"><span class="flex-1 text-[15px] font-medium group-hover:text-primary">Xem trang giới thiệu đầy đủ</span><span class="tx-chevron">@include('traceability.partials.icon', ['name' => 'chevron'])</span></a>
        </div>
    @endif

    @include('traceability.partials.related')
@endsection
