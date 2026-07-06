@extends('layouts.backend')
@section('title', 'Tạo đánh giá hiệu suất')


@section('content')
<div x-data="{
    tab: 'info',
    tabFields: {
        info:     ['organization_id', 'employee_id', 'reviewer_id', 'template_id', 'period'],
        comments: [],
        scores:   []
    },
    errs: {{ Js::from($errors->keys()) }},
    errCount(t) {
        return this.tabFields[t].filter(f => this.errs.includes(f)).length;
    },
    init() {
        const order = ['info', 'comments', 'scores'];
        for (const t of order) {
            if (this.errCount(t) > 0) { this.tab = t; break; }
        }
    },
    criteria: [],
    scores: {},
    templates: {{ Js::from($templates->map(fn($t) => ['id' => $t->id, 'criteria' => $t->criteria->map(fn($c) => ['criteria_key' => $c->criteria_key, 'criteria_name' => $c->criteria_name, 'weight' => $c->weight, 'max_score' => $c->max_score, 'description' => $c->description])->values()->all()])->values()->all()) }},
    selectTemplate(id) {
        const tpl = this.templates.find(t => String(t.id) === String(id));
        this.criteria = tpl ? tpl.criteria : [];
        this.scores = {};
        this.criteria.forEach(c => { this.scores[c.criteria_key] = { score: 0, comment: '' }; });
    },
    totalWeight() { return this.criteria.reduce((s, c) => s + parseFloat(c.weight), 0); },
}">

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Tạo đánh giá hiệu suất</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Điền thông tin và chấm điểm tiêu chí</p>
    </div>
    <a href="{{ route('backend.performance-reviews.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.performance-reviews.store') }}" novalidate data-performance-review-form>
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Card chính với tab ──────────────────────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">

            {{-- Tab navigation --}}
            <div class="border-b border-base-200 px-6">
                <nav class="flex -mb-px" role="tablist" aria-label="Form sections">

                    <button type="button" role="tab" :aria-selected="tab === 'info'"
                            @click="tab = 'info'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'info'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Thông tin chung
                        <span x-show="errCount('info') > 0" x-text="errCount('info')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'comments'"
                            @click="tab = 'comments'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'comments'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Nhận xét
                        <span x-show="errCount('comments') > 0" x-text="errCount('comments')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'scores'"
                            @click="tab = 'scores'"
                            class="flex items-center gap-1.5 px-1 py-4 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'scores'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Chấm điểm
                        <span x-show="errCount('scores') > 0" x-text="errCount('scores')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                </nav>
            </div>

            {{-- Tab panels --}}
            <div class="p-6">

                {{-- Panel: Thông tin chung --}}
                <div x-show="tab === 'info'" data-tab-label="Thông tin chung" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

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

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Nhân viên được đánh giá <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-employee" name="employee_id"
                                    class="select select-bordered select-sm w-full @error('employee_id') select-error @enderror @if(!$orgLocked) ts-init @endif"
                                    @if($orgLocked)
                                        data-ts-remote-url="{{ route('api.employees.options') }}"
                                    @else
                                        data-org-api="{{ route('api.employees.options') }}"
                                        data-selected-value="{{ old('employee_id') }}"
                                    @endif
                                    data-ts-placeholder="— Chọn nhân viên —"
                                    data-req="Vui lòng chọn nhân viên được đánh giá">
                                @if($orgLocked && $selectedEmployee)
                                <option value="{{ $selectedEmployee->id }}" selected>
                                    {{ $selectedEmployee->full_name }} ({{ $selectedEmployee->employee_code }})
                                </option>
                                @endif
                            </select>
                            @error('employee_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Người đánh giá <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-reviewer" name="reviewer_id"
                                    class="select select-bordered select-sm w-full @error('reviewer_id') select-error @enderror @if(!$orgLocked) ts-init @endif"
                                    @if($orgLocked)
                                        data-ts-remote-url="{{ route('api.employees.options') }}"
                                    @else
                                        data-org-api="{{ route('api.employees.options') }}"
                                        data-selected-value="{{ old('reviewer_id') }}"
                                    @endif
                                    data-ts-placeholder="— Chọn người đánh giá —"
                                    data-req="Vui lòng chọn người đánh giá">
                                @if($orgLocked && $selectedReviewer)
                                <option value="{{ $selectedReviewer->id }}" selected>
                                    {{ $selectedReviewer->full_name }} ({{ $selectedReviewer->employee_code }})
                                </option>
                                @endif
                            </select>
                            @error('reviewer_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mẫu đánh giá <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-template" name="template_id"
                                    class="select select-bordered select-sm w-full @error('template_id') select-error @enderror"
                                    data-ts-placeholder="— Chọn mẫu đánh giá —"
                                    data-req="Vui lòng chọn mẫu đánh giá"
                                    @if(!$orgLocked)
                                        data-org-api-templates="{{ route('api.reviewtemplate.options') }}"
                                        data-selected-value="{{ old('template_id') }}"
                                    @endif>
                                <option value="">— Chọn mẫu đánh giá —</option>
                                @if($orgLocked)
                                    @foreach($templates as $tpl)
                                    <option value="{{ $tpl->id }}" {{ old('template_id') == $tpl->id ? 'selected' : '' }}>{{ $tpl->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @error('template_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Kỳ đánh giá <span class="text-error">*</span></span>
                                <span class="label-text-alt text-xs text-base-content/40">VD: 2026-Q1, 2026-H2</span>
                            </label>
                            <input type="text" name="period" value="{{ old('period') }}"
                                   data-req="Vui lòng nhập kỳ đánh giá"
                                   data-val-maxlength="20"
                                   class="input input-bordered input-sm w-full font-mono @error('period') input-error @enderror"
                                   placeholder="VD: 2026-Q1">
                            @error('period')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Từ ngày</span>
                            </label>
                            <input type="text" name="period_start" id="fp-period-start"
                                   value="{{ old('period_start') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('period_start') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('period_start')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Đến ngày</span>
                            </label>
                            <input type="text" name="period_end" id="fp-period-end"
                                   value="{{ old('period_end') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('period_end') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('period_end')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    {{-- Tab footer: next --}}
                    <div class="flex justify-end pt-2">
                        <button type="button" @click="tab = 'comments'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Nhận xét
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- Panel: Nhận xét --}}
                <div x-show="tab === 'comments'" data-tab-label="Nhận xét" class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Điểm mạnh</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <textarea name="strengths"
                                  class="jodit-editor textarea textarea-bordered textarea-sm w-full @error('strengths') textarea-error @enderror"
                                  data-jodit-preset="compact">{{ old('strengths') }}</textarea>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Cần cải thiện</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <textarea name="improvements"
                                  class="jodit-editor textarea textarea-bordered textarea-sm w-full @error('improvements') textarea-error @enderror"
                                  data-jodit-preset="compact">{{ old('improvements') }}</textarea>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mục tiêu kỳ sau</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <textarea name="goals_next_period"
                                  class="jodit-editor textarea textarea-bordered textarea-sm w-full @error('goals_next_period') textarea-error @enderror"
                                  data-jodit-preset="compact">{{ old('goals_next_period') }}</textarea>
                    </div>

                    {{-- Tab footer: prev / next --}}
                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'info'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Thông tin chung
                        </button>
                        <button type="button" @click="tab = 'scores'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Chấm điểm
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- Panel: Chấm điểm --}}
                <div x-show="tab === 'scores'" data-tab-label="Chấm điểm" class="space-y-4">

                    {{-- Chưa chọn mẫu --}}
                    <div x-show="criteria.length === 0" class="py-10 text-center text-base-content/40">
                        <svg class="w-10 h-10 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        <p class="text-sm">Vui lòng chọn mẫu đánh giá ở tab <strong>Thông tin chung</strong></p>
                    </div>

                    {{-- Danh sách tiêu chí --}}
                    <div x-show="criteria.length > 0" class="space-y-3">
                        <div class="flex items-center justify-between text-xs text-base-content/40 pb-1">
                            <span>Tổng trọng số: <span x-text="totalWeight().toFixed(1)"></span>%</span>
                        </div>

                        <template x-for="(c, i) in criteria" :key="c.criteria_key">
                            <div class="border border-base-200 rounded-xl p-4">
                                <input type="hidden" :name="'scores['+i+'][criteria_key]'" :value="c.criteria_key"/>

                                <div class="flex items-start justify-between gap-3 mb-2">
                                    <div>
                                        <p class="text-sm font-semibold" x-text="c.criteria_name"></p>
                                        <p class="text-xs text-base-content/40 mt-0.5">
                                            Trọng số <span x-text="c.weight"></span>% · Tối đa <span x-text="c.max_score"></span> điểm
                                        </p>
                                        <p class="text-xs text-base-content/50 mt-1" x-show="c.description" x-text="c.description"></p>
                                    </div>
                                    <div class="flex items-center gap-2 shrink-0">
                                        <input type="number"
                                               :name="'scores['+i+'][score]'"
                                               x-model="scores[c.criteria_key].score"
                                               :min="0" :max="c.max_score" step="0.5"
                                               class="input input-sm input-bordered w-20 text-center"/>
                                        <span class="text-xs text-base-content/40">/ <span x-text="c.max_score"></span></span>
                                    </div>
                                </div>

                                <div class="form-control">
                                    <input type="text"
                                           :name="'scores['+i+'][comment]'"
                                           x-model="scores[c.criteria_key].comment"
                                           placeholder="Nhận xét (tùy chọn)..."
                                           class="input input-xs input-bordered w-full"/>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Tab footer: prev --}}
                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'comments'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Nhận xét
                        </button>
                        <span class="text-xs text-base-content/40">Điền xong? Nhấn <strong>Lưu đánh giá</strong> ở bên phải</span>
                    </div>

                </div>

            </div>{{-- /tab panels --}}
        </div>{{-- /card chính --}}

        {{-- ── Sidebar ──────────────────────────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
                        Lưu đánh giá
                    </p>

                    <p class="text-xs text-base-content/50 mb-4">
                        Đánh giá sẽ được lưu ở trạng thái
                        <span class="badge badge-ghost badge-sm">Nháp</span>.
                        Bạn có thể nộp sau khi hoàn tất.
                    </p>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.performance-reviews.index') }}"
                           class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Lưu đánh giá
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
</div>
@endsection

@push('styles')
    @vite(['Modules/PerformanceReview/resources/assets/sass/performance-review.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/jodit.js',
        'Modules/PerformanceReview/resources/assets/js/performance-review.js',
    ], 'build/backend')
@endpush
