@extends('layouts.backend')
@section('title', 'Thêm tài khoản')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-base-content">Thêm tài khoản</h1>
    <a href="{{ route('backend.users.index') }}" class="btn btn-ghost btn-sm">Quay lại</a>
</div>

@if($errors->any())
<div class="alert alert-error text-sm mb-5">
    <ul class="list-disc list-inside">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('backend.users.store') }}" class="card bg-base-100 shadow-sm border border-base-200 max-w-lg">
    @csrf
    <div class="card-body gap-3">

        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Họ và tên <span class="text-error">*</span></span></label>
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="255"
                   class="input input-bordered input-sm w-full @error('name') input-error @enderror">
        </div>

        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Email <span class="text-error">*</span></span></label>
            <input type="email" name="email" value="{{ old('email') }}" required maxlength="255"
                   class="input input-bordered input-sm w-full @error('email') input-error @enderror">
        </div>

        <div class="form-control">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Vai trò <span class="text-error">*</span></span></label>
            <select id="ts-account-form-role" name="role" data-ts-placeholder="— Chọn vai trò —"
                    class="select select-bordered select-sm w-full @error('role') select-error @enderror">
                <option value="">— Chọn vai trò —</option>
                @foreach ($labels as $value => $label)
                    <option value="{{ $value }}" @selected(old('role') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="alert alert-info alert-soft text-xs mt-1">
            Hệ thống sẽ gửi email chứa link để người dùng tự đặt mật khẩu.
        </div>

        <button type="submit" class="btn btn-primary btn-sm mt-2">Tạo tài khoản</button>
    </div>
</form>
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
