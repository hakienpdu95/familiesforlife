{{-- resources/views/backend/customers/show.blade.php --}}
@extends('layouts.backend')

@section('title', 'Chi tiết Khách hàng')

@section('breadcrumb')
    <div class="breadcrumbs text-sm">
        <ul>
            <li><a href="{{ route('backend.dashboard') }}">Trang chủ</a></li>
            <li><a href="{{ route('backend.customers.index') }}">Khách hàng</a></li>
            <li>Chi tiết</li>
        </ul>
    </div>
@endsection

@section('content')
    <h1 class="text-2xl font-bold">Chi tiết Khách hàng</h1>
@endsection
