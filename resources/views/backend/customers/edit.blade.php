{{-- resources/views/backend/customers/edit.blade.php --}}
@extends('layouts.backend')

@section('title', 'Chỉnh sửa Khách hàng')

@section('breadcrumb')
    <div class="breadcrumbs text-sm">
        <ul>
            <li><a href="{{ route('backend.dashboard') }}">Trang chủ</a></li>
            <li><a href="{{ route('backend.customers.index') }}">Khách hàng</a></li>
            <li>Chỉnh sửa</li>
        </ul>
    </div>
@endsection

@section('content')
    <h1 class="text-2xl font-bold">Chỉnh sửa Khách hàng</h1>
@endsection
