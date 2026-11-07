@extends('layouts.traceability')

@section('content')
    <article>
        <header class="px-5 pt-7">
            @if ($category)<p class="tx-label !text-primary">{{ $category }}</p>@endif
            <h1 class="tx-serif text-[28px] font-bold leading-[1.25] mt-2">{{ $title }}</h1>
            @if ($summary)
                <p class="mt-4 text-[17px] leading-relaxed text-base-content/75">{{ $summary }}</p>
            @endif
            @php($meta = array_filter([$published_label ? 'Đăng ngày '.$published_label : null, $updated_label ? 'cập nhật '.$updated_label : null, $reading_minutes ? $reading_minutes.' phút đọc' : null]))
            @if ($meta)
                <p class="mt-4 pb-5 border-b border-base-300 text-[13px] text-base-content/60">{{ implode(' · ', $meta) }}</p>
            @endif
        </header>

        @if ($sponsor)
            <aside class="mx-5 mt-5 flex items-center gap-3 py-3 border-y border-[#e6d39a]" aria-label="Công bố tài trợ">
                @if (! empty($sponsor['logo']))
                    <img src="{{ $sponsor['logo'] }}" alt="{{ $sponsor['name'] }}" width="40" height="40" class="w-10 h-10 object-contain shrink-0">
                @endif
                <p class="text-[13px] leading-snug text-base-content/75">
                    <span class="tx-label !text-[#8a6413] block mb-0.5">{{ $sponsor['label'] ?: 'Nội dung tài trợ' }}</span>
                    Bài viết được thực hiện với sự tài trợ của <strong class="text-base-content">{{ $sponsor['name'] ?: 'đối tác' }}</strong>.
                    @if (! empty($sponsor['disclosure'])){{ $sponsor['disclosure'] }}@endif
                </p>
            </aside>
        @endif

        @if ($images)
            <img src="{{ $images[0] }}" alt="{{ $title }}" width="960" height="540" fetchpriority="high" decoding="async" class="w-full aspect-video object-cover mt-5 bg-base-200">
        @endif

        @if (filled($content_html))
            <div class="tx-prose tx-prose-lg px-5 mt-6">{!! $content_html !!}</div>
        @endif

        @if ($citations)
            <section class="px-5 mt-8">
                <h2 class="tx-label mb-2">Nguồn tham khảo</h2>
                <ol class="border-t border-base-300 text-sm">
                    @foreach ($citations as $citation)
                        <li class="grid grid-cols-[24px_1fr] gap-2 py-2.5 border-b border-base-300">
                            <span class="text-base-content/40 tabular-nums">{{ $loop->iteration }}.</span>
                            <span class="text-base-content/75">
                                “{{ $citation['text'] }}”
                                <span class="block mt-0.5 text-[13px] text-base-content/60">
                                    @if (! empty($citation['url']))<a href="{{ $citation['url'] }}" target="_blank" rel="noopener nofollow" class="underline underline-offset-2 hover:text-primary">{{ $citation['source'] }}</a>@else{{ $citation['source'] }}@endif
                                </span>
                            </span>
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif

        @if ($tags)
            <p class="px-5 mt-6 text-[13px] text-base-content/60">Chủ đề: {{ implode(', ', $tags) }}</p>
        @endif

        <div class="px-5 mt-8">
            <a href="{{ $cta['url'] }}" class="btn btn-outline w-full h-12 rounded-lg font-medium">{{ $cta['label'] }} →</a>
        </div>
    </article>

    @include('traceability.partials.related')
@endsection
