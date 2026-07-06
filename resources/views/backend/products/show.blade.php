@extends('layouts.backend')
@section('title','Chi tiết sản phẩm')
@section('breadcrumb')
<nav class="breadcrumb-nav">
    <a href="{{ route('backend.dashboard') }}">Trang chủ</a><span class="sep">›</span>
    <a href="{{ route('backend.products.index') }}">Sản phẩm</a><span class="sep">›</span>
    <span class="current">Chi tiết</span>
</nav>
@endsection
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <h1 class="text-2xl font-bold text-base-content">Chi tiết sản phẩm</h1>
    <div class="flex gap-2">
        <a href="#" class="btn btn-primary btn-sm">Chỉnh sửa</a>
        <a href="{{ route('backend.products.index') }}" class="btn btn-ghost btn-sm">Quay lại</a>
    </div>
</div>
<div class="text-center py-16 text-base-content/40">
    <svg class="w-16 h-16 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
    <p class="font-medium">Trang hiển thị chi tiết sản phẩm từ <code class="text-xs bg-base-200 px-1 rounded">$product</code></p>
</div>
@endsection
