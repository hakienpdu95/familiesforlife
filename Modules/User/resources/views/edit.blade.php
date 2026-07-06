@extends('layouts.backend')
@section('title', 'Sửa tài khoản — ' . $user->name)


@php
$avatarUrl = 'https://api.dicebear.com/9.x/initials/svg?seed=' . urlencode($user->name)
           . '&backgroundColor=6366f1&fontFamily=Arial&fontSize=40&fontWeight=700';
@endphp

@section('content')
<div x-data="editUserPage({{ Js::from([
    'organizations' => $organizations->values(),
    'roles'         => $roles,
    'matrix'        => $matrix,
    'oldOrg'        => old('organization_id', $user->organization_id),
    'oldRole'       => old('system_role', $currentRole),
    'hasErrors'     => $errors->any(),
]) }})">

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">{{ $user->name }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $user->email }}</p>
    </div>
    <a href="{{ route('backend.users.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

{{-- Error banner --}}
@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('backend.users.update', $user) }}"
      novalidate id="edit-user-form">
    @csrf @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-5">

        {{-- ── Left: Basic info + Password ───────────────────────────────── --}}
        <div class="xl:col-span-2 space-y-5">

            {{-- Identity card --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <h2 class="card-title text-base mb-5">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Thông tin tài khoản
                    </h2>

                    {{-- Current user preview --}}
                    <div class="flex items-center gap-3 p-3 bg-base-200/50 rounded-xl mb-5">
                        <div class="w-12 h-12 rounded-full ring-2 ring-primary ring-offset-1 ring-offset-base-100 overflow-hidden shrink-0">
                            <img src="{{ $avatarUrl }}" class="w-full h-full object-cover" alt="{{ $user->name }}">
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold truncate text-base-content">{{ $user->name }}</p>
                            <p class="text-xs truncate mt-0.5 text-base-content/60">{{ $user->email }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-1 shrink-0">
                            <span x-show="selectedRoleLabel" x-text="selectedRoleLabel"
                                  class="badge badge-primary badge-sm"></span>
                            @if($user->is_active)
                            <span class="badge badge-success badge-xs">Hoạt động</span>
                            @else
                            <span class="badge badge-ghost badge-xs">Vô hiệu</span>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-4">

                        {{-- Name --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Họ và tên <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}"
                                   class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                                   placeholder="VD: Nguyễn Văn A">
                            @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Email --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Email <span class="text-error">*</span></span>
                            </label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                                   class="input input-bordered input-sm w-full @error('email') input-error @enderror"
                                   placeholder="email@congty.com">
                            @error('email')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Department --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Phòng ban</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                            </label>
                            <input type="text" name="department" value="{{ old('department', $user->department) }}"
                                   class="input input-bordered input-sm w-full @error('department') input-error @enderror"
                                   placeholder="VD: Kinh doanh, IT, HR...">
                            @error('department')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Active status --}}
                        <div class="form-control pt-1 border-t border-base-200">
                            <label class="flex items-start gap-2.5 cursor-pointer group select-none mt-2">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1"
                                       class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                       {{ old('is_active', $user->is_active ? '1' : '0') == '1' ? 'checked' : '' }}>
                                <div>
                                    <span class="text-sm font-medium group-hover:text-primary transition-colors">Tài khoản đang hoạt động</span>
                                    <p class="text-xs text-base-content/50 mt-0.5">Bỏ chọn để vô hiệu hoá tạm thời</p>
                                </div>
                            </label>
                        </div>

                        {{-- Meta timestamps --}}
                        <div class="flex justify-between text-xs text-base-content/40 px-0.5">
                            <span>Tạo {{ $user->created_at->format('d/m/Y') }}</span>
                            <span>Sửa {{ $user->updated_at->diffForHumans() }}</span>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Password card --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="card-title text-base">
                            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Đổi mật khẩu
                        </h2>
                        <button type="button" @click="generatePassword()"
                                class="btn btn-xs btn-outline gap-1.5">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Tạo ngẫu nhiên
                        </button>
                    </div>

                    <div class="alert alert-info py-2 px-3 mb-4 text-sm">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Để trống nếu không muốn đổi mật khẩu
                    </div>

                    <div class="space-y-4">

                        {{-- New password --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mật khẩu mới</span>
                                <span class="label-text-alt text-xs text-base-content/40">Để trống = không đổi</span>
                            </label>
                            <div class="relative">
                                <input :type="showPw ? 'text' : 'password'"
                                       name="password" id="password-input"
                                       x-model="password"
                                       class="input input-bordered input-sm w-full pr-10 @error('password') input-error @enderror"
                                       placeholder="Tối thiểu 8 ký tự">
                                <button type="button" @click="showPw = !showPw"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 text-base-content/40 hover:text-base-content transition-colors">
                                    <svg x-show="!showPw" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg x-show="showPw" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                            @error('password')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror

                            {{-- Strength indicator --}}
                            <div x-show="password.length > 0" x-transition class="mt-2 space-y-1">
                                <progress class="progress w-full h-1.5 transition-all"
                                          :class="strength.barCls" :value="strength.pct" max="100"></progress>
                                <div class="flex items-center justify-between">
                                    <div class="flex gap-2">
                                        <span class="text-xs transition-colors" :class="pwChecks.length ? 'text-success' : 'text-base-content/30'"><span x-text="pwChecks.length ? '✓' : '○'"></span> ≥8</span>
                                        <span class="text-xs transition-colors" :class="pwChecks.upper  ? 'text-success' : 'text-base-content/30'"><span x-text="pwChecks.upper  ? '✓' : '○'"></span> HOA</span>
                                        <span class="text-xs transition-colors" :class="pwChecks.number ? 'text-success' : 'text-base-content/30'"><span x-text="pwChecks.number ? '✓' : '○'"></span> Số</span>
                                        <span class="text-xs transition-colors" :class="pwChecks.special? 'text-success' : 'text-base-content/30'"><span x-text="pwChecks.special? '✓' : '○'"></span> Đặc biệt</span>
                                    </div>
                                    <span class="text-xs font-semibold transition-colors"
                                          :class="strength.textCls" x-text="strength.label"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Confirm password --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Xác nhận mật khẩu mới</span>
                                <span x-show="pwConfirm.length > 0 && password.length > 0" x-transition
                                      class="label-text-alt text-xs font-medium"
                                      :class="pwConfirm === password ? 'text-success' : 'text-error'"
                                      x-text="pwConfirm === password ? '✓ Khớp' : '✗ Không khớp'"></span>
                            </label>
                            <input :type="showPw ? 'text' : 'password'"
                                   name="password_confirmation" id="password-confirm-input"
                                   x-model="pwConfirm"
                                   :class="pwConfirm && password ? (pwConfirm === password ? 'input-success' : 'input-error') : ''"
                                   class="input input-bordered input-sm w-full transition-colors"
                                   placeholder="Nhập lại mật khẩu mới">
                        </div>

                    </div>
                </div>
            </div>

        </div>

        {{-- ── Right: Org & Role + Permission matrix ──────────────────────── --}}
        <div class="xl:col-span-3 space-y-5">

            {{-- Org & Role card --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <h2 class="card-title text-base mb-5">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        Tổ chức & Vai trò
                    </h2>

                    <div class="form-control mb-5">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tổ chức <span class="text-error">*</span></span>
                        </label>
                        <select id="org-select" name="organization_id"
                                class="select select-bordered select-sm w-full @error('organization_id') select-error @enderror"></select>
                        @error('organization_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Role presets --}}
                    <div class="mb-4">
                        <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide mb-2">Chọn nhanh vai trò</p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
                            <template x-for="preset in rolePresets" :key="preset.role">
                                <button type="button" @click="selectPreset(preset.role)"
                                        :class="selectedRole === preset.role
                                            ? 'border-primary bg-primary/10 text-primary ring-1 ring-primary/30'
                                            : 'border-base-300 hover:border-primary/40 hover:bg-base-200 text-base-content'"
                                        class="flex items-center gap-1.5 p-2 rounded-lg border text-xs font-medium transition-all text-left">
                                    <span x-text="preset.icon" class="text-sm leading-none shrink-0"></span>
                                    <span x-text="preset.label" class="leading-tight"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Vai trò hệ thống <span class="text-error">*</span></span>
                            <span class="label-text-alt text-xs text-base-content/40">Quyết định quyền truy cập</span>
                        </label>
                        <select id="role-select" name="system_role"
                                class="select select-bordered select-sm w-full @error('system_role') select-error @enderror"></select>
                        @error('system_role')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>
            </div>

            {{-- Permission matrix --}}
            <div class="card bg-base-100 shadow-sm border border-base-200" x-show="selectedRole" x-transition>
                <div class="card-body">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="card-title text-base">
                            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Quyền hạn theo vai trò
                        </h3>
                        <span class="badge badge-primary badge-sm" x-text="selectedRoleLabel"></span>
                    </div>

                    <div class="mb-3 p-2.5 bg-base-200/60 rounded-lg" x-show="sidebarModules.length > 0">
                        <p class="text-xs text-base-content/50 font-medium mb-1.5">Hiển thị trong sidebar:</p>
                        <div class="flex flex-wrap gap-1">
                            <template x-for="mod in sidebarModules" :key="mod">
                                <span class="badge badge-xs badge-ghost border border-base-300 font-normal" x-text="mod"></span>
                            </template>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table table-xs w-full">
                            <thead>
                                <tr class="text-xs uppercase text-base-content/40">
                                    <th>Module</th>
                                    <th>Mức quyền</th>
                                    <th class="hidden sm:table-cell">Mô tả ngắn</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="row in currentMatrix" :key="row.module">
                                    <tr>
                                        <td class="font-medium text-sm py-1.5" x-text="row.module"></td>
                                        <td class="py-1.5"><span :class="row.badgeClass" x-text="row.level"></span></td>
                                        <td class="text-xs text-base-content/50 hidden sm:table-cell py-1.5" x-text="row.desc"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Empty state --}}
            <div class="card bg-base-100 border border-dashed border-base-300" x-show="!selectedRole">
                <div class="card-body py-10 text-center text-base-content/25">
                    <svg class="w-10 h-10 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <p class="font-medium text-sm">Chọn vai trò để xem quyền hạn</p>
                </div>
            </div>

        </div>
    </div>

    {{-- ── Submit bar ───────────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-3 pt-4 mt-4 border-t border-base-200">
        <span class="text-xs text-base-content/40 hidden sm:block">
            Đang sửa: <strong class="text-base-content/70">{{ $user->name }}</strong>
        </span>
        <div class="ml-auto flex gap-2">
            <a href="{{ route('backend.users.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
            <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Lưu lại
            </button>
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
