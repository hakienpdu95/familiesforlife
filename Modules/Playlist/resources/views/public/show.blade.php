{{-- spec/Playlist_Technical_Specification.md §7.2/§7.3 — card polymorphic (video mở lightbox,
     bài viết điều hướng), SEO đầy đủ (OG + JSON-LD CollectionPage/ItemList). Lightbox modal TÁI
     DÙNG nguyên cấu trúc Alpine đã kiểm chứng ở Modules/Video/resources/views/public/index.blade.php
     — không viết lại từ đầu. --}}
@extends('layouts.frontend')

@section('title', $playlist->effective_meta_title)
@if($playlist->effective_meta_description)
@section('meta_description', $playlist->effective_meta_description)
@endif

@php
    $visibleItems = $playlist->visible_itemables;
    $ogSiteName = config('app.site_name');

    // JSON-LD CollectionPage + ItemList (§7.3) — schema.org không có type chuyên biệt cho
    // "playlist trộn nhiều loại nội dung"; ItemList là type tổng quát đúng ngữ nghĩa. Giữ
    // 'description' kể cả khi null — schema.org chấp nhận, nhưng lọc null trước encode để không
    // in ra khoá gây nhiễu (§8 — "JSON-LD khi meta null").
    $jsonLd = array_filter([
        '@context'    => 'https://schema.org',
        '@type'       => 'CollectionPage',
        'name'        => $playlist->effective_meta_title,
        'description' => $playlist->effective_meta_description,
        'url'         => $canonicalUrl,
        'mainEntity'  => [
            '@type' => 'ItemList',
            'itemListElement' => $visibleItems->values()->map(fn ($item, $i) => [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'url'      => $item->getPlaylistCardUrl(),
                'name'     => $item->getPlaylistCardTitle(),
            ])->all(),
        ],
    ], fn ($value) => $value !== null);
@endphp

@push('meta')
<link rel="canonical" href="{{ $canonicalUrl }}">

<meta property="og:type" content="website">
<meta property="og:title" content="{{ $playlist->effective_meta_title }}">
@if($playlist->effective_meta_description)
<meta property="og:description" content="{{ $playlist->effective_meta_description }}">
@endif
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:site_name" content="{{ $ogSiteName }}">
@if($playlist->effective_cover_image_url)
<meta property="og:image" content="{{ $playlist->effective_cover_image_url }}">
@endif

<script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
<div class="container mx-auto py-10 px-4">
    <h1 class="text-2xl font-bold mb-1">{{ $playlist->name }}</h1>
    @if($playlist->description)
    <p class="text-base-content/70 mb-6">{{ $playlist->description }}</p>
    @else
    <div class="mb-6"></div>
    @endif

    @if($visibleItems->isEmpty())
        {{-- Playlist còn active nhưng MỌI item đã bị ẩn/xoá nguồn — tránh trang trắng gây hiểu
             nhầm là lỗi, cùng cách Video xử lý empty state ở /videos (spec §7.2). --}}
        <div class="text-center py-16 text-base-content/60">
            <p>Playlist này hiện chưa có nội dung khả dụng.</p>
        </div>
    @else
    <div x-data="{ open: false, activeUrl: null, activeTitle: '' }">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($visibleItems as $item)
                @if($item->getPlaylistCardEmbedUrl())
                {{-- Video — mở lightbox phát trực tiếp (§0). --}}
                <button type="button"
                        @click="open = true; activeUrl = '{{ $item->getPlaylistCardEmbedUrl() }}'; activeTitle = @js($item->getPlaylistCardTitle())"
                        aria-haspopup="dialog"
                        class="text-left rounded-lg overflow-hidden border border-base-300 hover:shadow-lg transition relative">
                    <span class="badge badge-sm absolute top-2 left-2 z-10">{{ $item->getPlaylistCardTypeLabel() }}</span>
                    <img src="{{ $item->getPlaylistCardThumbnailUrl() ?? asset('images/post-cover-placeholder.svg') }}"
                         alt="{{ $item->getPlaylistCardTitle() }}" loading="lazy"
                         onerror="this.onerror=null; this.src='{{ asset('images/post-cover-placeholder.svg') }}';"
                         class="w-full aspect-video object-cover">
                    <div class="p-4">
                        <h3 class="font-semibold">{{ $item->getPlaylistCardTitle() }}</h3>
                        @if($item->getPlaylistCardDescription())
                        <p class="text-sm text-base-content/70 mt-1">{{ Str::limit($item->getPlaylistCardDescription(), 120) }}</p>
                        @endif
                    </div>
                </button>
                @else
                {{-- Bài viết — điều hướng thường, không mở lightbox (§0). --}}
                <a href="{{ $item->getPlaylistCardUrl() }}"
                   class="text-left rounded-lg overflow-hidden border border-base-300 hover:shadow-lg transition relative block">
                    <span class="badge badge-sm absolute top-2 left-2 z-10">{{ $item->getPlaylistCardTypeLabel() }}</span>
                    <img src="{{ $item->getPlaylistCardThumbnailUrl() ?? asset('images/post-cover-placeholder.svg') }}"
                         alt="{{ $item->getPlaylistCardTitle() }}" loading="lazy"
                         onerror="this.onerror=null; this.src='{{ asset('images/post-cover-placeholder.svg') }}';"
                         class="w-full aspect-video object-cover">
                    <div class="p-4">
                        <h3 class="font-semibold">{{ $item->getPlaylistCardTitle() }}</h3>
                        @if($item->getPlaylistCardDescription())
                        <p class="text-sm text-base-content/70 mt-1">{{ Str::limit($item->getPlaylistCardDescription(), 120) }}</p>
                        @endif
                    </div>
                </a>
                @endif
            @endforeach
        </div>

        {{-- Modal đặt NGOÀI vòng lặp (chỉ 1 instance dùng chung) — tái dùng nguyên cấu trúc Alpine
             từ Modules/Video/resources/views/public/index.blade.php. --}}
        <div x-show="open" x-cloak
             role="dialog" aria-modal="true" :aria-label="activeTitle"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/80"
             @keydown.escape.window="open = false">
            <div class="absolute inset-0" @click="open = false"></div>
            <div class="relative w-full max-w-3xl aspect-video mx-4">
                <button type="button" @click="open = false" aria-label="Đóng video"
                        class="absolute -top-10 right-0 text-white text-2xl leading-none">&times;</button>
                <template x-if="open">
                    <iframe :src="activeUrl + '?autoplay=1'" :title="activeTitle"
                            class="w-full h-full rounded-lg"
                            allow="autoplay; encrypted-media; picture-in-picture"
                            referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen></iframe>
                </template>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
