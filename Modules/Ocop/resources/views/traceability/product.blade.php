@extends('layouts.traceability')

@section('content')
    @php($group = $category ? trim(\Illuminate\Support\Str::after($category, ':')) : null)
    @php($facts = array_values(array_filter($facts)))

    @php($hasSubject = ! empty($subject['name']))

    <div class="tx-hero">
        @php($gallery = $gallery ?: array_map(fn ($u) => ['src' => $u, 'srcset' => null], $images))
        @php($shots = count($gallery))
        @if ($shots)
            <figure class="relative bg-[#f6f5f2]" data-gallery aria-roledescription="carousel" aria-label="Ảnh sản phẩm {{ $title }}">
                <div data-track class="flex snap-x snap-mandatory overflow-x-auto scroll-smooth [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                    @foreach ($gallery as $i => $shot)
                        <img src="{{ $shot['src'] }}" alt="{{ $title }}{{ $shots > 1 ? ' — ảnh '.($i + 1) : '' }}" width="800" height="600" @if (! empty($shot['srcset'])) srcset="{{ $shot['srcset'] }}" sizes="{{ \App\Shared\Traceability\TraceabilityService::RESPONSIVE_SIZES }}" @endif
                             class="snap-center shrink-0 w-full aspect-[4/3] max-h-[44vh] object-contain p-5 mix-blend-multiply"
                             @if ($i === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async">
                    @endforeach
                </div>
                @if ($shots > 1)
                    <button type="button" data-step="-1" aria-label="Ảnh trước" class="absolute left-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/90 shadow flex items-center justify-center text-[#44403c] hover:text-primary [&>svg]:w-4 [&>svg]:h-4 [&>svg]:rotate-180">@include('traceability.partials.icon', ['name' => 'chevron'])</button>
                    <button type="button" data-step="1" aria-label="Ảnh sau" class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/90 shadow flex items-center justify-center text-[#44403c] hover:text-primary [&>svg]:w-4 [&>svg]:h-4">@include('traceability.partials.icon', ['name' => 'chevron'])</button>
                    <div class="absolute bottom-2.5 inset-x-0 flex justify-center gap-1.5">
                        @foreach ($gallery as $i => $shot)
                            <button type="button" data-go="{{ $i }}" @if ($i === 0) data-active @endif aria-label="Ảnh {{ $i + 1 }}/{{ $shots }}"
                                    class="h-1.5 w-1.5 rounded-full bg-black/25 transition-all data-[active]:w-4 data-[active]:bg-primary"></button>
                        @endforeach
                    </div>
                @endif
            </figure>
        @endif

        <div class="px-4 pt-4 pb-5">
            <p class="tx-eyebrow">{{ $group ?: 'Sản phẩm OCOP' }}@if ($category_code) <span class="font-mono font-medium text-[#a8a29e]" title="Mã phân nhóm OCOP">· {{ $category_code }}</span>@endif</p>
            <h1 class="tx-display text-[26px] mt-1.5">{{ $title }}</h1>
            @if ($description)
                @php($longDescription = collect($info['panels'] ?? [])->flatMap(fn ($p) => $p['items'])->contains('key', 'mo-ta'))
                <p class="mt-2 text-[15px] leading-relaxed text-[#57534e]">{{ \Illuminate\Support\Str::limit(preg_replace('/\s+/u', ' ', $description), 220) }}@if ($longDescription) <a href="#mo-ta" onclick="document.getElementById('mo-ta').open=true" class="font-semibold text-primary whitespace-nowrap">Đọc thêm</a>@endif</p>
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

    @if ($info)
        <section id="thong-tin" class="tx-section">
            <h2 class="tx-section-title">Thông tin sản phẩm </h2>
            <div class="tx-card">
                @foreach ($info['groups'] as $group)
                    @if ($group['rows'])
                        <h3 @class(['px-4 pt-3 pb-1 text-[11.5px] font-semibold uppercase tracking-[0.06em] text-[#a8a29e]', 'border-t border-[var(--tx-line)]' => ! $loop->first])>{{ $group['title'] }}</h3>
                        <dl class="tx-rows">
                            @foreach ($group['rows'] as $row)
                                <div class="flex items-baseline gap-4 px-4 py-2.5 text-[14.5px]">
                                    <dt class="w-[38%] shrink-0 text-[#78716c]">{{ $row['label'] }}</dt>
                                    <dd class="flex-1 min-w-0 leading-snug break-words">
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
                    <h3 class="px-4 pt-3 pb-1 border-t border-[var(--tx-line)] text-[11.5px] font-semibold uppercase tracking-[0.06em] text-[#a8a29e]">{{ $panel['title'] }}</h3>
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
