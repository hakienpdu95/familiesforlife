@extends('layouts.frontend')

@section('title', $authorProfile->displayName())
@section('meta_description', $authorProfile->bio ?: $authorProfile->displayName())

@php
    // GEO (2026-07-28) — trang tác giả trước đó không có structured data riêng (Person chỉ được
    // embed bên trong Article schema của TỪNG bài, xem ArticleStructuredDataBuilder::buildAuthor()).
    // Controller đã abort_unless(is_public && eligible) TRƯỚC KHI vào view này (AuthorHubPublicController::show()),
    // nên KHÔNG cần lặp lại điều kiện đó ở đây như buildAuthor() phải làm (nó dùng chung cho
    // author của MỌI bài viết, kể cả author chưa public).
    $authorCanonicalUrl = route('post.public.author-hub.show', $authorProfile);
    $authorSameAs       = array_values(array_filter($authorProfile->social_links ?? []));

    $authorPersonSchema = array_filter([
        '@context'    => 'https://schema.org',
        '@type'       => 'Person',
        'name'        => $authorProfile->displayName(),
        'url'         => $authorCanonicalUrl,
        'image'       => $authorProfile->avatarUrl(),
        'jobTitle'    => $authorProfile->job_title,
        'description' => $authorProfile->credentials ?: $authorProfile->bio,
        'worksFor'    => ['@type' => 'Organization', 'name' => config('app.site_name'), 'url' => route('post.public.home')],
        'sameAs'      => $authorSameAs ?: null,
    ]);
@endphp

@push('meta')
<link rel="canonical" href="{{ $authorCanonicalUrl }}">
<meta property="og:type" content="profile">
<meta property="og:title" content="{{ $authorProfile->displayName() }}">
@if($authorProfile->bio)
<meta property="og:description" content="{{ $authorProfile->bio }}">
@endif
<meta property="og:url" content="{{ $authorCanonicalUrl }}">
<meta property="og:image" content="{{ $authorProfile->avatarUrl() }}">
<script type="application/ld+json">{!! json_encode($authorPersonSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('content')
<div class="container py-10">

    <nav class="text-xs breadcrumbs mb-4" aria-label="Breadcrumb">
        <ul>
            <li><a href="{{ route('post.public.home') }}">Trang Chủ</a></li>
            <li><a href="{{ route('post.public.author-hub.index') }}">Tác giả</a></li>
            <li>{{ $authorProfile->displayName() }}</li>
        </ul>
    </nav>

    {{-- ── Header — avatar to, tên, bio, mạng xã hội (§7.3). KHÔNG hiển thị số liệu hiệu
         suất (view_count) — chỉ số lượng bài đã xuất bản (§0). ─────────────────────── --}}
    <header class="flex flex-col sm:flex-row items-center sm:items-start gap-5 mb-10 text-center sm:text-left">
        <img src="{{ $authorProfile->avatarUrl() }}" alt="{{ $authorProfile->displayName() }}"
             class="w-28 h-28 rounded-full object-cover shrink-0">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-base-content">{{ $authorProfile->displayName() }}</h1>
            @if($authorProfile->job_title)
            <p class="text-sm font-medium text-primary mt-0.5">{{ $authorProfile->job_title }}</p>
            @endif
            <p class="text-sm text-base-content/50 mt-0.5">{{ $articles->total() }} bài đã xuất bản</p>

            @if($authorProfile->credentials)
            <p class="text-xs text-base-content/60 mt-1">{{ $authorProfile->credentials }}</p>
            @endif

            @if($authorProfile->bio)
            <p class="text-base-content/70 mt-3 max-w-2xl">{{ $authorProfile->bio }}</p>
            @endif

            @if(!empty($authorProfile->social_links))
            <div class="flex items-center justify-center sm:justify-start gap-2 mt-3">
                @foreach(['facebook' => 'Facebook', 'x' => 'X', 'linkedin' => 'LinkedIn', 'website' => 'Website'] as $key => $label)
                    @if(!empty($authorProfile->social_links[$key]))
                    <a href="{{ $authorProfile->social_links[$key] }}" target="_blank" rel="noopener nofollow"
                       class="badge badge-outline hover:badge-primary">{{ $label }}</a>
                    @endif
                @endforeach
            </div>
            @endif
        </div>
    </header>

    <h2 class="text-lg font-semibold text-base-content mb-4">Bài đã xuất bản</h2>

    <x-frontend.article-grid :articles="$articles" />

</div>
@endsection
