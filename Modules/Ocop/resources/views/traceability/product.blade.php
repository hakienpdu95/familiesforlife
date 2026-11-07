@extends('layouts.traceability')

@section('content')
    @if ($images)
        <figure class="relative bg-base-200">
            <div class="flex snap-x snap-mandatory overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($images as $i => $image)
                    <img src="{{ $image }}" alt="{{ $title }}{{ count($images) > 1 ? ' — ảnh '.($i + 1) : '' }}" width="800" height="600"
                         class="snap-center shrink-0 w-full aspect-[4/3] max-h-[52vh] object-contain p-4"
                         @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async">
                @endforeach
            </div>
            @if (count($images) > 1)
                <figcaption class="absolute bottom-3 right-3 rounded-full bg-black/60 text-white text-[11px] font-medium px-2.5 py-1">{{ count($images) }} ảnh · vuốt</figcaption>
            @endif
        </figure>
    @endif

    <div class="px-5 pt-6">
        <p class="tx-label !text-primary">Sản phẩm OCOP{{ $category ? ' · '.$category : '' }}</p>
        <h1 class="tx-serif text-[26px] font-bold leading-tight mt-1.5">{{ $title }}</h1>
        @if (! empty($subject['name']))
            <p class="mt-2 text-[15px] text-base-content/70">Sản xuất bởi <span class="font-medium text-base-content">{{ $subject['name'] }}</span>{{ $area ? ' · '.$area : '' }}</p>
        @endif

        @if ($stars)
            <div class="mt-5 flex items-center gap-4 py-4 border-y border-base-300">
                <div class="w-[68px] h-[68px] rounded-full border-2 border-[#c99a2e] flex flex-col items-center justify-center shrink-0 text-[#8a6413]">
                    <span class="text-[10px] font-bold tracking-[0.15em]">OCOP</span>
                    <span class="text-xl font-bold leading-none tx-serif">{{ $stars }}★</span>
                </div>
                <div class="min-w-0">
                    <p class="font-semibold">Đạt chuẩn OCOP {{ $stars }} sao</p>
                    @if (! empty($subject['cert_expiry_label']))
                        <p @class(['text-sm mt-0.5', 'text-base-content/60' => $subject['cert_valid'], 'text-error' => ! $subject['cert_valid']])>
                            Chứng nhận của chủ thể {{ $subject['cert_valid'] ? 'hiệu lực đến' : 'đã hết hạn ngày' }} {{ $subject['cert_expiry_label'] }}
                        </p>
                    @else
                        <p class="text-sm mt-0.5 text-base-content/60">Chương trình Mỗi xã một sản phẩm</p>
                    @endif
                </div>
            </div>
        @endif
    </div>

    @if ($channels)
        <section id="mua-hang" class="px-5 mt-8 scroll-mt-4">
            <h2 class="tx-label mb-1">Nơi mua chính hãng</h2>
            <ul>
                @foreach ($channels as $channel)
                    <li>
                        <a href="{{ $channel['url'] }}" target="_blank" rel="noopener nofollow sponsored" class="flex items-center gap-3 py-3 border-b border-base-300 group">
                            <span class="w-9 h-9 rounded-md {{ $channel['tone'] }} text-white font-bold flex items-center justify-center shrink-0" aria-hidden="true">{{ $channel['mark'] }}</span>
                            <span class="flex-1 min-w-0">
                                <span class="block font-medium group-hover:text-primary">{{ $channel['name'] }}</span>
                                <span class="block text-xs text-base-content/60 font-mono truncate">{{ $channel['host'] }}</span>
                            </span>
                            <span class="tx-chip !h-8 !px-3 !text-[13px] group-hover:border-primary group-hover:text-primary">Mở ↗</span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <p class="mt-2 text-xs text-base-content/60">Gian hàng do chủ thể đăng ký với ban quản lý. Hãy kiểm tra đúng tên miền trước khi đặt mua.</p>
        </section>
    @endif

    @if ($facts)
        <section class="px-5 mt-8">
            <h2 class="tx-label mb-1">Thông số sản phẩm</h2>
            @include('traceability.partials.spec', ['rows' => $facts])
        </section>
    @endif

    @if ($quality_docs)
        <section class="px-5 mt-8">
            <h2 class="tx-label mb-1">Hồ sơ chất lượng</h2>
            @include('traceability.partials.checklist', ['items' => $quality_docs])
        </section>
    @endif

    @if (! empty($subject['name']))
        <section class="px-5 mt-8">
            <h2 class="tx-label mb-2">Chủ thể sản xuất</h2>
            <p class="font-semibold text-[15px]">{{ $subject['name'] }}</p>
            <p class="text-sm text-base-content/60 mt-0.5">{{ collect([$subject['type'] ?? null, ! empty($subject['stars']) ? 'Hạng OCOP '.$subject['stars'].' sao' : null])->filter()->implode(' · ') }}</p>
            @if (! empty($subject['address']))<p class="text-sm text-base-content/70 mt-2">{{ $subject['address'] }}</p>@endif
            <div class="flex flex-wrap gap-2 mt-4">
                @if (! empty($subject['hotline']))
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $subject['hotline']) }}" class="tx-chip">@include('traceability.partials.icon', ['name' => 'phone']) {{ $subject['hotline'] }}</a>
                @endif
                @if (! empty($subject['website']))
                    <a href="{{ $subject['website'] }}" target="_blank" rel="noopener nofollow" class="tx-chip">@include('traceability.partials.icon', ['name' => 'globe']) Website</a>
                @endif
                @if (! empty($subject['url']))
                    <a href="{{ $subject['url'] }}" class="tx-chip">@include('traceability.partials.icon', ['name' => 'store']) Trang thương hiệu</a>
                @endif
            </div>
        </section>
    @endif

    @if ($sections)
        <section class="px-5 mt-8">
            @include('traceability.partials.accordion', ['sections' => $sections])
        </section>
    @endif

    @include('traceability.partials.related')
@endsection
