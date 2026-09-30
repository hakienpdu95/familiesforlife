@extends('layouts.frontend')

@section('title', $translation->seo_title ?: $translation->title)
@if($translation->seo_description || $translation->excerpt)
@section('meta_description', $translation->seo_description ?: $translation->excerpt)
@endif

@php
    $ogTitle       = $translation->seo_title ?: $translation->title;
    $ogDescription = $translation->seo_description ?: $translation->direct_answer ?: $translation->excerpt;
    $ogImage       = $article->cover_image_url;
    $ogSiteName    = config('app.site_name');
@endphp

@push('meta')
<link rel="canonical" href="{{ $canonicalUrl }}">
<link rel="alternate" type="text/markdown" href="{{ $canonicalUrl }}">

<meta property="og:type" content="article">
<meta property="og:title" content="{{ $ogTitle }}">
@if($ogDescription)
<meta property="og:description" content="{{ $ogDescription }}">
@endif
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:site_name" content="{{ $ogSiteName }}">
@if($ogImage)
<meta property="og:image" content="{{ $ogImage }}">
@endif
@if($translation->published_at)
<meta property="article:published_time" content="{{ $translation->published_at->toIso8601String() }}">
@endif
@if($translation->updated_at)
<meta property="article:modified_time" content="{{ $translation->updated_at->toIso8601String() }}">
@endif

<meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $ogTitle }}">
@if($ogDescription)
<meta name="twitter:description" content="{{ $ogDescription }}">
@endif
@if($ogImage)
<meta name="twitter:image" content="{{ $ogImage }}">
@endif

@if(!empty($structuredData))
@foreach($structuredData as $node)
<script type="application/ld+json">{!! json_encode($node, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endforeach
@endif
@endpush

@section('content')
<div class="container">

    @if($article->isCurrentlySponsored() && $translation->disclosure_text)
    <div class="alert alert-warning mb-4 flex items-center gap-2">
        @if($article->sponsor_logo_url)
        <img src="{{ $article->sponsor_logo_url }}" alt="{{ $article->sponsor_name }}" class="h-6">
        @endif
        <span class="badge {{ $article->sponsor_label->badgeClass() }}">{{ $article->sponsor_label->label() }}</span>
        <span class="text-sm">{{ $translation->disclosure_text }}</span>
    </div>
    @endif

    @if($leftCategory)
    <div class="breadcrumbs p-0">
        <div class="category">
            <div class="category-main">
                <a href="{{ route('post.public.category', ['category' => $leftCategory->slug]) }}" title="{{ $leftCategory->name }}">{{ $leftCategory->name }}</a>
            </div>
            <div class="category-sub">
                @foreach($rightCategories as $category)
                <a href="{{ route('post.public.category', ['category' => $category->slug]) }}" title="{{ $category->name }}">{{ $category->name }}</a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <article class="detail-wrap">
        <header class="detail__header">
            @php
                $authorProfile = $article->createdBy?->authorProfile;
                $authorIsLinkable = $authorProfile?->is_public
                    && \Modules\Post\Features\AuthorHub\Support\AuthorRoleResolver::isEligible($article->createdBy);
            @endphp
            <div class="detail__meta">{{ $translation->published_at?->format('d/m/Y') }}</div>
            <h1 id="btn_exp_edit" class="detail__title"> {{ $translation->title }} </h1>
            <div class="detail__tools print-hide">
                <div class="detail__author">
                    @if($authorIsLinkable)
                        <a href="{{ route('post.public.author-hub.show', $authorProfile) }}" class="hover:underline">{{ $authorProfile->displayName() }}</a>
                    @else
                        <strong>{{ $article->createdBy?->name ?? 'Ban biên tập' }}</strong>
                    @endif
                </div>
                <div class="detail__share">
                    <iframe title="Like share" src="https://www.facebook.com/plugins/like.php?href=https://treemvietnam.net.vn/thieu-sach-giao-khoa-phu-huynh-nen-lam-gi-de-con-khong-gian-doan-viec-hoc-d10076.html&amp;width=175&amp;layout=button_count&amp;action=like&amp;size=small&amp;share=true&amp;height=35" width="138" height="20" style="border: none; overflow: hidden;width: 138px;" scrolling="no" frameborder="0" allowtransparency="true" allow="encrypted-media"></iframe>
                </div>
            </div>
            @if($translation->excerpt)
            <h2 class="detail__summary" style="text-align: justify;">{{ $translation->excerpt }}</h2>
            @endif
        </header>    

        <div class="detail__content">
            <div id="content_detail" class="content_detail">
                {!! $content !!}
            </div>
        </div>

        <div class="detail__footer print-hide">
            <section class="zone"></section>
        </div>
    </article>

    @if($translation->direct_answer)
    <div class="alert bg-base-200 border-0 mb-4">
        <span class="text-sm font-medium">{{ $translation->direct_answer }}</span>
    </div>
    @endif

    @if($article->isCurrentlySponsored() && $translation->cta_text && $translation->cta_url)
    <div class="mt-4">
        <a href="{{ $translation->cta_url }}" target="_blank" rel="sponsored nofollow noopener"
           class="btn btn-warning btn-sm">{{ $translation->cta_text }}</a>
    </div>
    @endif

    @if($article->tags->isNotEmpty())
    <div class="flex flex-wrap gap-1.5 mt-4">
        @foreach($article->tags as $tag)
        <span class="badge badge-sm badge-outline">#{{ $tag->name }}</span>
        @endforeach
    </div>
    @endif

    <x-frontend.related-posts :articles="$relatedArticles" />

</div>
@endsection
