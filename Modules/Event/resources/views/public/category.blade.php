@extends('layouts.frontend')

@section('title', $category->name.' — Sự Kiện')
@section('meta_description', $category->name.' — sự kiện và hoạt động cho gia đình.')

@push('meta')
<link rel="canonical" href="{{ route('event.public.category', ['category' => $category->slug]) }}">
@endpush

@section('content')
<div class="container py-10">

    <div class="text-xs breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('event.public.home') }}">Sự Kiện</a></li>
            @if($category->parent)
            <li><a href="{{ route('event.public.category', ['category' => $category->parent->slug]) }}">{{ $category->parent->name }}</a></li>
            @endif
            <li><a href="{{ route('event.public.category', ['category' => $category->slug]) }}">{{ $category->name }}</a></li>
        </ul>
    </div>

    <h1 class="text-2xl font-bold text-base-content mb-6">
        {{ $search ? "Kết quả tìm kiếm trong “{$category->name}”: {$search}" : $category->name }}
    </h1>

    <x-frontend.event-grid :events="$events" />

</div>
@endsection
