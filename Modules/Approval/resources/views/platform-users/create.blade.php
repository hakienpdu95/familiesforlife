@extends('layouts.backend')
@section('title', 'Thêm nhân sự Platform')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-base-content">Thêm nhân sự Platform</h1>
    <a href="{{ route('backend.platform-users.index') }}" class="btn btn-ghost btn-sm">Quay lại</a>
</div>

@if($errors->any())
<div class="alert alert-error text-sm mb-5">
    <ul class="list-disc list-inside">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('backend.platform-users.store') }}" class="card bg-base-100 shadow-sm border border-base-200 max-w-lg">
    @csrf
    <div class="card-body gap-3">

        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Họ và tên <span class="text-error">*</span></span></label>
            <input type="text" name="name" value="{{ old('name') }}" class="input input-bordered input-sm w-full @error('name') input-error @enderror">
        </div>

        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Email <span class="text-error">*</span></span></label>
            <input type="email" name="email" value="{{ old('email') }}" class="input input-bordered input-sm w-full @error('email') input-error @enderror">
        </div>

        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Mật khẩu <span class="text-error">*</span></span></label>
            <input type="password" name="password" class="input input-bordered input-sm w-full @error('password') input-error @enderror">
        </div>

        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Xác nhận mật khẩu <span class="text-error">*</span></span></label>
            <input type="password" name="password_confirmation" class="input input-bordered input-sm w-full">
        </div>

        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Vai trò Platform <span class="text-error">*</span></span></label>
            <select name="role" class="select select-bordered select-sm w-full @error('role') select-error @enderror">
                <option value="">— Chọn vai trò —</option>
                @foreach ($labels as $value => $label)
                    <option value="{{ $value }}" {{ old('role') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-base-content/40">Không có "Super Admin" trong danh sách — tài khoản đó chỉ tạo được qua seeder thủ công (có review), xem spec/Platform_RBAC_Technical_Specification.md §3.8.</p>
        </div>

        <button type="submit" class="btn btn-primary btn-sm mt-2">Tạo tài khoản</button>
    </div>
</form>
@endsection
