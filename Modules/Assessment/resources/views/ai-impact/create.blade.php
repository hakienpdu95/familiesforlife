@extends('layouts.backend')
@section('title', 'Ghi nhận tác động AI')

@push('styles')
    @vite(['Modules/Assessment/resources/assets/sass/assessment.scss'], 'build/backend')
@endpush

@section('content')

<div class="flex items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Ghi nhận tác động AI</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Ghi lại kết quả cải thiện từ việc ứng dụng AI vào công việc</p>
    </div>
    <a href="{{ route('backend.ai-impact.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<div x-data="aiImpactForm()" class="max-w-2xl">
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">

            <form method="POST" action="{{ route('backend.ai-impact.store') }}"
                  novalidate data-ai-impact-form>
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- Tổ chức --}}
                    <div class="form-control sm:col-span-2">
                        <div class="p-4 rounded-lg {{ $isSuperAdmin ? 'bg-warning/5 border border-warning/20' : 'bg-base-200/50 border border-base-200' }}">
                            <p class="text-xs font-semibold uppercase tracking-wide text-base-content/50 mb-3">Tổ chức</p>

                            @if($isSuperAdmin)
                            <div class="form-control max-w-xs">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Chọn tổ chức <span class="text-error">*</span></span>
                                </label>
                                <select id="ts-organization_id" name="organization_id"
                                        class="select select-bordered select-sm w-full ts-init @error('organization_id') select-error @enderror"
                                        data-ts-placeholder="— Chọn tổ chức —"
                                        data-req="Vui lòng chọn tổ chức">
                                    <option value="">— Chọn tổ chức —</option>
                                    @foreach($organizations as $org)
                                    <option value="{{ $org->id }}" {{ old('organization_id') == $org->id ? 'selected' : '' }}>
                                        {{ $org->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('organization_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>
                            @else
                            <div class="flex items-center gap-2 text-sm text-base-content/70">
                                <svg class="w-4 h-4 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/>
                                </svg>
                                Thuộc: <strong>{{ $currentOrg?->name }}</strong>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Nhân viên --}}
                    <div class="form-control sm:col-span-2">
                        <label class="label py-1" for="ts-employee_id">
                            <span class="label-text text-xs font-medium">Nhân viên</span>
                        </label>
                        <select id="ts-employee_id" name="employee_id"
                                class="select select-bordered select-sm w-full ts-init @error('employee_id') select-error @enderror"
                                data-ts-placeholder="— Chọn nhân viên —">
                            <option value=""></option>
                            @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->full_name }}
                            </option>
                            @endforeach
                        </select>
                        @error('employee_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Danh mục tác động --}}
                    <div class="form-control">
                        <label class="label py-1" for="ts-impact_category">
                            <span class="label-text text-xs font-medium">Danh mục <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-impact_category" name="impact_category"
                                class="select select-bordered select-sm w-full ts-init @error('impact_category') select-error @enderror"
                                data-ts-placeholder="— Chọn danh mục —"
                                data-req="Vui lòng chọn danh mục">
                            <option value=""></option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->value }}" {{ old('impact_category') === $cat->value ? 'selected' : '' }}>
                                {{ match($cat->value) {
                                    'learning'     => 'Học tập & Phát triển',
                                    'productivity' => 'Năng suất làm việc',
                                    'quality'      => 'Chất lượng sản phẩm',
                                    'ai_adoption'  => 'Ứng dụng AI',
                                    'business'     => 'Tác động kinh doanh',
                                    default        => $cat->value,
                                } }}
                            </option>
                            @endforeach
                        </select>
                        @error('impact_category')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Loại chỉ số --}}
                    <div class="form-control">
                        <label class="label py-1" for="impact_type">
                            <span class="label-text text-xs font-medium">Chỉ số đo lường <span class="text-error">*</span></span>
                        </label>
                        <input type="text" id="impact_type" name="impact_type"
                               class="input input-bordered input-sm w-full @error('impact_type') input-error @enderror"
                               value="{{ old('impact_type') }}"
                               placeholder="VD: time_saving, productivity_gain...">
                        @error('impact_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Kỳ từ --}}
                    <div class="form-control">
                        <label class="label py-1" for="fp-period-start">
                            <span class="label-text text-xs font-medium">Kỳ bắt đầu <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="period_start" id="fp-period-start"
                               class="input input-bordered input-sm w-full fp-init @error('period_start') input-error @enderror"
                               value="{{ old('period_start') }}"
                               placeholder="DD/MM/YYYY">
                        @error('period_start')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Kỳ đến --}}
                    <div class="form-control">
                        <label class="label py-1" for="fp-period-end">
                            <span class="label-text text-xs font-medium">Kỳ kết thúc <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="period_end" id="fp-period-end"
                               class="input input-bordered input-sm w-full fp-init @error('period_end') input-error @enderror"
                               value="{{ old('period_end') }}"
                               placeholder="DD/MM/YYYY">
                        @error('period_end')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Giá trị baseline --}}
                    <div class="form-control">
                        <label class="label py-1" for="baseline_value">
                            <span class="label-text text-xs font-medium">Giá trị trước AI <span class="text-error">*</span></span>
                        </label>
                        <input type="number" id="baseline_value" name="baseline_value" step="0.01" min="0"
                               class="input input-bordered input-sm w-full @error('baseline_value') input-error @enderror"
                               value="{{ old('baseline_value') }}"
                               x-model.number="baselineVal"
                               placeholder="0">
                        @error('baseline_value')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Giá trị đạt được --}}
                    <div class="form-control">
                        <label class="label py-1" for="achieved_value">
                            <span class="label-text text-xs font-medium">Giá trị sau AI <span class="text-error">*</span></span>
                        </label>
                        <input type="number" id="achieved_value" name="achieved_value" step="0.01" min="0"
                               class="input input-bordered input-sm w-full @error('achieved_value') input-error @enderror"
                               value="{{ old('achieved_value') }}"
                               x-model.number="achievedVal"
                               placeholder="0">
                        <p class="mt-1 text-xs text-base-content/40">Hệ thống tự tính % cải thiện</p>
                        @error('achieved_value')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Chi phí đầu tư --}}
                    <div class="form-control">
                        <label class="label py-1" for="investment_cost">
                            <span class="label-text text-xs font-medium">Chi phí đầu tư AI (VND)</span>
                        </label>
                        <input type="number" id="investment_cost" name="investment_cost" step="1000" min="0"
                               class="input input-bordered input-sm w-full @error('investment_cost') input-error @enderror"
                               value="{{ old('investment_cost') }}"
                               x-model.number="investCost"
                               placeholder="0">
                        @error('investment_cost')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Giá trị lợi ích --}}
                    <div class="form-control">
                        <label class="label py-1" for="benefit_value">
                            <span class="label-text text-xs font-medium">Giá trị lợi ích thu về (VND)</span>
                        </label>
                        <input type="number" id="benefit_value" name="benefit_value" step="1000" min="0"
                               class="input input-bordered input-sm w-full @error('benefit_value') input-error @enderror"
                               value="{{ old('benefit_value') }}"
                               x-model.number="benefitVal"
                               placeholder="0">
                        <p class="mt-1 text-xs text-base-content/40">ROI = (lợi ích − đầu tư) / đầu tư × 100%</p>
                        @error('benefit_value')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Ghi chú --}}
                    <div class="form-control sm:col-span-2">
                        <label class="label py-1" for="notes">
                            <span class="label-text text-xs font-medium">Ghi chú / Mô tả</span>
                        </label>
                        <textarea id="notes" name="notes" rows="3"
                                  class="textarea textarea-bordered textarea-sm w-full @error('notes') textarea-error @enderror"
                                  placeholder="Mô tả cách bạn đã ứng dụng AI và kết quả cụ thể...">{{ old('notes') }}</textarea>
                        @error('notes')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>

                {{-- Preview ROI --}}
                <div class="mt-4 p-4 bg-base-200/60 rounded-xl" x-show="previewVisible" x-cloak>
                    <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wide mb-2">Xem trước kết quả tính toán</p>
                    <div class="flex flex-wrap gap-6 text-sm">
                        <div>
                            <span class="text-base-content/40 text-xs block">% Cải thiện</span>
                            <span class="font-bold text-success" x-text="improvementLabel">—</span>
                        </div>
                        <div x-show="roiVisible">
                            <span class="text-base-content/40 text-xs block">ROI</span>
                            <span class="font-bold text-accent" x-text="roiLabel">—</span>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3 mt-6">
                    <button type="submit" class="btn btn-primary btn-sm">Lưu bản ghi</button>
                    <a href="{{ route('backend.ai-impact.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
                </div>
            </form>

        </div>
    </div>
</div>

@endsection

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/tom-select.js',
        'Modules/Assessment/resources/assets/js/assessment.js',
    ], 'build/backend')
@endpush
