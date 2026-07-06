@extends('layouts.backend')
@section('title', 'Tạo vertical mới — ' . $organization->name)

@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <div class="flex items-center gap-2 text-sm text-base-content/40 mb-1">
            <a href="{{ route('backend.organizations.show', $organization) }}" class="hover:text-primary">{{ $organization->name }}</a>
            <span>/</span>
            <a href="{{ route('backend.organizations.verticals.index', $organization) }}" class="hover:text-primary">Dịch vụ triển khai</a>
            <span>/</span>
            <span>Tạo mới từ đầu</span>
        </div>
        <h1 class="text-2xl font-bold text-base-content">Tạo vertical mới từ đầu</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Instance hoàn toàn trống, độc lập với thư viện mẫu — thêm phase/checklist riêng sau khi tạo</p>
    </div>
    <a href="{{ route('backend.organizations.verticals.index', $organization) }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.organizations.verticals.store', $organization) }}"
      novalidate data-vertical-template-form>
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Card chính ──────────────────────────────────────────────── --}}
        <div class="space-y-5">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <h2 class="card-title text-base mb-5">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Thông tin vertical
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control sm:col-span-1">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mã vertical (code) <span class="text-error">*</span></span>
                                <span class="label-text-alt text-xs text-base-content/40">Tự động tạo từ tên nếu để trống</span>
                            </label>
                            <input type="text" name="code" value="{{ old('code') }}"
                                   data-req="Vui lòng nhập mã vertical"
                                   data-val-pattern="^[a-z0-9]+(-[a-z0-9]+)*$"
                                   data-val-pattern-msg="Chỉ dùng chữ thường, số và dấu gạch ngang (vd: quan-ly-kho)"
                                   class="input input-bordered input-sm w-full font-mono @error('code') input-error @enderror"
                                   placeholder="quan-ly-kho">
                            @error('code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control sm:col-span-1">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tên hiển thị (label) <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="label" value="{{ old('label') }}"
                                   data-req="Vui lòng nhập tên hiển thị"
                                   class="input input-bordered input-sm w-full @error('label') input-error @enderror"
                                   placeholder="Quản lý kho hàng" autofocus>
                            @error('label')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control sm:col-span-1">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Nhãn đối tượng triển khai <span class="text-error">*</span></span>
                                <span class="label-text-alt text-xs text-base-content/40">target_label</span>
                            </label>
                            <input type="text" name="target_label" value="{{ old('target_label', 'Tổ chức') }}"
                                   data-req="Vui lòng nhập nhãn đối tượng triển khai"
                                   class="input input-bordered input-sm w-full @error('target_label') input-error @enderror"
                                   placeholder="Tổ chức / HTX">
                            @error('target_label')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control sm:col-span-1">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Nhóm đối tượng <span class="text-error">*</span></span>
                                <span class="label-text-alt text-xs text-base-content/40">target_org_category</span>
                            </label>
                            <input type="text" name="target_org_category" value="{{ old('target_org_category', 'organization') }}"
                                   data-req="Vui lòng nhập nhóm đối tượng"
                                   class="input input-bordered input-sm w-full @error('target_org_category') input-error @enderror"
                                   placeholder="cooperative">
                            @error('target_org_category')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control sm:col-span-1">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Slug khảo sát sẵn sàng</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                            </label>
                            <input type="text" name="readiness_template_slug" value="{{ old('readiness_template_slug') }}"
                                   class="input input-bordered input-sm w-full font-mono @error('readiness_template_slug') input-error @enderror"
                                   placeholder="readiness_v1">
                            @error('readiness_template_slug')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control sm:col-span-1">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Slug khảo sát thu thập dữ liệu</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                            </label>
                            <input type="text" name="data_collection_template_slug" value="{{ old('data_collection_template_slug') }}"
                                   class="input input-bordered input-sm w-full font-mono @error('data_collection_template_slug') input-error @enderror"
                                   placeholder="data_collection_v1">
                            @error('data_collection_template_slug')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control sm:col-span-2">
                            <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                                <input type="checkbox" name="has_physical_assets" value="1"
                                       {{ old('has_physical_assets', true) ? 'checked' : '' }}
                                       class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0">
                                <div>
                                    <span class="text-sm font-medium group-hover:text-primary transition-colors">Có quản lý tài sản vật lý</span>
                                    <p class="text-xs text-base-content/50 mt-0.5">Khu / lô / cây — bật nếu vertical này theo dõi thực địa</p>
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

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.organizations.verticals.index', $organization) }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tạo vertical
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
    @vite(['Modules/Deployment/resources/assets/sass/deployment.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tom-select.js',
        'Modules/Deployment/resources/assets/js/deployment.js',
    ], 'build/backend')
@endpush
