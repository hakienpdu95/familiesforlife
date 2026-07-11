<!DOCTYPE html>
<html lang="{{ $locale }}" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $translation->seo_title ?: $translation->title }}</title>
    @if($translation->seo_description || $translation->excerpt)
    <meta name="description" content="{{ $translation->seo_description ?: $translation->excerpt }}">
    @endif

    {{-- §11.2 — canonical trỏ chính URL này (kể cả sau khi được redirect fallback tới đây) --}}
    <link rel="canonical" href="{{ route('post.public.article', ['locale' => $locale, 'slug' => $translation->slug]) }}">

    {{-- §11.2 — hreflang cho mọi locale đang published của cùng article --}}
    @foreach($hreflangs as $h)
    <link rel="alternate" hreflang="{{ $h->locale }}" href="{{ route('post.public.article', ['locale' => $h->locale, 'slug' => $h->slug]) }}">
    @endforeach

    @vite(['resources/css/app.css', 'resources/js/app.js'], 'build/backend')
</head>
<body class="bg-base-200 min-h-screen">

<div class="max-w-3xl mx-auto px-4 py-10">

    <div class="text-xs breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('post.public.home', ['locale' => $locale]) }}">Bài viết</a></li>
            @if($article->categories->isNotEmpty())
            <li><a href="{{ route('post.public.category', ['locale' => $locale, 'category' => $article->categories->first()->slug]) }}">{{ $article->categories->first()->name }}</a></li>
            @endif
        </ul>
    </div>

    <h1 class="text-3xl font-bold text-base-content mb-2">{{ $translation->title }}</h1>
    <p class="text-sm text-base-content/50 mb-4">{{ $translation->published_at?->format('d/m/Y') }}</p>

    @if($article->categories->isNotEmpty())
    <div class="flex flex-wrap gap-1.5 mb-4">
        @foreach($article->categories as $cat)
        <span class="badge badge-sm badge-ghost">{{ $cat->name }}</span>
        @endforeach
    </div>
    @endif

    @if($translation->excerpt)
    <p class="text-base-content/70 italic mb-4">{{ $translation->excerpt }}</p>
    @endif

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body prose max-w-none">
            {!! $content !!}
        </div>
    </div>

    @if($article->tags->isNotEmpty())
    <div class="flex flex-wrap gap-1.5 mt-4">
        @foreach($article->tags as $tag)
        <span class="badge badge-sm badge-outline">#{{ $tag->name }}</span>
        @endforeach
    </div>
    @endif

    @if($hreflangs->count() > 1)
    <div class="mt-6 flex gap-2">
        @foreach($hreflangs as $h)
        <a href="{{ route('post.public.article', ['locale' => $h->locale, 'slug' => $h->slug]) }}"
           class="btn btn-xs {{ $h->locale === $locale ? 'btn-primary' : 'btn-ghost' }}">{{ config('post.locales')[$h->locale] ?? $h->locale }}</a>
        @endforeach
    </div>
    @endif

</div>
</body>
</html>
