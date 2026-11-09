@extends('layouts.traceability')

@section('content')
    @php($hotline = ! empty($contact['hotline']) ? preg_replace('/[^0-9+]/', '', $contact['hotline']) : null)
    @php($area = $contact['address'] ?? null)

    <div class="tx-hero">
        @if ($images)
            <div class="flex snap-x snap-mandatory overflow-x-auto bg-[#efefec] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($images as $i => $image)
                    <img src="{{ $image }}" alt="{{ $title }}" width="960" height="600" @if (! empty($image_srcsets[$i])) srcset="{{ $image_srcsets[$i] }}" sizes="{{ \App\Shared\Traceability\TraceabilityService::RESPONSIVE_SIZES }}" @endif class="snap-center shrink-0 w-full aspect-[16/10] object-cover" @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async">
                @endforeach
            </div>
        @endif

        <div class="px-4 pt-4 pb-5">
            <p class="tx-eyebrow">{{ $type ?: 'Chủ thể OCOP' }}</p>
            <h1 class="tx-display tx-h1 mt-1.5">{{ $title }}</h1>
            @if ($subtitle)<p class="mt-1 text-[15px] text-[#78716c]">{{ $subtitle }}</p>@endif

            @include('traceability.partials.ocop-cert', [
                'certTitle' => 'Chủ thể có sản phẩm OCOP',
                'stars' => $stars,
                'rows' => [
                    'Sản phẩm' => $products_count ? $products_count.' sản phẩm OCOP' : null,
                    'Ngành nghề' => $is_food_business ? 'Sản xuất thực phẩm' : null,
                ],
                'docs' => $docs,
                'docsLabel' => 'Giấy phép, chứng nhận',
                'expiry' => $cert,
            ])
        </div>
    </div>

    @php($hasContact = $area || $hotline || ! empty($contact['email']) || ! empty($contact['website']) || ! empty($contact['map_url']))
    
    @if ($area || $hotline || ! empty($contact['email']) || ! empty($contact['website']) || ! empty($contact['map_url']))
        <section id="lien-he" class="tx-section">
            <h2 class="tx-section-title">Liên hệ</h2>
            <div class="tx-card tx-rows">
                @if ($area)
                    <div class="flex items-center gap-3.5 px-4 py-3">
                        <span class="tx-ico">@include('traceability.partials.icon', ['name' => 'map'])</span>
                        <span class="flex-1 text-[15px] leading-snug">{{ $area }}</span>
                        @if (! empty($contact['map_url']))
                            <a href="{{ $contact['map_url'] }}" target="_blank" rel="noopener" class="tx-pill shrink-0">Chỉ đường</a>
                        @endif
                    </div>
                @endif
                @if ($hotline)
                    <a href="tel:{{ $hotline }}" class="tx-row group">
                        <span class="tx-ico">@include('traceability.partials.icon', ['name' => 'phone'])</span>
                        <span class="flex-1 text-[15px] font-medium group-hover:text-primary">{{ $contact['hotline'] }}</span>
                        <span class="tx-pill">Gọi</span>
                    </a>
                @endif
                @if (! empty($contact['email']))
                    <a href="mailto:{{ $contact['email'] }}" class="tx-row group">
                        <span class="tx-ico">@include('traceability.partials.icon', ['name' => 'mail'])</span>
                        <span class="flex-1 min-w-0 truncate text-[15px] font-medium group-hover:text-primary">{{ $contact['email'] }}</span>
                        <span class="tx-chevron">@include('traceability.partials.icon', ['name' => 'chevron'])</span>
                    </a>
                @endif
                @if (! empty($contact['website']))
                    <a href="{{ $contact['website'] }}" target="_blank" rel="noopener nofollow" class="tx-row group">
                        <span class="tx-ico">@include('traceability.partials.icon', ['name' => 'globe'])</span>
                        <span class="flex-1 min-w-0 truncate text-[15px] font-medium group-hover:text-primary">{{ preg_replace('#^https?://(www\.)?#', '', rtrim($contact['website'], '/')) }}</span>
                        <span class="tx-chevron [&>svg]:w-4 [&>svg]:h-4">@include('traceability.partials.icon', ['name' => 'external'])</span>
                    </a>
                @endif
                @if (! $area && ! empty($contact['map_url']))
                    <a href="{{ $contact['map_url'] }}" target="_blank" rel="noopener" class="tx-row group">
                        <span class="tx-ico">@include('traceability.partials.icon', ['name' => 'map'])</span>
                        <span class="flex-1 text-[15px] font-medium group-hover:text-primary">Chỉ đường</span>
                        <span class="tx-chevron">@include('traceability.partials.icon', ['name' => 'chevron'])</span>
                    </a>
                @endif
            </div>
        </section>
    @endif


    @if ($sections)
        <section id="tim-hieu" class="tx-section">
            <h2 class="tx-section-title">Tìm hiểu thêm</h2>
            @include('traceability.partials.accordion', ['sections' => $sections, 'openFirst' => true])
        </section>
    @endif

    @include('traceability.partials.related')
@endsection
