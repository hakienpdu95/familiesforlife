@extends('layouts.backend')
@section('title', 'Sửa tài khoản')

@section('content')
@php
    $currentRole = $account->roles->first()?->name;
    $initial = mb_strtoupper(mb_substr(\Illuminate\Support\Str::afterLast(trim($account->name), ' '), 0, 1));
@endphp

<div class="max-full mx-auto">
    <div class="flex flex-wrap items-center justify-between gap-4 mt-4 mb-6">
        <div class="flex items-center gap-4 min-w-0">
            <div class="avatar avatar-placeholder">
                <div class="bg-primary text-primary-content w-14 rounded-full">
                    <span class="text-xl font-semibold">{{ $initial }}</span>
                </div>
            </div>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-bold text-base-content truncate">{{ $account->name }}</h1>
                    @if ($account->is_active)
                        <span class="badge badge-success badge-sm">Hoạt động</span>
                    @else
                        <span class="badge badge-ghost badge-sm">Đã vô hiệu hoá</span>
                    @endif
                </div>
                <p class="text-sm text-base-content/50 mt-0.5 truncate">{{ $account->email }}</p>
            </div>
        </div>
        <a href="{{ route('backend.users.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Quay lại
        </a>
    </div>

    @if (session('success'))
    <div class="alert alert-success text-sm mb-5">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
    <div class="alert alert-error text-sm mb-5">
        <ul class="list-disc list-inside">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-start">
        <form method="POST" action="{{ route('backend.users.update', $account) }}"
              class="lg:col-span-2 card bg-base-100 shadow-sm border border-base-200">
            @csrf
            @method('PUT')

            <section class="p-6 border-b border-base-200">
                <h2 class="font-semibold text-base-content">Thông tin cơ bản</h2>
                <p class="text-xs text-base-content/50 mt-0.5 mb-4">Tên hiển thị trong hệ thống.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Họ và tên <span class="text-error">*</span></span></label>
                        <input type="text" name="name" value="{{ old('name', $account->name) }}"
                               class="input input-bordered input-sm w-full @error('name') input-error @enderror">
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Email</span></label>
                        <input type="email" value="{{ $account->email }}" class="input input-bordered input-sm w-full" disabled>
                        <p class="mt-1 text-xs text-base-content/40">Không đổi được email qua đây.</p>
                    </div>
                </div>
            </section>

            <section class="p-6 border-b border-base-200">
                <h2 class="font-semibold text-base-content">Phân quyền hệ thống</h2>
                <p class="text-xs text-base-content/50 mt-0.5 mb-4">Vai trò quyết định tài khoản được làm gì trong hệ thống.</p>

                <div class="form-control md:max-w-sm">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Vai trò <span class="text-error">*</span></span></label>
                    <select id="ts-account-form-role" name="role" data-ts-placeholder="— Chọn vai trò —" class="select select-bordered select-sm w-full @error('role') select-error @enderror">
                        @foreach ($labels as $value => $label)
                            <option value="{{ $value }}" {{ old('role', $currentRole) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </section>

            <div class="flex justify-end gap-2 px-6 py-4">
                <a href="{{ route('backend.users.index') }}" class="btn btn-ghost btn-sm">Huỷ</a>
                <button type="submit" class="btn btn-primary btn-sm">Lưu thay đổi</button>
            </div>
        </form>

        <aside class="space-y-5">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-5">
                    <h2 class="font-semibold text-base-content mb-2">Tóm tắt tài khoản</h2>
                    <dl class="divide-y divide-base-200 text-sm">
                        <div class="flex justify-between gap-3 py-2">
                            <dt class="text-base-content/50">Vai trò</dt>
                            <dd><span class="badge badge-soft badge-sm {{ \App\Enums\RoleEnum::tryFrom((string) $currentRole)?->badgeClass() ?? 'badge-ghost' }}">{{ \App\Enums\RoleEnum::tryFrom((string) $currentRole)?->label() ?? $currentRole ?? '—' }}</span></dd>
                        </div>
                        <div class="flex justify-between gap-3 py-2">
                            <dt class="text-base-content/50">Trạng thái</dt>
                            <dd>{{ $account->is_active ? 'Hoạt động' : 'Đã vô hiệu hoá' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 py-2">
                            <dt class="text-base-content/50">Ngày tạo</dt>
                            <dd>{{ $account->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 py-2">
                            <dt class="text-base-content/50">Hoạt động cuối</dt>
                            <dd title="{{ $account->last_active_at?->format('d/m/Y H:i') }}">{{ $account->last_active_at?->diffForHumans() ?? 'Chưa đăng nhập' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 py-2">
                            <dt class="text-base-content/50">Cập nhật</dt>
                            <dd>{{ $account->updated_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            @canany(['sendPasswordResetLink', 'deactivate', 'activate'], $account)
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-5 gap-2">
                    <h2 class="font-semibold text-base-content">Thao tác tài khoản</h2>

                    @can('sendPasswordResetLink', $account)
                    <form method="POST" action="{{ route('backend.users.password-reset-link', $account) }}"
                          onsubmit="return confirm('Gửi email chứa link đặt lại mật khẩu cho tài khoản này?');">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-warning btn-sm w-full">Gửi link đặt lại mật khẩu</button>
                    </form>
                    @endcan

                    @can('deactivate', $account)
                    <form method="POST" action="{{ route('backend.users.destroy', $account) }}"
                          onsubmit="return confirm('Vô hiệu hoá tài khoản này?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline btn-error btn-sm w-full">Vô hiệu hoá</button>
                    </form>
                    @endcan

                    @can('activate', $account)
                    <form method="POST" action="{{ route('backend.users.activate', $account) }}"
                          onsubmit="return confirm('Kích hoạt lại tài khoản này?');">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-outline btn-success btn-sm w-full">Kích hoạt lại</button>
                    </form>
                    @endcan
                </div>
            </div>
            @endcanany
        </aside>
    </div>
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
