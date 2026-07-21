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
    spec/Page_Static_Pages_Technical_Specification.md §3.2.1/§10 — template "about" do developer
    dựng riêng, KHÔNG bị ép render qua khối content mặc định như page::public.show. Bố cục:
    hero full-width (ảnh cover nếu có) + phần nội dung tự do (content) bên dưới — khác hẳn layout
    của template default. Chỉnh sửa file này trực tiếp để đưa vào thiết kế thật của thương hiệu.
--}}
@section('content')

<section class="relative bg-neutral text-neutral-content">
    @if($page->getFirstMediaUrl('cover'))
    <div class="absolute inset-0">
        <img src="{{ $page->getFirstMediaUrl('cover') }}" alt="" class="w-full h-full object-cover opacity-30">
    </div>
    @endif
    <div class="relative max-w-5xl mx-auto px-4 py-20 text-center">
        <h1 class="text-4xl font-bold mb-4">{{ $page->title }}</h1>
        @if($page->excerpt)
        <p class="text-lg opacity-80 max-w-2xl mx-auto">{{ $page->excerpt }}</p>
        @endif
    </div>
</section>

<div class="max-w-3xl mx-auto px-4 py-12">
    <div class="prose max-w-none">
        {!! $page->content !!}
    </div>
</div>

@endsection
