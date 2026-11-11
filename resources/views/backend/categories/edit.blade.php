{{-- resources/views/backend/categories/edit.blade.php --}}
@extends('layouts.backend')

@section('title', __('Chỉnh sửa Danh mục'))

@section('breadcrumb')
    <div class="breadcrumbs text-sm">
        <ul>
            <li><a href="{{ route('backend.dashboard') }}">{{ __('Trang chủ') }}</a></li>
            <li><a href="{{ route('backend.categories.index') }}">{{ __('Danh mục') }}</a></li>
            <li>{{ __('Chỉnh sửa') }}</li>
        </ul>
    </div>
@endsection

@section('content')
    <h1 class="text-2xl font-bold">{{ __('Chỉnh sửa Danh mục') }}</h1>
@endsection
