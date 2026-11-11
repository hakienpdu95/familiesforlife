{{-- resources/views/backend/orders/edit.blade.php --}}
@extends('layouts.backend')

@section('title', __('Chỉnh sửa Đơn hàng'))

@section('breadcrumb')
    <div class="breadcrumbs text-sm">
        <ul>
            <li><a href="{{ route('backend.dashboard') }}">{{ __('Trang chủ') }}</a></li>
            <li><a href="{{ route('backend.orders.index') }}">{{ __('Đơn hàng') }}</a></li>
            <li>{{ __('Chỉnh sửa') }}</li>
        </ul>
    </div>
@endsection

@section('content')
    <h1 class="text-2xl font-bold">{{ __('Chỉnh sửa Đơn hàng') }}</h1>
@endsection
