@extends('layouts.backend')
@section('title', 'Tạo khảo sát mới')


@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Tạo khảo sát mới</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Điền thông tin cơ bản để bắt đầu xây dựng khảo sát</p>
    </div>
    <a href="{{ route('backend.surveys.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.surveys.store') }}" novalidate data-survey-form>
    @csrf

    <div class="space-y-5">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
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
                                <option value="{{ $org->id }}" {{ old('organization_id', $defaultOrgId ?? '') == $org->id ? 'selected' : '' }}>
                                    {{ $org->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('organization_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        @endif
                    </div>

                    {{-- Tiêu đề --}}
                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tiêu đề <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="title" value="{{ old('title') }}"
                               data-req="Vui lòng nhập tiêu đề khảo sát"
                               class="input input-bordered input-sm w-full @error('title') input-error @enderror"
                               placeholder="VD: Khảo sát độ hài lòng Q1 2026"
                               autofocus>
                        @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Mô tả --}}
                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mô tả</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <textarea name="description" rows="3"
                                  class="textarea textarea-bordered w-full text-sm @error('description') textarea-error @enderror"
                                  placeholder="Mô tả ngắn về mục đích hoặc nội dung khảo sát...">{{ old('description') }}</textarea>
                        @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Version --}}
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Version</span>
                            <span class="label-text-alt text-xs text-base-content/40">Mặc định: 1</span>
                        </label>
                        <input type="number" name="version" value="{{ old('version', 1) }}"
                               min="1" max="9999"
                               class="input input-bordered input-sm w-full @error('version') input-error @enderror"
                               placeholder="1">
                        <p class="mt-1 text-xs text-base-content/40">Phiên bản khảo sát, tăng khi cấu trúc thay đổi lớn</p>
                        @error('version')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Turnstile site --}}
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Turnstile site</span>
                            <span class="label-text-alt text-xs text-base-content/40">Bot protection khi submit</span>
                        </label>
                        <select name="turnstile_site_id"
                                class="select select-bordered select-sm w-full @error('turnstile_site_id') select-error @enderror">
                            <option value="">— Không dùng Turnstile —</option>
                            @foreach($turnstileSites as $site)
                            <option value="{{ $site->id }}" {{ old('turnstile_site_id') == $site->id ? 'selected' : '' }}>
                                {{ $site->name }}
                            </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-base-content/40">
                            Domain sẽ nhúng form khảo sát này.
                            <a href="{{ route('backend.turnstile-sites.index') }}" class="link link-primary">Quản lý Turnstile sites</a>
                        </p>
                        @error('turnstile_site_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Submit bar --}}
    <div class="flex gap-2 pt-4 mt-2 border-t border-base-200">
        <button type="submit" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tạo khảo sát
        </button>
        <a href="{{ route('backend.surveys.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
    </div>

</form>

@endsection

@push('styles')
    @vite(['Modules/Survey/resources/assets/sass/survey.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite(['resources/js/modules/tom-select.js'], 'build/backend')
    @vite([
        'Modules/Survey/resources/assets/js/survey.js',
    ], 'build/backend')
@endpush
