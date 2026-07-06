@extends('layouts.backend')
@section('title', 'Thêm tình trạng Pipeline')


@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thêm tình trạng mới</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Tạo tình trạng cho quy trình bán hàng của tổ chức</p>
    </div>
    <a href="{{ route('lead-pipeline-stage.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('lead-pipeline-stage.store') }}" novalidate data-pipeline-stage-form>
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Card chính ──────────────────────────────────────────────── --}}
        <div class="space-y-5">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">

                    <h2 class="card-title text-base mb-5">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                        </svg>
                        Thông tin tình trạng
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

                        {{-- Mã tình trạng --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mã tình trạng <span class="text-error">*</span></span>
                                <span class="label-text-alt text-xs text-base-content/40">Không thể thay đổi sau khi tạo</span>
                            </label>
                            <input type="text" name="code" value="{{ old('code') }}"
                                   data-req="Vui lòng nhập mã tình trạng"
                                   pattern="[a-z0-9_]+" title="Chỉ dùng chữ thường, số và dấu gạch dưới"
                                   maxlength="32"
                                   class="input input-bordered input-sm w-full font-mono @error('code') input-error @enderror"
                                   placeholder="VD: qualified" autofocus>
                            <p class="mt-1 text-xs text-base-content/40">Chỉ dùng chữ thường, số và <code class="bg-base-200 px-1 rounded">_</code></p>
                            @error('code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Tên hiển thị --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tên hiển thị <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="label" value="{{ old('label') }}"
                                   data-req="Vui lòng nhập tên hiển thị"
                                   maxlength="64"
                                   class="input input-bordered input-sm w-full @error('label') input-error @enderror"
                                   placeholder="VD: Đủ điều kiện">
                            @error('label')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Màu --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Màu <span class="text-error">*</span></span>
                            </label>
                            <div class="color-picker-combo">
                                <input type="color" id="colorPicker"
                                       value="{{ old('color', '#6b7280') }}">
                                <input type="text" name="color" id="colorText"
                                       value="{{ old('color', '#6b7280') }}"
                                       maxlength="16"
                                       data-req="Vui lòng nhập màu"
                                       class="input input-bordered input-sm flex-1 font-mono @error('color') input-error @enderror"
                                       placeholder="#6b7280">
                            </div>
                            @error('color')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Thứ tự hiển thị --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Thứ tự hiển thị</span>
                            </label>
                            <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}"
                                   min="0" max="255"
                                   class="input input-bordered input-sm w-full @error('sort_order') input-error @enderror"
                                   placeholder="0">
                            @error('sort_order')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Xác suất chốt --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Xác suất chốt (%)</span>
                                <span class="label-text-alt text-xs text-base-content/40">0–100</span>
                            </label>
                            <input type="number" name="probability" value="{{ old('probability', 0) }}"
                                   min="0" max="100"
                                   class="input input-bordered input-sm w-full @error('probability') input-error @enderror"
                                   placeholder="0">
                            <p class="mt-1 text-xs text-base-content/40">Dùng tính weighted pipeline value</p>
                            @error('probability')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    {{-- Loại tình trạng kết thúc --}}
                    <div class="form-control mt-2">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Loại tình trạng kết thúc</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <div class="flex flex-wrap gap-6">
                            <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                                <input type="checkbox" name="is_won" value="1"
                                       class="checkbox checkbox-sm checkbox-success mt-0.5 shrink-0"
                                       {{ old('is_won') ? 'checked' : '' }}>
                                <div>
                                    <span class="text-sm font-medium group-hover:text-success transition-colors">Thành công (Won)</span>
                                    <p class="text-xs text-base-content/50 mt-0.5">Cơ hội được chốt thành công</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                                <input type="checkbox" name="is_lost" value="1"
                                       class="checkbox checkbox-sm checkbox-error mt-0.5 shrink-0"
                                       {{ old('is_lost') ? 'checked' : '' }}>
                                <div>
                                    <span class="text-sm font-medium group-hover:text-error transition-colors">Thất bại (Lost)</span>
                                    <p class="text-xs text-base-content/50 mt-0.5">Cơ hội không đạt được</p>
                                </div>
                            </label>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
                        Xuất bản
                    </p>

                    <div class="flex gap-2">
                        <a href="{{ route('lead-pipeline-stage.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
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
        </div>

    </div>
</form>

@endsection

@push('styles')
    @vite(['Modules/LeadPipelineStage/resources/assets/sass/lead-pipeline-stage.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tom-select.js',
        'Modules/LeadPipelineStage/resources/assets/js/lead-pipeline-stage.js',
    ], 'build/backend')
@endpush
