@extends('layouts.frontend')

@section('title', $category->name)
@section('meta_description', $category->description ?: $category->name)

@section('content')
<div class="max-w-6xl mx-auto px-4 py-10">

    <div class="text-xs breadcrumbs mb-4">
        <ul>
            <li><a href="{{ route('post.public.home') }}">Trang Chủ</a></li>
            @foreach($breadcrumb as $node)
            <li><a href="{{ route('post.public.category', ['category' => $node->slug]) }}">{{ $node->name }}</a></li>
            @endforeach
        </ul>
    </div>

    <h1 class="text-2xl font-bold text-base-content mb-6">
        {{ $search ? "Kết quả tìm kiếm trong “{$category->name}”: {$search}" : $category->name }}
    </h1>

    <x-frontend.article-grid :articles="$articles" />

</div>
@endsection
