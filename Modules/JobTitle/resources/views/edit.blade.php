@extends('layouts.backend')
@section('title', 'Chỉnh sửa chức danh')


@push('styles')
    @vite(['Modules/JobTitle/resources/assets/sass/job-title.scss'], 'build/backend')
@endpush

@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Chỉnh sửa chức danh</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $jobTitle->name }} · <span class="font-mono">{{ $jobTitle->code }}</span></p>
    </div>
    <a href="{{ route('backend.job-titles.show', $jobTitle) }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

@if($jobTitle->is_locked)
<div class="alert alert-warning py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
    </svg>
    <p>Chức danh này đã bị khóa (<code>is_locked = true</code>). Không thể chỉnh sửa.</p>
</div>
@endif

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

<form method="POST" action="{{ route('backend.job-titles.update', $jobTitle) }}" novalidate data-job-title-form>
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Card chính ──────────────────────────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Thông tin chức danh
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

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
                            <select id="ts-organization" name="organization_id"
                                    class="select select-bordered select-sm w-full ts-init @error('organization_id') select-error @enderror"
                                    data-ts-placeholder="— Chọn tổ chức —"
                                    data-req="Vui lòng chọn tổ chức">
                                <option value="">— Chọn tổ chức —</option>
                                @foreach($organizations as $org)
                                <option value="{{ $org->id }}" {{ old('organization_id', $jobTitle->organization_id) == $org->id ? 'selected' : '' }}>
                                    {{ $org->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('organization_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        @endif
                    </div>

                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tên chức danh <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $jobTitle->name) }}"
                               data-req="Vui lòng nhập tên chức danh"
                               {{ $jobTitle->is_locked ? 'disabled' : '' }}
                               class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                               placeholder="VD: Trưởng phòng Kinh doanh">
                        @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mã chức danh <span class="text-error">*</span></span>
                            <span class="label-text-alt text-base-content/40 text-xs">Tự động uppercase</span>
                        </label>
                        <input type="text" name="code" value="{{ old('code', $jobTitle->code) }}"
                               data-req="Vui lòng nhập mã chức danh"
                               {{ $jobTitle->is_locked ? 'disabled' : '' }}
                               class="input input-bordered input-sm w-full font-mono @error('code') input-error @enderror"
                               placeholder="VD: MGR">
                        @error('code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Nhóm chức danh <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-category" name="category"
                                data-req="Vui lòng chọn nhóm chức danh"
                                {{ $jobTitle->is_locked ? 'disabled' : '' }}
                                class="select select-bordered select-sm w-full ts-init @error('category') select-error @enderror"
                                data-ts-placeholder="— Chọn nhóm —">
                            <option value="">— Chọn nhóm —</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat['value'] }}"
                                    {{ old('category', $jobTitle->category->value) === $cat['value'] ? 'selected' : '' }}>
                                {{ $cat['text'] }}
                            </option>
                            @endforeach
                        </select>
                        @error('category')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Cấp bậc <span class="text-error">*</span></span>
                            <span class="label-text-alt text-base-content/40 text-xs">1–20</span>
                        </label>
                        <input type="number" name="level" value="{{ old('level', $jobTitle->level) }}"
                               min="1" max="20"
                               data-req="Vui lòng nhập cấp bậc"
                               {{ $jobTitle->is_locked ? 'disabled' : '' }}
                               class="input input-bordered input-sm w-full @error('level') input-error @enderror"
                               placeholder="1">
                        <p class="mt-1 text-xs text-base-content/40">CEO = 20, Thực tập = 1</p>
                        @error('level')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>

                <div class="form-control mt-4">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Mô tả vai trò / trách nhiệm</span>
                    </label>
                    @if($jobTitle->is_locked)
                    <textarea name="description" rows="4" disabled
                              class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                    >{{ old('description', $jobTitle->description) }}</textarea>
                    @else
                    <textarea name="description"
                              class="jodit-editor textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                              data-jodit-preset="compact">{{ old('description', $jobTitle->description) }}</textarea>
                    @endif
                    @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

            </div>
        </div>

        {{-- ── Sidebar sticky: Xuất bản ───────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">

            @if($jobTitle->is_system)
            <div class="alert alert-info py-2 px-3 text-xs gap-2">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Chức danh do hệ thống tạo sẵn
            </div>
            @endif

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
                        Xuất bản
                    </p>

                    <div class="form-control mb-3">
                        <input type="hidden" name="is_active" value="0">
                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="checkbox" name="is_active" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('is_active', $jobTitle->is_active ? '1' : '0') === '1' ? 'checked' : '' }}
                                   {{ $jobTitle->is_locked ? 'disabled' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Đang hoạt động</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Hiển thị trong danh sách chọn</p>
                            </div>
                        </label>
                    </div>

                    <div class="flex justify-between text-xs text-base-content/40 mb-4 px-0.5">
                        <span>Tạo {{ $jobTitle->created_at->format('d/m/Y') }}</span>
                        <span>Sửa {{ $jobTitle->updated_at->diffForHumans() }}</span>
                    </div>

                    @if(! $jobTitle->is_locked)
                    <div class="flex gap-2">
                        <a href="{{ route('backend.job-titles.show', $jobTitle) }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
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
                    @endif

                </div>
            </div>

        </div>

    </div>
</form>

@endsection

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/jodit.js',
        'Modules/JobTitle/resources/assets/js/job-title.js',
    ], 'build/backend')
@endpush
