@props([
    'translation', // PostArticleTranslation (with article.categories loaded)
    'size' => 'md', // lg | md | sm
])

@php
    $article = $translation->article;
    $category = $article?->categories->first();
    $styles = match ($size) {
        'lg' => ['ratio' => 'aspect-[16/10]', 'title' => 'text-2xl font-extrabold', 'gap' => 'gap-6', 'excerpt' => true],
        'sm' => ['ratio' => 'aspect-[4/3]', 'title' => 'font-bold text-sm', 'gap' => 'gap-3', 'excerpt' => false],
        default => ['ratio' => 'aspect-[16/9]', 'title' => 'font-bold', 'gap' => 'gap-3', 'excerpt' => false],
    };
@endphp

<a href="{{ route('post.public.article', ['slug' => $translation->slug]) }}"
   class="group flex flex-col {{ $styles['gap'] }}">
    <div class="{{ $styles['ratio'] }} rounded-xl overflow-hidden bg-base-200">
        @if($article?->cover_image_url)
        <img src="{{ $article->cover_image_url }}" alt="{{ $translation->title }}" class="h-full w-full object-cover" loading="lazy">
        @else
        <div class="ph h-full w-full"></div>
        @endif
    </div>
    <div>
        @if($category)
        <span class="text-xs font-black uppercase tracking-wide text-primary">{{ $category->name }}</span>
        @endif
        <h3 class="{{ $styles['title'] }} leading-snug group-hover:text-primary mt-1">{{ $translation->title }}</h3>
        @if($styles['excerpt'] && $translation->excerpt)
        <p class="mt-2 text-sm text-base-content/60 line-clamp-2">{{ $translation->excerpt }}</p>
        @endif
        <p class="mt-1 text-xs font-semibold text-secondary">{{ $translation->published_at?->format('d/m/Y') }}</p>
    </div>
</a>
