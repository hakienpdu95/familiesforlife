@extends('layouts.frontend')

@php
    $pageTitle = $provinceName ? "Di sản & Văn hóa {$provinceName}" : 'Di sản & Văn hóa';
    $currentPage = $sites->currentPage();
    $canonicalUrl = route('heritage.public.index', array_filter([
        'province' => $provinceCode,
        'page'     => $currentPage > 1 ? $currentPage : null,
    ]));
    $metaDescription = $provinceName
        ? "Di tích, di sản văn hóa tại {$provinceName} — loại hình, xếp hạng, cùng bài viết, lễ hội và sản phẩm OCOP liên quan."
        : 'Di tích, di sản văn hóa có cấu trúc — loại hình, xếp hạng, toạ độ — cùng bài viết, lễ hội và sản phẩm OCOP liên quan.';

    // Bố cục đồng nhất trang danh mục bài viết (danh-muc/{slug}): trang 1 lấy di tích đầu tiên
    // (nổi bật nhất theo thứ tự is_featured → sort_order) làm "tin to" ngang, phần còn lại là lưới 4 cột.
    $collection = $sites->getCollection();
    $lead = $currentPage === 1 ? $collection->first() : null;
    $gridItems = $lead ? $collection->slice(1)->values() : $collection;
    $showProvince = ! $provinceCode;
@endphp

@section('title', $pageTitle)
@section('meta_description', $metaDescription)

@push('meta')
<link rel="canonical" href="{{ $canonicalUrl }}">
@if($currentPage > 1)
<link rel="prev" href="{{ route('heritage.public.index', array_filter(['province' => $provinceCode, 'page' => $currentPage - 1 > 1 ? $currentPage - 1 : null])) }}">
@endif
@if($sites->hasMorePages())
<link rel="next" href="{{ route('heritage.public.index', array_filter(['province' => $provinceCode, 'page' => $currentPage + 1])) }}">
@endif
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $metaDescription }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<script type="application/ld+json">{!! json_encode([
    '@context'    => 'https://schema.org',
    '@type'       => 'CollectionPage',
    'name'        => $pageTitle,
    'url'         => $canonicalUrl,
    'description' => $metaDescription,
    'mainEntity'  => [
        '@type'           => 'ItemList',
        'itemListElement' => $collection->values()->map(fn ($site, $i) => [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'url'      => route('heritage.public.show', ['slug' => $site->slug, 'id' => $site->id]),
            'name'     => $site->name,
        ])->all(),
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
<div class="container">

    <h1 class="text-2xl font-bold text-base-content mb-6">
        <a href="{{ route('heritage.public.index', array_filter(['province' => $provinceCode])) }}" class="link link-hover">{{ $pageTitle }}</a>
    </h1>

    <div class="mb-6">
        <x-frontend.banner-slot placement="category_top" :context="array_filter(['province_code' => $provinceCode])" />
    </div>

    @if($lead)
    <div class="mb-8">
        @include('heritage::public.partials.site-card', ['site' => $lead, 'size' => 'lg', 'showProvince' => $showProvince])
    </div>
    @endif

    <section class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
        @forelse($gridItems as $site)
        @include('heritage::public.partials.site-card', ['site' => $site, 'size' => 'sm', 'showProvince' => $showProvince])
        @empty
        @unless($lead)
        <p class="col-span-full text-center text-base-content/40 py-10">Chưa có di tích nào.</p>
        @endunless
        @endforelse
    </section>

    @if($sites->hasPages())
    <div class="pt-10 flex justify-center">
        {{ $sites->onEachSide(1)->links() }}
    </div>
    @endif

</div>
@endsection
