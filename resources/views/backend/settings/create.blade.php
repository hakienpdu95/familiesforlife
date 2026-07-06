{{-- resources/views/backend/settings/create.blade.php --}}
@extends('layouts.backend')

@section('title', 'Thêm mới Cài đặt')

@section('breadcrumb')
    <div class="breadcrumbs text-sm">
        <ul>
            <li><a href="{{ route('backend.dashboard') }}">Trang chủ</a></li>
            <li><a href="{{ route('backend.settings.index') }}">Cài đặt</a></li>
            <li>Thêm mới</li>
        </ul>
    </div>
@endsection

@section('content')
    <h1 class="text-2xl font-bold">Thêm mới Cài đặt</h1>
@endsection
