@extends('layouts.backend')
@section('title', isset($customer) ? 'Sửa khách hàng' : 'Thêm khách hàng mới')

@push('styles')
@vite(['Modules/Customer/resources/assets/sass/customer.scss'], 'build/backend')
@endpush

@push('scripts')
@vite([
    'resources/js/modules/toastify.js',
    'resources/js/modules/flatpickr.js',
    'resources/js/modules/tom-select.js',
    'Modules/Customer/resources/assets/js/customer.js',
], 'build/backend')
@endpush

@section('content')
<div x-data="{
    type: {{ old('customer_type', $customer->customer_type->value ?? 1) }},
    tab: 'basic',
    submitting: false,
    tabFields: {
        basic:    ['customer_type', 'organization_id', 'first_name', 'last_name', 'company_name'],
        contact:  ['primary_email', 'primary_phone'],
        classify: ['tag_ids'],
    },
    errs: {{ Js::from($errors->keys()) }},
    errCount(t) { return this.tabFields[t].filter(f => this.errs.includes(f)).length; },
    init() {
        const order = ['basic', 'contact', 'classify'];
        for (const t of order) { if (this.errCount(t) > 0) { this.tab = t; break; } }
    }
}">

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">
            {{ isset($customer) ? 'Sửa: '.$customer->display_name : 'Thêm khách hàng mới' }}
        </h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            {{ isset($customer) ? 'Cập nhật thông tin khách hàng' : 'Tạo hồ sơ khách hàng mới trong hệ thống' }}
        </p>
    </div>
    <a href="{{ isset($customer) ? route('customer.show', $customer) : route('customer.index') }}"
       class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST"
      action="{{ isset($customer) ? route('customer.update', $customer) : route('customer.store') }}"
      data-customer-form
      novalidate
      @submit="submitting = true">
    @csrf
    @if(isset($customer)) @method('PUT') @endif

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

                    <button type="button" role="tab" :aria-selected="tab === 'contact'"
                            @click="tab = 'contact'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'contact'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Liên hệ & Địa chỉ
                        <span x-show="errCount('contact') > 0" x-text="errCount('contact')"
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

            <div class="p-6">

                {{-- ── Panel: Thông tin cơ bản ─────────────────────── --}}
                <div x-show="tab === 'basic'" data-tab-label="Thông tin cơ bản" class="space-y-4">

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
                                <option value="{{ $org->id }}" {{ old('organization_id', $customer?->organization_id ?? $defaultOrgId ?? '') == $org->id ? 'selected' : '' }}>
                                    {{ $org->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('organization_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            @isset($customer)
                            <p class="mt-1 text-xs text-warning">Đổi tổ chức có thể làm mất Người phụ trách / Nguồn hiện tại nếu không thuộc tổ chức mới.</p>
                            @endisset
                        @endif
                    </div>

                    {{-- Loại khách hàng --}}
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Loại khách hàng <span class="text-error">*</span></span>
                        </label>
                        <div class="flex gap-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="customer_type" value="1" class="radio radio-primary radio-sm"
                                       x-model.number="type"
                                       {{ old('customer_type', $customer->customer_type->value ?? 1) == 1 ? 'checked' : '' }}>
                                <span class="text-sm">Cá nhân</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="customer_type" value="2" class="radio radio-primary radio-sm"
                                       x-model.number="type"
                                       {{ old('customer_type', $customer->customer_type->value ?? 1) == 2 ? 'checked' : '' }}>
                                <span class="text-sm">Doanh nghiệp</span>
                            </label>
                        </div>
                        @error('customer_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Cá nhân --}}
                    <div x-show="type === 1" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Họ <span class="text-error">*</span></span>
                                </label>
                                <input type="text" name="first_name"
                                       value="{{ old('first_name', $customer->first_name ?? '') }}"
                                       data-req="Vui lòng nhập họ"
                                       class="input input-bordered input-sm w-full @error('first_name') input-error @enderror"
                                       placeholder="VD: Nguyễn">
                                @error('first_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Tên <span class="text-error">*</span></span>
                                </label>
                                <input type="text" name="last_name"
                                       value="{{ old('last_name', $customer->last_name ?? '') }}"
                                       data-req="Vui lòng nhập tên"
                                       class="input input-bordered input-sm w-full @error('last_name') input-error @enderror"
                                       placeholder="VD: Văn A">
                                @error('last_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Giới tính</span>
                                </label>
                                <select name="gender" id="ts-gender"
                                        class="select select-bordered select-sm w-full ts-init"
                                        data-ts-placeholder="— Không chọn —">
                                    <option value="">— Không chọn —</option>
                                    <option value="M" {{ old('gender', $customer->gender ?? '') === 'M' ? 'selected' : '' }}>Nam</option>
                                    <option value="F" {{ old('gender', $customer->gender ?? '') === 'F' ? 'selected' : '' }}>Nữ</option>
                                    <option value="O" {{ old('gender', $customer->gender ?? '') === 'O' ? 'selected' : '' }}>Khác</option>
                                </select>
                            </div>
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Ngày sinh</span>
                                </label>
                                <input type="text" name="date_of_birth" id="fp-date-of-birth"
                                       value="{{ old('date_of_birth', isset($customer) && $customer->date_of_birth ? $customer->date_of_birth->format('Y-m-d') : '') }}"
                                       class="input input-bordered input-sm w-full fp-init @error('date_of_birth') input-error @enderror"
                                       placeholder="DD/MM/YYYY">
                                @error('date_of_birth')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>

                    {{-- Doanh nghiệp --}}
                    <div x-show="type === 2" class="space-y-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tên doanh nghiệp <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="company_name"
                                   value="{{ old('company_name', $customer->company_name ?? '') }}"
                                   data-req="Vui lòng nhập tên doanh nghiệp"
                                   class="input input-bordered input-sm w-full @error('company_name') input-error @enderror"
                                   placeholder="VD: Công ty TNHH ABC">
                            @error('company_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Mã số thuế</span>
                                </label>
                                <input type="text" name="tax_code"
                                       value="{{ old('tax_code', $customer->tax_code ?? '') }}"
                                       class="input input-bordered input-sm w-full font-mono @error('tax_code') input-error @enderror"
                                       placeholder="VD: 0123456789">
                                @error('tax_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Quy mô công ty</span>
                                </label>
                                <select name="company_size" id="ts-company-size"
                                        class="select select-bordered select-sm w-full ts-init"
                                        data-ts-placeholder="— Chọn quy mô —">
                                    <option value="">— Chọn quy mô —</option>
                                    @foreach($sizes as $size)
                                    <option value="{{ $size['value'] }}"
                                            {{ old('company_size', $customer->company_size->value ?? '') == $size['value'] ? 'selected' : '' }}>
                                        {{ $size['label'] }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Ngành nghề</span>
                                </label>
                                <input type="text" name="industry"
                                       value="{{ old('industry', $customer->industry ?? '') }}"
                                       class="input input-bordered input-sm w-full"
                                       placeholder="VD: Công nghệ thông tin">
                            </div>
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Người đại diện</span>
                                </label>
                                <input type="text" name="representative_name"
                                       value="{{ old('representative_name', $customer->representative_name ?? '') }}"
                                       class="input input-bordered input-sm w-full"
                                       placeholder="VD: Nguyễn Văn B">
                            </div>
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Chức danh người đại diện</span>
                            </label>
                            <input type="text" name="representative_title"
                                   value="{{ old('representative_title', $customer->representative_title ?? '') }}"
                                   class="input input-bordered input-sm w-full"
                                   placeholder="VD: Giám đốc điều hành">
                        </div>
                    </div>

                    {{-- Custom meta fields --}}
                    @if($fieldDefs->count())
                    <div class="divider my-1 text-xs text-base-content/30">Trường tùy chỉnh</div>
                    @foreach($fieldDefs as $def)
                    @php
                        $metaVal = isset($customer) ? $customer->meta->firstWhere('definition_id', $def->id)?->getValue() : null;
                        $oldKey  = 'meta.'.$def->field_key;
                    @endphp
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">{{ $def->label }}{{ $def->is_required ? ' *' : '' }}</span>
                        </label>
                        @if($def->value_type->value === 4)
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="meta[{{ $def->field_key }}]" value="1"
                                   class="checkbox checkbox-sm checkbox-primary"
                                   {{ old($oldKey, $metaVal) ? 'checked' : '' }}>
                            <span class="text-sm text-base-content/60">{{ $def->label }}</span>
                        </label>
                        @elseif($def->value_type->value === 5)
                        <input type="text" name="meta[{{ $def->field_key }}]"
                               value="{{ old($oldKey, $metaVal ? \Carbon\Carbon::parse($metaVal)->format('Y-m-d') : '') }}"
                               class="input input-bordered input-sm w-full fp-init"
                               placeholder="DD/MM/YYYY">
                        @elseif($def->value_type->value === 2 || $def->value_type->value === 3)
                        <input type="number" name="meta[{{ $def->field_key }}]"
                               value="{{ old($oldKey, $metaVal) }}"
                               step="{{ $def->value_type->value === 3 ? '0.01' : '1' }}"
                               class="input input-bordered input-sm w-full">
                        @else
                        <input type="text" name="meta[{{ $def->field_key }}]"
                               value="{{ old($oldKey, $metaVal) }}"
                               class="input input-bordered input-sm w-full">
                        @endif
                    </div>
                    @endforeach
                    @endif

                    <div class="flex justify-end pt-2">
                        <button type="button" @click="tab = 'contact'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Liên hệ & Địa chỉ
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- ── Panel: Liên hệ & Địa chỉ ────────────────────── --}}
                <div x-show="tab === 'contact'" data-tab-label="Liên hệ & Địa chỉ" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Email chính</span>
                            </label>
                            <input type="email" name="primary_email"
                                   value="{{ old('primary_email', $customer->primary_email ?? '') }}"
                                   data-val-email="Email không đúng định dạng"
                                   class="input input-bordered input-sm w-full @error('primary_email') input-error @enderror"
                                   placeholder="email@company.com">
                            @error('primary_email')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Email phụ</span>
                            </label>
                            <input type="email" name="secondary_email"
                                   value="{{ old('secondary_email', $customer->secondary_email ?? '') }}"
                                   data-val-email="Email không đúng định dạng"
                                   class="input input-bordered input-sm w-full @error('secondary_email') input-error @enderror"
                                   placeholder="alt@company.com">
                            @error('secondary_email')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Điện thoại chính</span>
                            </label>
                            <input type="tel" name="primary_phone"
                                   value="{{ old('primary_phone', $customer->primary_phone ?? '') }}"
                                   class="input input-bordered input-sm w-full @error('primary_phone') input-error @enderror"
                                   placeholder="0901 234 567">
                            @error('primary_phone')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Điện thoại phụ</span>
                            </label>
                            <input type="tel" name="secondary_phone"
                                   value="{{ old('secondary_phone', $customer->secondary_phone ?? '') }}"
                                   class="input input-bordered input-sm w-full"
                                   placeholder="0901 234 568">
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Website</span>
                        </label>
                        <input type="url" name="website"
                               value="{{ old('website', $customer->website ?? '') }}"
                               data-val-url="URL phải bắt đầu bằng https://"
                               class="input input-bordered input-sm w-full @error('website') input-error @enderror"
                               placeholder="https://company.vn">
                        @error('website')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="divider my-1 text-xs text-base-content/30">Địa chỉ</div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tỉnh / Thành phố</span>
                            </label>
                            <select id="ts-province" name="province_code"
                                    class="select select-bordered select-sm w-full ts-init @error('province_code') select-error @enderror"
                                    data-ts-placeholder="Chọn tỉnh/thành...">
                                <option value="">Chọn tỉnh/thành...</option>
                                @foreach($provinces ?? [] as $p)
                                <option value="{{ $p->province_code }}"
                                        {{ old('province_code', $customer->province_code ?? '') === $p->province_code ? 'selected' : '' }}>
                                    {{ $p->name }}
                                </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="province_name" id="customer-province-name"
                                   value="{{ old('province_name', $customer->province_name ?? '') }}">
                            @error('province_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Phường / Xã</span>
                            </label>
                            <select id="ts-ward" name="ward_code"
                                    class="select select-bordered select-sm w-full @error('ward_code') select-error @enderror"
                                    data-selected-ward="{{ old('ward_code', $customer->ward_code ?? '') }}"
                                    {{ !old('province_code', $customer->province_code ?? null) ? 'disabled' : '' }}>
                                <option value="">{{ old('province_code', $customer->province_code ?? null) ? 'Chọn phường/xã...' : 'Chọn tỉnh trước...' }}</option>
                            </select>
                            <input type="hidden" name="ward_name" id="customer-ward-name"
                                   value="{{ old('ward_name', $customer->ward_name ?? '') }}">
                            @error('ward_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Địa chỉ cụ thể</span>
                        </label>
                        <input type="text" name="address_line"
                               value="{{ old('address_line', $customer->address_line ?? '') }}"
                               class="input input-bordered input-sm w-full"
                               placeholder="VD: 123 Nguyễn Trãi, Phường Bến Thành">
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'basic'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Thông tin cơ bản
                        </button>
                        <button type="button" @click="tab = 'classify'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Phân loại
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- ── Panel: Phân loại ─────────────────────────────── --}}
                <div x-show="tab === 'classify'" data-tab-label="Phân loại" class="space-y-4">

                    @if($tags->count())
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tags</span>
                        </label>
                        <select id="ts-tag-ids" name="tag_ids[]" multiple
                                class="select select-bordered select-sm w-full @error('tag_ids') select-error @enderror"
                                data-ts-placeholder="— Chọn tags —">
                            @foreach($tags as $tag)
                            <option value="{{ $tag->id }}"
                                    {{ in_array($tag->id, old('tag_ids', isset($customer) ? $customer->tags->pluck('id')->toArray() : [])) ? 'selected' : '' }}>
                                {{ $tag->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('tag_ids')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                    @endif

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Ghi chú nội bộ</span>
                        </label>
                        <textarea name="notes" rows="4"
                                  class="textarea textarea-bordered textarea-sm w-full"
                                  placeholder="Ghi chú riêng, không hiển thị cho khách hàng...">{{ old('notes', $customer->notes ?? '') }}</textarea>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'contact'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Liên hệ & Địa chỉ
                        </button>
                        <span class="text-xs text-base-content/40">Nhấn <strong>{{ isset($customer) ? 'Lưu lại' : 'Tạo khách hàng' }}</strong> ở bên phải khi xong</span>
                    </div>

                </div>

            </div>{{-- /tab panels --}}
        </div>{{-- /main card --}}

        {{-- ── Sidebar sticky ──────────────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>

                    {{-- Lifecycle stage --}}
                    <div class="form-control mb-3">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">Tình trạng <span class="text-error">*</span></span>
                        </label>
                        <select name="lifecycle_stage" id="ts-lifecycle-stage"
                                class="select select-bordered select-sm w-full ts-init @error('lifecycle_stage') select-error @enderror"
                                data-ts-placeholder="— Chọn tình trạng —">
                            <option value="">— Chọn tình trạng —</option>
                            @foreach($stages as $stage)
                            <option value="{{ $stage['value'] }}"
                                    {{ old('lifecycle_stage', $customer->lifecycle_stage->value ?? 1) == $stage['value'] ? 'selected' : '' }}>
                                {{ $stage['label'] }}
                            </option>
                            @endforeach
                        </select>
                        @error('lifecycle_stage')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Source --}}
                    <div class="form-control mb-3">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">Nguồn</span>
                        </label>
                        <select name="source_id" id="ts-source-id"
                                class="select select-bordered select-sm w-full ts-init"
                                data-ts-placeholder="— Chọn nguồn —">
                            <option value="">— Chọn nguồn —</option>
                            @foreach($sources as $source)
                            <option value="{{ $source->id }}"
                                    {{ old('source_id', $customer->source_id ?? '') == $source->id ? 'selected' : '' }}>
                                {{ $source->label }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Assigned to --}}
                    <div class="form-control mb-3">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">Người phụ trách</span>
                        </label>
                        <select name="assigned_to" id="customer-assigned"
                                class="select select-bordered select-sm w-full ts-init"
                                data-ts-placeholder="— Chưa phân công —"
                                @if(!$orgLocked)
                                    data-org-api="{{ route('api.employees.options') }}"
                                    data-org-api-extra="&value=user_id"
                                    data-selected-value="{{ old('assigned_to', $customer->assigned_to ?? '') }}"
                                @endif>
                            <option value="">— Chưa phân công —</option>
                            @if($orgLocked)
                                @foreach($assignableEmployees as $e)
                                <option value="{{ $e->user_id }}" {{ old('assigned_to', $customer->assigned_to ?? '') == $e->user_id ? 'selected' : '' }}>
                                    {{ $e->full_name }} ({{ $e->employee_code }})
                                </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    @isset($customer)
                    <div class="flex justify-between text-xs text-base-content/40 mb-4 px-0.5">
                        <span>Tạo {{ $customer->created_at->format('d/m/Y') }}</span>
                        <span>Sửa {{ $customer->updated_at->diffForHumans() }}</span>
                    </div>
                    @endisset

                    <div class="flex gap-2">
                        <a href="{{ isset($customer) ? route('customer.show', $customer) : route('customer.index') }}"
                           class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5"
                                :disabled="submitting"
                                :class="{ 'btn-disabled': submitting }">
                            <span x-show="submitting" class="loading loading-spinner loading-xs"></span>
                            <svg x-show="!submitting" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="{{ isset($customer) ? 'M5 13l4 4L19 7' : 'M12 4v16m8-8H4' }}"/>
                            </svg>
                            <span x-text="submitting ? 'Đang lưu...' : '{{ isset($customer) ? 'Lưu lại' : 'Tạo khách hàng' }}'"></span>
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
</div>{{-- /x-data --}}
@endsection
