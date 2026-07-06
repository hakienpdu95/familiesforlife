@extends('layouts.backend')
@section('title', 'Thêm ' . $vertical->targetLabel())

@push('styles')
    @vite(['Modules/Deployment/resources/assets/sass/deployment.scss'], 'build/backend')
@endpush

@section('content')

<div x-data="targetCreate(
        '{{ route('deployment.targets.lookup', ['vertical' => $vertical->code()]) }}',
        '{{ old('tax_code', '') }}'
     )">

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thêm {{ $vertical->targetLabel() }}</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Nhập thông tin tổ chức được triển khai</p>
    </div>
    <a href="{{ route('deployment.targets.index', ['vertical' => $vertical->code()]) }}"
       class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

{{-- Error banner --}}
@if($errors->any())
<div class="flex items-start gap-3 bg-error/10 border border-error/30 rounded-lg py-3 px-4 mb-5 text-sm text-error">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
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
      action="{{ route('deployment.targets.store', ['vertical' => $vertical->code()]) }}"
      novalidate
      data-target-form
      data-org-slugs-api="{{ route('deployment.targets.organization-slugs', ['vertical' => $vertical->code()]) }}">
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- Main card --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-5 gap-2">
                    <svg class="w-4 h-4 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Thông tin {{ $vertical->targetLabel() }}
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- Mã số thuế --}}
                    <div class="form-control">
                        <label class="label py-0 pb-1.5" for="field-tax-code">
                            <span class="label-text font-medium">Mã số thuế</span>
                        </label>
                        <div class="relative">
                            <input type="text"
                                   id="field-tax-code"
                                   name="tax_code"
                                   x-model="taxCode"
                                   x-on:input.debounce.600ms="lookup()"
                                   class="input input-bordered input-sm w-full pr-8 @error('tax_code') input-error @enderror"
                                   placeholder="0123456789">
                            <span x-show="searching"
                                  class="absolute right-2 top-2.5 loading loading-spinner loading-xs"></span>
                        </div>
                        <p class="mt-1 text-xs text-base-content/40">Nhập MST để tìm tổ chức đã có</p>
                        @error('tax_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Điện thoại --}}
                    <div class="form-control">
                        <label class="label py-0 pb-1.5" for="field-phone">
                            <span class="label-text font-medium">Điện thoại</span>
                        </label>
                        <input type="text"
                               id="field-phone"
                               name="phone"
                               x-ref="orgPhone"
                               value="{{ old('phone') }}"
                               class="input input-bordered input-sm w-full @error('phone') input-error @enderror"
                               placeholder="0901 234 567">
                        @error('phone')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Found-org confirm banner --}}
                    <template x-if="foundOrg && !useExisting">
                        <div class="sm:col-span-2 alert alert-warning flex-col items-start gap-1 py-3 text-sm">
                            <p class="font-semibold">
                                Tổ chức "<span x-text="foundOrg.name"></span>" đã tồn tại trong hệ thống.
                            </p>
                            <p class="text-xs opacity-70" x-text="foundOrg.full_address || ''"></p>
                            <div class="flex gap-2 mt-1">
                                <button type="button" class="btn btn-warning btn-xs"
                                        x-on:click="applyOrg()">Dùng tổ chức này</button>
                                <button type="button" class="btn btn-ghost btn-xs"
                                        x-on:click="foundOrg = null">Tạo mới</button>
                            </div>
                        </div>
                    </template>

                    <template x-if="useExisting">
                        <div class="sm:col-span-2 alert alert-success py-2 text-sm">
                            Đã chọn tổ chức có sẵn. Thông tin được điền tự động từ hệ thống.
                        </div>
                    </template>

                    {{-- Tên tổ chức — required, full width --}}
                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5" for="field-name">
                            <span class="label-text font-medium">
                                Tên tổ chức <span class="text-error">*</span>
                            </span>
                        </label>
                        <input type="text"
                               id="field-name"
                               name="name"
                               x-ref="orgName"
                               value="{{ old('name') }}"
                               class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                               placeholder="VD: HTX Nông nghiệp An Bình"
                               data-req="Vui lòng nhập tên tổ chức.">
                        @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Email --}}
                    <div class="form-control">
                        <label class="label py-0 pb-1.5" for="field-email">
                            <span class="label-text font-medium">Email</span>
                        </label>
                        <input type="email"
                               id="field-email"
                               name="email"
                               x-ref="orgEmail"
                               value="{{ old('email') }}"
                               class="input input-bordered input-sm w-full @error('email') input-error @enderror"
                               placeholder="lienhe@example.com">
                        @error('email')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Người đại diện --}}
                    <div class="form-control">
                        <label class="label py-0 pb-1.5" for="field-representative">
                            <span class="label-text font-medium">Người đại diện</span>
                        </label>
                        <input type="text"
                               id="field-representative"
                               name="representative_name"
                               value="{{ old('representative_name') }}"
                               class="input input-bordered input-sm w-full @error('representative_name') input-error @enderror"
                               placeholder="Họ và tên người đại diện">
                        @error('representative_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Địa chỉ — full width --}}
                    <div class="form-control sm:col-span-2">
                        <label class="label py-0 pb-1.5" for="field-address">
                            <span class="label-text font-medium">Địa chỉ</span>
                        </label>
                        <input type="text"
                               id="field-address"
                               name="full_address"
                               x-ref="orgAddress"
                               value="{{ old('full_address') }}"
                               class="input input-bordered input-sm w-full @error('full_address') input-error @enderror"
                               placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành">
                        @error('full_address')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>

                {{-- Ghi chú — outside grid --}}
                <div class="form-control mt-4">
                    <label class="label py-0 pb-1.5" for="field-notes">
                        <span class="label-text font-medium">Ghi chú</span>
                        <span class="label-text-alt text-base-content/40 text-xs">Tùy chọn</span>
                    </label>
                    <textarea id="field-notes"
                              name="notes"
                              rows="3"
                              class="textarea textarea-bordered textarea-sm w-full @error('notes') textarea-error @enderror"
                              placeholder="Thông tin thêm, lưu ý triển khai...">{{ old('notes') }}</textarea>
                    @error('notes')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

            </div>
        </div>

        {{-- Sidebar: publish block --}}
        <div class="xl:sticky xl:top-4 space-y-4">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
                        Phân công
                    </p>

                    {{-- Dự án --}}
                    <div class="form-control mb-3">
                        <label class="label py-0 pb-1" for="ts-project-id">
                            <span class="label-text text-xs font-medium">
                                Dự án <span class="text-error">*</span>
                            </span>
                            <a href="{{ route('deployment.projects.create', ['vertical' => $vertical->code()]) }}"
                               class="label-text-alt link link-primary text-xs"
                               target="_blank">+ Tạo mới</a>
                        </label>
                        <select id="ts-project-id"
                                name="project_id"
                                class="select select-bordered select-sm w-full ts-init @error('project_id') select-error @enderror"
                                data-ts-placeholder="— Chọn dự án —"
                                data-req="Vui lòng chọn dự án.">
                            <option value="">— Chọn dự án —</option>
                            @foreach($projects as $p)
                            <option value="{{ $p->id }}" @selected(old('project_id') == $p->id)>
                                {{ $p->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('project_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Người phụ trách --}}
                    <div class="form-control mb-4">
                        <label class="label py-0 pb-1" for="ts-employee-id">
                            <span class="label-text text-xs font-medium">Người phụ trách</span>
                        </label>
                        <select id="ts-employee-id"
                                name="assigned_employee_id"
                                class="select select-bordered select-sm w-full ts-init @error('assigned_employee_id') select-error @enderror"
                                data-ts-placeholder="— Không chỉ định —">
                            <option value="">— Không chỉ định —</option>
                            @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" @selected(old('assigned_employee_id') == $emp->id)>
                                {{ $emp->full_name }} ({{ $emp->employee_code }})
                            </option>
                            @endforeach
                        </select>
                        @error('assigned_employee_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Tổ chức tham chiếu — chọn để xem slug khảo sát đã cấu hình cho vertical này --}}
                    <div class="form-control mb-1">
                        <label class="label py-0 pb-1" for="ts-organization">
                            <span class="label-text text-xs font-medium">Tổ chức tham chiếu</span>
                        </label>
                        @if($orgLocked)
                            <input type="text"
                                   value="{{ $organizations->first()->name }}"
                                   readonly
                                   class="input input-bordered input-sm w-full bg-base-200 cursor-not-allowed">
                            <input type="hidden" id="org-slugs-locked-id" value="{{ $organizations->first()->id }}">
                        @else
                            <select id="ts-organization"
                                    class="select select-bordered select-sm w-full ts-init"
                                    data-ts-placeholder="— Chọn tổ chức —">
                                <option value="">— Chọn tổ chức —</option>
                                @foreach($organizations as $org)
                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                                @endforeach
                            </select>
                        @endif
                        <p class="mt-1 text-xs text-base-content/40">Xem slug khảo sát đã cấu hình cho vertical này.</p>
                    </div>

                    <div id="org-slugs-preview" class="hidden bg-base-200/60 rounded-lg p-3 mb-4 space-y-1.5 text-xs">
                        <div class="flex justify-between gap-2">
                            <span class="text-base-content/50">Slug khảo sát sẵn sàng</span>
                            <span id="org-slug-readiness" class="font-mono font-medium text-base-content text-right">—</span>
                        </div>
                        <div class="flex justify-between gap-2">
                            <span class="text-base-content/50">Slug khảo sát thu thập dữ liệu</span>
                            <span id="org-slug-data-collection" class="font-mono font-medium text-base-content text-right">—</span>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('deployment.targets.index', ['vertical' => $vertical->code()]) }}"
                           class="btn btn-ghost btn-sm flex-1">
                            Hủy
                        </a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                            Thêm
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

</div>{{-- /x-data="targetCreate" --}}

@endsection

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'Modules/Deployment/resources/assets/js/deployment.js',
    ], 'build/backend')
@endpush
