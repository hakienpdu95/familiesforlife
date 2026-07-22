@props([
    'translation', // PostArticleTranslation (with article.categories loaded)
])

@php($category = $translation->article?->categories->first())

{{-- vgd-news-hightl-sma-h — 1 tin nhỏ trong col-left/col-right của x-frontend.hero. --}}
<article class="vgd-news-hightl-sma-h mar-b-20">
    <figure class="vgd-news-hightl-sma-h__bg pos-rel mar-b-10">
        <a href="{{ route('post.public.article', ['slug' => $translation->slug, 'id' => $translation->id]) }}">
            <img src="{{ $translation->article?->cover_image_url ?: asset('images/post-cover-placeholder.svg') }}"
                 alt="{{ $translation->title }}"
                 class="img-fluid img-cover" loading="lazy">

            @if($category)
            <span class="badge badge-primary badge-sm pos-ab hero-story__badge rounded-sm px-2">{{ $category->name }}</span>
            @endif
        </a>
    </figure>
    <header class="vgd-news-hightl-sma-h__tit mar-t-10 text-trun line-cl-3">
        <h3>
            <a href="{{ route('post.public.article', ['slug' => $translation->slug, 'id' => $translation->id]) }}" class="fw-semi-bold hover-color-link">{{ $translation->title }}</a>
        </h3>
    </header>
</article>
