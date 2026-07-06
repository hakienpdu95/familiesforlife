@extends('layouts.backend')
@section('title', 'Thêm công việc')

@section('content')
<div x-data="{
    tab: 'basic',
    tabFields: {
        basic:   ['title', 'organization_id', 'project_id'],
        timing:  ['start_date', 'due_date'],
        classify: ['task_type', 'status', 'priority'],
    },
    errs: {{ Js::from($errors->keys()) }},
    errCount(t) {
        return this.tabFields[t].filter(f => this.errs.includes(f)).length;
    },
    init() {
        const order = Object.keys(this.tabFields);
        for (const t of order) {
            if (this.errCount(t) > 0) { this.tab = t; break; }
        }
    }
}">

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thêm công việc</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Tạo công việc mới cho dự án</p>
    </div>
    <a href="{{ route('backend.tasks.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.tasks.store') }}" novalidate data-task-form>
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Card chính: tab nav + panels ────────────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">

            {{-- Tab navigation --}}
            <div class="border-b border-base-200 px-6">
                <nav class="flex -mb-px" role="tablist" aria-label="Form sections">

                    <button type="button" role="tab" :aria-selected="tab === 'basic'"
                            @click="tab = 'basic'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'basic'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Thông tin
                        <span x-show="errCount('basic') > 0" x-text="errCount('basic')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'timing'"
                            @click="tab = 'timing'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'timing'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Thời gian
                        <span x-show="errCount('timing') > 0" x-text="errCount('timing')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'classify'"
                            @click="tab = 'classify'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'classify'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Phân loại
                        <span x-show="errCount('classify') > 0" x-text="errCount('classify')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                </nav>
            </div>

            {{-- Tab panels --}}
            <div class="p-6">

                {{-- Tab: Thông tin --}}
                <div x-show="tab === 'basic'" data-tab-label="Thông tin" class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tiêu đề <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="title" value="{{ old('title') }}"
                               class="input input-bordered input-sm w-full @error('title') input-error @enderror"
                               placeholder="Nhập tiêu đề công việc..." autofocus maxlength="500"
                               data-req="Vui lòng nhập tiêu đề công việc">
                        @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Tổ chức --}}
                    <div class="form-control">
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

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Dự án <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-project" name="project_id"
                                    class="select select-bordered select-sm w-full ts-init @error('project_id') select-error @enderror"
                                    data-ts-placeholder="— Chọn dự án —"
                                    data-req="Vui lòng chọn dự án"
                                    @if(!$orgLocked)
                                        data-org-api="{{ route('api.projects.options') }}"
                                        data-selected-value="{{ old('project_id') }}"
                                    @endif>
                                <option value="">— Chọn dự án —</option>
                                @if($orgLocked)
                                    @foreach($projects as $prj)
                                    <option value="{{ $prj->id }}" {{ old('project_id') == $prj->id ? 'selected' : '' }}>
                                        {{ $prj->name }} ({{ $prj->code }})
                                    </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('project_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Người thực hiện</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                            </label>
                            <select id="ts-employee" name="employee_id"
                                    class="select select-bordered select-sm w-full ts-init @error('employee_id') select-error @enderror"
                                    data-ts-placeholder="— Chưa phân công —"
                                    @if(!$orgLocked)
                                        data-org-api="{{ route('api.employees.options') }}"
                                        data-selected-value="{{ old('employee_id') }}"
                                    @endif>
                                <option value="">— Chưa phân công —</option>
                                @if($orgLocked)
                                    @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->full_name }}
                                    </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('employee_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mô tả</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <textarea name="description" rows="4"
                                  class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                                  placeholder="Mô tả chi tiết công việc...">{{ old('description') }}</textarea>
                        @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="button" @click="tab = 'timing'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Thời gian
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- Tab: Thời gian --}}
                <div x-show="tab === 'timing'" data-tab-label="Thời gian" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ngày bắt đầu</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                            </label>
                            <input type="text" name="start_date" id="fp-start-date"
                                   value="{{ old('start_date') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('start_date') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('start_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Hạn hoàn thành</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                            </label>
                            <input type="text" name="due_date" id="fp-due-date"
                                   value="{{ old('due_date') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('due_date') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('due_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Giờ dự kiến</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                            </label>
                            <input type="number" name="estimated_hours" value="{{ old('estimated_hours') }}"
                                   min="0" step="0.25"
                                   class="input input-bordered input-sm w-full @error('estimated_hours') input-error @enderror"
                                   placeholder="0.00">
                            @error('estimated_hours')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Story Points</span>
                                <span class="label-text-alt text-xs text-base-content/40">1 – 21</span>
                            </label>
                            <input type="number" name="story_points" value="{{ old('story_points') }}"
                                   min="1" max="21" step="1"
                                   class="input input-bordered input-sm w-full @error('story_points') input-error @enderror">
                            @error('story_points')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'basic'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Thông tin
                        </button>
                        <button type="button" @click="tab = 'classify'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Phân loại
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- Tab: Phân loại --}}
                <div x-show="tab === 'classify'" data-tab-label="Phân loại" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Loại công việc <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-task-type" name="task_type"
                                    class="select select-bordered select-sm w-full ts-init @error('task_type') select-error @enderror"
                                    data-ts-placeholder="— Chọn loại —"
                                    data-req="Vui lòng chọn loại công việc">
                                <option value="">— Chọn loại —</option>
                                @foreach($taskTypes as $t)
                                <option value="{{ $t['value'] }}" {{ old('task_type', 'task') === $t['value'] ? 'selected' : '' }}>
                                    {{ $t['text'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('task_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Trạng thái <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-status" name="status"
                                    class="select select-bordered select-sm w-full ts-init @error('status') select-error @enderror"
                                    data-ts-placeholder="— Chọn trạng thái —"
                                    data-req="Vui lòng chọn trạng thái">
                                <option value="">— Chọn trạng thái —</option>
                                @foreach($statuses as $s)
                                <option value="{{ $s['value'] }}" {{ old('status', 'todo') === $s['value'] ? 'selected' : '' }}>
                                    {{ $s['text'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('status')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mức ưu tiên <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-priority" name="priority"
                                    class="select select-bordered select-sm w-full ts-init @error('priority') select-error @enderror"
                                    data-ts-placeholder="— Chọn mức ưu tiên —"
                                    data-req="Vui lòng chọn mức ưu tiên">
                                <option value="">— Chọn mức ưu tiên —</option>
                                @foreach($priorities as $p)
                                <option value="{{ $p['value'] }}" {{ old('priority', 'medium') === $p['value'] ? 'selected' : '' }}>
                                    {{ $p['text'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('priority')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'timing'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Thời gian
                        </button>
                        <span class="text-xs text-base-content/40">Nhấn <strong>Tạo</strong> ở bên phải khi xong</span>
                    </div>

                </div>

            </div>
        </div>

        {{-- ── Sidebar ──────────────────────────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
                        Xuất bản
                    </p>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.tasks.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tạo
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
</div>
@endsection

@push('styles')
    @vite(['Modules/Task/resources/assets/sass/task.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/tom-select.js',
        'Modules/Task/resources/assets/js/task.js',
    ], 'build/backend')
@endpush
