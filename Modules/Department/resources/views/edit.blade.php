@extends('layouts.backend')
@section('title', 'Sửa phòng ban: ' . $department->name)


@push('styles')
    @vite(['Modules/Department/resources/assets/sass/department.scss'], 'build/backend')
@endpush

@section('content')
<div x-data="{
    tab: 'basic',
    tabFields: {
        basic:   ['organization_id', 'name', 'code', 'function', 'parent_id', 'branch_id', 'merged_into_id'],
        detail:  ['budget_code', 'headcount_limit', 'effective_from', 'effective_to'],
        contact: ['internal_phone', 'internal_email'],
    },
    errs: {{ Js::from($errors->keys()) }},
    errCount(t) {
        return this.tabFields[t].filter(f => this.errs.includes(f)).length;
    },
    init() {
        const order = ['basic', 'detail', 'contact'];
        for (const t of order) {
            if (this.errCount(t) > 0) { this.tab = t; break; }
        }
    }
}">

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Sửa phòng ban</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            {{ $department->name }} <span class="font-mono opacity-60">({{ $department->code }})</span>
        </p>
    </div>
    <a href="{{ route('backend.departments.show', $department) }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Xem chi tiết
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

<form method="POST" action="{{ route('backend.departments.update', $department) }}" novalidate data-department-form>
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Card chính: tab nav + panels ──────────────────────────────── --}}
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

                    <button type="button" role="tab" :aria-selected="tab === 'detail'"
                            @click="tab = 'detail'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'detail'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Chi tiết
                        <span x-show="errCount('detail') > 0" x-text="errCount('detail')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'contact'"
                            @click="tab = 'contact'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'contact'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Liên hệ nội bộ
                        <span x-show="errCount('contact') > 0" x-text="errCount('contact')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                </nav>
            </div>

            <div class="p-6">

                {{-- ── Tab: Thông tin cơ bản ───────────────────────────── --}}
                <div x-show="tab === 'basic'" data-tab-label="Thông tin cơ bản" class="space-y-4">

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
                                    <option value="{{ $org->id }}" {{ old('organization_id', $department->organization_id) == $org->id ? 'selected' : '' }}>
                                        {{ $org->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('organization_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            @endif
                        </div>

                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tên phòng ban <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="name" value="{{ old('name', $department->name) }}"
                                   data-req="Vui lòng nhập tên phòng ban"
                                   class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                                   placeholder="VD: Phòng Kinh doanh">
                            @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mã phòng ban <span class="text-error">*</span></span>
                                <span class="label-text-alt text-base-content/40 text-xs">Duy nhất trong org</span>
                            </label>
                            <input type="text" name="code" value="{{ old('code', $department->code) }}"
                                   data-req="Vui lòng nhập mã phòng ban"
                                   class="input input-bordered input-sm w-full font-mono @error('code') input-error @enderror"
                                   placeholder="VD: KD01">
                            @error('code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Chức năng</span>
                            </label>
                            <select id="ts-function" name="function"
                                    class="select select-bordered select-sm w-full ts-init @error('function') select-error @enderror"
                                    data-ts-placeholder="— Không xác định —">
                                <option value="">— Không xác định —</option>
                                @foreach($functions as $f)
                                <option value="{{ $f['value'] }}"
                                        {{ old('function', $department->function?->value) === $f['value'] ? 'selected' : '' }}>
                                    {{ $f['text'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('function')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Chi nhánh</span>
                                <span class="label-text-alt text-base-content/40 text-xs">NULL = cấp tổ chức</span>
                            </label>
                            <select id="ts-branch" name="branch_id"
                                    class="select select-bordered select-sm w-full ts-init @error('branch_id') select-error @enderror"
                                    data-ts-placeholder="— Toàn tổ chức —"
                                    @if(!$orgLocked)
                                        data-branch-api="{{ route('api.branches.options') }}"
                                        data-selected-branch="{{ old('branch_id', $department->branch_id) }}"
                                    @endif>
                                <option value="">— Toàn tổ chức —</option>
                                @if($orgLocked)
                                    @foreach($branchOptions as $opt)
                                    <option value="{{ $opt['value'] }}"
                                            {{ old('branch_id', $department->branch_id) == $opt['value'] ? 'selected' : '' }}>
                                        {{ $opt['text'] }}
                                    </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('branch_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Phòng ban cha</span>
                            </label>
                            <select id="ts-parent" name="parent_id"
                                    class="select select-bordered select-sm w-full ts-init @error('parent_id') select-error @enderror"
                                    data-ts-placeholder="— Không có (root) —"
                                    @if(!$orgLocked)
                                        data-parent-api="{{ route('api.departments.options') }}"
                                        data-selected-parent="{{ old('parent_id', $department->parent_id) }}"
                                    @endif>
                                <option value="">— Không có (root) —</option>
                                @if($orgLocked)
                                    @foreach($parentOptions as $opt)
                                    <option value="{{ $opt['value'] }}"
                                            {{ old('parent_id', $department->parent_id) == $opt['value'] ? 'selected' : '' }}>
                                        {{ $opt['text'] }}
                                    </option>
                                    @endforeach
                                @endif
                            </select>
                            <p class="mt-1 text-xs text-base-content/40">Tối đa 3 cấp phân cấp</p>
                            @error('parent_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Sáp nhập vào</span>
                                <span class="label-text-alt text-xs text-warning">Bắt buộc khi trạng thái = Đã sáp nhập</span>
                            </label>
                            <select id="ts-merged-into" name="merged_into_id"
                                    class="select select-bordered select-sm w-full ts-init @error('merged_into_id') select-error @enderror"
                                    data-ts-placeholder="— Chọn phòng ban đích —">
                                <option value="">— Chọn phòng ban đích —</option>
                                @foreach($mergedIntoOptions as $opt)
                                <option value="{{ $opt['value'] }}"
                                        {{ old('merged_into_id', $department->merged_into_id) == $opt['value'] ? 'selected' : '' }}>
                                    {{ $opt['text'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('merged_into_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="button" @click="tab = 'detail'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Chi tiết
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- ── Tab: Chi tiết ───────────────────────────────────── --}}
                <div x-show="tab === 'detail'" data-tab-label="Chi tiết" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mã trung tâm chi phí</span>
                                <span class="label-text-alt text-base-content/40 text-xs">Budget code</span>
                            </label>
                            <input type="text" name="budget_code" value="{{ old('budget_code', $department->budget_code) }}"
                                   class="input input-bordered input-sm w-full font-mono @error('budget_code') input-error @enderror"
                                   placeholder="VD: CC-KD-001">
                            @error('budget_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Biên chế tối đa</span>
                                <span class="label-text-alt text-base-content/40 text-xs">Headcount limit</span>
                            </label>
                            <input type="number" name="headcount_limit"
                                   value="{{ old('headcount_limit', $department->headcount_limit) }}"
                                   min="1"
                                   class="input input-bordered input-sm w-full @error('headcount_limit') input-error @enderror"
                                   placeholder="VD: 20">
                            <p class="mt-1 text-xs text-base-content/40">Chỉ hiển thị cảnh báo, không chặn thêm nhân viên</p>
                            @error('headcount_limit')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ngày thành lập</span>
                            </label>
                            <input type="text" name="effective_from" id="fp-effective-from"
                                   value="{{ old('effective_from', $department->effective_from?->format('Y-m-d') ?? '') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('effective_from') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('effective_from')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ngày giải thể</span>
                            </label>
                            <input type="text" name="effective_to" id="fp-effective-to"
                                   value="{{ old('effective_to', $department->effective_to?->format('Y-m-d') ?? '') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('effective_to') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('effective_to')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Chức năng nhiệm vụ</span>
                        </label>
                        <textarea name="description" rows="4"
                                  class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                                  placeholder="Mô tả chức năng, nhiệm vụ của phòng ban...">{{ old('description', $department->description) }}</textarea>
                        @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'basic'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Thông tin cơ bản
                        </button>
                        <button type="button" @click="tab = 'contact'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Liên hệ nội bộ
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- ── Tab: Liên hệ nội bộ ─────────────────────────────── --}}
                <div x-show="tab === 'contact'" data-tab-label="Liên hệ nội bộ" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Điện thoại nội bộ</span>
                            </label>
                            <input type="text" name="internal_phone"
                                   value="{{ old('internal_phone', $department->internal_phone) }}"
                                   class="input input-bordered input-sm w-full @error('internal_phone') input-error @enderror"
                                   placeholder="VD: 101">
                            @error('internal_phone')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Email nội bộ</span>
                            </label>
                            <input type="email" name="internal_email"
                                   value="{{ old('internal_email', $department->internal_email) }}"
                                   data-val-email="Email không đúng định dạng"
                                   class="input input-bordered input-sm w-full @error('internal_email') input-error @enderror"
                                   placeholder="VD: kinhdoanh@company.vn">
                            @error('internal_email')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'detail'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Chi tiết
                        </button>
                        <span class="text-xs text-base-content/40">Nhấn <strong>Lưu lại</strong> ở bên phải khi xong</span>
                    </div>

                </div>

            </div>
        </div>

        {{-- ── Sidebar sticky: Xuất bản ───────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
                        Xuất bản
                    </p>

                    <div class="form-control mb-3">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">
                                Trạng thái <span class="text-error">*</span>
                            </span>
                        </label>
                        <select id="ts-status" name="status"
                                class="select select-bordered select-sm w-full ts-init @error('status') select-error @enderror"
                                data-ts-placeholder="— Chọn trạng thái —">
                            @foreach($statuses as $s)
                            <option value="{{ $s['value'] }}"
                                    {{ old('status', $department->status->value) === $s['value'] ? 'selected' : '' }}>
                                {{ $s['text'] }}
                            </option>
                            @endforeach
                        </select>
                        @error('status')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-between text-xs text-base-content/40 mb-4 px-0.5">
                        <span>Tạo {{ $department->created_at?->format('d/m/Y') }}</span>
                        <span>Sửa {{ $department->updated_at?->diffForHumans() }}</span>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.departments.show', $department) }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
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

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Thông tin</p>
                    <div class="space-y-1.5 text-xs text-base-content/60">
                        <div class="flex justify-between">
                            <span>Mã</span>
                            <span class="font-mono font-medium text-base-content">{{ $department->code }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Cấp</span>
                            <span class="font-medium text-base-content">{{ $department->depth }}</span>
                        </div>
                        @if($department->createdBy)
                        <div class="flex justify-between">
                            <span>Người tạo</span>
                            <span>{{ $department->createdBy->name }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>
</div>
@endsection

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/tom-select.js',
        'Modules/Department/resources/assets/js/department.js',
    ], 'build/backend')
@endpush
