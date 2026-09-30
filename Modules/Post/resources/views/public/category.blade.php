@extends('layouts.frontend')

@section('title', $category->name)
@section('meta_description', $category->description ?: $category->name)

@php
    $categoryCurrentPage = $articles->currentPage();
    $categoryCanonicalUrl = route('post.public.category', array_filter([
        'category' => $category->slug,
        'q'        => $search,
        'page'     => $categoryCurrentPage > 1 ? $categoryCurrentPage : null,
    ]));

    $categoryJsonLd = [
        [
            '@context'    => 'https://schema.org',
            '@type'       => 'CollectionPage',
            'name'        => $category->name,
            'url'         => $categoryCanonicalUrl,
            'description' => $category->description ?: $category->name,
            'mainEntity'  => [
                '@type'           => 'ItemList',
                'itemListElement' => $articles->getCollection()->values()->map(fn ($t, $i) => [
                    '@type'    => 'ListItem',
                    'position' => $i + 1,
                    'url'      => route('post.public.article', ['slug' => $t->slug, 'id' => $t->id]),
                    'name'     => $t->title,
                ])->all(),
            ],
        ],
    ];
@endphp

@push('meta')
<link rel="canonical" href="{{ $categoryCanonicalUrl }}">
@if($categoryCurrentPage > 1)
<link rel="prev" href="{{ route('post.public.category', array_filter(['category' => $category->slug, 'q' => $search, 'page' => $categoryCurrentPage - 1 > 1 ? $categoryCurrentPage - 1 : null])) }}">
@endif
@if($articles->hasMorePages())
<link rel="next" href="{{ route('post.public.category', array_filter(['category' => $category->slug, 'q' => $search, 'page' => $categoryCurrentPage + 1])) }}">
@endif
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $category->name }}">
<meta property="og:description" content="{{ $category->description ?: $category->name }}">
<meta property="og:url" content="{{ $categoryCanonicalUrl }}">
@foreach($categoryJsonLd as $node)
<script type="application/ld+json">{!! json_encode($node, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endforeach
@endpush

@php
    $isMagazine = ! $search && $articles->currentPage() === 1;
    $collection = $articles->getCollection();

    $shownArticleIds = $isMagazine
        ? $collection->pluck('article_id')->when($lead, fn ($ids) => $ids->push($lead->article_id))->values()
        : collect();
    $lastArticle = $isMagazine ? $collection->last() : null;
@endphp

@section('content')
<div class="container">

    <h1 class="text-2xl font-bold text-base-content mb-6">
        @if($search)
            Kết quả tìm kiếm trong “<a href="{{ route('post.public.category', ['category' => $category->slug]) }}" class="link link-hover">{{ $category->name }}</a>”: {{ $search }}
        @else
            <a href="{{ route('post.public.category', ['category' => $category->slug]) }}" class="link link-hover">{{ $category->name }}</a>
        @endif
    </h1>

    <div class="mb-6">
        <x-frontend.banner-slot placement="category_top" :context="['category_slug' => $category->slug]" />
    </div>

    @if($lead)
    <div class="mb-8">
        <x-frontend.article-card :translation="$lead" size="lg" />
    </div>
    @endif

    @if($isMagazine)
    <div class="mb-10" x-data="loadMoreArticles({
             endpoint: '{{ route('post.public.load-more') }}',
             exclude: '{{ $shownArticleIds->implode(',') }}',
             afterPublishedAt: {{ $lastArticle ? "'".$lastArticle->published_at->toISOString()."'" : 'null' }},
             afterId: {{ $lastArticle?->id ?? 'null' }},
             loaded: {{ $shownArticleIds->count() }},
             maxTotal: {{ config('post.load_more_max_total') }},
             hasMore: {{ ($articles->hasMorePages() && $shownArticleIds->count() < config('post.load_more_max_total')) ? 'true' : 'false' }},
             categoryId: {{ $category->id }},
             limit: 12,
         })">
        <section class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6" x-ref="grid">
            @forelse($collection as $t)
            <x-frontend.article-card :translation="$t" size="sm" />
            @empty
            <p class="col-span-full text-center text-base-content/40 py-10">Chưa có bài viết nào.</p>
            @endforelse
        </section>

        <div class="pt-10 flex justify-center" x-show="hasMore" x-cloak>
            <button type="button" class="btn btn-primary vgd-load-more-button" @click="loadMore()" :disabled="loading">
                <span x-show="!loading">Xem thêm bài viết</span>
                <span x-show="loading" x-cloak>Đang tải...</span>
            </button>
        </div>
    </div>
    @else
    <x-frontend.article-grid :articles="$articles" />
    @endif

</div>
@endsection
