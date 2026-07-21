@extends('layouts.frontend')

@section('title', $page->metaTitle())
@section('meta_description', $page->metaDescription())

@push('meta')
    @if($page->seo_noindex)
    <meta name="robots" content="noindex">
    @endif
    @if($page->getFirstMediaUrl('cover'))
    <meta property="og:image" content="{{ $page->getFirstMediaUrl('cover') }}">
    @endif
    <meta property="og:title" content="{{ $page->metaTitle() }}">
    @if($page->metaDescription())
    <meta property="og:description" content="{{ $page->metaDescription() }}">
    @endif
@endpush

{{--
    spec/Page_Static_Pages_Technical_Specification.md §0/§3.2.1 — template "contact" là thiết
    kế riêng, KHÔNG có form thu thập Lead (ngoài phạm vi, xem §10) — content được Admin biên
    tập tự do (địa chỉ, SĐT, email, nhúng bản đồ qua iframe HTML...) trong khối bên dưới.
--}}
@section('content')

<div class="max-w-4xl mx-auto px-4 py-12">
    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold text-base-content mb-3">{{ $page->title }}</h1>
        @if($page->excerpt)
        <p class="text-base-content/60 max-w-xl mx-auto">{{ $page->excerpt }}</p>
        @endif
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body prose max-w-none">
            {!! $page->content !!}
        </div>
    </div>
</div>

@endsection
