@extends('layouts.traceability')

@section('content')
    @include('traceability.partials.hero')

    @if ($sponsor)
        <section class="mx-4 mt-4 rounded-xl border-2 border-amber-300 bg-amber-50 px-3 py-3" aria-label="Công bố tài trợ">
            <p class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-amber-800">
                <i class="fa-solid fa-handshake" aria-hidden="true"></i> Công bố tài trợ
            </p>
            <div class="flex items-center gap-3 mt-2">
                @if (! empty($sponsor['logo']))
                    <img src="{{ $sponsor['logo'] }}" alt="{{ $sponsor['name'] }}" width="48" height="48" class="w-12 h-12 rounded-lg object-contain bg-white border border-amber-200">
                @endif
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-amber-900">{{ $sponsor['name'] ?: 'Nhà tài trợ' }}</p>
                    @if (! empty($sponsor['label']))<p class="text-xs text-amber-800/80">{{ $sponsor['label'] }}</p>@endif
                </div>
            </div>
            @if (! empty($sponsor['disclosure']))
                <p class="text-xs text-amber-900/80 mt-2 leading-relaxed">{{ $sponsor['disclosure'] }}</p>
            @endif
        </section>
    @endif

    @if ($summary)
        <section class="mx-4 mt-4 rounded-xl bg-primary/5 border-l-4 border-primary px-3 py-3">
            <p class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wide text-primary mb-1">
                <i class="fa-solid fa-bolt" aria-hidden="true"></i> Tóm tắt 30 giây
            </p>
            <p class="text-sm leading-relaxed text-base-content/80">{{ $summary }}</p>
        </section>
    @endif

    @include('traceability.partials.fact-grid', ['facts' => $facts])

    @if (filled($content_html))
        <article class="px-4 mt-6 prose prose-sm max-w-none prose-img:rounded-lg prose-img:mx-auto prose-a:text-primary [&_table]:block [&_table]:overflow-x-auto [&_iframe]:w-full [&_iframe]:aspect-video">
            {!! $content_html !!}
        </article>
    @endif

    @if ($citations)
        <section class="px-4 mt-6">
            <h2 class="text-sm font-bold mb-2 flex items-center gap-1.5"><i class="fa-solid fa-quote-left text-primary" aria-hidden="true"></i> Nguồn tham khảo</h2>
            <ol class="space-y-2 list-decimal list-inside text-sm">
                @foreach ($citations as $citation)
                    <li class="rounded-lg bg-base-200 px-3 py-2">
                        <span class="text-base-content/80">“{{ $citation['text'] }}”</span>
                        <span class="block text-xs mt-1 text-base-content/60">
                            — @if (! empty($citation['url']))<a href="{{ $citation['url'] }}" target="_blank" rel="noopener nofollow" class="link link-primary">{{ $citation['source'] }}</a>@else{{ $citation['source'] }}@endif
                        </span>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    @if ($tags)
        <div class="px-4 mt-5 flex flex-wrap gap-1.5">
            @foreach ($tags as $tag)<span class="badge badge-ghost badge-sm"><i class="fa-solid fa-tag mr-1 opacity-50" aria-hidden="true"></i>{{ $tag }}</span>@endforeach
        </div>
    @endif
    @include('traceability.partials.related')
@endsection
