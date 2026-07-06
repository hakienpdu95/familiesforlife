@extends('layouts.backend')
@section('title', 'Sửa mục tiêu KPI')


@push('styles')
    @vite(['Modules/KpiGoal/resources/assets/sass/kpi-goal.scss'], 'build/backend')
@endpush

@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Sửa mục tiêu KPI</h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $goal->title }}</p>
    </div>
    <a href="{{ route('backend.kpi.goals.show', $goal) }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
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

<form method="POST" action="{{ route('backend.kpi.goals.update', $goal) }}" novalidate data-kpi-goal-form>
    @csrf @method('PUT')
    <input type="hidden" name="parent_goal_id" value="{{ $goal->parent_goal_id }}">

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Cards chính ──────────────────────────────────────────────── --}}
        <div class="space-y-5">

            {{-- Card: Thông tin mục tiêu --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">

                    <h2 class="card-title text-base mb-5">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Thông tin mục tiêu
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        {{-- Tổ chức: readonly sau khi tạo --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tổ chức</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không thể thay đổi</span>
                            </label>
                            <input type="text" value="{{ $orgName }}" readonly
                                   class="input input-bordered input-sm w-full bg-base-200 cursor-not-allowed">
                        </div>

                        {{-- Nhân viên: readonly sau khi tạo --}}
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Nhân viên</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không thể thay đổi</span>
                            </label>
                            <input type="text"
                                   value="{{ $goal->employee?->full_name }} ({{ $goal->employee?->employee_code }})"
                                   class="input input-bordered input-sm w-full bg-base-200" readonly>
                        </div>

                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tên mục tiêu <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="title" value="{{ old('title', $goal->title) }}"
                                   data-req="Vui lòng nhập tên mục tiêu"
                                   class="input input-bordered input-sm w-full @error('title') input-error @enderror"
                                   placeholder="VD: Đạt doanh số 500 triệu Q3">
                            @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mô tả</span>
                            </label>
                            <textarea name="description"
                                      class="jodit-editor textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                                      data-jodit-preset="compact">{{ old('description', $goal->description) }}</textarea>
                            @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Giá trị mục tiêu <span class="text-error">*</span></span>
                            </label>
                            <input type="number" name="target_value" value="{{ old('target_value', $goal->target_value) }}"
                                   step="any"
                                   data-req="Vui lòng nhập giá trị mục tiêu"
                                   class="input input-bordered input-sm w-full @error('target_value') input-error @enderror"
                                   placeholder="VD: 500">
                            @error('target_value')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Đơn vị</span>
                            </label>
                            <input type="text" name="unit" value="{{ old('unit', $goal->unit) }}"
                                   class="input input-bordered input-sm w-full @error('unit') input-error @enderror"
                                   placeholder="%, VND, tasks…">
                            @error('unit')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Hướng</span>
                            </label>
                            <select id="ts-direction" name="direction"
                                    class="select select-bordered select-sm w-full ts-init @error('direction') select-error @enderror"
                                    data-ts-placeholder="— Chọn hướng —">
                                @foreach($directions as $d)
                                <option value="{{ $d['value'] }}" {{ old('direction', $goal->direction->value) === $d['value'] ? 'selected' : '' }}>
                                    {{ $d['text'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('direction')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Trọng số</span>
                                <span class="label-text-alt text-xs text-base-content/40">Tổng active = 100%</span>
                            </label>
                            <div class="join">
                                <input type="number" name="weight_percent" value="{{ old('weight_percent', $goal->weight_percent) }}"
                                       min="1" max="100"
                                       class="input input-bordered input-sm w-full join-item @error('weight_percent') input-error @enderror"
                                       placeholder="10">
                                <span class="join-item btn btn-sm btn-disabled border-base-300 bg-base-200 font-normal">%</span>
                            </div>
                            @error('weight_percent')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>
                </div>
            </div>

            {{-- Card: Kỳ đánh giá --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">

                    <h2 class="card-title text-base mb-5">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Kỳ đánh giá
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Nhãn kỳ</span>
                            </label>
                            <input type="text" name="cycle_label" value="{{ old('cycle_label', $goal->cycle_label) }}"
                                   class="input input-bordered input-sm w-full font-mono @error('cycle_label') input-error @enderror"
                                   placeholder="VD: Q3-2024">
                            @error('cycle_label')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Bắt đầu</span>
                            </label>
                            <input type="text" name="cycle_start" id="fp-cycle-start"
                                   value="{{ old('cycle_start', $goal->cycle_start?->format('Y-m-d') ?? '') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('cycle_start') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('cycle_start')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Kết thúc</span>
                            </label>
                            <input type="text" name="cycle_end" id="fp-cycle-end"
                                   value="{{ old('cycle_end', $goal->cycle_end?->format('Y-m-d') ?? '') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('cycle_end') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('cycle_end')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>
                </div>
            </div>

        </div>{{-- /cards main --}}

        {{-- ── Sidebar sticky ───────────────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>

                    <div class="flex justify-between text-xs text-base-content/40 mb-4 px-0.5">
                        <span>Tạo {{ $goal->created_at->format('d/m/Y') }}</span>
                        <span>Sửa {{ $goal->updated_at->diffForHumans() }}</span>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.kpi.goals.show', $goal) }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
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

        </div>{{-- /sidebar --}}

    </div>{{-- /grid --}}
</form>

@endsection

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/jodit.js',
        'Modules/KpiGoal/resources/assets/js/kpi-goal.js',
    ], 'build/backend')
@endpush
