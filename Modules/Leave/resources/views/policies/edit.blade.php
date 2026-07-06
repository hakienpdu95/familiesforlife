@extends('layouts.backend')
@section('title', 'Sửa chính sách nghỉ phép')


@push('styles')
    @vite(['Modules/Leave/resources/assets/sass/leave.scss'], 'build/backend')
@endpush

@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Sửa chính sách nghỉ phép</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $policy->name }}</p>
    </div>
    <a href="{{ route('backend.leave.policies.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

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

<form method="POST" action="{{ route('backend.leave.policies.update', $policy) }}" novalidate data-leave-policy-form>
    @csrf @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Cards chính ──────────────────────────────────────────────── --}}
        <div class="space-y-5">

            {{-- Card: Thông tin chính sách --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">

                    <h2 class="card-title text-base mb-5">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Thông tin chính sách
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        {{-- Tổ chức (readonly — không đổi sau khi tạo) --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tổ chức</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không thể thay đổi</span>
                            </label>
                            <input type="text" value="{{ $orgName }}" readonly
                                   class="input input-bordered input-sm w-full bg-base-200 cursor-not-allowed">
                        </div>

                        {{-- leave_type: readonly sau khi tạo --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Loại nghỉ</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không thể thay đổi</span>
                            </label>
                            <input type="text" value="{{ $policy->leave_type->label() }}"
                                   class="input input-bordered input-sm w-full bg-base-200" readonly>
                        </div>

                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tên chính sách <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="name" value="{{ old('name', $policy->name) }}"
                                   data-req="Vui lòng nhập tên chính sách"
                                   class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                                   placeholder="VD: Nghỉ phép năm toàn công ty">
                            @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Số ngày / năm <span class="text-error">*</span></span>
                            </label>
                            <input type="number" name="days_per_year" value="{{ old('days_per_year', $policy->days_per_year) }}"
                                   step="0.5" min="0"
                                   data-req="Vui lòng nhập số ngày"
                                   class="input input-bordered input-sm w-full @error('days_per_year') input-error @enderror"
                                   placeholder="12">
                            @error('days_per_year')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ngày chuyển sang năm sau</span>
                                <span class="label-text-alt text-xs text-base-content/40">0 = không cho phép</span>
                            </label>
                            <input type="number" name="carry_over_days" value="{{ old('carry_over_days', $policy->carry_over_days) }}"
                                   step="0.5" min="0"
                                   class="input input-bordered input-sm w-full @error('carry_over_days') input-error @enderror"
                                   placeholder="0">
                            @error('carry_over_days')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Báo trước tối thiểu</span>
                                <span class="label-text-alt text-xs text-base-content/40">ngày</span>
                            </label>
                            <input type="number" name="min_advance_days" value="{{ old('min_advance_days', $policy->min_advance_days) }}"
                                   min="0"
                                   class="input input-bordered input-sm w-full @error('min_advance_days') input-error @enderror"
                                   placeholder="1">
                            @error('min_advance_days')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tối đa liên tiếp</span>
                                <span class="label-text-alt text-xs text-base-content/40">ngày</span>
                            </label>
                            <input type="number" name="max_consecutive_days" value="{{ old('max_consecutive_days', $policy->max_consecutive_days) }}"
                                   min="1"
                                   class="input input-bordered input-sm w-full @error('max_consecutive_days') input-error @enderror"
                                   placeholder="Không giới hạn">
                            @error('max_consecutive_days')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Hiệu lực từ ngày</span>
                            </label>
                            <input type="text" name="effective_from" id="fp-effective-from"
                                   value="{{ old('effective_from', $policy->effective_from?->format('Y-m-d') ?? '') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('effective_from') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('effective_from')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control sm:col-span-2">
                            <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                                <input type="hidden" name="requires_approval" value="0">
                                <input type="checkbox" name="requires_approval" value="1"
                                       class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                       {{ old('requires_approval', $policy->requires_approval ? '1' : '0') == '1' ? 'checked' : '' }}>
                                <div>
                                    <span class="text-sm font-medium group-hover:text-primary transition-colors">Yêu cầu duyệt</span>
                                    <p class="text-xs text-base-content/50 mt-0.5">Đơn nghỉ phép phải được manager duyệt trước khi chốt</p>
                                </div>
                            </label>
                        </div>

                    </div>
                </div>
            </div>

            {{-- Card: Phạm vi áp dụng --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">

                    <h2 class="card-title text-base mb-2">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Phạm vi áp dụng
                    </h2>
                    <p class="text-xs text-base-content/50 mb-4">Ưu tiên: Chức danh > Phòng ban > Toàn công ty. Để trống = áp dụng tất cả.</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Áp dụng cho chức danh</span>
                            </label>
                            <select id="ts-job_title_id" name="job_title_id"
                                    class="select select-bordered select-sm w-full ts-init @error('job_title_id') select-error @enderror"
                                    data-ts-placeholder="— Toàn bộ —">
                                <option value="">— Toàn bộ —</option>
                                @foreach($jobTitles as $jt)
                                <option value="{{ $jt->id }}" {{ old('job_title_id', $policy->job_title_id) == $jt->id ? 'selected' : '' }}>
                                    {{ $jt->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('job_title_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Áp dụng cho phòng ban</span>
                            </label>
                            <select id="ts-department_id" name="department_id"
                                    class="select select-bordered select-sm w-full ts-init @error('department_id') select-error @enderror"
                                    data-ts-placeholder="— Toàn bộ —">
                                <option value="">— Toàn bộ —</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id', $policy->department_id) == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('department_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>
                </div>
            </div>

        </div>{{-- /cards main --}}

        {{-- ── Sidebar sticky ───────────────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>

                    <div class="form-control mb-4">
                        <input type="hidden" name="is_active" value="0">
                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="checkbox" name="is_active" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('is_active', $policy->is_active ? '1' : '0') == '1' ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Đang áp dụng</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Chính sách có hiệu lực ngay</p>
                            </div>
                        </label>
                    </div>

                    <div class="flex justify-between text-xs text-base-content/40 mb-4 px-0.5">
                        <span>Tạo {{ $policy->created_at->format('d/m/Y') }}</span>
                        <span>Sửa {{ $policy->updated_at->diffForHumans() }}</span>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.leave.policies.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Lưu lại
                        </button>
                    </div>

                    <p class="text-center text-xs text-base-content/30 mt-2.5">
                        <span class="text-error">*</span> là trường bắt buộc
                    </p>

                </div>
            </div>

        </div>{{-- /sidebar --}}

    </div>{{-- /grid --}}
</form>

@endsection

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/flatpickr.js',
        'Modules/Leave/resources/assets/js/leave.js',
    ], 'build/backend')
@endpush
