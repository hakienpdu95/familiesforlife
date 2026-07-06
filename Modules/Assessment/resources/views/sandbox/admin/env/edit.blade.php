@extends('layouts.backend')
@section('title', 'Sửa môi trường: ' . $env->name)

@push('styles')
    @vite(['Modules/Assessment/resources/assets/sass/assessment.scss'], 'build/backend')
@endpush

@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">{{ $env->name }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            @if($env->organization_id === null)
                <span class="badge badge-info badge-sm">Hệ thống — dùng chung</span>
            @else
                <span class="badge badge-ghost badge-sm">Riêng: {{ $envOrgName ?? 'Tổ chức #'.$env->organization_id }}</span>
            @endif
            &nbsp;·&nbsp; <span class="font-mono text-xs">{{ $env->env_code }}</span>
        </p>
    </div>
    <a href="{{ route('backend.sandbox-admin.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Danh sách
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

<form method="POST" action="{{ route('backend.sandbox-admin.env.update', $env) }}"
      novalidate data-sandbox-env-form>
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Main column ─────────────────────────────────── --}}
        <div class="space-y-4">

            {{-- Main fields card --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-base-content/50 mb-4">Thông tin môi trường</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        {{-- Name --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5" for="name">
                                <span class="label-text text-xs font-medium">Tên môi trường <span class="text-error">*</span></span>
                            </label>
                            <input type="text" id="name" name="name"
                                   class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                                   value="{{ old('name', $env->name) }}"
                                   placeholder="VD: AI Văn phòng — Foundation">
                            @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Type --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5" for="ts-type">
                                <span class="label-text text-xs font-medium">Loại kỹ năng <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-type" name="type"
                                    class="select select-bordered select-sm w-full ts-init @error('type') select-error @enderror"
                                    data-ts-placeholder="— Chọn loại —">
                                <option value="">— Chọn loại —</option>
                                @foreach(['office' => 'Văn phòng số', 'data' => 'Phân tích dữ liệu', 'sales' => 'Kinh doanh', 'hr' => 'Nhân sự', 'workflow' => 'Quy trình làm việc', 'leadership' => 'Lãnh đạo & Chiến lược', 'custom' => 'Tuỳ chỉnh'] as $val => $label)
                                <option value="{{ $val }}" {{ old('type', $env->type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Tier --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5" for="ts-tier">
                                <span class="label-text text-xs font-medium">Cấp độ (Tier) <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-tier" name="tier"
                                    class="select select-bordered select-sm w-full ts-init @error('tier') select-error @enderror"
                                    data-ts-placeholder="— Chọn cấp độ —">
                                <option value="">— Chọn cấp độ —</option>
                                <option value="1" {{ old('tier', $env->tier) == '1' ? 'selected' : '' }}>Tier 1 — Cơ bản (Foundation)</option>
                                <option value="2" {{ old('tier', $env->tier) == '2' ? 'selected' : '' }}>Tier 2 — Nâng cao (Intermediate)</option>
                                <option value="3" {{ old('tier', $env->tier) == '3' ? 'selected' : '' }}>Tier 3 — Chuyên sâu (Advanced)</option>
                            </select>
                            @error('tier')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Sort order --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5" for="sort_order">
                                <span class="label-text text-xs font-medium">Thứ tự hiển thị</span>
                            </label>
                            <input type="number" id="sort_order" name="sort_order" min="0"
                                   class="input input-bordered input-sm w-full"
                                   value="{{ old('sort_order', $env->sort_order) }}">
                        </div>

                        {{-- Description --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5" for="description">
                                <span class="label-text text-xs font-medium">Mô tả</span>
                            </label>
                            <textarea id="description" name="description" rows="3"
                                      class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                                      placeholder="Mô tả ngắn về môi trường này...">{{ old('description', $env->description) }}</textarea>
                            @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>
                </div>
            </div>

        </div>{{-- end main column --}}

        {{-- ── Sidebar ─────────────────────────────────────── --}}
        <div class="space-y-4">
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-4 space-y-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-base-content/50">Xuất bản</p>

                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1"
                               class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                               {{ old('is_active', $env->is_active ? '1' : '') == '1' ? 'checked' : '' }}>
                        <span class="text-sm leading-snug">
                            Kích hoạt
                            <span class="text-xs text-base-content/40 block mt-0.5">Hiển thị cho nhân viên</span>
                        </span>
                    </label>

                    <div class="border-t border-base-200 pt-4 space-y-1 text-xs text-base-content/40">
                        <p>Tạo: {{ $env->created_at?->format('d/m/Y H:i') }}</p>
                        <p>Cập nhật: {{ $env->updated_at?->format('d/m/Y H:i') }}</p>
                    </div>

                    <div class="border-t border-base-200 pt-4 flex flex-col gap-2">
                        <button type="submit" class="btn btn-primary btn-sm w-full">Cập nhật</button>
                        <a href="{{ route('backend.sandbox-admin.index') }}" class="btn btn-ghost btn-sm w-full">Hủy</a>
                    </div>
                </div>
            </div>
        </div>{{-- end sidebar --}}

    </div>
</form>

@endsection

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'Modules/Assessment/resources/assets/js/assessment.js',
    ], 'build/backend')
@endpush
