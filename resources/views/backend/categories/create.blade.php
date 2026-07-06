@extends('layouts.backend')
@section('title','Thêm danh mục')
@section('breadcrumb')
<nav class="breadcrumb-nav"><a href="{{ route('backend.dashboard') }}">Trang chủ</a><span class="sep">›</span><a href="{{ route('backend.categories.index') }}">Danh mục</a><span class="sep">›</span><span class="current">Thêm mới</span></nav>
@endsection
@section('content')
<h1 class="text-2xl font-bold text-base-content mb-6">Thêm danh mục</h1>
<div class="alert alert-info"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Form thêm danh mục — tương tự form thêm nhanh ở trang index nhưng đầy đủ hơn.</div>
@endsection
