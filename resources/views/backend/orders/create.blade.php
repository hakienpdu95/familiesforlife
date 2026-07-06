{{-- resources/views/backend/orders/create.blade.php --}}
@extends('layouts.backend')

@section('title', 'Thêm mới Đơn hàng')

@section('breadcrumb')
    <div class="breadcrumbs text-sm">
        <ul>
            <li><a href="{{ route('backend.dashboard') }}">Trang chủ</a></li>
            <li><a href="{{ route('backend.orders.index') }}">Đơn hàng</a></li>
            <li>Thêm mới</li>
        </ul>
    </div>
@endsection

@section('content')
    <h1 class="text-2xl font-bold">Thêm mới Đơn hàng</h1>
@endsection
