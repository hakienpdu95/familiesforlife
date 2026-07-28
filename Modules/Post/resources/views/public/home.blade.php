@extends('layouts.frontend')

@section('title', $search ? "Tìm kiếm: {$search}" : 'Trang Chủ')
@section('meta_description', 'Cẩm nang gia đình — hoạt động, trường học, nuôi dạy con và trải nghiệm cho cả nhà.')

@php
    // Technical SEO (2026-07-28) — canonical trước đó CỐ ĐỊNH route('post.public.home') bất kể
    // page/q — khiến trang 2+ (tìm kiếm, hoặc trang chủ không magazine layout) tự khai "trang
    // chính là trang 1", trong khi nội dung 2 trang khác nhau thật (agent kiểm tra
    // cometweb.io/blog/technical-seo phát hiện). Giữ nguyên $homeUrl (route trần) cho
    // Organization/WebSite JSON-LD bên dưới — 2 khái niệm khác nhau, entity URL không đổi theo
    // trang, chỉ <link rel=canonical>/next/prev mới cần phản ánh đúng trang hiện tại.
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

{{--
    GEO (2026-07-28) — Organization + WebSite JSON-LD trước đây nhúng RIÊNG ở đây (chỉ trang
    chủ). Đã gộp thành 1 node DUY NHẤT, xuất hiện trên MỌI trang công khai, ở
    layouts/frontend.blade.php (bao gồm cả description + SearchAction) — tránh 2 node
    Organization/WebSite trùng lặp không liên kết @id trên cùng 1 trang.
--}}
@endpush

@php
    // Trang 1 (không tìm kiếm) mới dựng bố cục "tạp chí" (hero/feature/tài trợ) — trang sau
    // và kết quả tìm kiếm hiển thị dạng lưới thuần, gọn hơn cho việc lướt nhanh nhiều trang.
    $isMagazineLayout = ! $search && $articles->currentPage() === 1;
    $collection       = $articles->getCollection();
    $featureChunks    = $isMagazineLayout ? $collection->take(6)->chunk(3) : collect();
    $remaining        = $isMagazineLayout ? $collection->slice(6)->values() : $collection;

    // "Xem thêm bài viết" (Alpine, resources/js/frontend.js loadMoreArticles) chỉ áp dụng cho
    // bố cục tạp chí. exclude = mọi article_id đã hiển thị (hero 5 tin + feature chunks + lưới)
    // — CỐ ĐỊNH, chỉ tính 1 lần ở đây, không phình theo số lần bấm "Xem thêm" (xem
    // LoadMoreArticlesQuery — các lần sau tiếp tục bằng cursor published_at/id của bài cuối
    // lưới, không cần thêm gì vào exclude vì đã nằm sau cursor).
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

{{--
    Technical GEO/SEO (2026-07-28) — thứ bậc heading trước đây bị ĐẢO NGƯỢC: các component
    hero/section-feature/event-spotlight/cta-band phía trên đều tự render H3/H2 riêng, khiến
    chúng xuất hiện TRƯỚC thẻ <h1> DUY NHẤT của trang (vốn nằm tận dưới ở khối "Bài Viết"/"Thêm
    Bài Viết") — sai hoàn toàn ngữ nghĩa phân cấp mà AI crawler/trình đọc màn hình dựa vào để hiểu
    outline trang. Thêm <h1> ẩn thị giác (sr-only — vẫn có trong HTML thô, AI crawler và trình đọc
    màn hình đọc được bình thường) ngay đầu trang, hạ <h1> cũ phía dưới xuống <h2> (nó vốn là tiêu
    đề 1 SECTION "Bài Viết", không phải tiêu đề CHÍNH của trang) — không đổi bố cục hiển thị.
--}}
<h1 class="sr-only">{{ $search ? "Kết quả tìm kiếm: {$search}" : 'Trang Chủ — ' . config('app.site_name') }}</h1>

<x-frontend.breaking-news-ticker :items="$breakingNews" />

{{--
    Technical GEO/SEO (2026-07-28, đợt 2) — lần sửa đầu chỉ xử lý H1, nhưng x-frontend.hero VÀ
    x-frontend.section-feature (bên dưới, trong $featureChunks) đều tự render <h3> cho tiêu đề bài
    (qua x-frontend.article-card/hero-story) mà KHÔNG có <h2> nào đứng trước — H3 đầu tiên của
    trang vẫn đứng ngay sau H1, nhảy cấp y hệt lỗi cũ. event-spotlight/cta-band bên dưới đã có H2
    riêng nên không sao. sr-only H2 này che cho TOÀN BỘ khối hero+feature-chunks (bất kể
    $isMagazineLayout hay không — hero render theo $featured, có thể xuất hiện cả ở trang 2+ khi
    không tìm kiếm), không đổi bố cục hiển thị.
--}}
<h2 class="sr-only">Tin nổi bật</h2>

@if($featured)
<x-frontend.hero :featured="$featured" :side="$heroSide" />
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
        <h2 class="text-2xl font-bold text-base-content mb-6">
            {{ $search ? "Kết quả tìm kiếm: “{$search}”" : ($isMagazineLayout ? 'Thêm Bài Viết' : 'Bài Viết') }}
        </h2>

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
