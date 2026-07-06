@extends('layouts.backend')
@section('title', 'Tạo mẫu đánh giá')


@push('styles')
    @vite(['Modules/PerformanceReview/resources/assets/sass/performance-review.scss'], 'build/backend')
@endpush

@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Tạo mẫu đánh giá mới</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Định nghĩa bộ tiêu chí và trọng số</p>
    </div>
    <a href="{{ route('backend.review-templates.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.review-templates.store') }}" novalidate data-review-template-form>
@csrf

<div x-data="{
    criteria: [
        { criteria_key: '', criteria_name: '', weight: '', max_score: 5, description: '' }
    ],
    addCriteria() {
        this.criteria.push({ criteria_key: '', criteria_name: '', weight: '', max_score: 5, description: '' });
    },
    removeCriteria(i) {
        if (this.criteria.length > 1) this.criteria.splice(i, 1);
    },
    totalWeight() {
        return this.criteria.reduce((s, c) => s + (parseFloat(c.weight) || 0), 0);
    },
    slugify(v) { return v.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, ''); },
}">

<div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

    {{-- ── Cards chính ──────────────────────────────────────────────── --}}
    <div class="space-y-5">

        {{-- Card: Thông tin mẫu --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Thông tin mẫu
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
                                   class="input input-sm input-bordered w-full bg-base-200 cursor-not-allowed">
                            <p class="mt-1 text-xs text-base-content/40">Xác định từ tài khoản của bạn.</p>
                        @else
                            <select id="ts-organization" name="organization_id"
                                    class="select select-sm select-bordered w-full ts-init @error('organization_id') select-error @enderror"
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

                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tên mẫu <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               placeholder="VD: Đánh giá Q4/2026 — Kinh doanh"
                               data-req="Vui lòng nhập tên mẫu"
                               class="input input-sm input-bordered w-full @error('name') input-error @enderror"/>
                        @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Chu kỳ <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-period_type" name="period_type"
                                class="select select-sm select-bordered w-full ts-init"
                                data-ts-placeholder="— Chọn chu kỳ —">
                            @foreach(\Modules\PerformanceReview\Enums\PeriodType::cases() as $pt)
                            <option value="{{ $pt->value }}" {{ old('period_type', 'quarterly') == $pt->value ? 'selected' : '' }}>{{ $pt->label() }}</option>
                            @endforeach
                        </select>
                        @error('period_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Thang điểm tối đa <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-rating_scale" name="rating_scale"
                                class="select select-sm select-bordered w-full ts-init"
                                data-ts-placeholder="— Chọn thang điểm —">
                            <option value="5" {{ old('rating_scale', '5') == '5' ? 'selected' : '' }}>5 điểm</option>
                            <option value="10" {{ old('rating_scale') == '10' ? 'selected' : '' }}>10 điểm</option>
                        </select>
                        @error('rating_scale')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Áp dụng cho phòng ban chức năng</span>
                            <span class="label-text-alt text-xs text-base-content/40">Để trống = áp dụng tất cả</span>
                        </label>
                        <input type="text" name="apply_to_function" value="{{ old('apply_to_function') }}"
                               placeholder="VD: sales, marketing, hr"
                               class="input input-sm input-bordered w-full @error('apply_to_function') input-error @enderror"/>
                        @error('apply_to_function')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- Card: Tiêu chí đánh giá --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="card-title text-base">Tiêu chí đánh giá</h2>
                        <p class="text-xs text-base-content/40 mt-0.5">
                            Tổng trọng số:
                            <span :class="Math.abs(totalWeight()-100) < 0.01 ? 'text-success font-bold' : 'text-error font-bold'"
                                  x-text="totalWeight().toFixed(1) + '%'"></span>
                            (phải bằng 100%)
                        </p>
                    </div>
                    <button type="button" @click="addCriteria()" class="btn btn-ghost btn-sm gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Thêm tiêu chí
                    </button>
                </div>

                <div class="space-y-4">
                    <template x-for="(c, i) in criteria" :key="i">
                        <div class="border border-base-200 rounded-xl p-4 relative">
                            <button type="button" @click="removeCriteria(i)" x-show="criteria.length > 1"
                                    class="absolute top-3 right-3 btn btn-ghost btn-xs btn-square text-error">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>

                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div class="form-control">
                                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Tên tiêu chí <span class="text-error">*</span></span></label>
                                    <input type="text" :name="'criteria['+i+'][criteria_name]'" x-model="c.criteria_name"
                                           @input="c.criteria_key = slugify(c.criteria_name)"
                                           placeholder="VD: Chất lượng công việc"
                                           class="input input-xs input-bordered w-full"/>
                                </div>
                                <div class="form-control">
                                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Khóa định danh <span class="text-error">*</span></span></label>
                                    <input type="text" :name="'criteria['+i+'][criteria_key]'" x-model="c.criteria_key"
                                           placeholder="work_quality"
                                           class="input input-xs input-bordered w-full font-mono"/>
                                </div>
                                <div class="form-control">
                                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Trọng số (%) <span class="text-error">*</span></span></label>
                                    <input type="number" :name="'criteria['+i+'][weight]'" x-model="c.weight"
                                           min="0.01" max="100" step="0.5" placeholder="30"
                                           class="input input-xs input-bordered w-full"/>
                                </div>
                                <div class="form-control">
                                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Điểm tối đa <span class="text-error">*</span></span></label>
                                    <input type="number" :name="'criteria['+i+'][max_score]'" x-model="c.max_score"
                                           min="1" max="10" placeholder="5"
                                           class="input input-xs input-bordered w-full"/>
                                </div>
                            </div>

                            <div class="form-control">
                                <label class="label py-0.5"><span class="label-text text-xs font-medium">Hướng dẫn chấm điểm</span></label>
                                <input type="text" :name="'criteria['+i+'][description]'" x-model="c.description"
                                       placeholder="Mô tả cách đánh giá tiêu chí này..."
                                       class="input input-xs input-bordered w-full"/>
                            </div>
                        </div>
                    </template>
                </div>

            </div>
        </div>

    </div>{{-- /cards main --}}

    {{-- ── Sidebar sticky ───────────────────────────────────────────── --}}
    <div class="xl:sticky xl:top-4 space-y-4">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">

                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Lưu mẫu</p>

                <p class="text-xs text-base-content/50 mb-4">
                    Đảm bảo tổng trọng số = 100% trước khi lưu.
                </p>

                <div class="flex gap-2">
                    <a href="{{ route('backend.review-templates.index') }}"
                       class="btn btn-ghost btn-sm flex-1">Hủy</a>
                    <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tạo mẫu
                    </button>
                </div>

                <p class="text-center text-xs text-base-content/30 mt-2.5">
                    <span class="text-error">*</span> là trường bắt buộc
                </p>

            </div>
        </div>

    </div>{{-- /sidebar --}}

</div>{{-- /grid --}}
</div>{{-- /x-data --}}
</form>

@endsection

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'Modules/PerformanceReview/resources/assets/js/performance-review.js',
    ], 'build/backend')
@endpush
