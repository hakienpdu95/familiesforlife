@extends('layouts.backend')
@section('title', 'Đăng ký nghỉ phép')


@push('styles')
    @vite(['Modules/Leave/resources/assets/sass/leave.scss'], 'build/backend')
@endpush

@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Đăng ký nghỉ phép</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Tạo đơn nghỉ phép cho nhân viên</p>
    </div>
    <a href="{{ route('backend.leave.requests.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.leave.requests.store') }}" novalidate
      data-leave-request-form
      data-org-locked="{{ $orgLocked ? '1' : '0' }}"
      data-locked-org-id="{{ $orgLocked ? ($organizations->first()->id ?? '') : '' }}">
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Card chính ───────────────────────────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Thông tin đơn nghỉ
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- Tổ chức --}}
                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tổ chức <span class="text-error">*</span></span>
                        </label>
                        @if($orgLocked)
                            <input type="hidden" name="organization_id" value="{{ $organizations->first()->id }}">
                            <input type="text" value="{{ $organizations->first()->name }}" readonly
                                   class="input input-bordered input-sm w-full bg-base-200 cursor-not-allowed">
                            <p class="mt-1 text-xs text-base-content/40">Xác định từ tài khoản của bạn.</p>
                        @else
                            <select id="ts-organization_id" name="organization_id"
                                    class="select select-bordered select-sm w-full @error('organization_id') select-error @enderror"
                                    data-ts-placeholder="— Chọn tổ chức —"
                                    data-req="Vui lòng chọn tổ chức">
                                <option value="">— Chọn tổ chức —</option>
                                @foreach($organizations as $org)
                                <option value="{{ $org->id }}" {{ old('organization_id', $defaultOrgId ?? '') == $org->id ? 'selected' : '' }}>
                                    {{ $org->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('organization_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        @endif
                    </div>

                    {{-- Nhân viên — loaded dynamically based on org --}}
                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Nhân viên <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-employee_id" name="employee_id"
                                data-req="Vui lòng chọn nhân viên"
                                data-old-value="{{ old('employee_id') }}"
                                class="select select-bordered select-sm w-full @error('employee_id') select-error @enderror">
                            <option value="">— Chọn nhân viên —</option>
                            @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->full_name }} ({{ $emp->employee_code }})
                            </option>
                            @endforeach
                        </select>
                        @error('employee_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Loại nghỉ <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-leave_type" name="leave_type"
                                data-req="Vui lòng chọn loại nghỉ"
                                class="select select-bordered select-sm w-full ts-init @error('leave_type') select-error @enderror"
                                data-ts-placeholder="— Chọn loại —">
                            <option value="">— Chọn loại —</option>
                            @foreach($leaveTypes as $type)
                            <option value="{{ $type['value'] }}" {{ old('leave_type') === $type['value'] ? 'selected' : '' }}>
                                {{ $type['text'] }}
                            </option>
                            @endforeach
                        </select>
                        @error('leave_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Từ ngày <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="date_from" id="fp-date-from"
                               value="{{ old('date_from', now()->format('Y-m-d')) }}"
                               data-req="Vui lòng chọn ngày bắt đầu"
                               class="input input-bordered input-sm w-full fp-init @error('date_from') input-error @enderror"
                               placeholder="DD/MM/YYYY">
                        @error('date_from')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Đến ngày <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="date_to" id="fp-date-to"
                               value="{{ old('date_to', now()->format('Y-m-d')) }}"
                               data-req="Vui lòng chọn ngày kết thúc"
                               class="input input-bordered input-sm w-full fp-init @error('date_to') input-error @enderror"
                               placeholder="DD/MM/YYYY">
                        @error('date_to')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Lý do</span>
                        </label>
                        <textarea name="reason"
                                  class="jodit-editor textarea textarea-bordered textarea-sm w-full @error('reason') textarea-error @enderror"
                                  data-jodit-preset="compact">{{ old('reason') }}</textarea>
                        @error('reason')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tệp đính kèm</span>
                            <span class="label-text-alt text-xs text-base-content/40">URL</span>
                        </label>
                        <input type="text" name="attachment_url" value="{{ old('attachment_url') }}"
                               data-val-url="URL không đúng định dạng"
                               class="input input-bordered input-sm w-full @error('attachment_url') input-error @enderror"
                               placeholder="https://drive.google.com/...">
                        @error('attachment_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>
            </div>
        </div>{{-- /card chính --}}

        {{-- ── Sidebar sticky ───────────────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Gửi đơn</p>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.leave.requests.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Gửi đơn
                        </button>
                    </div>

                    <p class="text-center text-xs text-base-content/30 mt-2.5">
                        <span class="text-error">*</span> là trường bắt buộc
                    </p>

                </div>
            </div>

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-2">Lưu ý</p>
                    <ul class="text-xs text-base-content/60 space-y-1.5 list-disc list-inside">
                        <li>Số ngày được tính tự động (trừ Chủ nhật)</li>
                        <li>Hệ thống kiểm tra số dư nghỉ phép còn lại</li>
                        <li>Đơn có thể cần duyệt tùy chính sách</li>
                    </ul>
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
        'resources/js/modules/jodit.js',
        'Modules/Leave/resources/assets/js/leave.js',
    ], 'build/backend')
@endpush
