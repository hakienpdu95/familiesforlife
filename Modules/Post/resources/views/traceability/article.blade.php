@extends('layouts.traceability')

@section('content')
    <article class="tx-hero">
        <header class="px-3 pt-5">
            @if ($category)<p class="tx-eyebrow">{{ $category }}</p>@endif
            <h1 class="tx-display text-[26px] leading-[1.25] mt-1.5">{{ $title }}</h1>
            @php($meta = array_filter([$published_label ? __('Đăng :date', ['date' => $published_label]) : null, $updated_label ? __('Cập nhật :date', ['date' => $updated_label]) : null, $reading_minutes ? __(':count phút đọc', ['count' => $reading_minutes]) : null]))
            @if ($meta)
                <p class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-[13px] font-medium text-[#78716c]">
                    @foreach ($meta as $m)
                        @if (! $loop->first)<span class="w-1 h-1 rounded-full bg-[#a8a29e]" aria-hidden="true"></span>@endif
                        <span>{{ $m }}</span>
                    @endforeach
                </p>
            @endif
            @if ($summary)
                <p class="mt-4 pl-3.5 border-l-[3px] border-primary text-[16.5px] leading-relaxed text-[#44403c]">{{ $summary }}</p>
            @endif
        </header>

        @if ($sponsor)
            <aside class="mx-4 mt-4 flex items-center gap-3 rounded-lg bg-[#fdf8ea] border border-[#ead9a8] px-3.5 py-3" aria-label="{{ __('Công bố tài trợ') }}">
                @if (! empty($sponsor['logo']))
                    <img src="{{ $sponsor['logo'] }}" alt="{{ $sponsor['name'] }}" width="40" height="40" class="w-10 h-10 rounded-md bg-base-100 object-contain shrink-0">
                @endif
                <p class="text-[13px] leading-snug text-base-content/75">
                    <span class="block mb-0.5 text-[11px] font-semibold uppercase tracking-[0.1em] text-[#8a6413]">{{ $sponsor['label'] ?: __('Nội dung tài trợ') }}</span>
                    {{ __('Bài viết được thực hiện với sự tài trợ của') }} <strong class="text-base-content">{{ $sponsor['name'] ?: __('đối tác') }}</strong>.
                    @if (! empty($sponsor['disclosure'])){{ $sponsor['disclosure'] }}@endif
                </p>
            </aside>
        @endif

        @if ($images)
            <img src="{{ $images[0] }}" alt="{{ $title }}" width="960" height="540" @if (! empty($image_srcsets[0])) srcset="{{ $image_srcsets[0] }}" sizes="{{ \App\Shared\Traceability\TraceabilityService::RESPONSIVE_SIZES }}" @endif fetchpriority="high" decoding="async" class="block w-full aspect-video object-cover mt-5 bg-[#efefec]">
        @endif

        @if (filled($content_html))
            <div class="tx-prose tx-prose-lg px-3 pt-6 pb-2">{!! $content_html !!}</div>
        @endif

        @if ($cta)
        <div class="px-4 pt-4 pb-6">
            <a href="{{ $cta['url'] }}" class="btn btn-outline w-full h-12 rounded-lg text-[15px] font-semibold shadow-none border-[rgb(28_25_23/.16)] hover:border-primary hover:bg-transparent hover:text-primary">{{ $cta['label'] }} →</a>
        </div>
        @endif
    </article>

    @if ($citations)
        <section class="tx-section">
            <h2 class="tx-section-title">{{ __('Nguồn tham khảo') }} <small>{{ count($citations) }}</small></h2>
            <ol class="tx-card tx-rows text-[14px]">
                @foreach ($citations as $citation)
                    <li class="flex gap-3 px-4 py-3">
                        <span class="w-6 h-6 shrink-0 rounded-md bg-[#f2f2ef] text-[12px] font-semibold text-[#78716c] flex items-center justify-center tabular-nums">{{ $loop->iteration }}</span>
                        <span class="min-w-0 text-base-content/80">
                            “{{ $citation['text'] }}”
                            @if (! empty($citation['source']))
                                <span class="block mt-1 text-[13px] text-base-content/55">
                                    @if (! empty($citation['url']))<a href="{{ $citation['url'] }}" target="_blank" rel="noopener nofollow" class="underline underline-offset-2 hover:text-primary">{{ $citation['source'] }}</a>@else{{ $citation['source'] }}@endif
                                </span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    @if ($tags)
        <section class="tx-section">
            <h2 class="tx-section-title">{{ __('Chủ đề') }}</h2>
            <p class="flex flex-wrap gap-2">
                @foreach ($tags as $tag)
                    <span class="inline-flex items-center h-8 px-2.5 rounded-md bg-base-100 border border-[var(--tx-line)] text-[13px] font-medium text-[#57534e]">#{{ $tag }}</span>
                @endforeach
            </p>
        </section>
    @endif

    @include('traceability.partials.related')
@endsection
