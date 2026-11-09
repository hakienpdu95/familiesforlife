@php($shots = count($gallery))
@if ($shots)
    <figure class="tx-gallery relative bg-[#f6f5f2]" aria-roledescription="carousel" aria-label="Ảnh {{ $title }}" data-tx-gallery>
        <div class="swiper tx-gallery-main" data-main>
            <div class="swiper-wrapper">
                @foreach ($gallery as $i => $shot)
                    <div class="swiper-slide" role="group" aria-label="Ảnh {{ $i + 1 }}/{{ $shots }}">
                        <img src="{{ $shot['src'] }}" alt="{{ $title }}{{ $shots > 1 ? ' — ảnh '.($i + 1) : '' }}" width="800" height="600"
                             @if (! empty($shot['srcset'])) srcset="{{ $shot['srcset'] }}" sizes="{{ \App\Shared\Traceability\TraceabilityService::RESPONSIVE_SIZES }}" @endif
                             @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async" draggable="false"
                             class="block w-full aspect-[4/3] max-h-[42svh] object-contain p-4 sm:p-6 mix-blend-multiply select-none">
                    </div>
                @endforeach
            </div>

            @if ($shots > 1)
                <span class="tx-gallery-count absolute z-10 top-3 right-3 h-6 px-2.5 rounded-full bg-black/55 backdrop-blur-sm text-white text-[12px] font-medium tabular-nums inline-flex items-center pointer-events-none" aria-hidden="true"><span data-current>1</span>/{{ $shots }}</span>
                <button type="button" data-prev aria-label="Ảnh trước"
                        class="tx-gallery-nav left-3 [&>svg]:rotate-180">@include('traceability.partials.icon', ['name' => 'chevron'])</button>
                <button type="button" data-next aria-label="Ảnh sau"
                        class="tx-gallery-nav right-3">@include('traceability.partials.icon', ['name' => 'chevron'])</button>
            @endif
        </div>

        @if ($shots > 1)
            <div class="swiper tx-gallery-thumbs pb-3 pt-1" data-thumbs>
                <div class="swiper-wrapper">
                    @foreach ($gallery as $i => $shot)
                        <div class="swiper-slide !w-14 !h-14">
                            <img src="{{ $shot['thumb'] ?? $shot['src'] }}" alt="" width="56" height="56" loading="lazy" decoding="async" draggable="false"
                                 class="w-full h-full rounded-lg object-cover bg-white">
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </figure>

    @if ($shots > 1)
        @once
            @push('head')
                @vite(['resources/js/pages/trace-gallery.js'], 'build/frontend')
            @endpush
        @endonce
    @endif
@endif
