@extends('layouts.backend')
@section('title','Sửa sản phẩm')
@push('styles')
@vite(['resources/js/modules/filepond.js','resources/js/modules/jodit.js','resources/js/modules/tom-select.js'])
@endpush
@section('breadcrumb')
<nav class="breadcrumb-nav">
    <a href="{{ route('backend.dashboard') }}">Trang chủ</a><span class="sep">›</span>
    <a href="{{ route('backend.products.index') }}">Sản phẩm</a><span class="sep">›</span>
    <span class="current">Chỉnh sửa</span>
</nav>
@endsection
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Chỉnh sửa sản phẩm</h1>
        <p class="text-sm text-base-content/50 mt-0.5">ID: <span class="font-mono text-primary">#{{ $product->id ?? '1' }}</span></p>
    </div>
    <div class="flex gap-2">
        <a href="#" class="btn btn-ghost btn-sm gap-1" target="_blank"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>Xem trang</a>
        <a href="{{ route('backend.products.index') }}" class="btn btn-ghost btn-sm">Quay lại</a>
    </div>
</div>
<div class="alert alert-info mb-5 text-sm">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    Đây là trang chỉnh sửa — form giống trang tạo mới, dữ liệu được điền sẵn từ DB.
</div>
<div class="text-center py-16 text-base-content/40">
    <svg class="w-16 h-16 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
    <p class="font-medium">Form edit giống hệt create, bind dữ liệu từ <code class="text-xs bg-base-200 px-1 rounded">$product</code></p>
    <p class="text-sm mt-1">Dùng <code class="text-xs bg-base-200 px-1 rounded">@include('backend.products._form', ['product' => $product])</code></p>
</div>
@endsection
