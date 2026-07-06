@extends('layouts.backend')
@section('title', 'Thêm nhiệm vụ mới')

@push('styles')
    @vite(['Modules/Assessment/resources/assets/sass/assessment.scss'], 'build/backend')
@endpush

@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thêm nhiệm vụ mới</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Môi trường: <strong>{{ $env->name }}</strong></p>
    </div>
    <a href="{{ route('backend.sandbox-admin.tasks', $env) }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Danh sách nhiệm vụ
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

<form method="POST" action="{{ route('backend.sandbox-admin.task.store', $env) }}"
      novalidate data-sandbox-task-form>
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Main column ─────────────────────────────────── --}}
        <div class="space-y-4">

            {{-- Card: Nội dung nhiệm vụ --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-base-content/50 mb-4">Nội dung nhiệm vụ</h3>
                    <div class="space-y-4">

                        {{-- Title --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5" for="title">
                                <span class="label-text text-xs font-medium">Tiêu đề nhiệm vụ <span class="text-error">*</span></span>
                            </label>
                            <input type="text" id="title" name="title"
                                   class="input input-bordered input-sm w-full @error('title') input-error @enderror"
                                   value="{{ old('title') }}"
                                   placeholder="VD: Soạn email từ chối lịch họp bằng AI">
                            @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Instruction --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5" for="instruction">
                                <span class="label-text text-xs font-medium">Hướng dẫn nhiệm vụ <span class="text-error">*</span></span>
                                <span class="label-text-alt text-xs text-base-content/40">Nhân viên đọc trước khi làm</span>
                            </label>
                            <textarea id="instruction" name="instruction" rows="6"
                                      class="textarea textarea-bordered textarea-sm w-full @error('instruction') textarea-error @enderror"
                                      placeholder="Mô tả tình huống cụ thể, yêu cầu đầu ra, ràng buộc cần tuân theo...">{{ old('instruction') }}</textarea>
                            <label class="label py-0.5">
                                <span class="label-text-alt text-xs text-base-content/40">Mô tả rõ: bối cảnh, yêu cầu, ràng buộc. Càng cụ thể nhân viên càng biết mình cần làm gì.</span>
                            </label>
                            @error('instruction')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Expected output --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5" for="expected_output">
                                <span class="label-text text-xs font-medium">Kết quả mong đợi</span>
                                <span class="label-text-alt text-xs text-base-content/40">Mô tả một bài làm tốt</span>
                            </label>
                            <textarea id="expected_output" name="expected_output" rows="3"
                                      class="textarea textarea-bordered textarea-sm w-full @error('expected_output') textarea-error @enderror"
                                      placeholder="VD: Email 150-200 từ, tiêu đề rõ ràng, nội dung chuyên nghiệp lịch sự...">{{ old('expected_output') }}</textarea>
                            @error('expected_output')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Scoring rubric --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5" for="scoring_rubric">
                                <span class="label-text text-xs font-medium">Tiêu chí chấm điểm (Rubric)</span>
                                <span class="label-text-alt text-xs text-base-content/40">Phân cách bằng |</span>
                            </label>
                            <input type="text" id="scoring_rubric" name="scoring_rubric"
                                   class="input input-bordered input-sm w-full @error('scoring_rubric') input-error @enderror"
                                   value="{{ old('scoring_rubric') }}"
                                   placeholder="Ngữ điệu chuyên nghiệp|Cấu trúc rõ ràng|Đề xuất giải pháp thay thế">
                            <label class="label py-0.5">
                                <span class="label-text-alt text-xs text-base-content/40">Hiển thị cho nhân viên để họ biết tiêu chí đánh giá</span>
                            </label>
                            @error('scoring_rubric')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- Card: Cài đặt --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-base-content/50 mb-4">Cài đặt nhiệm vụ</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        {{-- Time limit --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5" for="time_limit_minutes">
                                <span class="label-text text-xs font-medium">Giới hạn thời gian (phút) <span class="text-error">*</span></span>
                            </label>
                            <input type="number" id="time_limit_minutes" name="time_limit_minutes" min="5" max="180"
                                   class="input input-bordered input-sm w-full @error('time_limit_minutes') input-error @enderror"
                                   value="{{ old('time_limit_minutes', 30) }}">
                            <label class="label py-0.5">
                                <span class="label-text-alt text-xs text-base-content/40">5–180 phút. Ảnh hưởng điểm Năng suất.</span>
                            </label>
                            @error('time_limit_minutes')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Sort order --}}
                        <div class="form-control">
                            <label class="label py-0 pb-1.5" for="sort_order">
                                <span class="label-text text-xs font-medium">Thứ tự (độ khó)</span>
                            </label>
                            <input type="number" id="sort_order" name="sort_order" min="0"
                                   class="input input-bordered input-sm w-full"
                                   value="{{ old('sort_order', 0) }}">
                            <label class="label py-0.5">
                                <span class="label-text-alt text-xs text-base-content/40">0 = đầu tiên. Sắp xếp theo độ khó tăng dần.</span>
                            </label>
                        </div>

                        {{-- AI Tools allowed --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5" for="ai_tools_allowed">
                                <span class="label-text text-xs font-medium">Công cụ AI cho phép</span>
                                <span class="label-text-alt text-xs text-base-content/40">Phân cách bằng |</span>
                            </label>
                            <input type="text" id="ai_tools_allowed" name="ai_tools_allowed"
                                   class="input input-bordered input-sm w-full @error('ai_tools_allowed') input-error @enderror"
                                   value="{{ old('ai_tools_allowed', 'ChatGPT|Claude|Gemini') }}"
                                   placeholder="ChatGPT|Claude|Gemini|Copilot|NotebookLM">
                            <label class="label py-0.5">
                                <span class="label-text-alt text-xs text-base-content/40">Khai báo để nhân viên biết nên dùng công cụ nào. Ảnh hưởng điểm AI Adoption.</span>
                            </label>
                            @error('ai_tools_allowed')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        {{-- Target position --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5" for="target_position_code">
                                <span class="label-text text-xs font-medium">Vị trí mục tiêu (tuỳ chọn)</span>
                                <span class="label-text-alt text-xs text-base-content/40">Mã vị trí phù hợp với nhiệm vụ này</span>
                            </label>
                            <input type="text" id="target_position_code" name="target_position_code"
                                   class="input input-bordered input-sm w-full font-mono @error('target_position_code') input-error @enderror"
                                   value="{{ old('target_position_code') }}"
                                   placeholder="VD: SALES_EXEC, HR_STAFF, ALL">
                            @error('target_position_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
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
                            <span class="text-xs text-base-content/40 block mt-0.5">Nhân viên có thể thực hành</span>
                        </span>
                    </label>

                    <div class="border-t border-base-200 pt-3 rounded-lg bg-base-200/40 p-3 space-y-1">
                        <p class="text-xs font-medium text-base-content/60">Môi trường</p>
                        <p class="text-sm font-semibold">{{ $env->name }}</p>
                        <p class="text-xs text-base-content/40 font-mono">{{ $env->env_code }}</p>
                    </div>

                    <div class="border-t border-base-200 pt-4 flex flex-col gap-2">
                        <button type="submit" class="btn btn-primary btn-sm w-full">Tạo nhiệm vụ</button>
                        <a href="{{ route('backend.sandbox-admin.tasks', $env) }}" class="btn btn-ghost btn-sm w-full">Hủy</a>
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
        'Modules/Assessment/resources/assets/js/assessment.js',
    ], 'build/backend')
@endpush
