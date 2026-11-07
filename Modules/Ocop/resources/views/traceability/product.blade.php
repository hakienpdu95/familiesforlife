@extends('layouts.traceability')

@section('content')
    @php($group = $category ? trim(\Illuminate\Support\Str::after($category, ':')) : null)
    @php($facts = array_values(array_filter($facts)))

    @php($hasSubject = ! empty($subject['name']))

    <div class="tx-hero">
        @if ($images)
            <figure class="relative bg-[#f6f5f2]">
                <div class="flex snap-x snap-mandatory overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($images as $i => $image)
                        <img src="{{ $image }}" alt="{{ $title }}{{ count($images) > 1 ? ' — ảnh '.($i + 1) : '' }}" width="800" height="600" @if (! empty($image_srcsets[$i])) srcset="{{ $image_srcsets[$i] }}" sizes="{{ \App\Shared\Traceability\TraceabilityService::RESPONSIVE_SIZES }}" @endif
                             class="snap-center shrink-0 w-full aspect-[4/3] max-h-[44vh] object-contain p-5 mix-blend-multiply"
                             @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async">
                    @endforeach
                </div>
                @if (count($images) > 1)
                    <figcaption class="absolute bottom-3 right-3 rounded-md bg-black/55 text-white text-[11px] font-medium px-2 py-1">{{ count($images) }} ảnh · vuốt</figcaption>
                @endif
            </figure>
        @endif

        <div class="px-4 pt-4 pb-5">
            <p class="tx-eyebrow">{{ $group ?: 'Sản phẩm OCOP' }}</p>
            <h1 class="tx-display text-[26px] mt-1.5">{{ $title }}</h1>

            @include('traceability.partials.ocop-cert', [
                'stars' => $stars,
                'rows' => ['Chủ thể' => $subject['name'] ?? null, 'Địa bàn' => $area],
                'expiry' => ! empty($subject['cert_expiry_label']) ? ['label' => $subject['cert_expiry_label'], 'valid' => $subject['cert_valid']] : null,
            ])
        </div>
    </div>

    @if ($quality_docs)
        <section id="ho-so" class="tx-section">
            <h2 class="tx-section-title">Hồ sơ chất lượng</h2>
            @include('traceability.partials.checklist', ['items' => $quality_docs])
        </section>
    @endif

    @if ($facts)
        <section id="thong-so" class="tx-section">
            <h2 class="tx-section-title">Thông số sản phẩm</h2>
            @include('traceability.partials.spec', ['rows' => $facts])
        </section>
    @endif

    @if ($channels)
        <section id="mua-hang" class="tx-section">
            <h2 class="tx-section-title">Nơi mua chính hãng</h2>
            <ul @class(['grid gap-2', 'grid-cols-2' => count($channels) > 1])>
                @foreach ($channels as $channel)
                    <li>
                        <a href="{{ $channel['url'] }}" target="_blank" rel="noopener nofollow sponsored"
                           class="tx-card flex items-center gap-3 h-full p-3 hover:border-primary/40 transition-colors group">
                            <span class="w-10 h-10 rounded-md {{ $channel['tone'] }} text-white text-lg font-bold flex items-center justify-center shrink-0" aria-hidden="true">{{ $channel['mark'] }}</span>
                            <span class="flex-1 min-w-0 leading-tight">
                                <span class="block text-[15px] font-semibold group-hover:text-primary">{{ $channel['name'] }}</span>
                                <span class="block mt-0.5 text-[12px] text-[#78716c] font-mono truncate">{{ $channel['host'] }}</span>
                            </span>
                            <span class="tx-chevron [&>svg]:w-4 [&>svg]:h-4">@include('traceability.partials.icon', ['name' => 'external'])</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($hasSubject)
        @php($subjectMeta = collect([$subject['type'] ?? null, ! empty($subject['stars']) ? 'Hạng OCOP '.$subject['stars'].' sao' : null])->filter()->implode(' · '))
        @php($hotline = ! empty($subject['hotline']) ? preg_replace('/[^0-9+]/', '', $subject['hotline']) : null)
        <section id="chu-the" class="tx-section">
            <h2 class="tx-section-title">Chủ thể sản xuất</h2>
            <div class="tx-card tx-rows">
                @if (! empty($subject['url']))
                    <a href="{{ $subject['url'] }}" class="tx-row group">
                        <span class="tx-ico">@include('traceability.partials.icon', ['name' => 'store'])</span>
                        <span class="flex-1 min-w-0 leading-snug">
                            <span class="block text-[15px] font-semibold group-hover:text-primary">{{ $subject['name'] }}</span>
                            <span class="block text-[13px] text-[#78716c]">{{ $subjectMeta ?: 'Xem hồ sơ chủ thể' }}</span>
                        </span>
                        <span class="tx-chevron">@include('traceability.partials.icon', ['name' => 'chevron'])</span>
                    </a>
                @else
                    <div class="tx-row">
                        <span class="tx-ico">@include('traceability.partials.icon', ['name' => 'store'])</span>
                        <span class="flex-1 min-w-0 leading-snug">
                            <span class="block text-[15px] font-semibold">{{ $subject['name'] }}</span>
                            @if ($subjectMeta)<span class="block text-[13px] text-[#78716c]">{{ $subjectMeta }}</span>@endif
                        </span>
                    </div>
                @endif
                @if (! empty($subject['address']))
                    <div class="tx-row">
                        <span class="tx-ico">@include('traceability.partials.icon', ['name' => 'map'])</span>
                        <span class="flex-1 min-w-0 text-[14.5px] leading-snug">{{ $subject['address'] }}</span>
                    </div>
                @endif
                @if ($hotline)
                    <a href="tel:{{ $hotline }}" class="tx-row group">
                        <span class="tx-ico">@include('traceability.partials.icon', ['name' => 'phone'])</span>
                        <span class="flex-1 text-[15px] font-medium group-hover:text-primary">{{ $subject['hotline'] }}</span>
                        <span class="tx-pill">Gọi</span>
                    </a>
                @endif
                @if (! empty($subject['website']))
                    <a href="{{ $subject['website'] }}" target="_blank" rel="noopener nofollow" class="tx-row group">
                        <span class="tx-ico">@include('traceability.partials.icon', ['name' => 'globe'])</span>
                        <span class="flex-1 min-w-0 truncate text-[15px] font-medium group-hover:text-primary">{{ preg_replace('#^https?://(www\.)?#', '', rtrim($subject['website'], '/')) }}</span>
                        <span class="tx-chevron">@include('traceability.partials.icon', ['name' => 'external'])</span>
                    </a>
                @endif
            </div>
        </section>
    @endif

    @if ($sections)
        <section id="tim-hieu" class="tx-section">
            <h2 class="tx-section-title">Tìm hiểu thêm</h2>
            @include('traceability.partials.accordion', ['sections' => $sections])
        </section>
    @endif

    @include('traceability.partials.related')
@endsection
