<!DOCTYPE html>
<html lang="{{ $locale }}" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $category->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'], 'build/backend')
</head>
<body class="bg-base-200 min-h-screen">

<div class="max-w-5xl mx-auto px-4 py-10">

    <div class="text-xs breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('post.public.home', ['locale' => $locale]) }}">Bài viết</a></li>
            @foreach($breadcrumb as $node)
            <li><a href="{{ route('post.public.category', ['locale' => $locale, 'category' => $node->slug]) }}">{{ $node->name }}</a></li>
            @endforeach
        </ul>
    </div>

    <h1 class="text-2xl font-bold text-base-content mb-6">{{ $category->name }}</h1>

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
        <p class="col-span-full text-center text-base-content/40 py-10">Chưa có bài viết nào trong danh mục này.</p>
        @endforelse
    </div>

    @if($articles->hasPages())
    <div class="mt-8">{{ $articles->links() }}</div>
    @endif

</div>
</body>
</html>
