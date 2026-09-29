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

    <nav class="text-xs breadcrumbs" aria-label="Breadcrumb">
        <ul>
            <li><a href="{{ route('post.public.home') }}">Trang Chủ</a></li>
            @if($article->categories->isNotEmpty())
            <li><a href="{{ route('post.public.category', ['category' => $article->categories->first()->slug]) }}">{{ $article->categories->first()->name }}</a></li>
            @endif
        </ul>
    </nav>

    @if($article->isCurrentlySponsored() && $translation->disclosure_text)
    <div class="alert alert-warning mb-4 flex items-center gap-2">
        @if($article->sponsor_logo_url)
        <img src="{{ $article->sponsor_logo_url }}" alt="{{ $article->sponsor_name }}" class="h-6">
        @endif
        <span class="badge {{ $article->sponsor_label->badgeClass() }}">{{ $article->sponsor_label->label() }}</span>
        <span class="text-sm">{{ $translation->disclosure_text }}</span>
    </div>
    @endif

    <article class="detail-wrap">
        <header class="detail__header">
            <div class="detail__meta">06:29 29/09/2026</div>
            <h1 id="btn_exp_edit" class="detail__title" data-id="10076" style="font-family: 'VNE1', sans-serif; font-weight: 700;"> Thiếu sách giáo khoa, phụ huynh nên làm gì để con không gián đoạn việc học? </h1>
            <div class="detail__tools print-hide">
                <div class="detail__author">
                    <img src="/templates/themes/images/favicon.jpg" style="width: 30px; height: 30px; border-radius: 50%;position: relative;top: -2px;" alt="Icon No Avatar Tre Em Viet Nam">
                    <strong>Hương Giang</strong>
                </div>
                <div class="detail__share">
                    <iframe title="Like share" src="https://www.facebook.com/plugins/like.php?href=https://treemvietnam.net.vn/thieu-sach-giao-khoa-phu-huynh-nen-lam-gi-de-con-khong-gian-doan-viec-hoc-d10076.html&amp;width=175&amp;layout=button_count&amp;action=like&amp;size=small&amp;share=true&amp;height=35" width="138" height="20" style="border: none; overflow: hidden;width: 138px;" scrolling="no" frameborder="0" allowtransparency="true" allow="encrypted-media"></iframe>
                </div>
            </div>
            <h2 class="detail__summary" style="text-align: justify;">Khi sách giáo khoa chưa kịp đến tay học sinh, việc tìm một bản sách để con học tạm là nhu cầu hoàn toàn dễ hiểu của nhiều gia đình. Tuy nhiên, nếu lựa chọn tải file trên mạng rồi in, photocopy, phụ huynh cũng cần biết một số quy định về quyền tác giả để tìm được cách vừa giúp con có tài liệu học tập, vừa phù hợp với pháp luật.</h2>
        </header>    

        <div class="detail__content">
            <div id="content_detail" class="content_detail"></div>
        </div>

        <div class="detail__footer print-hide">
            <section class="zone"></section>
        </div>
    </article>

    <header class="text-center mb-4">
        <span class="text-xs font-black uppercase tracking-wide text-primary">
            {{ $article->categories->first()?->name }}
        </span>
        <h1 class="text-3xl font-bold text-base-content mt-1 mb-2">{{ $translation->title }}</h1>
        @php
            $authorProfile = $article->createdBy?->authorProfile;
            $authorIsLinkable = $authorProfile?->is_public
                && \Modules\Post\Features\AuthorHub\Support\AuthorRoleResolver::isEligible($article->createdBy);
        @endphp
        <p class="text-sm text-secondary font-semibold">
            Bởi
            @if($authorIsLinkable)
                <a href="{{ route('post.public.author-hub.show', $authorProfile) }}" class="hover:underline">{{ $authorProfile->displayName() }}</a>
            @else
                {{ $article->createdBy?->name ?? 'Ban biên tập' }}
            @endif
            · {{ $translation->published_at?->format('d/m/Y') }}
            @if($translation->updated_at && $translation->published_at
                && $translation->updated_at->format('Y-m-d') !== $translation->published_at->format('Y-m-d'))
            · Cập nhật: {{ $translation->updated_at->format('d/m/Y') }}
            @endif
        </p>
    </header>

    @if($article->categories->isNotEmpty())
    <div class="flex flex-wrap gap-1.5 mb-4">
        @foreach($article->categories as $cat)
        <a href="{{ route('post.public.category', ['category' => $cat->slug]) }}" class="badge badge-sm badge-ghost hover:badge-primary">{{ $cat->name }}</a>
        @endforeach
    </div>
    @endif

    @if($translation->direct_answer)
    <div class="alert bg-base-200 border-0 mb-4">
        <span class="text-sm font-medium">{{ $translation->direct_answer }}</span>
    </div>
    @endif

    @if($translation->excerpt)
    <p class="text-base-content/70 italic mb-4">{{ $translation->excerpt }}</p>
    @endif

    <div class="card bg-base-100 shadow-sm border border-base-300">
        <div class="card-body prose max-w-none">
            {!! $content !!}
        </div>
    </div>

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
