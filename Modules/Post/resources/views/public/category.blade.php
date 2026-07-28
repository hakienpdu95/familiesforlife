@extends('layouts.frontend')

@section('title', $category->name)
@section('meta_description', $category->description ?: $category->name)

@php
    // GEO (2026-07-28) — trang danh mục trước đó KHÔNG có JSON-LD/OG/canonical nào. BreadcrumbList
    // dùng thẳng $breadcrumb (cây tổ tiên thật đã có sẵn từ PublicCategoryController, hiển thị
    // UI ngay bên dưới) — CHÍNH XÁC hơn bản giản lược 1 cấp ở article.blade.php. ItemList chỉ liệt
    // kê bài viết ĐANG hiển thị trên trang này (không phải toàn bộ category — tránh payload phình
    // to vô ích cho category nhiều nghìn bài, đúng nguyên tắc "so what's visible" của CollectionPage).
    $categoryCanonicalUrl = route('post.public.category', ['category' => $category->slug]);

    $categoryBreadcrumbItems = collect([
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang Chủ', 'item' => route('post.public.home')],
    ])->concat($breadcrumb->values()->map(fn ($node, $i) => [
        '@type'    => 'ListItem',
        'position' => $i + 2,
        'name'     => $node->name,
        'item'     => route('post.public.category', ['category' => $node->slug]),
    ]))->all();

    $categoryJsonLd = [
        [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $categoryBreadcrumbItems,
        ],
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
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $category->name }}">
<meta property="og:description" content="{{ $category->description ?: $category->name }}">
<meta property="og:url" content="{{ $categoryCanonicalUrl }}">
@foreach($categoryJsonLd as $node)
<script type="application/ld+json">{!! json_encode($node, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endforeach
@endpush

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
