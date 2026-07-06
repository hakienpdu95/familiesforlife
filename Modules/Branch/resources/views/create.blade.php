@extends('layouts.backend')
@section('title', 'Thêm chi nhánh mới')


@push('styles')
    @vite(['Modules/Branch/resources/assets/sass/branch.scss'], 'build/backend')
@endpush

@section('content')
<div x-data="{
    tab: 'basic',
    tabFields: {
        basic:    ['name', 'code', 'type', 'organization_id'],
        contact:  ['phone', 'email'],
        address:  ['province_code', 'ward_code'],
        settings: [],
    },
    errs: {{ Js::from($errors->keys()) }},
    errCount(t) {
        return this.tabFields[t].filter(f => this.errs.includes(f)).length;
    },
    init() {
        const order = ['basic', 'contact', 'address', 'settings'];
        for (const t of order) {
            if (this.errCount(t) > 0) { this.tab = t; break; }
        }
    }
}">

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thêm chi nhánh mới</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Điền đầy đủ thông tin để tạo chi nhánh</p>
    </div>
    <a href="{{ route('backend.branches.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.branches.store') }}" novalidate data-branch-form>
    @csrf

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
                        Liên hệ
                        <span x-show="errCount('contact') > 0" x-text="errCount('contact')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'address'"
                            @click="tab = 'address'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'address'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Địa chỉ & Toạ độ
                        <span x-show="errCount('address') > 0" x-text="errCount('address')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'settings'"
                            @click="tab = 'settings'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'settings'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Cài đặt
                        <span x-show="errCount('settings') > 0" x-text="errCount('settings')"
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
                                    <option value="{{ $org->id }}" {{ old('organization_id', $defaultOrgId) == $org->id ? 'selected' : '' }}>
                                        {{ $org->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('organization_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            @endif
                        </div>

                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tên chi nhánh <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                   data-req="Vui lòng nhập tên chi nhánh"
                                   class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                                   placeholder="VD: Chi nhánh Hà Nội">
                            @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mã chi nhánh <span class="text-error">*</span></span>
                                <span class="label-text-alt text-base-content/40 text-xs">Duy nhất trong org</span>
                            </label>
                            <input type="text" name="code" value="{{ old('code') }}"
                                   data-req="Vui lòng nhập mã chi nhánh"
                                   class="input input-bordered input-sm w-full font-mono @error('code') input-error @enderror"
                                   placeholder="VD: HN01">
                            @error('code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Chi nhánh cha</span>
                            </label>
                            <select id="ts-parent" name="parent_id"
                                    class="select select-bordered select-sm w-full ts-init @error('parent_id') select-error @enderror"
                                    data-ts-placeholder="— Không có (root) —"
                                    @if(!$orgLocked)
                                        data-parent-api="{{ route('api.branches.options') }}"
                                        data-selected-parent="{{ old('parent_id') }}"
                                    @endif>
                                <option value="">— Không có (root) —</option>
                                @if($orgLocked)
                                    @foreach($parentOptions as $opt)
                                    <option value="{{ $opt['value'] }}" {{ old('parent_id') == $opt['value'] ? 'selected' : '' }}>
                                        {{ $opt['text'] }}
                                    </option>
                                    @endforeach
                                @endif
                            </select>
                            <p class="mt-1 text-xs text-base-content/40">Tối đa 3 cấp: Trụ sở → Vùng → Chi nhánh</p>
                            @error('parent_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Loại chi nhánh <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-type" name="type"
                                    data-req="Vui lòng chọn loại"
                                    class="select select-bordered select-sm w-full ts-init @error('type') select-error @enderror"
                                    data-ts-placeholder="— Chọn loại —">
                                @foreach($types as $t)
                                <option value="{{ $t['value'] }}" {{ old('type', 'branch') === $t['value'] ? 'selected' : '' }}>
                                    {{ $t['text'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="button" @click="tab = 'contact'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Liên hệ
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- ── Tab: Liên hệ ────────────────────────────────────── --}}
                <div x-show="tab === 'contact'" data-tab-label="Liên hệ" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Số điện thoại</span>
                            </label>
                            <input type="tel" name="phone" value="{{ old('phone') }}"
                                   class="input input-bordered input-sm w-full @error('phone') input-error @enderror"
                                   placeholder="VD: 0901 234 567">
                            @error('phone')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Email</span>
                            </label>
                            <input type="email" name="email" value="{{ old('email') }}"
                                   data-val-email="Email không đúng định dạng"
                                   class="input input-bordered input-sm w-full @error('email') input-error @enderror"
                                   placeholder="hanoi@company.com">
                            @error('email')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Fax</span>
                            </label>
                            <input type="text" name="fax" value="{{ old('fax') }}"
                                   class="input input-bordered input-sm w-full @error('fax') input-error @enderror"
                                   placeholder="VD: 024 1234 5678">
                            @error('fax')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mã số thuế chi nhánh</span>
                                <span class="label-text-alt text-base-content/40 text-xs">Hóa đơn điện tử</span>
                            </label>
                            <input type="text" name="tax_code" value="{{ old('tax_code') }}"
                                   class="input input-bordered input-sm w-full font-mono @error('tax_code') input-error @enderror"
                                   placeholder="0123456789">
                            @error('tax_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'basic'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Thông tin cơ bản
                        </button>
                        <button type="button" @click="tab = 'address'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Địa chỉ
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- ── Tab: Địa chỉ & Toạ độ ──────────────────────────── --}}
                <div x-show="tab === 'address'" data-tab-label="Địa chỉ & Toạ độ" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tỉnh / Thành phố</span>
                            </label>
                            <select id="ts-province" name="province_code"
                                    class="select select-bordered select-sm w-full ts-init @error('province_code') select-error @enderror"
                                    data-ts-placeholder="Chọn tỉnh/thành...">
                                <option value="">Chọn tỉnh/thành...</option>
                                @foreach($provinces as $prov)
                                <option value="{{ $prov->province_code }}"
                                        {{ old('province_code') === $prov->province_code ? 'selected' : '' }}>
                                    {{ $prov->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('province_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Phường / Xã</span>
                            </label>
                            <select id="ts-ward" name="ward_code"
                                    data-selected-ward="{{ old('ward_code') }}"
                                    class="select select-bordered select-sm w-full @error('ward_code') select-error @enderror"
                                    {{ !old('province_code') ? 'disabled' : '' }}>
                                <option value="">{{ old('province_code') ? 'Chọn phường/xã...' : 'Chọn tỉnh trước...' }}</option>
                            </select>
                            @error('ward_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Địa chỉ chi tiết</span>
                            </label>
                            <input type="text" name="address" value="{{ old('address') }}"
                                   class="input input-bordered input-sm w-full @error('address') input-error @enderror"
                                   placeholder="VD: Số 123, đường Nguyễn Huệ, phường Bến Nghé">
                            @error('address')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Vĩ độ (Lat)</span>
                                <span class="label-text-alt text-base-content/40 text-xs">Map view</span>
                            </label>
                            <input type="number" name="lat" value="{{ old('lat') }}" step="0.0000001"
                                   class="input input-bordered input-sm w-full font-mono @error('lat') input-error @enderror"
                                   placeholder="21.0285">
                            @error('lat')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Kinh độ (Lng)</span>
                                <span class="label-text-alt text-base-content/40 text-xs">Map view</span>
                            </label>
                            <input type="number" name="lng" value="{{ old('lng') }}" step="0.0000001"
                                   class="input input-bordered input-sm w-full font-mono @error('lng') input-error @enderror"
                                   placeholder="105.8542">
                            @error('lng')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'contact'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Liên hệ
                        </button>
                        <button type="button" @click="tab = 'settings'" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Cài đặt
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- ── Tab: Cài đặt ────────────────────────────────────── --}}
                <div x-show="tab === 'settings'" data-tab-label="Cài đặt" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Múi giờ</span>
                                <span class="label-text-alt text-base-content/40 text-xs">NULL = kế thừa từ org</span>
                            </label>
                            <input type="text" name="timezone" value="{{ old('timezone') }}"
                                   class="input input-bordered input-sm w-full @error('timezone') input-error @enderror"
                                   placeholder="Asia/Ho_Chi_Minh">
                            @error('timezone')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tiền tệ</span>
                                <span class="label-text-alt text-base-content/40 text-xs">ISO 4217</span>
                            </label>
                            <input type="text" name="currency" value="{{ old('currency') }}"
                                   class="input input-bordered input-sm w-full font-mono @error('currency') input-error @enderror"
                                   placeholder="VND" maxlength="3">
                            @error('currency')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ngày khai trương</span>
                            </label>
                            <input type="text" name="opened_at" id="fp-opened-at"
                                   value="{{ old('opened_at') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('opened_at') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('opened_at')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ngày đóng cửa</span>
                            </label>
                            <input type="text" name="closed_at" id="fp-closed-at"
                                   value="{{ old('closed_at') }}"
                                   class="input input-bordered input-sm w-full fp-init @error('closed_at') input-error @enderror"
                                   placeholder="DD/MM/YYYY">
                            @error('closed_at')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'address'" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Địa chỉ & Toạ độ
                        </button>
                        <span class="text-xs text-base-content/40">Nhấn <strong>Tạo mới</strong> ở bên phải khi xong</span>
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

                    <div class="form-control mb-4">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">
                                Trạng thái <span class="text-error">*</span>
                            </span>
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
                        <a href="{{ route('backend.branches.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tạo mới
                        </button>
                    </div>

                    <p class="text-center text-xs text-base-content/30 mt-2.5">
                        <span class="text-error">*</span> là trường bắt buộc
                    </p>

                </div>
            </div>

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Hướng dẫn</p>
                    <ul class="text-xs text-base-content/60 space-y-1.5 list-disc list-inside">
                        <li>Mã chi nhánh phải duy nhất trong org (VD: HN01, HCM02)</li>
                        <li>Tối đa 3 cấp: Trụ sở → Vùng → Chi nhánh</li>
                        <li>MST chi nhánh dùng cho hóa đơn điện tử</li>
                    </ul>
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
        'Modules/Branch/resources/assets/js/branch.js',
    ], 'build/backend')
@endpush
