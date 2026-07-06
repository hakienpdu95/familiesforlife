@extends('layouts.backend')
@section('title', 'Tạo dự án — ' . $vertical->label())

@push('styles')
    @vite(['Modules/Deployment/resources/assets/sass/deployment.scss'], 'build/backend')
@endpush

@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Tạo dự án mới</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            Vertical:
            <span class="badge badge-primary badge-outline badge-xs align-middle">{{ $vertical->code() }}</span>
        </p>
    </div>
    <a href="{{ route('deployment.projects.index', ['vertical' => $vertical->code()]) }}"
       class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

{{-- Error banner --}}
@if($errors->any())
<div class="flex items-start gap-3 bg-error/10 border border-error/30 rounded-lg py-3 px-4 mb-5 text-sm text-error">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST"
      action="{{ route('deployment.projects.store', ['vertical' => $vertical->code()]) }}"
      novalidate
      data-project-form>
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- Main card --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-5 gap-2">
                    <svg class="w-4 h-4 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Thông tin dự án
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- Tên dự án — full width --}}
                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5" for="field-name">
                            <span class="label-text font-medium">
                                Tên dự án <span class="text-error">*</span>
                            </span>
                        </label>
                        <input type="text"
                               id="field-name"
                               name="name"
                               value="{{ old('name') }}"
                               class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                               placeholder="VD: Triển khai Q3-2026"
                               data-req="Vui lòng nhập tên dự án.">
                        @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Mã dự án --}}
                    <div class="form-control">
                        <label class="label py-0 pb-1.5" for="field-code">
                            <span class="label-text font-medium">
                                Mã dự án <span class="text-error">*</span>
                            </span>
                            <span class="label-text-alt text-base-content/40 text-xs">Tự động in hoa</span>
                        </label>
                        <input type="text"
                               id="field-code"
                               name="code"
                               value="{{ old('code') }}"
                               class="input input-bordered input-sm w-full font-mono @error('code') input-error @enderror"
                               placeholder="VD: TXNG-Q3-2026"
                               data-req="Vui lòng nhập mã dự án.">
                        <p class="mt-1 text-xs text-base-content/40">
                            Chỉ dùng chữ HOA, số và dấu <code class="bg-base-200 px-1 rounded text-[10px]">-</code>
                        </p>
                        @error('code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>

                <div class="divider my-4 text-xs text-base-content/30">Lịch trình</div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- Ngày bắt đầu --}}
                    <div class="form-control">
                        <label class="label py-0 pb-1.5" for="fp-start-date">
                            <span class="label-text font-medium">Ngày bắt đầu</span>
                        </label>
                        <input type="text"
                               id="fp-start-date"
                               name="start_date"
                               value="{{ old('start_date') }}"
                               class="input input-bordered input-sm w-full fp-init @error('start_date') input-error @enderror"
                               placeholder="DD/MM/YYYY"
                               autocomplete="off">
                        @error('start_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Ngày kết thúc --}}
                    <div class="form-control">
                        <label class="label py-0 pb-1.5" for="fp-end-date">
                            <span class="label-text font-medium">Ngày kết thúc</span>
                        </label>
                        <input type="text"
                               id="fp-end-date"
                               name="end_date"
                               value="{{ old('end_date') }}"
                               class="input input-bordered input-sm w-full fp-init @error('end_date') input-error @enderror"
                               placeholder="DD/MM/YYYY"
                               autocomplete="off">
                        @error('end_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>

                {{-- Mô tả — outside grid --}}
                <div class="form-control mt-4">
                    <label class="label py-0 pb-1.5" for="field-description">
                        <span class="label-text font-medium">Mô tả</span>
                        <span class="label-text-alt text-base-content/40 text-xs">Tùy chọn</span>
                    </label>
                    <textarea id="field-description"
                              name="description"
                              rows="4"
                              class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                              placeholder="Mô tả ngắn về mục tiêu, phạm vi và các mốc quan trọng của dự án...">{{ old('description') }}</textarea>
                    @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

            </div>
        </div>

        {{-- Sidebar: publish block --}}
        <div class="xl:sticky xl:top-4 space-y-4">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
                        Xuất bản
                    </p>

                    <div class="form-control mb-4">
                        <label class="label py-0 pb-1" for="ts-status">
                            <span class="label-text text-xs font-medium">
                                Trạng thái <span class="text-error">*</span>
                            </span>
                        </label>
                        <select id="ts-status"
                                name="status"
                                class="select select-bordered select-sm w-full ts-init @error('status') select-error @enderror"
                                data-ts-placeholder="— Chọn trạng thái —">
                            <option value="">— Chọn trạng thái —</option>
                            @foreach(\Modules\Project\Enums\ProjectStatus::cases() as $s)
                            <option value="{{ $s->value }}" @selected(old('status', 'planning') === $s->value)>
                                {{ $s->label() }}
                            </option>
                            @endforeach
                        </select>
                        @error('status')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('deployment.projects.index', ['vertical' => $vertical->code()]) }}"
                           class="btn btn-ghost btn-sm flex-1">
                            Hủy
                        </a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tạo dự án
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

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/tom-select.js',
        'Modules/Deployment/resources/assets/js/deployment.js',
    ], 'build/backend')
@endpush
