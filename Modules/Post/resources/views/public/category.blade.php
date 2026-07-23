@extends('layouts.frontend')

@section('title', $category->name)
@section('meta_description', $category->description ?: $category->name)

@php
    // "Tin to" (card lớn, cùng kiểu x-frontend.section-feature ở trang chủ; $lead do
    // PublicCategoryController::show() truyền vào — ưu tiên is_featured, fallback mới nhất,
    // xem leadArticleForCategory()) + "Xem thêm bài viết" (load-more, thay cho Previous/Next)
    // chỉ áp dụng ở trang 1, không tìm kiếm — cùng nguyên tắc $isMagazineLayout ở
    // public/home.blade.php. Trang 2+/tìm kiếm giữ nguyên phân trang cổ điển
    // (LoadMoreArticlesQuery dùng cursor, không hỗ trợ offset/search) và không có tin to.
    $isMagazine = ! $search && $articles->currentPage() === 1;
    $collection = $articles->getCollection();

    // $lead đã bị loại khỏi $articles ngay từ query (excludeArticleIds) — lưới ($collection)
    // luôn đủ đúng số bài của trang, không cần "bóc" phần tử đầu ra khỏi nó nữa.
    // exclude = mọi article_id đã hiển thị (tin to + lưới ban đầu), CỐ ĐỊNH, không phình theo
    // số lần bấm "Xem thêm" — cùng nguyên tắc $shownArticleIds ở public/home.blade.php.
    $shownArticleIds = $isMagazine
        ? $collection->pluck('article_id')->when($lead, fn ($ids) => $ids->push($lead->article_id))->values()
        : collect();
    $lastArticle = $isMagazine ? $collection->last() : null;
@endphp

@section('content')
<div class="container py-10">

    <div class="text-xs breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('post.public.home') }}">Trang Chủ</a></li>
            @foreach($breadcrumb as $node)
            <li><a href="{{ route('post.public.category', ['category' => $node->slug]) }}">{{ $node->name }}</a></li>
            @endforeach
        </ul>
    </div>

    <h1 class="text-2xl font-bold text-base-content mb-6">
        {{ $search ? "Kết quả tìm kiếm trong “{$category->name}”: {$search}" : $category->name }}
    </h1>

    {{-- spec/Banner_Management_Technical_Specification.md §7.2/§7.5 — có ngữ cảnh category, banner
         gắn đúng danh mục này ưu tiên hiển thị trước banner "Toàn site". --}}
    <div class="mb-6">
        <x-frontend.banner-slot placement="category_top" :context="['category_slug' => $category->slug]" />
    </div>

    @if($lead)
    <div class="mb-8">
        <x-frontend.article-card :translation="$lead" size="lg" />
    </div>
    @endif

    @if($isMagazine)
    <div x-data="loadMoreArticles({
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
            <button type="button" class="btn btn-primary" @click="loadMore()" :disabled="loading">
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
