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
            @if ($description)
                <p class="mt-2 text-[15px] leading-relaxed text-[#57534e] line-clamp-3">{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', $description), 220) }}</p>
            @endif

            @include('traceability.partials.ocop-cert', [
                'stars' => $stars,
                'rows' => ['Chủ thể' => $subject['name'] ?? null, 'Địa bàn' => $area],
                'docs' => $quality_docs,
                'docsLabel' => 'Hồ sơ chất lượng',
                'expiry' => ! empty($subject['cert_expiry_label']) ? ['label' => $subject['cert_expiry_label'], 'valid' => $subject['cert_valid']] : null,
            ])
        </div>
    </div>

    @if ($channels)
        <section id="mua-hang" class="tx-section">
            <h2 class="tx-section-title">Mua chính hãng <small>{{ count($channels) }} gian hàng</small></h2>
            <ul class="tx-card tx-rows">
                @foreach ($channels as $channel)
                    <li>
                        <a href="{{ $channel['url'] }}" target="_blank" rel="noopener nofollow sponsored" class="tx-row group">
                            <span class="w-10 h-10 rounded-md {{ $channel['tone'] }} text-white text-lg font-bold flex items-center justify-center shrink-0" aria-hidden="true">{{ $channel['mark'] }}</span>
                            <span class="flex-1 min-w-0 leading-tight">
                                <span class="block text-[15px] font-semibold">{{ $channel['name'] }}</span>
                                <span class="block mt-0.5 text-[12.5px] text-[#78716c] truncate">{{ $channel['host'] }}</span>
                            </span>
                            <span class="shrink-0 inline-flex items-center gap-1 h-9 px-3 rounded-md text-white text-[13.5px] font-semibold {{ $channel['button'] }} [&>svg]:w-3.5 [&>svg]:h-3.5">Mua ngay @include('traceability.partials.icon', ['name' => 'external'])</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @php($longDescription = $description && mb_strlen($description) > 220)
    @php($infoSections = array_values(array_filter([
        $longDescription ? ['title' => 'Mô tả sản phẩm', 'text' => $description] : null,
        ...$sections,
    ])))
    @if ($facts || $infoSections)
        <section id="thong-tin" class="tx-section">
            <h2 class="tx-section-title">Thông tin sản phẩm</h2>
            <div class="tx-card tx-rows">
                @foreach ($facts as $row)
                    <div class="flex items-baseline justify-between gap-4 px-4 py-3 text-[15px]">
                        <span class="text-[#78716c] shrink-0">{{ $row['label'] }}</span>
                        <span class="font-medium text-right break-words min-w-0">{{ $row['value'] }}</span>
                    </div>
                @endforeach
                @foreach ($infoSections as $section)
                    <details class="tx-details group/acc">
                        <summary class="tx-row-link flex items-center justify-between gap-4 px-4 min-h-[3.25rem] py-3 text-[15px] font-semibold">
                            {{ $section['title'] }}
                            <span class="tx-chevron transition-transform group-open/acc:rotate-90">@include('traceability.partials.icon', ['name' => 'chevron'])</span>
                        </summary>
                        @if (! empty($section['html']))
                            <div class="tx-prose px-4 pb-4">{!! $section['html'] !!}</div>
                        @else
                            <p class="px-4 pb-4 text-[15px] leading-relaxed text-[#44403c] whitespace-pre-line">{{ $section['text'] }}</p>
                        @endif
                    </details>
                @endforeach
            </div>
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

    @include('traceability.partials.related')
@endsection
