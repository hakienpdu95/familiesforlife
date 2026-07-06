@extends('layouts.backend')
@section('title', 'Thêm môi trường Sandbox')

@push('styles')
    @vite(['Modules/Assessment/resources/assets/sass/assessment.scss'], 'build/backend')
@endpush

@section('content')

<div x-data="{ scope: {{ \Illuminate\Support\Js::from(old('scope', 'global')) }} }">

{{-- Page header --}}
<div class="flex items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thêm môi trường Sandbox</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Cấu hình môi trường thực hành AI cho nhân viên</p>
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

<form method="POST" action="{{ route('backend.sandbox-admin.env.store') }}"
      novalidate data-sandbox-env-form>
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Main column ─────────────────────────────────── --}}
        <div class="space-y-4">

            {{-- Org Selector Block (§27 Biến thể A) --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-base-content/50 mb-3">Phạm vi sử dụng</p>

                    @if($isSuperAdmin)
                    <div class="flex gap-6 flex-wrap mb-3">
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="radio" name="scope" value="global"
                                   class="radio radio-sm radio-info mt-0.5"
                                   x-model="scope"
                                   {{ old('scope', 'global') === 'global' ? 'checked' : '' }}>
                            <div>
                                <p class="text-sm font-medium">Toàn hệ thống</p>
                                <p class="text-xs text-base-content/40">Template dùng chung cho tất cả tổ chức</p>
                            </div>
                        </label>
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="radio" name="scope" value="org"
                                   class="radio radio-sm radio-primary mt-0.5"
                                   x-model="scope"
                                   {{ old('scope') === 'org' ? 'checked' : '' }}>
                            <div>
                                <p class="text-sm font-medium">Riêng tổ chức cụ thể</p>
                                <p class="text-xs text-base-content/40">Chỉ tổ chức được chọn mới thấy</p>
                            </div>
                        </label>
                    </div>

                    {{-- Org dropdown — NO ts-init (starts hidden), JS init thủ công --}}
                    <div x-show="scope === 'org'" x-cloak class="form-control">
                        <label class="label py-0 pb-1.5" for="ts-organization_id">
                            <span class="label-text font-medium">Chọn tổ chức <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-organization_id" name="organization_id"
                                class="select select-bordered select-sm w-full max-w-sm @error('organization_id') select-error @enderror"
                                data-ts-placeholder="— Chọn tổ chức —">
                            <option value="">— Chọn tổ chức —</option>
                            @foreach($organizations as $org)
                            <option value="{{ $org->id }}" {{ old('organization_id') == $org->id ? 'selected' : '' }}>
                                {{ $org->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('organization_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        @if($organizations->isEmpty())
                        <p class="mt-1 text-xs text-warning">Chưa có tổ chức doanh nghiệp nào trong hệ thống.</p>
                        @endif
                    </div>

                    @else
                    <input type="hidden" name="scope" value="org">
                    <div class="flex items-center gap-2 text-sm text-base-content/70">
                        <svg class="w-4 h-4 opacity-40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        Riêng cho: <strong>{{ $currentOrg?->name ?? 'Tổ chức của bạn' }}</strong>
                    </div>
                    <p class="mt-1 text-xs text-base-content/40">Môi trường sẽ chỉ hiển thị cho nhân viên trong tổ chức của bạn.</p>
                    @endif
                </div>
            </div>

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
                                   value="{{ old('name') }}"
                                   placeholder="VD: AI Văn phòng — Foundation">
                            @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Env Code --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5" for="env_code">
                                <span class="label-text text-xs font-medium">Mã (env_code) <span class="text-error">*</span></span>
                            </label>
                            <input type="text" id="env_code" name="env_code"
                                   class="input input-bordered input-sm w-full font-mono @error('env_code') input-error @enderror"
                                   value="{{ old('env_code') }}"
                                   placeholder="AI_OFFICE_F1">
                            <label class="label py-0.5">
                                <span class="label-text-alt text-xs text-base-content/40">Chỉ gồm CHỮ HOA, số và gạch dưới. Không thể thay đổi sau khi tạo.</span>
                            </label>
                            @error('env_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
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
                                <option value="{{ $val }}" {{ old('type') === $val ? 'selected' : '' }}>{{ $label }}</option>
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
                                <option value="1" {{ old('tier') == '1' ? 'selected' : '' }}>Tier 1 — Cơ bản (Foundation)</option>
                                <option value="2" {{ old('tier') == '2' ? 'selected' : '' }}>Tier 2 — Nâng cao (Intermediate)</option>
                                <option value="3" {{ old('tier') == '3' ? 'selected' : '' }}>Tier 3 — Chuyên sâu (Advanced)</option>
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
                                   value="{{ old('sort_order', 0) }}">
                        </div>

                        {{-- Description --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5" for="description">
                                <span class="label-text text-xs font-medium">Mô tả</span>
                            </label>
                            <textarea id="description" name="description" rows="3"
                                      class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                                      placeholder="Mô tả ngắn về môi trường này...">{{ old('description') }}</textarea>
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
                               {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                        <span class="text-sm leading-snug">
                            Kích hoạt
                            <span class="text-xs text-base-content/40 block mt-0.5">Hiển thị cho nhân viên</span>
                        </span>
                    </label>

                    <div class="border-t border-base-200 pt-4 flex flex-col gap-2">
                        <button type="submit" class="btn btn-primary btn-sm w-full">Tạo môi trường</button>
                        <a href="{{ route('backend.sandbox-admin.index') }}" class="btn btn-ghost btn-sm w-full">Hủy</a>
                    </div>
                </div>
            </div>
        </div>{{-- end sidebar --}}

    </div>
</form>

</div>{{-- end x-data --}}
@endsection

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'Modules/Assessment/resources/assets/js/assessment.js',
    ], 'build/backend')
@endpush
