{{-- resources/views/backend/settings/index.blade.php --}}
@extends('layouts.backend')

@section('title', 'Danh sách Cài đặt')

@section('breadcrumb')
    <div class="breadcrumbs text-sm">
        <ul>
            <li><a href="{{ route('backend.dashboard') }}">Trang chủ</a></li>
            <li><a href="{{ route('backend.settings.index') }}">Cài đặt</a></li>
            <li>Danh sách</li>
        </ul>
    </div>
@endsection

@section('content')
    <h1 class="text-2xl font-bold">Danh sách Cài đặt</h1>
@endsection
