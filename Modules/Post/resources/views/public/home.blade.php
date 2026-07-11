<!DOCTYPE html>
<html lang="{{ $locale }}" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bài viết</title>
    <link rel="alternate" hreflang="x-default" href="{{ route('post.public.home', ['locale' => config('post.default_locale')]) }}">
    @foreach(config('post.locales') as $code => $label)
    <link rel="alternate" hreflang="{{ $code }}" href="{{ route('post.public.home', ['locale' => $code]) }}">
    @endforeach
    @vite(['resources/css/app.css', 'resources/js/app.js'], 'build/backend')
</head>
<body class="bg-base-200 min-h-screen">

<div class="max-w-5xl mx-auto px-4 py-10">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-base-content">Bài viết</h1>
        <div class="flex gap-2">
            @foreach(config('post.locales') as $code => $label)
            <a href="{{ route('post.public.home', ['locale' => $code]) }}"
               class="btn btn-xs {{ $locale === $code ? 'btn-primary' : 'btn-ghost' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    @if($categories->isNotEmpty())
    <div class="flex flex-wrap gap-2 mb-6">
        @foreach($categories as $c)
        <a href="{{ route('post.public.category', ['locale' => $locale, 'category' => $c->slug]) }}"
           class="badge badge-lg badge-outline hover:badge-primary">{{ $c->name }}</a>
        @endforeach
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($articles as $t)
        <a href="{{ route('post.public.article', ['locale' => $locale, 'slug' => $t->slug]) }}"
           class="card bg-base-100 shadow-sm border border-base-200 hover:shadow-md transition-shadow">
            @if($t->article?->cover_image_url)
            <figure><img src="{{ $t->article->cover_image_url }}" alt="{{ $t->title }}" class="h-40 w-full object-cover"></figure>
            @endif
            <div class="card-body p-4">
                <h2 class="card-title text-base">{{ $t->title }}</h2>
                @if($t->excerpt)
                <p class="text-sm text-base-content/60 line-clamp-2">{{ $t->excerpt }}</p>
                @endif
                <p class="text-xs text-base-content/40 mt-1">{{ $t->published_at?->format('d/m/Y') }}</p>
            </div>
        </a>
        @empty
        <p class="col-span-full text-center text-base-content/40 py-10">Chưa có bài viết nào.</p>
        @endforelse
    </div>

    @if($articles->hasPages())
    <div class="mt-8">{{ $articles->links() }}</div>
    @endif

</div>
</body>
</html>
