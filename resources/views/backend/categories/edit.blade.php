{{-- resources/views/backend/categories/edit.blade.php --}}
@extends('layouts.backend')

@section('title', 'Chỉnh sửa Danh mục')

@section('breadcrumb')
    <div class="breadcrumbs text-sm">
        <ul>
            <li><a href="{{ route('backend.dashboard') }}">Trang chủ</a></li>
            <li><a href="{{ route('backend.categories.index') }}">Danh mục</a></li>
            <li>Chỉnh sửa</li>
        </ul>
    </div>
@endsection

@section('content')
    <h1 class="text-2xl font-bold">Chỉnh sửa Danh mục</h1>
@endsection
