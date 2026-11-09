@extends('layouts.traceability')

@section('content')
    @php($group = $category ? trim(\Illuminate\Support\Str::after($category, ':')) : null)
    @php($facts = array_values(array_filter($facts)))


    <div class="tx-hero">
        @php($gallery = $gallery ?: array_map(fn ($u) => ['src' => $u, 'srcset' => null], $images))
        @php($shots = count($gallery))
        @if ($shots)
            <figure class="relative bg-[#f6f5f2]" data-gallery aria-roledescription="carousel" aria-label="Ảnh sản phẩm {{ $title }}">
                <div data-track class="flex snap-x snap-mandatory overflow-x-auto scroll-smooth [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($gallery as $i => $shot)
                        <img src="{{ $shot['src'] }}" alt="{{ $title }}{{ $shots > 1 ? ' — ảnh '.($i + 1) : '' }}" width="800" height="600" @if (! empty($shot['srcset'])) srcset="{{ $shot['srcset'] }}" sizes="{{ \App\Shared\Traceability\TraceabilityService::RESPONSIVE_SIZES }}" @endif
                             class="snap-center shrink-0 w-full aspect-[4/3] max-h-[40svh] object-contain p-4 sm:p-5 mix-blend-multiply"
                             @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async">
                    @endforeach
                </div>
                @if ($shots > 1)
                    <button type="button" data-step="-1" aria-label="Ảnh trước" class="absolute left-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/90 shadow flex items-center justify-center text-[#44403c] hover:text-primary [&>svg]:w-4 [&>svg]:h-4 [&>svg]:rotate-180">@include('traceability.partials.icon', ['name' => 'chevron'])</button>
                    <button type="button" data-step="1" aria-label="Ảnh sau" class="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/90 shadow flex items-center justify-center text-[#44403c] hover:text-primary [&>svg]:w-4 [&>svg]:h-4">@include('traceability.partials.icon', ['name' => 'chevron'])</button>
                    <div class="absolute bottom-1 inset-x-0 flex justify-center">
                        @foreach ($gallery as $i => $shot)
                            <button type="button" data-go="{{ $i }}" @if ($i === 0) data-active @endif aria-label="Ảnh {{ $i + 1 }}/{{ $shots }}"
                                    class="group/dot p-1.5"><span class="block h-1.5 w-1.5 rounded-full bg-black/25 transition-all group-data-[active]/dot:w-4 group-data-[active]/dot:bg-primary"></span></button>
                        @endforeach
                    </div>
                @endif
            </figure>
        @endif

        <div class="px-4 pt-4 pb-5">
            <p class="tx-eyebrow">{{ $group ?: 'Sản phẩm OCOP' }}@if ($category_code) <span class="font-mono font-medium text-[#a8a29e]" title="Mã phân nhóm OCOP">· {{ $category_code }}</span>@endif</p>
            <h1 class="tx-display tx-h1 mt-1.5">{{ $title }}</h1>
            @if ($description)
                @php($longDescription = collect($info['panels'] ?? [])->flatMap(fn ($p) => $p['items'])->contains('key', 'mo-ta'))
                <p class="mt-2 text-[15px] leading-relaxed text-[#57534e] [overflow-wrap:anywhere]">{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', $description), 220) }}@if ($longDescription) <a href="#mo-ta" onclick="document.getElementById('mo-ta').open=true" class="font-semibold text-primary whitespace-nowrap">Đọc thêm</a>@endif</p>
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
                        <a href="{{ $channel['url'] }}" target="_blank" rel="noopener nofollow sponsored" class="tx-row gap-3.5 py-3.5"
                           aria-label="Mua {{ $title }} trên {{ $channel['name'] }} (mở trang mới)">
                            <span class="w-11 h-11 rounded-[10px] bg-white ring-1 ring-[rgb(28_25_23/.08)] flex items-center justify-center shrink-0">
                                <img src="{{ $channel['logo'] }}" alt="" width="32" height="32" loading="lazy" decoding="async" class="w-8 h-8 object-contain">
                            </span>
                            <span class="flex-1 min-w-0">
                                <span class="block text-[15px] font-semibold leading-5 text-[#1c1917]">{{ $channel['name'] }}</span>
                                <span class="mt-0.5 flex items-center gap-1 text-[12.5px] leading-4 text-[#78716c] min-w-0">
                                    @if ($channel['official'])
                                        <svg class="w-3.5 h-3.5 shrink-0 text-[#15803d]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.86-9.7a.75.75 0 0 0-1.22-.88l-3.24 4.5-1.6-1.6a.75.75 0 1 0-1.06 1.06l2.22 2.22a.75.75 0 0 0 1.14-.09l3.76-5.2Z" clip-rule="evenodd"/></svg>
                                    @endif
                                    <span class="truncate">{{ $channel['host'] }}</span>
                                </span>
                            </span>
                            <span class="shrink-0 inline-flex items-center gap-1.5 h-10 pl-3.5 pr-3 max-[359.98px]:pl-3 max-[359.98px]:pr-2.5 rounded-lg text-white text-[13.5px] font-semibold transition-colors {{ $channel['button'] }} [&>svg]:w-3.5 [&>svg]:h-3.5 [&>svg]:opacity-80 max-[389.98px]:[&>svg]:hidden">Mua ngay @include('traceability.partials.icon', ['name' => 'external'])</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if ($info)
        <section id="thong-tin" class="tx-section">
            <h2 class="tx-section-title">Thông tin sản phẩm </h2>
            <div class="tx-card">
                @foreach ($info['groups'] as $group)
                    @if ($group['rows'])
                        <h3 @class(['px-4 pt-3 pb-1 text-[12px] font-semibold uppercase tracking-[0.06em] text-[#a8a29e]', 'border-t border-[var(--tx-line)]' => ! $loop->first])>{{ $group['title'] }}</h3>
                        <dl class="tx-rows">
                            @foreach ($group['rows'] as $row)
                                <div class="flex items-baseline gap-3 px-4 py-2.5 text-[14.5px] max-[359.98px]:px-3.5">
                                    <dt class="w-[clamp(6.5rem,36%,9.5rem)] shrink-0 text-[#78716c] leading-snug">{{ $row['label'] }}</dt>
                                    <dd class="flex-1 min-w-0 leading-snug [overflow-wrap:anywhere]">
                                        @if (filled($row['value']))
                                            <span @class(['font-medium', 'font-mono tracking-wide' => ! empty($row['mono'])])>{{ $row['value'] }}</span>
                                            @if (! empty($row['note']))<span class="block mt-0.5 text-[12.5px] text-[#78716c]">{{ $row['note'] }}</span>@endif
                                        @else
                                            <span class="text-[#a8a29e]">Chưa khai báo</span>
                                        @endif
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                @endforeach

                @foreach ($info['panels'] as $panel)
                    <h3 class="px-4 pt-3 pb-1 border-t border-[var(--tx-line)] text-[12px] font-semibold uppercase tracking-[0.06em] text-[#a8a29e]">{{ $panel['title'] }}</h3>
                    <div class="tx-rows">
                        @foreach ($panel['items'] as $section)
                            @php($has = filled($section['html'] ?? null) || filled($section['text'] ?? null))
                            @if ($has)
                                <details @if ($section['key'] === 'mo-ta') id="mo-ta" @endif class="tx-details group/acc scroll-mt-20" @if (! empty($section['open'])) open @endif>
                                    <summary class="tx-row-link flex items-center gap-4 px-4 min-h-[3.25rem] py-3">
                                        <span class="flex-1 min-w-0 leading-snug">
                                            <span class="block text-[15px] font-semibold">{{ $section['title'] }}</span>
                                            @if (! empty($section['preview']))
                                                <span class="block text-[13px] text-[#78716c] truncate group-open/acc:hidden">{{ $section['preview'] }}</span>
                                            @endif
                                        </span>
                                        <span class="tx-chevron transition-transform group-open/acc:rotate-90">@include('traceability.partials.icon', ['name' => 'chevron'])</span>
                                    </summary>
                                    @if (! empty($section['html']))
                                        <div class="tx-prose text-[15px] px-4 pb-4">{!! $section['html'] !!}</div>
                                    @else
                                        <p class="px-4 pb-4 text-[15px] leading-relaxed text-[#44403c] whitespace-pre-line">{{ $section['text'] }}</p>
                                    @endif
                                </details>
                            @else
                                <div class="flex items-center justify-between gap-4 px-4 min-h-[3rem] py-2.5">
                                    <span class="text-[15px] font-semibold text-[#78716c]">{{ $section['title'] }}</span>
                                    <span class="text-[13px] text-[#a8a29e]">Chưa khai báo</span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endforeach

            </div>
        </section>
    @endif

    @include('traceability.partials.related')

    @if ($shots > 1)
        <script>
            document.querySelectorAll('[data-gallery]').forEach(function (g) {
                var t = g.querySelector('[data-track]'), dots = g.querySelectorAll('[data-go]'), cur = 0;
                function go(i) { t.scrollTo({ left: ((i + dots.length) % dots.length) * t.clientWidth }); }
                t.addEventListener('scroll', function () {
                    var i = Math.round(t.scrollLeft / t.clientWidth);
                    if (i === cur) return;
                    cur = i;
                    dots.forEach(function (b, k) { b.toggleAttribute('data-active', k === i); });
                }, { passive: true });
                dots.forEach(function (b) { b.addEventListener('click', function () { go(+b.dataset.go); }); });
                g.querySelectorAll('[data-step]').forEach(function (b) { b.addEventListener('click', function () { go(cur + +b.dataset.step); }); });
            });
        </script>
    @endif
@endsection
