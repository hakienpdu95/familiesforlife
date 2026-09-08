@extends('layouts.backend')
@section('title', 'Sinh lệnh tạo Ý tưởng mới')

@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Sinh lệnh tạo Ý tưởng mới</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            Dán hàng loạt từ khóa, câu hỏi hóng được, hay ý tưởng vụn vặt vào đây — hệ thống ghép sẵn 1 câu lệnh
            (prompt) hoàn chỉnh để bạn copy sang ChatGPT/Claude, "nhào nặn" ra 5 Ý tưởng Bài viết sắc bén. Công cụ
            này KHÔNG gọi AI trong app, chỉ sinh và lưu lại câu lệnh.
        </p>
    </div>
    <a href="{{ route('backend.bulkideationpromptstudio.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.bulkideationpromptstudio.store') }}" novalidate data-bulk-ideation-form>
    @csrf

    <div class="space-y-5">

        {{-- ── Card chính ──────────────────────────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    Thông tin ý tưởng
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- Tên gọi cho prompt --}}
                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tên gọi cho prompt này <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="label" value="{{ old('label') }}"
                               data-req="Vui lòng nhập tên gọi cho prompt"
                               maxlength="{{ config('bulk_ideation_prompt_studio.limits.label_max', 150) }}"
                               class="input input-bordered input-sm w-full @error('label') input-error @enderror"
                               placeholder="VD: Ý tưởng bài viết — Hăm tã mùa hè" autofocus>
                        @error('label')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Chuyên mục --}}
                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Chuyên mục</span>
                            <span class="label-text-alt text-xs text-base-content/40">Để lấy ngữ cảnh thương hiệu</span>
                        </label>
                        <select id="ts-post_category_uuid" name="post_category_uuid"
                                data-detail-url-template="{{ route('backend.api.contentfoundation.category-foundations.show', ['category' => '__UUID__']) }}"
                                class="select select-bordered select-sm w-full ts-init @error('post_category_uuid') select-error @enderror"
                                data-ts-placeholder="— Chưa chọn —">
                            <option value="">— Chưa chọn —</option>
                            @foreach($categoryFoundations as $cat)
                                <option value="{{ $cat['uuid'] }}" {{ old('post_category_uuid') === $cat['uuid'] ? 'selected' : '' }}>
                                    {{ str_repeat('　', $cat['depth']) }}{{ $cat['name'] }}
                                </option>
                            @endforeach
                        </select>
                        <p id="category-foundation-hint" hidden class="mt-1 text-xs text-base-content/40"></p>
                        @error('post_category_uuid')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Mục tiêu bài viết --}}
                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mục tiêu bài viết</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <input type="text" name="business_goal" value="{{ old('business_goal') }}"
                               maxlength="{{ config('bulk_ideation_prompt_studio.limits.business_goal_max', 500) }}"
                               class="input input-bordered input-sm w-full @error('business_goal') input-error @enderror"
                               placeholder="VD: Giáo dục khách hàng về hăm tã, Tăng nhận diện bỉm mùa hè">
                        @error('business_goal')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>

                {{-- Kho nguyên liệu — full width, ngoài grid --}}
                <div class="form-control mt-4">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Kho nguyên liệu (Raw Inputs) <span class="text-error">*</span></span>
                        <span class="label-text-alt text-xs text-base-content/40">Mỗi ý 1 dòng</span>
                    </label>
                    <textarea name="raw_inputs" rows="12"
                              data-req="Vui lòng dán ít nhất 1 từ khóa/ý tưởng"
                              maxlength="{{ config('bulk_ideation_prompt_studio.limits.raw_inputs_max', 8000) }}"
                              class="textarea textarea-bordered textarea-sm w-full font-mono text-xs @error('raw_inputs') textarea-error @enderror"
                              placeholder="Dán các từ khóa, câu hỏi hóng được trên group, hoặc ý tưởng rời rạc vào đây (mỗi ý 1 dòng)...">{{ old('raw_inputs') }}</textarea>
                    @error('raw_inputs')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

            </div>
        </div>

        {{-- ── Xuất bản — full width, dưới card chính ─────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <p class="text-xs text-base-content/40">
                        <span class="text-error">*</span> là trường bắt buộc
                    </p>
                    <div class="flex gap-2">
                        <a href="{{ route('backend.bulkideationpromptstudio.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Sinh lệnh tạo Ý tưởng
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>

@endsection

@push('styles')
    @vite(['Modules/BulkIdeationPromptStudio/resources/assets/sass/bulk-ideation-prompt-studio.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tom-select.js',
        'Modules/BulkIdeationPromptStudio/resources/assets/js/bulk-ideation-prompt-studio.js',
    ], 'build/backend')
@endpush
