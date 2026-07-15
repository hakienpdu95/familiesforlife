@extends('layouts.frontend')

@section('title', $search ? "Tìm kiếm: {$search}" : 'Trang Chủ')
@section('meta_description', 'Cẩm nang gia đình — hoạt động, trường học, nuôi dạy con và trải nghiệm cho cả nhà.')

@push('meta')
<link rel="canonical" href="{{ route('post.public.home') }}">
@endpush

@php
    // Trang 1 (không tìm kiếm) mới dựng bố cục "tạp chí" (hero/feature/tài trợ) — trang sau
    // và kết quả tìm kiếm hiển thị dạng lưới thuần, gọn hơn cho việc lướt nhanh nhiều trang.
    $isMagazineLayout = ! $search && $articles->currentPage() === 1;
    $collection       = $articles->getCollection();
    $featureChunks    = $isMagazineLayout ? $collection->take(6)->chunk(3) : collect();
    $remaining        = $isMagazineLayout ? $collection->slice(6)->values() : $collection;

    // "Xem thêm bài viết" (Alpine, resources/js/frontend.js loadMoreArticles) chỉ áp dụng cho
    // bố cục tạp chí. exclude = mọi article_id đã hiển thị (hero + feature chunks + lưới) —
    // CỐ ĐỊNH, chỉ tính 1 lần ở đây, không phình theo số lần bấm "Xem thêm" (xem
    // LoadMoreArticlesQuery — các lần sau tiếp tục bằng cursor published_at/id của bài cuối
    // lưới, không cần thêm gì vào exclude vì đã nằm sau cursor).
    if ($isMagazineLayout) {
        $shownArticleIds = $collection->pluck('article_id')
            ->when($featured, fn ($ids) => $ids->push($featured->article_id))
            ->unique()
            ->values();

        $lastArticle = $remaining->last();
    }
@endphp

@section('content')

@if($featured)
<x-frontend.hero :featured="$featured" />
@endif

<x-frontend.promo-bar :categories="$categories" />

@if($isMagazineLayout)
<div class="container">
    @foreach($featureChunks as $chunk)
    <x-frontend.section-feature :lead="$chunk->first()" :side="$chunk->slice(1)" />
    @endforeach
</div>

<x-frontend.event-spotlight :events="$upcomingEvents" />
<x-frontend.cta-band :categories="$categories" />
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
        <h1 class="text-2xl font-bold text-base-content mb-6">
            {{ $search ? "Kết quả tìm kiếm: “{$search}”" : ($isMagazineLayout ? 'Thêm Bài Viết' : 'Bài Viết') }}
        </h1>

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
