@extends('layouts.backend')
@section('title', 'Tạo dự án mới')


@section('content')
<div x-data="{
    tab: 'basic',
    tabFields: {
        basic:    ['organization_id', 'name', 'code'],
        scope:    ['owner_id'],
        timeline: []
    },
    errs: {{ Js::from($errors->keys()) }},
    errCount(t) {
        return this.tabFields[t].filter(f => this.errs.includes(f)).length;
    },
    init() {
        const order = ['basic', 'scope', 'timeline'];
        for (const t of order) {
            if (this.errCount(t) > 0) { this.tab = t; break; }
        }
    }
}">

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Tạo dự án mới</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Điền thông tin chi tiết dự án</p>
    </div>
    <a href="{{ route('backend.projects.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.projects.store') }}" novalidate data-project-form>
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Card chính với tab ──────────────────────────────────────────── --}}
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
                        Thông tin cơ bản
                        <span x-show="errCount('basic') > 0" x-text="errCount('basic')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'scope'"
                            @click="tab = 'scope'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'scope'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Phạm vi & Phụ trách
                        <span x-show="errCount('scope') > 0" x-text="errCount('scope')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'timeline'"
                            @click="tab = 'timeline'"
                            class="flex items-center gap-1.5 px-1 py-4 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'timeline'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Thời gian & Ngân sách
                        <span x-show="errCount('timeline') > 0" x-text="errCount('timeline')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                </nav>
            </div>

            {{-- Tab panels --}}
            <div class="p-6">

                {{-- Panel: Thông tin cơ bản --}}
                <div x-show="tab === 'basic'" data-tab-label="Thông tin cơ bản" class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tên dự án <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               data-req="Vui lòng nhập tên dự án"
                               class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                               placeholder="VD: Dự án phát triển sản phẩm X" autofocus>
                        @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

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
                                <span class="label-text font-medium">Mã dự án <span class="text-error">*</span></span>
                                <span class="label-text-alt text-xs text-base-content/40">VD: PRJ2026-001</span>
                            </label>
                            <input type="text" name="code" value="{{ old('code') }}"
                                   data-req="Vui lòng nhập mã dự án"
                                   data-val-maxlength="50"
                                   class="input input-bordered input-sm w-full font-mono @error('code') input-error @enderror"
                                   placeholder="PRJ2026-001" maxlength="50">
                            @error('code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Phân loại</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                            </label>
                            <input type="text" name="category" value="{{ old('category') }}"
                                   class="input input-bordered input-sm w-full @error('category') input-error @enderror"
                                   placeholder="VD: internal, client, rd...">
                            @error('category')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mô tả dự án</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <textarea name="description"
                                  class="jodit-editor textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                                  data-jodit-preset="standard">{{ old('description') }}</textarea>
                        @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Tab footer: next --}}
                    <div class="flex justify-end pt-2">
                        <button type="button" @click="tab = 'scope'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Phạm vi & Phụ trách
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- Panel: Phạm vi & Phụ trách --}}
                <div x-show="tab === 'scope'" data-tab-label="Phạm vi & Phụ trách" class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Người phụ trách <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-owner" name="owner_id"
                                class="select select-bordered select-sm w-full ts-init @error('owner_id') select-error @enderror"
                                data-ts-placeholder="— Chọn người phụ trách —"
                                data-req="Vui lòng chọn người phụ trách"
                                @if(!$orgLocked)
                                    data-org-api="{{ route('api.employees.options') }}"
                                    data-selected-value="{{ old('owner_id') }}"
                                @endif>
                            <option value="">— Chọn người phụ trách —</option>
                            @if($orgLocked)
                                @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ old('owner_id') == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->full_name }} ({{ $emp->employee_code }})
                                </option>
                                @endforeach
                            @endif
                        </select>
                        @error('owner_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Chi nhánh chủ trì</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                            </label>
                            <select id="ts-branch" name="branch_id"
                                    class="select select-bordered select-sm w-full ts-init @error('branch_id') select-error @enderror"
                                    data-ts-placeholder="— Không xác định —"
                                    @if(!$orgLocked)
                                        data-org-api="{{ route('api.branches.options') }}"
                                        data-selected-value="{{ old('branch_id') }}"
                                    @endif>
                                <option value="">— Không xác định —</option>
                                @if($orgLocked)
                                    @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }} ({{ $branch->code }})
                                    </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('branch_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Phòng ban chủ trì</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                            </label>
                            <select id="ts-department" name="department_id"
                                    class="select select-bordered select-sm w-full ts-init @error('department_id') select-error @enderror"
                                    data-ts-placeholder="— Không xác định —"
                                    @if(!$orgLocked)
                                        data-org-api="{{ route('api.departments.options') }}"
                                        data-selected-value="{{ old('department_id') }}"
                                    @endif>
                                <option value="">— Không xác định —</option>
                                @if($orgLocked)
                                    @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }} ({{ $dept->code }})
                                    </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('department_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    {{-- Tab footer: prev / next --}}
                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'basic'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Thông tin cơ bản
                        </button>
                        <button type="button" @click="tab = 'timeline'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Thời gian & Ngân sách
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- Panel: Thời gian & Ngân sách --}}
                <div x-show="tab === 'timeline'" data-tab-label="Thời gian & Ngân sách" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ngày bắt đầu</span>
                            </label>
                            <input type="text" name="start_date" id="fp-start-date"
                                   value="{{ old('start_date') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('start_date') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('start_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ngày kết thúc</span>
                            </label>
                            <input type="text" name="end_date" id="fp-end-date"
                                   value="{{ old('end_date') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('end_date') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('end_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ngân sách</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                            </label>
                            <input type="number" name="budget" value="{{ old('budget') }}"
                                   min="0" step="1000"
                                   class="input input-bordered input-sm w-full @error('budget') input-error @enderror"
                                   placeholder="0">
                            @error('budget')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Đơn vị tiền tệ</span>
                            </label>
                            <input type="text" name="currency" value="{{ old('currency', 'VND') }}"
                                   maxlength="3"
                                   class="input input-bordered input-sm w-full font-mono @error('currency') input-error @enderror"
                                   placeholder="VND">
                            <p class="mt-1 text-xs text-base-content/40">3 ký tự viết hoa, VD: VND, USD</p>
                            @error('currency')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    {{-- Tab footer: prev --}}
                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'scope'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Phạm vi & Phụ trách
                        </button>
                        <span class="text-xs text-base-content/40">Điền xong? Nhấn <strong>Tạo dự án</strong> ở bên phải</span>
                    </div>

                </div>

            </div>{{-- /tab panels --}}
        </div>{{-- /card chính --}}

        {{-- ── Sidebar ──────────────────────────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
                        Xuất bản
                    </p>

                    <div class="form-control mb-3">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">Trạng thái <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-status" name="status"
                                class="select select-bordered select-sm w-full ts-init @error('status') select-error @enderror"
                                data-ts-placeholder="— Chọn trạng thái —">
                            @foreach($statuses as $s)
                            <option value="{{ $s['value'] }}" {{ old('status', 'planning') === $s['value'] ? 'selected' : '' }}>
                                {{ $s['text'] }}
                            </option>
                            @endforeach
                        </select>
                        @error('status')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control mb-4">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">Mức ưu tiên <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-priority" name="priority"
                                class="select select-bordered select-sm w-full ts-init @error('priority') select-error @enderror"
                                data-ts-placeholder="— Chọn mức ưu tiên —">
                            @foreach($priorities as $p)
                            <option value="{{ $p['value'] }}" {{ old('priority', 'medium') === $p['value'] ? 'selected' : '' }}>
                                {{ $p['text'] }}
                            </option>
                            @endforeach
                        </select>
                        @error('priority')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.projects.index') }}"
                           class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tạo dự án
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
    @vite(['Modules/Project/resources/assets/sass/project.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/jodit.js',
        'Modules/Project/resources/assets/js/project.js',
    ], 'build/backend')
@endpush
