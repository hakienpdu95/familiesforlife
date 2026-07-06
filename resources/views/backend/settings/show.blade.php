{{-- resources/views/backend/settings/show.blade.php --}}
@extends('layouts.backend')

@section('title', 'Chi tiết Cài đặt')

@section('breadcrumb')
    <div class="breadcrumbs text-sm">
        <ul>
            <li><a href="{{ route('backend.dashboard') }}">Trang chủ</a></li>
            <li><a href="{{ route('backend.settings.index') }}">Cài đặt</a></li>
            <li>Chi tiết</li>
        </ul>
    </div>
@endsection

@section('content')
    <h1 class="text-2xl font-bold">Chi tiết Cài đặt</h1>
@endsection
