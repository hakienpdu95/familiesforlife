@extends('layouts.backend')
@section('title', 'Thêm Assessment')


@section('content')
<div x-data="assessmentForm({{ Js::from([
    'name'      => old('name', ''),
    'aggModel'  => old('aggregation_model', 'weighted_domain'),
    'classType' => old('classification_type', 'score_band'),
]) }})">

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thêm Assessment mới</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Cấu hình mô hình chấm điểm và phân loại kết quả khảo sát</p>
    </div>
    <a href="{{ route('assessments.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('assessments.store') }}" novalidate data-assessment-form>
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Card chính ──────────────────────────────────────────────── --}}
        <div class="space-y-5">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">

                    <h2 class="card-title text-base mb-5">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Thông tin cơ bản
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
                                    <option value="{{ $org->id }}"
                                        {{ old('organization_id', $defaultOrgId ?? '') == $org->id ? 'selected' : '' }}>
                                        {{ $org->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('organization_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            @endif
                        </div>

                        {{-- Tên hiển thị --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tên hiển thị <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="name" x-model="name"
                                   data-req="Vui lòng nhập tên assessment"
                                   class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                                   placeholder="VD: AI Readiness Assessment 2026" autofocus>
                            @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Assessment code preview --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Assessment Code</span>
                                <span class="label-text-alt text-xs text-base-content/40">Tự động tạo khi lưu</span>
                            </label>
                            <div class="input input-bordered input-sm w-full font-mono bg-base-200/60 text-base-content/50
                                        flex items-center text-sm cursor-default select-all"
                                 x-text="preview"></div>
                            <p class="mt-1 text-xs text-base-content/40">
                                Sinh từ tên kèm hash ngẫu nhiên — không thể thay đổi sau khi tạo
                            </p>
                        </div>

                        {{-- Aggregation model --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mô hình tổng hợp điểm <span class="text-error">*</span></span>
                            </label>
                            <select name="aggregation_model" x-model="aggModel"
                                    class="select select-bordered select-sm w-full @error('aggregation_model') select-error @enderror">
                                <option value="weighted_domain">Điểm domain có trọng số</option>
                                <option value="flat_sum">Tổng điểm đơn giản</option>
                                <option value="sectioned">Điểm theo section</option>
                            </select>
                            <div class="mt-2 rounded-lg bg-base-200/60 px-3 py-2.5 text-xs text-base-content/60 leading-relaxed"
                                 x-text="aggDesc"></div>
                            @error('aggregation_model')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Classification type --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Kiểu phân loại kết quả <span class="text-error">*</span></span>
                            </label>
                            <select name="classification_type" x-model="classType"
                                    class="select select-bordered select-sm w-full @error('classification_type') select-error @enderror">
                                <option value="score_band">Dải điểm (Score Band)</option>
                                <option value="pass_fail">Đạt / Không đạt</option>
                                <option value="persona_match">Khớp Persona</option>
                                <option value="none">Không phân loại</option>
                            </select>
                            <div class="mt-2 rounded-lg bg-base-200/60 px-3 py-2.5 text-xs text-base-content/60 leading-relaxed"
                                 x-text="classDesc"></div>
                            @error('classification_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    {{-- Mục đích sử dụng --}}
                    <div class="form-control mb-4 sm:col-span-2">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium text-sm">Mục đích sử dụng <span class="text-error">*</span></span>
                        </label>
                        <select name="source_type"
                                class="select select-bordered select-sm w-full @error('source_type') select-error @enderror">
                            <option value="standalone" {{ old('source_type', 'standalone') === 'standalone' ? 'selected' : '' }}>
                                Standalone — custom / future modules
                            </option>
                            <option value="lead_scoring" {{ old('source_type') === 'lead_scoring' ? 'selected' : '' }}>
                                Lead Scoring — dùng cho organizations.lead_assessment_code
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-base-content/40">
                            Survey-linked và Global template được tạo tự động — không tạo thủ công.
                        </p>
                        @error('source_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Ghi chú nguồn --}}
                    <div class="form-control mb-4 sm:col-span-2">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium text-sm">Ghi chú nguồn</span>
                            <span class="label-text-alt text-xs text-base-content/40">Tùy chọn</span>
                        </label>
                        <input type="text" name="source_ref"
                               value="{{ old('source_ref') }}"
                               placeholder="VD: crm-lead-q3, hr-eval-2026"
                               class="input input-bordered input-sm w-full @error('source_ref') input-error @enderror">
                        @error('source_ref')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- has_scoring --}}
                    <div class="form-control mt-2">
                        <label class="flex items-start gap-2.5 cursor-pointer group select-none">
                            <input type="checkbox" name="has_scoring" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('has_scoring', '1') == '1' ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Bật chấm điểm tự động</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Tính điểm và lưu kết quả mỗi khi có phản hồi khảo sát mới</p>
                            </div>
                        </label>
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
                        <a href="{{ route('assessments.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
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
</div>
@endsection

@push('styles')
    @vite(['Modules/Assessment/resources/assets/sass/assessment.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tom-select.js',
        'Modules/Assessment/resources/assets/js/assessment.js',
    ], 'build/backend')
@endpush
