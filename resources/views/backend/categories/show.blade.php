{{-- resources/views/backend/categories/show.blade.php --}}
@extends('layouts.backend')

@section('title', 'Chi tiết Danh mục')

@section('breadcrumb')
    <div class="breadcrumbs text-sm">
        <ul>
            <li><a href="{{ route('backend.dashboard') }}">Trang chủ</a></li>
            <li><a href="{{ route('backend.categories.index') }}">Danh mục</a></li>
            <li>Chi tiết</li>
        </ul>
    </div>
@endsection

@section('content')
    <h1 class="text-2xl font-bold">Chi tiết Danh mục</h1>
@endsection
