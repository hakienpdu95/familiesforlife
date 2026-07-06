@extends('layouts.backend')
@section('title', 'Tạo SOP mới')


@section('content')
<div class="p-6">

    <div class="mb-5">
        <h1 class="text-xl font-bold">Tạo SOP mới</h1>
        <p class="text-sm opacity-60 mt-0.5">Định nghĩa quy trình vận hành chuẩn mới</p>
    </div>

    @if($errors->any())
    <div class="alert alert-error mb-5">
        <ul class="list-disc pl-4 text-sm space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('backend.sop.store') }}" novalidate
          data-sop-form>
        @csrf

        <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

            {{-- ── Nội dung chính ──────────────────────────────────────────── --}}
            <div class="space-y-5">

                {{-- Thông tin cơ bản --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5 space-y-4">
                        <p class="text-sm font-semibold">Thông tin cơ bản</p>

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
                                    <option value="{{ $org->id }}"
                                        {{ old('organization_id', $defaultOrgId ?? '') == $org->id ? 'selected' : '' }}>
                                        {{ $org->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('organization_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-[180px_1fr] gap-4">

                            <div class="form-control">
                                <label class="label" for="code">
                                    <span class="label-text font-medium">Mã SOP <span class="text-error">*</span></span>
                                </label>
                                <input id="code" type="text" name="code" value="{{ old('code') }}"
                                       data-req="Mã SOP"
                                       class="input input-bordered input-sm font-mono uppercase @error('code') input-error @enderror"
                                       placeholder="vd: SOP-HR-001"
                                       oninput="this.value=this.value.toUpperCase()">
                                @error('code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="form-control">
                                <label class="label" for="title">
                                    <span class="label-text font-medium">Tên quy trình <span class="text-error">*</span></span>
                                </label>
                                <input id="title" type="text" name="title" value="{{ old('title') }}"
                                       data-req="Tên quy trình"
                                       class="input input-bordered input-sm @error('title') input-error @enderror"
                                       placeholder="Nhập tên quy trình...">
                                @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                        </div>

                        <div class="form-control">
                            <label class="label" for="description">
                                <span class="label-text font-medium">Mô tả tổng quan</span>
                            </label>
                            <textarea id="description" name="description"
                                      class="jodit-editor textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                                      data-jodit-preset="compact"
                                      rows="3"
                                      placeholder="Mô tả mục tiêu, phạm vi áp dụng của quy trình...">{{ old('description') }}</textarea>
                            @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- Phạm vi & Phân công --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5 space-y-4">
                        <p class="text-sm font-semibold">Phạm vi & Phân công</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            <div class="form-control">
                                <label class="label" for="ts-type">
                                    <span class="label-text font-medium">Loại SOP <span class="text-error">*</span></span>
                                </label>
                                <select id="ts-type" name="type"
                                        class="select select-bordered select-sm w-full ts-init @error('type') select-error @enderror">
                                    @foreach($types as $t)
                                    <option value="{{ $t['value'] }}" {{ old('type') === $t['value'] ? 'selected' : '' }}>{{ $t['text'] }}</option>
                                    @endforeach
                                </select>
                                @error('type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="form-control">
                                <label class="label" for="ts-owner">
                                    <span class="label-text font-medium">Người phụ trách <span class="text-error">*</span></span>
                                </label>
                                <select id="ts-owner" name="owner_id"
                                        class="select select-bordered select-sm w-full @error('owner_id') select-error @enderror @if(!$orgLocked) ts-init @endif"
                                        @if($orgLocked)
                                            data-ts-remote-url="{{ route('api.users.options') }}"
                                        @else
                                            data-org-api="{{ route('api.users.options') }}"
                                            data-selected-value="{{ old('owner_id') }}"
                                        @endif
                                        data-ts-placeholder="Chọn người phụ trách...">
                                    @if($orgLocked && $selectedOwner)
                                    <option value="{{ $selectedOwner->id }}" selected>{{ $selectedOwner->name }}</option>
                                    @endif
                                </select>
                                @error('owner_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="form-control">
                                <label class="label" for="ts-department">
                                    <span class="label-text font-medium">Phòng ban áp dụng</span>
                                </label>
                                <select id="ts-department" name="department_id"
                                        class="select select-bordered select-sm w-full @if(!$orgLocked) ts-init @endif"
                                        @if($orgLocked)
                                            data-ts-remote-url="{{ route('api.departments.options') }}"
                                        @else
                                            data-org-api="{{ route('api.departments.options') }}"
                                            data-selected-value="{{ old('department_id') }}"
                                        @endif
                                        data-ts-placeholder="Tất cả phòng ban...">
                                    @if($orgLocked && $selectedDept)
                                    <option value="{{ $selectedDept->id }}" selected>{{ $selectedDept->name }}</option>
                                    @endif
                                </select>
                                @error('department_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="form-control">
                                <label class="label" for="ts-branch">
                                    <span class="label-text font-medium">Chi nhánh áp dụng</span>
                                </label>
                                <select id="ts-branch" name="branch_id"
                                        class="select select-bordered select-sm w-full @if(!$orgLocked) ts-init @endif"
                                        @if($orgLocked)
                                            data-ts-remote-url="{{ route('api.branches.options') }}"
                                        @else
                                            data-org-api="{{ route('api.branches.options') }}"
                                            data-selected-value="{{ old('branch_id') }}"
                                        @endif
                                        data-ts-placeholder="Tất cả chi nhánh...">
                                    @if($orgLocked && $selectedBranch)
                                    <option value="{{ $selectedBranch->id }}" selected>{{ $selectedBranch->name }}</option>
                                    @endif
                                </select>
                                @error('branch_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Thời hạn hiệu lực --}}
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-5 space-y-4">
                        <p class="text-sm font-semibold">Thời hạn hiệu lực</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                            <div class="form-control">
                                <label class="label" for="fp-effective-date">
                                    <span class="label-text font-medium">Ngày hiệu lực</span>
                                </label>
                                <input id="fp-effective-date" name="effective_date"
                                       value="{{ old('effective_date') }}"
                                       class="input input-bordered input-sm w-full fp-init @error('effective_date') input-error @enderror"
                                       placeholder="dd/mm/yyyy" autocomplete="off">
                                @error('effective_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                            <div class="form-control">
                                <label class="label" for="fp-expired-date">
                                    <span class="label-text font-medium">Ngày hết hạn</span>
                                </label>
                                <input id="fp-expired-date" name="expired_date"
                                       value="{{ old('expired_date') }}"
                                       class="input input-bordered input-sm w-full fp-init @error('expired_date') input-error @enderror"
                                       placeholder="dd/mm/yyyy" autocomplete="off">
                                @error('expired_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>

                        </div>
                    </div>
                </div>

            </div>

            {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
            <aside class="xl:sticky xl:top-4 space-y-4">

                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4 space-y-4">
                        <div class="flex items-center gap-2">
                            <span class="size-2 rounded-full bg-warning inline-block"></span>
                            <span class="text-sm font-medium">Nháp</span>
                        </div>
                        <p class="text-xs opacity-50">SOP mới tạo sẽ ở trạng thái Nháp cho đến khi được gửi duyệt.</p>
                        <div class="flex flex-col gap-2 pt-1 border-t border-base-200">
                            <button type="submit" class="btn btn-primary btn-sm w-full">Lưu SOP</button>
                            <a href="{{ route('backend.sop.index') }}"
                               class="btn btn-ghost btn-sm w-full">Hủy</a>
                        </div>
                        <p class="text-center text-xs opacity-30">
                            <span class="text-error">*</span> là trường bắt buộc
                        </p>
                    </div>
                </div>

                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4">
                        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Quy ước mã SOP</p>
                        <div class="text-xs text-base-content/60 space-y-1.5">
                            <p><span class="font-mono font-semibold">SOP-HR-001</span> — Nhân sự</p>
                            <p><span class="font-mono font-semibold">SOP-OPS-012</span> — Vận hành</p>
                            <p><span class="font-mono font-semibold">SOP-SALE-003</span> — Kinh doanh</p>
                        </div>
                    </div>
                </div>

            </aside>

        </div>
    </form>

</div>
@endsection

@push('scripts')
@vite([
    'resources/js/modules/toastify.js',
    'resources/js/modules/flatpickr.js',
    'resources/js/modules/tom-select.js',
    'resources/js/modules/jodit.js',
    'Modules/Sop/resources/assets/sass/sop.scss',
    'Modules/Sop/resources/assets/js/sop.js',
], 'build/backend')
@endpush
