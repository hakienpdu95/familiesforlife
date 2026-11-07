@extends('layouts.traceability')

@section('content')
    @if ($image)
        <img src="{{ $image }}" alt="{{ $title }}" width="800" height="450" class="w-full aspect-video object-cover">
    @endif

    <div class="px-4 pt-5 space-y-5">
        <div>
            <div class="flex flex-wrap gap-2 mb-2">
                @if ($type)
                    <span class="badge badge-outline badge-sm">{{ $type }}</span>
                @endif
                @if ($rank)
                    <span class="badge badge-primary badge-sm">{{ $rank }}</span>
                @endif
            </div>
            <h1 class="text-2xl font-bold leading-snug">{{ $title }}</h1>
            @if ($era)
                <p class="flex items-center gap-1.5 text-sm text-base-content/60 mt-1">
                    <i class="fa-solid fa-clock" aria-hidden="true"></i>
                    {{ $era }}
                </p>
            @endif
        </div>

        @if ($visiting)
            <div @class([
                'flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium',
                'bg-green-50 text-green-800' => $visiting['tone'] === 'success',
                'bg-amber-50 text-amber-800' => $visiting['tone'] === 'warning',
                'bg-red-50 text-red-800' => $visiting['tone'] === 'error',
            ])>
                <span @class([
                    'w-2 h-2 rounded-full',
                    'bg-green-500' => $visiting['tone'] === 'success',
                    'bg-amber-500' => $visiting['tone'] === 'warning',
                    'bg-red-500' => $visiting['tone'] === 'error',
                ])></span>
                {{ $visiting['label'] }}
            </div>
        @endif

        @if ($address || $map_url)
        <section class="flex items-start gap-3 rounded-lg bg-base-200 px-3 py-3">
            <i class="fa-solid fa-location-dot text-primary mt-0.5" aria-hidden="true"></i>
            <div class="text-sm flex-1">
                <p class="font-medium">{{ $address ?: 'Xem vị trí trên bản đồ' }}</p>
                @if ($map_url)
                    <a href="{{ $map_url }}" target="_blank" rel="noopener" class="link link-primary text-xs mt-1 inline-flex items-center gap-1">
                        <i class="fa-solid fa-map" aria-hidden="true"></i>
                        Chỉ đường trên bản đồ
                    </a>
                @endif
            </div>
        </section>
        @endif

        @if ($intro)
            <section>
                <h2 class="text-sm font-bold mb-2">Giới thiệu</h2>
                <p class="text-sm leading-relaxed text-base-content/80 whitespace-pre-line">{{ $intro }}</p>
            </section>
        @endif

        @if (filled($content_html))
            <section class="prose prose-sm max-w-none prose-img:rounded-lg prose-img:mx-auto prose-a:text-primary [&_table]:block [&_table]:overflow-x-auto [&_iframe]:w-full [&_iframe]:aspect-video">
                {!! $content_html !!}
            </section>
        @endif
    </div>
@endsection
