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

@section('content')
<div class="max-w-3xl mx-auto px-4 py-10">

    @if($page->getFirstMediaUrl('cover'))
    <img src="{{ $page->getFirstMediaUrl('cover') }}" alt="{{ $page->title }}"
         class="w-full rounded-lg mb-6 object-cover max-h-96">
    @endif

    <h1 class="text-3xl font-bold text-base-content mb-6">{{ $page->title }}</h1>

    <div class="prose max-w-none">
        {!! $page->content !!}
    </div>

</div>
@endsection
