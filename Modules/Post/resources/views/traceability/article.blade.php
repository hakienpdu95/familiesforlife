@extends('layouts.traceability')

@section('content')
    @if ($image)
        <img src="{{ $image }}" alt="{{ $title }}" width="800" height="450" class="w-full aspect-video object-cover">
    @endif

    <article class="px-4 pt-5 space-y-4">
        @if ($category || $published_at)
            <div class="flex flex-wrap items-center gap-2 text-xs">
                @if ($category)
                    <span class="badge badge-primary badge-sm">{{ $category }}</span>
                @endif
                @if ($published_at)
                    <span class="flex items-center gap-1 text-base-content/50">
                        <i class="fa-solid fa-calendar" aria-hidden="true"></i>
                        {{ $published_at }}
                    </span>
                @endif
            </div>
        @endif

        <h1 class="text-2xl font-bold leading-snug">{{ $title }}</h1>

        @if ($excerpt)
            <p class="text-base leading-relaxed text-base-content/80 border-l-4 border-primary pl-3">{{ $excerpt }}</p>
        @endif

        @if (filled($content_html))
            <div class="prose prose-sm max-w-none prose-img:rounded-lg prose-img:mx-auto prose-a:text-primary [&_table]:block [&_table]:overflow-x-auto [&_iframe]:w-full [&_iframe]:aspect-video">
                {!! $content_html !!}
            </div>
        @endif
    </article>
@endsection
