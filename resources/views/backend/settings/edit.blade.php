{{-- resources/views/backend/settings/edit.blade.php --}}
@extends('layouts.backend')

@section('title', 'Chỉnh sửa Cài đặt')

@section('breadcrumb')
    <div class="breadcrumbs text-sm">
        <ul>
            <li><a href="{{ route('backend.dashboard') }}">Trang chủ</a></li>
            <li><a href="{{ route('backend.settings.index') }}">Cài đặt</a></li>
            <li>Chỉnh sửa</li>
        </ul>
    </div>
@endsection

@section('content')
    <h1 class="text-2xl font-bold">Chỉnh sửa Cài đặt</h1>
@endsection
