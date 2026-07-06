@extends('layouts.backend')
@section('title', 'Thêm nhân viên mới')


@section('content')
<div x-data="{
    tab: 'basic',
    tabFields: {
        basic:    ['organization_id', 'full_name', 'employee_code', 'email', 'branch_id', 'department_id', 'job_title_id', 'manager_id', 'employment_type'],
        personal: ['phone', 'gender', 'date_of_birth', 'national_id', 'national_id_issued', 'tax_code', 'locale', 'personal_email', 'work_location'],
        contract: ['hired_at', 'probation_end_date', 'contract_start', 'contract_end', 'salary_base'],
        contact:  ['address', 'emergency_contact_name', 'emergency_contact_phone', 'bank_account', 'bank_name', 'notes'],
    },
    errs: {{ Js::from($errors->keys()) }},
    errCount(t) { return this.tabFields[t].filter(f => this.errs.includes(f)).length; },
    init() {
        const order = ['basic', 'personal', 'contract', 'contact'];
        for (const t of order) { if (this.errCount(t) > 0) { this.tab = t; break; } }
    }
}">

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thêm nhân viên mới</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Điền thông tin hồ sơ nhân sự</p>
    </div>
    <a href="{{ route('backend.employees.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.employees.store') }}" novalidate data-employee-form>
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Card chính với tabs ──────────────────────────────────────── --}}
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
                        Vị trí & Tổ chức
                        <span x-show="errCount('basic') > 0" x-text="errCount('basic')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'personal'"
                            @click="tab = 'personal'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'personal'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Hồ sơ cá nhân
                        <span x-show="errCount('personal') > 0" x-text="errCount('personal')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'contract'"
                            @click="tab = 'contract'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'contract'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Hợp đồng & Lương
                        <span x-show="errCount('contract') > 0" x-text="errCount('contract')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'contact'"
                            @click="tab = 'contact'"
                            class="flex items-center gap-1.5 px-1 py-4 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'contact'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Liên hệ & Ghi chú
                        <span x-show="errCount('contact') > 0" x-text="errCount('contact')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                </nav>
            </div>

            {{-- Tab panels --}}
            <div class="p-6">

                {{-- Panel: Vị trí & Tổ chức --}}
                <div x-show="tab === 'basic'" data-tab-label="Vị trí & Tổ chức" class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Họ và tên <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="full_name" value="{{ old('full_name') }}"
                               data-req="Vui lòng nhập họ và tên"
                               class="input input-bordered input-sm w-full @error('full_name') input-error @enderror"
                               placeholder="VD: Nguyễn Văn A" autofocus>
                        @error('full_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
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
                                <span class="label-text font-medium">Mã nhân viên <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="employee_code" value="{{ old('employee_code') }}"
                                   data-req="Vui lòng nhập mã nhân viên"
                                   class="input input-bordered input-sm w-full font-mono @error('employee_code') input-error @enderror"
                                   placeholder="VD: NV001">
                            @error('employee_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Email công việc <span class="text-error">*</span></span>
                            </label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                   data-req="Vui lòng nhập email công việc"
                                   data-val-email="Email không đúng định dạng"
                                   class="input input-bordered input-sm w-full @error('email') input-error @enderror"
                                   placeholder="ten.ho@congty.com">
                            @error('email')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Chi nhánh <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-branch_id" name="branch_id"
                                    data-req="Vui lòng chọn chi nhánh"
                                    class="select select-bordered select-sm w-full ts-init @error('branch_id') select-error @enderror"
                                    data-ts-placeholder="— Chọn chi nhánh —"
                                    @if(!$orgLocked)
                                        data-org-api="{{ route('api.branches.options') }}"
                                        data-selected-value="{{ old('branch_id') }}"
                                    @endif>
                                <option value="">— Chọn chi nhánh —</option>
                                @if($orgLocked)
                                    @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ old('branch_id') == $b->id ? 'selected' : '' }}>
                                        {{ $b->name }} ({{ $b->code }})
                                    </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('branch_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Phòng ban <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-department_id" name="department_id"
                                    data-req="Vui lòng chọn phòng ban"
                                    class="select select-bordered select-sm w-full ts-init @error('department_id') select-error @enderror"
                                    data-ts-placeholder="— Chọn phòng ban —"
                                    @if(!$orgLocked)
                                        data-org-api="{{ route('api.departments.options') }}"
                                        data-selected-value="{{ old('department_id') }}"
                                    @endif>
                                <option value="">— Chọn phòng ban —</option>
                                @if($orgLocked)
                                    @foreach($departments as $d)
                                    <option value="{{ $d->id }}" {{ old('department_id') == $d->id ? 'selected' : '' }}>
                                        {{ $d->name }} ({{ $d->code }})
                                    </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('department_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Chức danh</span>
                            </label>
                            <select id="ts-job_title_id" name="job_title_id"
                                    class="select select-bordered select-sm w-full ts-init @error('job_title_id') select-error @enderror"
                                    data-ts-placeholder="— Chưa xác định —"
                                    @if(!$orgLocked)
                                        data-org-api="{{ route('api.jobtitle.options') }}"
                                        data-selected-value="{{ old('job_title_id') }}"
                                    @endif>
                                <option value="">— Chưa xác định —</option>
                                @if($orgLocked)
                                    @foreach($jobTitles as $jt)
                                    <option value="{{ $jt->id }}" {{ old('job_title_id') == $jt->id ? 'selected' : '' }}>
                                        {{ $jt->name }} (Lv.{{ $jt->level }})
                                    </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('job_title_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Quản lý trực tiếp</span>
                            </label>
                            <select id="ts-manager_id" name="manager_id"
                                    class="select select-bordered select-sm w-full ts-init @error('manager_id') select-error @enderror"
                                    data-ts-placeholder="— Không có —"
                                    @if(!$orgLocked)
                                        data-org-api="{{ route('api.employees.options') }}"
                                        data-selected-value="{{ old('manager_id') }}"
                                    @endif>
                                <option value="">— Không có —</option>
                                @if($orgLocked)
                                    @foreach($managers as $m)
                                    <option value="{{ $m->id }}" {{ old('manager_id') == $m->id ? 'selected' : '' }}>
                                        {{ $m->full_name }} ({{ $m->employee_code }})
                                    </option>
                                    @endforeach
                                @endif
                            </select>
                            @error('manager_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Loại hợp đồng <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-employment_type" name="employment_type"
                                class="select select-bordered select-sm w-full ts-init @error('employment_type') select-error @enderror"
                                data-ts-placeholder="— Chọn loại hợp đồng —">
                            <option value="">— Chọn loại hợp đồng —</option>
                            @foreach($employmentTypes as $t)
                            <option value="{{ $t['value'] }}" {{ old('employment_type', 'full_time') === $t['value'] ? 'selected' : '' }}>
                                {{ $t['text'] }}
                            </option>
                            @endforeach
                        </select>
                        @error('employment_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Tab footer: next --}}
                    <div class="flex justify-end pt-2">
                        <button type="button" @click="tab = 'personal'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Hồ sơ cá nhân
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- Panel: Hồ sơ cá nhân --}}
                <div x-show="tab === 'personal'" data-tab-label="Hồ sơ cá nhân" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Số điện thoại</span>
                            </label>
                            <input type="text" name="phone" value="{{ old('phone') }}"
                                   class="input input-bordered input-sm w-full @error('phone') input-error @enderror"
                                   placeholder="VD: 0901234567">
                            @error('phone')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Giới tính</span>
                            </label>
                            <select id="ts-gender" name="gender"
                                    class="select select-bordered select-sm w-full ts-init @error('gender') select-error @enderror"
                                    data-ts-placeholder="— Không xác định —">
                                <option value="">— Không xác định —</option>
                                <option value="male"   {{ old('gender') === 'male'   ? 'selected' : '' }}>Nam</option>
                                <option value="female" {{ old('gender') === 'female' ? 'selected' : '' }}>Nữ</option>
                                <option value="other"  {{ old('gender') === 'other'  ? 'selected' : '' }}>Khác</option>
                            </select>
                            @error('gender')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ngày sinh</span>
                            </label>
                            <input type="text" name="date_of_birth" id="fp-date-of-birth"
                                   value="{{ old('date_of_birth') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('date_of_birth') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('date_of_birth')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Số CCCD/CMND</span>
                                <span class="label-text-alt text-xs text-base-content/40">eKYC</span>
                            </label>
                            <input type="text" name="national_id" value="{{ old('national_id') }}"
                                   class="input input-bordered input-sm w-full font-mono @error('national_id') input-error @enderror"
                                   placeholder="VD: 012345678901">
                            @error('national_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ngày cấp CCCD</span>
                            </label>
                            <input type="text" name="national_id_issued" id="fp-national-id-issued"
                                   value="{{ old('national_id_issued') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('national_id_issued') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('national_id_issued')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mã số thuế cá nhân</span>
                                <span class="label-text-alt text-xs text-base-content/40">Quyết toán TNCN</span>
                            </label>
                            <input type="text" name="tax_code" value="{{ old('tax_code') }}"
                                   class="input input-bordered input-sm w-full font-mono @error('tax_code') input-error @enderror"
                                   placeholder="VD: 0123456789">
                            @error('tax_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ngôn ngữ giao diện</span>
                                <span class="label-text-alt text-xs text-base-content/40">Mặc định theo org</span>
                            </label>
                            <select id="ts-locale" name="locale"
                                    class="select select-bordered select-sm w-full ts-init @error('locale') select-error @enderror"
                                    data-ts-placeholder="— Mặc định theo org —">
                                <option value="">— Mặc định theo org —</option>
                                <option value="vi" {{ old('locale') === 'vi' ? 'selected' : '' }}>Tiếng Việt</option>
                                <option value="en" {{ old('locale') === 'en' ? 'selected' : '' }}>English</option>
                            </select>
                            @error('locale')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Email cá nhân</span>
                            </label>
                            <input type="email" name="personal_email" value="{{ old('personal_email') }}"
                                   class="input input-bordered input-sm w-full @error('personal_email') input-error @enderror"
                                   placeholder="VD: ten.ho@gmail.com">
                            @error('personal_email')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Nơi làm việc</span>
                            </label>
                            <select id="ts-work_location" name="work_location"
                                    class="select select-bordered select-sm w-full ts-init @error('work_location') select-error @enderror"
                                    data-ts-placeholder="— Chưa xác định —">
                                <option value="">— Chưa xác định —</option>
                                <option value="office"  {{ old('work_location') === 'office'  ? 'selected' : '' }}>Tại văn phòng</option>
                                <option value="remote"  {{ old('work_location') === 'remote'  ? 'selected' : '' }}>Làm việc từ xa</option>
                                <option value="hybrid"  {{ old('work_location') === 'hybrid'  ? 'selected' : '' }}>Kết hợp</option>
                            </select>
                            @error('work_location')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    {{-- Tab footer: prev / next --}}
                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'basic'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Vị trí & Tổ chức
                        </button>
                        <button type="button" @click="tab = 'contract'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Hợp đồng & Lương
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- Panel: Hợp đồng & Lương --}}
                <div x-show="tab === 'contract'" data-tab-label="Hợp đồng & Lương" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ngày vào làm</span>
                            </label>
                            <input type="text" name="hired_at" id="fp-hired-at"
                                   value="{{ old('hired_at') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('hired_at') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('hired_at')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Kết thúc thử việc</span>
                            </label>
                            <input type="text" name="probation_end_date" id="fp-probation-end-date"
                                   value="{{ old('probation_end_date') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('probation_end_date') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('probation_end_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Bắt đầu hợp đồng</span>
                            </label>
                            <input type="text" name="contract_start" id="fp-contract-start"
                                   value="{{ old('contract_start') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('contract_start') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('contract_start')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Hết hạn hợp đồng</span>
                            </label>
                            <input type="text" name="contract_end" id="fp-contract-end"
                                   value="{{ old('contract_end') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('contract_end') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            <p class="mt-1 text-base-content/40 text-xs">Để trống = không thời hạn</p>
                            @error('contract_end')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    @if(auth()->user()?->hasAnyRole(['System_Admin', 'HR']))
                    <div class="divider text-xs text-base-content/40">Thông tin lương (chỉ HR)</div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Lương cơ bản</span>
                        </label>
                        <input type="number" name="salary_base" value="{{ old('salary_base') }}"
                               step="1000"
                               class="input input-bordered input-sm w-full @error('salary_base') input-error @enderror"
                               placeholder="VD: 15000000">
                        <p class="mt-1 text-xs text-base-content/40">VND</p>
                        @error('salary_base')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        <input type="hidden" name="salary_currency" value="VND">
                    </div>
                    @endif

                    {{-- Tab footer: prev / next --}}
                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'personal'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Hồ sơ cá nhân
                        </button>
                        <button type="button" @click="tab = 'contact'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Liên hệ & Ghi chú
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- Panel: Liên hệ & Ghi chú --}}
                <div x-show="tab === 'contact'" data-tab-label="Liên hệ & Ghi chú" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Địa chỉ thường trú</span>
                            </label>
                            <textarea name="address" rows="2"
                                      class="textarea textarea-bordered textarea-sm w-full @error('address') textarea-error @enderror"
                                      placeholder="VD: 123 Nguyễn Huệ, Phường Bến Nghé, Quận 1, TP.HCM">{{ old('address') }}</textarea>
                            @error('address')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Người liên hệ khẩn cấp</span>
                            </label>
                            <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}"
                                   class="input input-bordered input-sm w-full @error('emergency_contact_name') input-error @enderror"
                                   placeholder="VD: Nguyễn Thị B">
                            @error('emergency_contact_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">SĐT khẩn cấp</span>
                            </label>
                            <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}"
                                   class="input input-bordered input-sm w-full @error('emergency_contact_phone') input-error @enderror"
                                   placeholder="VD: 0901234567">
                            @error('emergency_contact_phone')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    @if(auth()->user()?->hasAnyRole(['System_Admin', 'HR']))
                    <div class="divider text-xs text-base-content/40">Thông tin ngân hàng (chỉ HR)</div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Số tài khoản</span>
                            </label>
                            <input type="text" name="bank_account" value="{{ old('bank_account') }}"
                                   class="input input-bordered input-sm w-full font-mono @error('bank_account') input-error @enderror"
                                   placeholder="VD: 0123456789">
                            @error('bank_account')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ngân hàng</span>
                            </label>
                            <input type="text" name="bank_name" value="{{ old('bank_name') }}"
                                   class="input input-bordered input-sm w-full @error('bank_name') input-error @enderror"
                                   placeholder="VD: Vietcombank">
                            @error('bank_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>
                    @endif

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Ghi chú nội bộ HR</span>
                        </label>
                        <textarea name="notes"
                                  class="jodit-editor textarea textarea-bordered textarea-sm w-full @error('notes') textarea-error @enderror"
                                  data-jodit-preset="standard">{{ old('notes') }}</textarea>
                        <p class="mt-1 text-xs text-base-content/40">Không hiển thị với nhân viên</p>
                        @error('notes')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Tab footer: prev --}}
                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'contract'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Hợp đồng & Lương
                        </button>
                        <span class="text-xs text-base-content/40">Nhấn <strong>Tạo nhân viên</strong> ở bên phải khi xong</span>
                    </div>

                </div>

            </div>{{-- /tab panels --}}
        </div>{{-- /card chính --}}

        {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>

                    <div class="form-control mb-4">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">Trạng thái <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-status" name="status"
                                class="select select-bordered select-sm w-full ts-init @error('status') select-error @enderror"
                                data-ts-placeholder="— Chọn trạng thái —">
                            @foreach($statuses as $s)
                            <option value="{{ $s['value'] }}" {{ old('status', 'active') === $s['value'] ? 'selected' : '' }}>
                                {{ $s['text'] }}
                            </option>
                            @endforeach
                        </select>
                        @error('status')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.employees.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tạo nhân viên
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
    @vite(['Modules/Employee/resources/assets/sass/employee.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/jodit.js',
        'Modules/Employee/resources/assets/js/employee.js',
    ], 'build/backend')
@endpush
