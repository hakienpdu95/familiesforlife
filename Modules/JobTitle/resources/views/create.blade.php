@extends('layouts.backend')
@section('title', 'Thêm chức danh mới')


@push('styles')
    @vite(['Modules/JobTitle/resources/assets/sass/job-title.scss'], 'build/backend')
@endpush

@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thêm chức danh mới</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Điền thông tin để tạo chức danh mới</p>
    </div>
    <a href="{{ route('backend.job-titles.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.job-titles.store') }}" novalidate data-job-title-form>
    @csrf

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
                                <option value="{{ $org->id }}" {{ old('organization_id', $defaultOrgId ?? '') == $org->id ? 'selected' : '' }}>
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
                        <input type="text" name="name" value="{{ old('name') }}"
                               data-req="Vui lòng nhập tên chức danh"
                               class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                               placeholder="VD: Trưởng phòng Kinh doanh">
                        @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mã chức danh <span class="text-error">*</span></span>
                            <span class="label-text-alt text-base-content/40 text-xs">Tự động uppercase</span>
                        </label>
                        <input type="text" name="code" value="{{ old('code') }}"
                               data-req="Vui lòng nhập mã chức danh"
                               class="input input-bordered input-sm w-full font-mono @error('code') input-error @enderror"
                               placeholder="VD: MGR, DIR, STAFF">
                        @error('code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Nhóm chức danh <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-category" name="category"
                                data-req="Vui lòng chọn nhóm chức danh"
                                class="select select-bordered select-sm w-full ts-init @error('category') select-error @enderror"
                                data-ts-placeholder="— Chọn nhóm —">
                            <option value="">— Chọn nhóm —</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat['value'] }}" {{ old('category', 'staff') === $cat['value'] ? 'selected' : '' }}>
                                {{ $cat['text'] }}
                            </option>
                            @endforeach
                        </select>
                        @error('category')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Cấp bậc <span class="text-error">*</span></span>
                            <span class="label-text-alt text-base-content/40 text-xs">1–20 (cao hơn = cấp cao hơn)</span>
                        </label>
                        <input type="number" name="level" value="{{ old('level', 1) }}"
                               min="1" max="20"
                               data-req="Vui lòng nhập cấp bậc"
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
                    <textarea name="description"
                              class="jodit-editor textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                              data-jodit-preset="compact">{{ old('description') }}</textarea>
                    @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

            </div>
        </div>

        {{-- ── Sidebar sticky: Xuất bản ───────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
                        Xuất bản
                    </p>

                    <div class="form-control mb-4">
                        <input type="hidden" name="is_active" value="0">
                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="checkbox" name="is_active" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Đang hoạt động</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Hiển thị trong danh sách chọn</p>
                            </div>
                        </label>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.job-titles.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tạo mới
                        </button>
                    </div>

                    <p class="text-center text-xs text-base-content/30 mt-2.5">
                        <span class="text-error">*</span> là trường bắt buộc
                    </p>

                </div>
            </div>

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Hướng dẫn</p>
                    <ul class="text-xs text-base-content/60 space-y-1.5 list-disc list-inside">
                        <li>Mã chức danh phải duy nhất trong org (VD: CEO, MGR, STAFF)</li>
                        <li>Cấp bậc 1–20: cao hơn = cấp cao hơn (CEO = 20, Thực tập = 1)</li>
                        <li>Reviewer phải có cấp bậc ≥ reviewee khi đánh giá hiệu suất</li>
                        <li>Dùng "Vô hiệu" thay vì xóa nếu chức danh đang được gán</li>
                    </ul>
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
