@extends('layouts.backend')
@section('title', 'Sửa Assessment — ' . $assessment->name)


@section('content')
<div x-data="assessmentForm({{ Js::from([
    'name'      => old('name', $assessment->name),
    'aggModel'  => old('aggregation_model', $assessment->aggregation_model),
    'classType' => old('classification_type', $assessment->classification_type),
]) }})">

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">{{ $assessment->name }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5 font-mono">{{ $assessment->assessment_code }}</p>
    </div>
    <a href="{{ route('assessments.show', $assessment->assessment_code) }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('assessments.update', $assessment->assessment_code) }}"
      novalidate data-assessment-form>
    @csrf @method('PUT')

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
                                <input type="text" value="{{ $organizations->first()->name }}" readonly
                                       class="input input-bordered input-sm w-full bg-base-200 cursor-not-allowed">
                                <p class="mt-1 text-xs text-base-content/40">Xác định từ tài khoản của bạn.</p>
                            @else
                                <select id="ts-organization" name="organization_id"
                                        class="select select-bordered select-sm w-full ts-init"
                                        data-ts-placeholder="— Chọn tổ chức —">
                                    <option value="">— Chọn tổ chức —</option>
                                    @foreach($organizations as $org)
                                    <option value="{{ $org->id }}"
                                        {{ old('organization_id', $assessment->organization_id) == $org->id ? 'selected' : '' }}>
                                        {{ $org->name }}
                                    </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        {{-- Assessment code (read-only) --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Assessment Code</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không thể thay đổi</span>
                            </label>
                            <input type="text" value="{{ $assessment->assessment_code }}" disabled
                                   class="input input-bordered input-sm w-full font-mono field-readonly">
                        </div>

                        {{-- Tên hiển thị --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tên hiển thị <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="name" value="{{ old('name', $assessment->name) }}"
                                   data-req="Vui lòng nhập tên assessment"
                                   class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                                   placeholder="VD: AI Readiness Assessment 2026">
                            @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
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

                    {{-- has_scoring --}}
                    <div class="form-control mt-2">
                        <label class="flex items-start gap-2.5 cursor-pointer group select-none">
                            <input type="checkbox" name="has_scoring" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('has_scoring', $assessment->has_scoring) ? 'checked' : '' }}>
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

                    {{-- is_active --}}
                    <div class="form-control mb-4">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">Trạng thái <span class="text-error">*</span></span>
                        </label>
                        <select name="is_active"
                                class="select select-bordered select-sm w-full @error('is_active') select-error @enderror">
                            <option value="1" {{ old('is_active', $assessment->is_active ? '1' : '0') === '1' ? 'selected' : '' }}>
                                Hoạt động
                            </option>
                            <option value="0" {{ old('is_active', $assessment->is_active ? '1' : '0') === '0' ? 'selected' : '' }}>
                                Không hoạt động
                            </option>
                        </select>
                        @error('is_active')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Meta timestamps --}}
                    <div class="flex justify-between text-xs text-base-content/40 mb-4 px-0.5">
                        <span>Tạo {{ $assessment->created_at->format('d/m/Y') }}</span>
                        <span>Sửa {{ $assessment->updated_at->diffForHumans() }}</span>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('assessments.show', $assessment->assessment_code) }}"
                           class="btn btn-ghost btn-sm flex-1">Hủy</a>
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
