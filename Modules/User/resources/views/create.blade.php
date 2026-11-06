@extends('layouts.backend')
@section('title', 'Thêm tài khoản')

@section('content')
<div class="max-w-3xl">
    <div class="flex items-center justify-between mt-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Thêm tài khoản</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Tạo tài khoản nội bộ và gửi email mời đặt mật khẩu.</p>
        </div>
        <a href="{{ route('backend.users.index') }}" class="btn btn-ghost btn-sm">Quay lại</a>
    </div>

    @if ($errors->any())
    <div class="alert alert-error text-sm mb-5">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('backend.users.store') }}" class="card bg-base-100 shadow-sm border border-base-200">
        @csrf

        @include('user::partials.account-fields')

        <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
            <p class="text-xs text-base-content/50">Hệ thống không đặt mật khẩu thay người dùng — họ tự đặt qua link trong email.</p>
            <div class="flex gap-2">
                <a href="{{ route('backend.users.index') }}" class="btn btn-ghost btn-sm">Huỷ</a>
                <button type="submit" class="btn btn-primary btn-sm">Tạo tài khoản</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('styles')
    @vite(['Modules/User/resources/assets/sass/user.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tom-select.js',
        'Modules/User/resources/assets/js/user.js',
    ], 'build/backend')
@endpush
