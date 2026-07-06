@extends('layouts.backend')
@section('title', 'Chỉnh sửa: ' . $organization->name)


@section('content')
<div x-data="{
    tab: 'basic',
    tabFields: {
        basic:   ['name', 'tax_code', 'industry'],
        contact: ['phone', 'email', 'website'],
        address: ['province_code', 'ward_code', 'address']
    },
    errs: {{ Js::from($errors->keys()) }},
    errCount(t) {
        return this.tabFields[t].filter(f => this.errs.includes(f)).length;
    },
    init() {
        const order = ['basic', 'contact', 'address'];
        for (const t of order) {
            if (this.errCount(t) > 0) { this.tab = t; break; }
        }
    }
}">

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Chỉnh sửa tổ chức</h1>
        <p class="text-sm text-base-content/50 mt-0.5 flex items-center gap-2">
            {{ $organization->name }}
            @if($organization->status->value === 'active')
                <span class="badge badge-success badge-sm">Hoạt động</span>
            @elseif($organization->status->value === 'suspended')
                <span class="badge badge-error badge-sm">Tạm khóa</span>
            @else
                <span class="badge badge-ghost badge-sm">Không hoạt động</span>
            @endif
        </p>
    </div>
    <a href="{{ route('backend.organizations.show', $organization) }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.organizations.update', $organization) }}"
      novalidate data-org-form>
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Card chính với tab ───────────────────────────────────────── --}}
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
                            class="flex items-center gap-1.5 px-1 py-4 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'address'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Địa chỉ
                        <span x-show="errCount('address') > 0" x-text="errCount('address')"
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
                            <span class="label-text font-medium">Tên tổ chức <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $organization->name) }}"
                               data-req="Vui lòng nhập tên tổ chức"
                               class="input input-bordered input-sm w-full @error('name') input-error @enderror">
                        @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Slug</span>
                            <span class="label-text-alt text-xs text-base-content/40">Thận trọng khi thay đổi</span>
                        </label>
                        <input type="text" name="slug" value="{{ old('slug', $organization->slug) }}"
                               class="input input-bordered input-sm w-full font-mono @error('slug') input-error @enderror">
                        <p class="mt-1 text-xs text-base-content/40">Chỉ dùng chữ thường, số và dấu <code class="bg-base-200 px-1 rounded">-</code></p>
                        @error('slug')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mã số thuế <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="tax_code" value="{{ old('tax_code', $organization->tax_code) }}"
                                   data-req="Vui lòng nhập mã số thuế"
                                   data-val-maxlength="20"
                                   class="input input-bordered input-sm w-full font-mono @error('tax_code') input-error @enderror"
                                   maxlength="20">
                            <p class="mt-1 text-xs text-base-content/40">10 hoặc 13 chữ số, tối đa 20 ký tự</p>
                            @error('tax_code')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ngành nghề</span>
                            </label>
                            <input type="text" name="industry" value="{{ old('industry', $organization->industry) }}"
                                   class="input input-bordered input-sm w-full"
                                   placeholder="VD: Công nghệ thông tin, Bán lẻ...">
                        </div>

                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mô tả</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <textarea name="description"
                                  class="jodit-editor textarea textarea-bordered textarea-sm w-full"
                                  data-jodit-preset="compact">{{ old('description', $organization->description) }}</textarea>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="button" @click="tab = 'contact'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Liên hệ
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- Panel: Liên hệ --}}
                <div x-show="tab === 'contact'" data-tab-label="Liên hệ" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Số điện thoại</span>
                            </label>
                            <input type="text" name="phone" value="{{ old('phone', $organization->phone) }}"
                                   data-val-maxlength="20"
                                   class="input input-bordered input-sm w-full"
                                   placeholder="028 1234 5678">
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Email liên hệ</span>
                            </label>
                            <input type="email" name="email" value="{{ old('email', $organization->email) }}"
                                   data-val-email="Email không đúng định dạng"
                                   class="input input-bordered input-sm w-full @error('email') input-error @enderror">
                            @error('email')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Website</span>
                        </label>
                        <input type="url" name="website" value="{{ old('website', $organization->website) }}"
                               data-val-url="URL không hợp lệ — phải bắt đầu bằng https://"
                               class="input input-bordered input-sm w-full @error('website') input-error @enderror">
                        <p class="mt-1 text-xs text-base-content/40">Bắt đầu bằng <code class="bg-base-200 px-1 rounded text-xs">https://</code></p>
                        @error('website')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'basic'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Thông tin cơ bản
                        </button>
                        <button type="button" @click="tab = 'address'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Địa chỉ
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- Panel: Địa chỉ --}}
                <div x-show="tab === 'address'" data-tab-label="Địa chỉ" class="space-y-4">

                    <x-address-picker
                        :province-value="old('province_code', $organization->province_code)"
                        :ward-value="old('ward_code', $organization->ward_code)"
                        instance-id="org-e"
                        :required="true"
                    />

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Địa chỉ cụ thể</span>
                            <span class="label-text-alt text-xs text-base-content/40">Số nhà, tên đường...</span>
                        </label>
                        <input type="text" name="address" value="{{ old('address', $organization->address) }}"
                               class="input input-bordered input-sm w-full @error('address') input-error @enderror"
                               placeholder="VD: 123 Nguyễn Trãi, Phường Bến Thành, Quận 1">
                        @error('address')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'contact'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Liên hệ
                        </button>
                        <span class="text-xs text-base-content/40">Nhấn <strong>Lưu thay đổi</strong> ở bên phải khi xong</span>
                    </div>

                </div>

            </div>{{-- /tab panels --}}
        </div>{{-- /card chính --}}

        {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">

            {{-- Xuất bản --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>

                    <div class="form-control mb-3">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">Trạng thái <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-status" name="status"
                                class="select select-bordered select-sm w-full ts-init @error('status') select-error @enderror"
                                data-ts-placeholder="— Chọn trạng thái —">
                            <option value="">— Chọn trạng thái —</option>
                            <option value="active"    {{ old('status', $organization->status->value) === 'active'    ? 'selected' : '' }}>Hoạt động</option>
                            <option value="inactive"  {{ old('status', $organization->status->value) === 'inactive'  ? 'selected' : '' }}>Không hoạt động</option>
                            <option value="suspended" {{ old('status', $organization->status->value) === 'suspended' ? 'selected' : '' }}>Tạm khóa</option>
                        </select>
                        @error('status')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-between text-xs text-base-content/40 mb-4 px-0.5">
                        <span>Tạo {{ $organization->created_at->format('d/m/Y') }}</span>
                        <span>Sửa {{ $organization->updated_at->diffForHumans() }}</span>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.organizations.show', $organization) }}"
                           class="btn btn-ghost btn-sm flex-1">Hủy</a>
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

            {{-- Liên kết nhanh --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-0">
                    <a href="{{ route('backend.users.index', ['org' => $organization->id]) }}"
                       class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-base-200/60 transition-colors rounded-2xl group">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-base-content/40 group-hover:text-primary transition-colors shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="text-sm font-medium">Quản lý thành viên</span>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <span class="badge badge-ghost badge-sm">{{ $organization->members_count }}</span>
                            <svg class="w-3.5 h-3.5 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    </a>
                </div>
            </div>

        </div>{{-- /sidebar --}}

    </div>{{-- /grid --}}

</form>
</div>
@endsection

@push('styles')
    @vite(['Modules/Organization/resources/assets/sass/organization.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/jodit.js',
        'Modules/Organization/resources/assets/js/organization.js',
    ], 'build/backend')
@endpush
