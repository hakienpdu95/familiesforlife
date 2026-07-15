@extends('layouts.frontend')

@section('title', $category->name)
@section('meta_description', $category->description ?: $category->name)

@section('content')
<div class="container py-10">

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

    {{-- spec/Banner_Management_Technical_Specification.md §7.2/§7.5 — có ngữ cảnh category, banner
         gắn đúng danh mục này ưu tiên hiển thị trước banner "Toàn site". --}}
    <div class="mb-6">
        <x-frontend.banner-slot placement="category_top" :context="['category_slug' => $category->slug]" />
    </div>

    <x-frontend.article-grid :articles="$articles" />

</div>
@endsection
