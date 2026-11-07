@extends('layouts.traceability')

@section('content')
    @php($kicker = array_filter([$is_ranked ? $rank : null, $type]))
    @if ($images)
        <figure class="relative">
            <img src="{{ $images[0] }}" alt="{{ $title }}" width="960" height="1100" fetchpriority="high" decoding="async"
                 class="w-full aspect-[4/5] max-h-[70vh] object-cover bg-base-300">
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/25 to-transparent"></div>
            <figcaption class="absolute inset-x-0 bottom-0 px-5 pb-6 text-white">
                @if ($kicker)<p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-white/85">{{ implode(' · ', $kicker) }}</p>@endif
                <h1 class="tx-serif text-[30px] font-bold leading-[1.2] mt-2 [text-shadow:0_1px_12px_rgb(0_0_0/0.35)]">{{ $title }}</h1>
                @if ($era)<p class="mt-2 text-[15px] italic text-white/85">{{ $era }}</p>@endif
            </figcaption>
        </figure>
    @else
        <div class="px-5 pt-7">
            @if ($kicker)<p class="tx-label !text-primary">{{ implode(' · ', $kicker) }}</p>@endif
            <h1 class="tx-serif text-[28px] font-bold leading-[1.25] mt-2">{{ $title }}</h1>
            @if ($era)<p class="mt-2 text-[15px] text-base-content/65 italic">{{ $era }}</p>@endif
        </div>
    @endif

    @if ($visiting)
        <div class="px-5 mt-5">
            <p @class([
                'inline-flex items-center gap-2 rounded-full px-3 h-8 text-[13px] font-medium',
                'bg-[#e8f5ec] text-[#166534]' => $visiting['tone'] === 'success',
                'bg-[#fdf6e3] text-[#854d0e]' => $visiting['tone'] === 'warning',
                'bg-[#fdecec] text-[#991b1b]' => $visiting['tone'] === 'error',
            ])>
                <span @class(['w-2 h-2 rounded-full', 'bg-success' => $visiting['tone'] === 'success', 'bg-warning' => $visiting['tone'] === 'warning', 'bg-error' => $visiting['tone'] === 'error'])></span>
                {{ $visiting['label'] }}
            </p>
        </div>
    @endif

    <section class="px-5 mt-7">
        <h2 class="tx-label mb-1">Thông tin di tích</h2>
        @include('traceability.partials.spec', ['rows' => [
            $rank ? ['label' => 'Xếp hạng', 'value' => $rank] : null,
            $type ? ['label' => 'Loại hình', 'value' => $type] : null,
            $era ? ['label' => 'Niên đại', 'value' => $era] : null,
            $address ? ['label' => 'Địa chỉ', 'value' => $address] : null,
            $coordinates ? ['label' => 'Toạ độ', 'value' => $coordinates] : null,
        ]])
        @if ($map_url)
            <a href="{{ $map_url }}" target="_blank" rel="noopener" class="tx-chip mt-4">@include('traceability.partials.icon', ['name' => 'map']) Mở bản đồ chỉ đường</a>
        @endif
    </section>

    @if ($intro)
        <section class="px-5 mt-8">
            <h2 class="tx-label mb-2">Giới thiệu</h2>
            <p class="tx-serif text-[17px] leading-[1.7] text-base-content/85">{{ $intro }}</p>
        </section>
    @endif

    @if (filled($content_html))
        <section class="px-5 mt-8">
            <h2 class="tx-label mb-2">Lịch sử và giá trị</h2>
            <div class="tx-prose">{!! $content_html !!}</div>
        </section>
    @endif

    @if ($detail_url && ($cta['url'] ?? null) !== $detail_url)
        <div class="px-5 mt-6">
            <a href="{{ $detail_url }}" class="text-sm font-medium text-primary">Xem trang giới thiệu đầy đủ ›</a>
        </div>
    @endif

    @include('traceability.partials.related')
@endsection
