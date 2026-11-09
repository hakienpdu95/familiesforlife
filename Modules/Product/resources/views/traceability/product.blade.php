@extends('layouts.traceability')

@section('content')
    <div class="tx-hero">
        @php($shots = count($images))
        @if ($shots)
            <figure class="relative bg-[#f6f5f2]" data-gallery aria-roledescription="carousel" aria-label="Ảnh {{ $title }}">
                <div data-track class="flex snap-x snap-mandatory overflow-x-auto scroll-smooth [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($images as $i => $src)
                        <img src="{{ $src }}" alt="{{ $title }}{{ $shots > 1 ? ' — ảnh '.($i + 1) : '' }}" width="800" height="600"
                             onerror="this.remove()" @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async"
                             class="snap-center shrink-0 w-full aspect-[4/3] max-h-[40svh] object-contain p-4 sm:p-5 mix-blend-multiply">
                    @endforeach
                </div>
                @if ($shots > 1)
                    <span data-counter class="absolute top-2.5 right-2.5 h-6 px-2 rounded-full bg-black/55 text-white text-[12px] font-medium tabular-nums flex items-center">1/{{ $shots }}</span>
                    <button type="button" data-step="-1" aria-label="Ảnh trước" class="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/90 shadow flex items-center justify-center text-[#44403c] hover:text-primary [&>svg]:w-4 [&>svg]:h-4 [&>svg]:rotate-180">@include('traceability.partials.icon', ['name' => 'chevron'])</button>
                    <button type="button" data-step="1" aria-label="Ảnh sau" class="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/90 shadow flex items-center justify-center text-[#44403c] hover:text-primary [&>svg]:w-4 [&>svg]:h-4">@include('traceability.partials.icon', ['name' => 'chevron'])</button>
                @endif
            </figure>
        @endif

        <div class="px-4 pt-4 pb-5">
            <p class="tx-eyebrow">{{ $kind }}@if ($category)<span class="font-medium text-[#a8a29e]"> · {{ $category }}</span>@endif</p>
            <h1 class="tx-display tx-h1 mt-1.5">{{ $title }}</h1>
            @if ($summary)
                <p class="mt-1.5 text-[14px] leading-[1.6] text-[#78716c] [overflow-wrap:anywhere]">{{ $summary }}</p>
            @endif

            @if ($price || $availability)
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    @if ($price)
                        <span class="inline-flex items-baseline gap-1.5 h-9 px-3 rounded-lg bg-[#f5f5f4] text-[13px] text-[#78716c]">Giá tham khảo <strong class="text-[16px] font-bold text-[#1c1917] tabular-nums">{{ $price }}</strong></span>
                    @endif
                    @if ($availability)
                        <span @class([
                            'inline-flex items-center gap-1.5 h-9 px-3 rounded-lg text-[13px] font-semibold',
                            'bg-[#fffbeb] text-[#92400e]' => $availability['tone'] === 'warn',
                            'bg-[#f5f5f4] text-[#57534e]' => $availability['tone'] === 'muted',
                        ])><span @class(['w-2 h-2 rounded-full', 'bg-[#f59e0b]' => $availability['tone'] === 'warn', 'bg-[#a8a29e]' => $availability['tone'] === 'muted']) aria-hidden="true"></span>{{ $availability['label'] }}</span>
                    @endif
                </div>
                @if ($availability)
                    <p class="mt-2 text-[13px] leading-snug text-[#78716c]">{{ $availability['text'] }}</p>
                @endif
            @endif
        </div>
    </div>

    @if ($channels)
        <section id="mua-hang" class="tx-section">
            <h2 class="tx-section-title">Nơi mua <small>{{ count($channels) }} kênh</small></h2>
            <ul class="tx-card tx-rows">
                @foreach ($channels as $channel)
                    <li>
                        <a href="{{ $channel['url'] }}" target="_blank" rel="noopener nofollow sponsored" class="tx-row gap-3.5 py-3.5"
                           aria-label="Mua {{ $title }} tại {{ $channel['name'] }} (mở trang mới)">
                            <span class="w-11 h-11 rounded-[10px] bg-white ring-1 ring-[rgb(28_25_23/.08)] flex items-center justify-center shrink-0 text-[#57534e] [&>svg]:w-5 [&>svg]:h-5">
                                @if ($channel['logo'])
                                    <img src="{{ $channel['logo'] }}" alt="" width="32" height="32" loading="lazy" decoding="async" class="w-8 h-8 object-contain">
                                @else
                                    @include('traceability.partials.icon', ['name' => 'store'])
                                @endif
                            </span>
                            <span class="flex-1 min-w-0">
                                <span class="block text-[15px] font-semibold leading-5 text-[#1c1917]">{{ $channel['name'] }}</span>
                                <span class="mt-0.5 flex items-center gap-1 text-[12.5px] leading-4 text-[#78716c] min-w-0">
                                    @if ($channel['mismatch'])
                                        <svg class="w-3.5 h-3.5 shrink-0 text-[#d97706]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.49 2.86a1.75 1.75 0 0 1 3.02 0l6.28 10.9A1.75 1.75 0 0 1 16.28 16.4H3.72a1.75 1.75 0 0 1-1.51-2.63l6.28-10.9ZM10 6.5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 6.5Zm0 7.5a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/></svg>
                                    @elseif ($channel['official'])
                                        <svg class="w-3.5 h-3.5 shrink-0 text-[#15803d]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.86-9.7a.75.75 0 0 0-1.22-.88l-3.24 4.5-1.6-1.6a.75.75 0 1 0-1.06 1.06l2.22 2.22a.75.75 0 0 0 1.14-.09l3.76-5.2Z" clip-rule="evenodd"/></svg>
                                    @endif
                                    <span @class(['truncate', 'text-[#b45309]' => $channel['mismatch']])>{{ $channel['host'] }}</span>
                                </span>
                                @if ($channel['mismatch'])
                                    <span class="mt-0.5 block text-[12px] leading-4 font-medium text-[#b45309]">Không phải tên miền {{ $channel['name'] }}</span>
                                @endif
                            </span>
                            <span class="shrink-0 inline-flex items-center gap-1.5 h-10 pl-3.5 pr-3 max-[359.98px]:pl-3 max-[359.98px]:pr-2.5 rounded-lg text-white text-[13.5px] font-semibold transition-colors {{ $channel['button'] }} [&>svg]:w-3.5 [&>svg]:h-3.5 [&>svg]:opacity-80 max-[389.98px]:[&>svg]:hidden">Mua ngay @include('traceability.partials.icon', ['name' => 'external'])</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($specs)
        <section id="thong-tin" class="tx-section">
            <h2 class="tx-section-title">Thông tin sản phẩm</h2>
            <dl class="tx-card tx-rows">
                @foreach ($specs as $row)
                    <div class="flex items-baseline gap-3 px-4 py-2.5 text-[14.5px] max-[359.98px]:px-3.5">
                        <dt class="w-[clamp(6.5rem,36%,9.5rem)] shrink-0 text-[#78716c] leading-snug">{{ $row['label'] }}</dt>
                        <dd class="flex-1 min-w-0 leading-snug [overflow-wrap:anywhere]">
                            @if (! empty($row['url']))
                                <a href="{{ $row['url'] }}" target="_blank" rel="noopener nofollow" class="font-medium text-primary hover:underline">{{ $row['value'] }}</a>
                            @else
                                <span @class(['font-medium', 'font-mono tracking-wide' => ! empty($row['mono'])])>{{ $row['value'] }}</span>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endif

    @if ($description || $content_html)
        <section id="gioi-thieu" class="tx-section">
            <h2 class="tx-section-title">Giới thiệu</h2>
            <div class="tx-card">
                @if ($description)
                    <div class="tx-prose text-[15px] px-4 py-3.5 [overflow-wrap:anywhere]">{!! $description !!}</div>
                @endif
                @if ($content_html)
                    <details class="tx-details group/acc @if ($description) border-t border-[var(--tx-line)] @endif" @unless ($description) open @endunless>
                        <summary class="tx-row-link flex items-center gap-4 px-4 min-h-[3.25rem] py-3">
                            <span class="flex-1 text-[15px] font-semibold">Thông tin chi tiết</span>
                            <span class="tx-chevron transition-transform group-open/acc:rotate-90">@include('traceability.partials.icon', ['name' => 'chevron'])</span>
                        </summary>
                        <div class="tx-prose text-[15px] px-4 pb-4">{!! $content_html !!}</div>
                    </details>
                @endif
            </div>
        </section>
    @endif

    @include('traceability.partials.related')

    @if ($shots > 1)
        <script>
            document.querySelectorAll('[data-gallery]').forEach(function (g) {
                var t = g.querySelector('[data-track]'), n = g.querySelector('[data-counter]'), total = t.children.length, cur = 0;
                function go(i) { t.scrollTo({ left: ((i + total) % total) * t.clientWidth }); }
                t.addEventListener('scroll', function () {
                    var i = Math.round(t.scrollLeft / t.clientWidth);
                    if (i === cur) return;
                    cur = i;
                    if (n) n.textContent = (i + 1) + '/' + total;
                }, { passive: true });
                g.querySelectorAll('[data-step]').forEach(function (b) { b.addEventListener('click', function () { go(cur + +b.dataset.step); }); });
            });
        </script>
    @endif
@endsection
