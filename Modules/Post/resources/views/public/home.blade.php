@extends('layouts.frontend')

@section('title', $search ? "Tìm kiếm: {$search}" : 'Trang Chủ')
@section('meta_description', 'Cẩm nang gia đình — hoạt động, trường học, nuôi dạy con và trải nghiệm cho cả nhà.')

@php
    $homeCurrentPage = $articles->currentPage();
    $homeCanonicalQuery = array_filter(['q' => $search, 'page' => $homeCurrentPage > 1 ? $homeCurrentPage : null]);
    $homeCanonicalUrl = route('post.public.home', $homeCanonicalQuery);
@endphp

@push('meta')
<link rel="canonical" href="{{ $homeCanonicalUrl }}">
@if($homeCurrentPage > 1)
<link rel="prev" href="{{ route('post.public.home', array_filter(['q' => $search, 'page' => $homeCurrentPage - 1 > 1 ? $homeCurrentPage - 1 : null])) }}">
@endif
@if($articles->hasMorePages())
<link rel="next" href="{{ route('post.public.home', array_filter(['q' => $search, 'page' => $homeCurrentPage + 1])) }}">
@endif

@endpush

@php
    $isMagazineLayout = ! $search && $articles->currentPage() === 1;
    $collection       = $articles->getCollection();
    $featureChunks    = $isMagazineLayout ? $collection->take(6)->chunk(3) : collect();
    $remaining        = $isMagazineLayout ? $collection->slice(6)->values() : $collection;

    if ($isMagazineLayout) {
        $shownArticleIds = $collection->pluck('article_id')
            ->when($featured, fn ($ids) => $ids->push($featured->article_id))
            ->merge($heroSide->pluck('article_id'))
            ->unique()
            ->values();

        $lastArticle = $remaining->last();
    }
@endphp

@section('content')

<h1 class="sr-only">{{ $search ? "Kết quả tìm kiếm: {$search}" : 'Trang Chủ — ' . config('app.site_name') }}</h1>

<x-frontend.breaking-news-ticker :items="$breakingNews" />

@if($featured)
<x-frontend.hero :featured="$featured" :side="$heroSide" />
@endif

<x-frontend.promo-bar :categories="$categories" :provinces="$featuredProvinces" />

@if($isMagazineLayout)
<div class="container">
    @foreach($featureChunks as $chunk)
    <x-frontend.section-feature :lead="$chunk->first()" :side="$chunk->slice(1)" />
    @endforeach
</div>

<x-frontend.event-spotlight :events="$upcomingEvents" />

<div class="container">
    @foreach($featureChunks as $chunk)
    <x-frontend.section-feature :lead="$chunk->first()" :side="$chunk->slice(1)" />
    @endforeach
</div>

<x-frontend.newsletter-signup />
@endif

<div class="container">
    <div class="py-10"
         @if($isMagazineLayout)
         x-data="loadMoreArticles({
             endpoint: '{{ route('post.public.load-more') }}',
             exclude: '{{ $shownArticleIds->implode(',') }}',
             afterPublishedAt: {{ $lastArticle ? "'".$lastArticle->published_at->toISOString()."'" : 'null' }},
             afterId: {{ $lastArticle?->id ?? 'null' }},
             loaded: {{ $shownArticleIds->count() }},
             maxTotal: {{ config('post.load_more_max_total') }},
             hasMore: {{ ($articles->hasMorePages() && $shownArticleIds->count() < config('post.load_more_max_total')) ? 'true' : 'false' }},
         })"
         @endif
    >

        <section class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6" @if($isMagazineLayout) x-ref="grid" @endif>
            @forelse($remaining as $t)
            <x-frontend.article-card :translation="$t" size="sm" />
            @empty
            <p class="col-span-full text-center text-base-content/40 py-10">Chưa có bài viết nào.</p>
            @endforelse
        </section>

        @if($isMagazineLayout)
        <div class="pt-10 flex justify-center" x-show="hasMore" x-cloak>
            <button type="button" class="btn btn-primary" @click="loadMore()" :disabled="loading">
                <span x-show="!loading" x-cloak>Xem thêm bài viết</span>
                <span x-show="loading" x-cloak>Đang tải...</span>
            </button>
        </div>
        @elseif($articles->hasPages())
        <div class="pt-10 flex justify-center">{{ $articles->onEachSide(1)->links() }}</div>
        @endif
    </div>
</div>
@endsection
