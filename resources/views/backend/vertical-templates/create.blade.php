@extends('layouts.backend')
@section('title', 'Thêm bản mẫu Vertical')

@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <div class="flex items-center gap-2 text-sm text-base-content/40 mb-1">
            <a href="{{ route('backend.vertical-templates.index') }}" class="hover:text-primary">Thư viện mẫu Vertical</a>
            <span>/</span>
            <span>Thêm bản mẫu</span>
        </div>
        <h1 class="text-2xl font-bold text-base-content">Thêm bản mẫu mới</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Bản mẫu dùng chung — tổ chức nhân bản hoặc tạo mới độc lập từ đây</p>
    </div>
    <a href="{{ route('backend.vertical-templates.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.vertical-templates.store') }}" novalidate data-vertical-template-form>
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Card chính ──────────────────────────────────────────────── --}}
        <div class="space-y-5">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <h2 class="card-title text-base mb-5">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Thông tin bản mẫu
                    </h2>

                    {{-- Tổ chức sở hữu — để trống = mẫu thư viện dùng chung; chọn tổ chức = tạo luôn bản instance riêng cho tổ chức đó --}}
                    <div class="form-control mb-4">
                        <label class="label py-0 pb-1.5" for="ts-organization">
                            <span class="label-text font-medium">Tổ chức sở hữu</span>
                            <span class="label-text-alt text-xs text-base-content/40">organization_id — không bắt buộc</span>
                        </label>
                        <select id="ts-organization"
                                name="organization_id"
                                class="select select-bordered select-sm w-full ts-init @error('organization_id') select-error @enderror"
                                data-ts-placeholder="— Mẫu thư viện dùng chung —">
                            <option value="">— Mẫu thư viện dùng chung —</option>
                            @foreach($organizations as $org)
                            <option value="{{ $org->id }}" @selected(old('organization_id') == $org->id)>
                                {{ $org->name }}
                            </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-base-content/40">Để trống nếu tạo mẫu dùng chung cho mọi tổ chức nhân bản. Chọn tổ chức nếu muốn tạo thẳng bản instance riêng cho tổ chức đó.</p>
                        @error('organization_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    @include('backend.vertical-templates._fields')
                </div>
            </div>
        </div>

        {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.vertical-templates.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tạo bản mẫu
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
