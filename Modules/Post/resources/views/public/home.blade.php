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
@endphp

@section('content')

@if($featured)
<x-frontend.hero :featured="$featured" />
@endif

<x-frontend.promo-bar :categories="$categories" />

@if($isMagazineLayout)
<div class="max-w-6xl mx-auto px-4">
    @foreach($featureChunks as $chunk)
    <x-frontend.section-feature :lead="$chunk->first()" :side="$chunk->slice(1)" />
    @endforeach
</div>

<x-frontend.sponsor-spotlight :sponsored="$sponsored" />
<x-frontend.cta-band :categories="$categories" />
@endif

<div class="max-w-6xl mx-auto px-4">
    <div class="py-10">
        <h1 class="text-2xl font-bold text-base-content mb-6">
            {{ $search ? "Kết quả tìm kiếm: “{$search}”" : ($isMagazineLayout ? 'Thêm Bài Viết' : 'Bài Viết') }}
        </h1>

        <section class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @forelse($remaining as $t)
            <x-frontend.article-card :translation="$t" size="sm" />
            @empty
            <p class="col-span-full text-center text-base-content/40 py-10">Chưa có bài viết nào.</p>
            @endforelse
        </section>

        @if($articles->hasPages())
        <div class="pt-10 flex justify-center">{{ $articles->onEachSide(1)->links() }}</div>
        @endif
    </div>
</div>
@endsection
