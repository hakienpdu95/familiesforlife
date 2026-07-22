@props([
    'translation', // PostArticleTranslation (with article.categories, article.createdBy loaded)
    'size' => 'md', // lg | md | sm
])

@php
    $article = $translation->article;
    $category = $article?->categories->first();
    $styles = match ($size) {
        'sm' => ['ratio' => 'aspect-[4/3]', 'title' => 'font-bold text-sm', 'gap' => 'gap-3', 'excerpt' => false],
        default => ['ratio' => 'aspect-[16/9]', 'title' => 'font-bold', 'gap' => 'gap-3', 'excerpt' => false],
    };
@endphp

@if($size === 'lg')
{{-- spec/tinto.png — tin to: ảnh bìa bên trái (~60%) ghép ngang panel nền trắng bên phải
     (category/tiêu đề/tác giả), không bo góc, khác hẳn thẻ ảnh-trên-chữ-dưới của md/sm. --}}
<a href="{{ route('post.public.article', ['slug' => $translation->slug, 'id' => $translation->id]) }}"
   class="group grid grid-cols-1 sm:grid-cols-5 sm:items-stretch overflow-hidden bg-base-100 border border-base-300">
    <div class="sm:col-span-3 aspect-[16/10] sm:aspect-auto bg-base-200">
        <img src="{{ $article?->cover_image_url ?: asset('images/post-cover-placeholder.svg') }}"
             alt="{{ $translation->title }}" class="h-full w-full object-cover" loading="lazy">
    </div>
    <div class="sm:col-span-2 flex flex-col justify-center p-6 sm:p-10">
        @if($category)
        <span class="text-xs font-black uppercase tracking-[0.2em] text-primary">{{ $category->name }}</span>
        @endif
        <h3 class="mt-3 text-2xl sm:text-3xl font-extrabold leading-snug group-hover:text-primary">{{ $translation->title }}</h3>
        <p class="mt-4 text-sm font-bold text-secondary">Bởi {{ $article?->createdBy?->name ?? 'Ban biên tập' }}</p>
    </div>
</a>
@else
<a href="{{ route('post.public.article', ['slug' => $translation->slug, 'id' => $translation->id]) }}"
   class="group flex flex-col {{ $styles['gap'] }}">
    <div class="{{ $styles['ratio'] }} overflow-hidden bg-base-200">
        <img src="{{ $article?->cover_image_url ?: asset('images/post-cover-placeholder.svg') }}"
             alt="{{ $translation->title }}" class="h-full w-full object-cover" loading="lazy">
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
@endif
