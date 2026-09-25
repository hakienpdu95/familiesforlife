@props([
    'featured', // PostArticleTranslation (with article.categories loaded) — "col-middle"
    'side' => null, // Collection<PostArticleTranslation> tối đa 4 bài — 2 "col-left" + 2 "col-right"
])

@php($side = ($side ?? collect())->values())

<section class="vgd-news-hightl-h transition-opacity duration-700 ease-out mb-6"
         x-data="{ shown: false }"
         x-init="requestAnimationFrame(() => shown = true)"
         :class="shown ? 'opacity-100' : 'opacity-0'">
    <div class="container">
        <div class="row">
            <div class="col-left flex-1 mw-0">
                @foreach($side->slice(0, 2) as $t)
                <x-frontend.hero-story :translation="$t" />
                @endforeach
            </div>

            <div class="col-middle pos-rel">
                <article class="vgd-news-hightl-big-h mar-b-20">
                    <figure class="vgd-news-hightl-big-h__bg pos-rel mar-b-20">
                        <a href="{{ route('post.public.article', ['slug' => $featured->slug, 'id' => $featured->id]) }}">
                            <img src="{{ $featured->article?->cover_image_url ?: asset('images/post-cover-placeholder.svg') }}"
                                 alt="{{ $featured->title }}"
                                 class="img-fluid img-cover" loading="lazy">
                        </a>
                    </figure>
                    <header class="vgd-news-hightl-big-h__tit mar-t-10 text-trun line-cl-3">
                        <h3>
                            <a href="{{ route('post.public.article', ['slug' => $featured->slug, 'id' => $featured->id]) }}" class="fw-bold hover-color-link">{{ $featured->title }}</a>
                        </h3>
                    </header>
                </article>
            </div>

            <div class="col-right flex-1 mw-0">
                @foreach($side->slice(2, 2) as $t)
                <x-frontend.hero-story :translation="$t" />
                @endforeach
            </div>
        </div>
    </div>
</section>
