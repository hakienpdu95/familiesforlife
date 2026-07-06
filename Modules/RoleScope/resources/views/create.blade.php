@extends('layouts.backend')
@section('title', 'Cấp quyền phạm vi mới')


@section('content')
<div x-data="grantRoleScopeForm({{ Js::from([
    'orgLocked'    => $orgLocked,
    'orgId'        => $orgLocked ? ($organizations->first()->id ?? null) : null,
    'orgName'      => $orgLocked ? ($organizations->first()->name ?? '') : '',
    'organizations' => $orgLocked ? [] : $organizations->map(fn($o) => ['value' => $o->id, 'text' => $o->name])->values()->all(),
    'defaultOrgId' => $defaultOrgId,
    'oldOrgId'     => old('organization_id'),
    'users'        => $users->map(fn($u) => ['value' => $u->id, 'text' => $u->name . ' (' . $u->email . ')'])->values()->all(),
    'roles'        => $roles->map(fn($r) => ['value' => $r->id, 'text' => $r->name])->values()->all(),
    'branches'     => $branches->map(fn($b) => ['value' => $b->id, 'text' => $b->name . ' [' . $b->code . ']'])->values()->all(),
    'departments'  => $departments->map(fn($d) => ['value' => $d->id, 'text' => $d->name . ' [' . $d->code . ']', 'branch_id' => $d->branch_id])->values()->all(),
    'oldUserId'    => old('user_id'),
    'oldRoleId'    => old('role_id'),
    'oldBranchId'  => old('scope_branch_id'),
    'oldDeptId'    => old('scope_dept_id'),
]) }})">

<div class="mb-5">
    <h1 class="text-xl font-bold">Cấp quyền phạm vi mới</h1>
    <p class="text-sm opacity-60 mt-0.5">Gán role cho user với phạm vi chi nhánh hoặc phòng ban cụ thể</p>
</div>

{{-- Error banner --}}
@if($errors->any())
<div class="alert alert-error mb-5">
    <ul class="list-disc pl-4 text-sm space-y-0.5">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('backend.role-scopes.store') }}" novalidate data-role-scope-form>
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Card chính ───────────────────────────────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5 space-y-4">

                {{-- Tổ chức --}}
                @if($orgLocked)
                    <input type="hidden" name="organization_id" value="{{ $organizations->first()->id }}">
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-medium">Tổ chức</span>
                        </label>
                        <input type="text" value="{{ $organizations->first()->name }}" readonly
                               class="input input-bordered input-sm w-full bg-base-200 cursor-not-allowed">
                        <p class="mt-1 text-xs opacity-40">Xác định từ tài khoản của bạn.</p>
                    </div>
                @else
                    <div class="form-control">
                        <label class="label" for="select-org">
                            <span class="label-text font-medium">Tổ chức <span class="text-error">*</span></span>
                            <span class="label-text-alt text-xs opacity-40">Chọn trước để lọc user</span>
                        </label>
                        <select id="select-org" name="organization_id"
                                class="select select-bordered select-sm w-full @error('organization_id') select-error @enderror"
                                data-req="Vui lòng chọn tổ chức"></select>
                        @error('organization_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                @endif

                {{-- User --}}
                <div class="form-control">
                    <label class="label" for="select-user">
                        <span class="label-text font-medium">User <span class="text-error">*</span></span>
                        <span class="label-text-alt text-xs opacity-40">User trong tổ chức</span>
                    </label>
                    <select id="select-user" name="user_id"
                            class="select select-bordered select-sm w-full @error('user_id') select-error @enderror"
                            data-req="Vui lòng chọn user"></select>
                    @error('user_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                {{-- Role --}}
                <div class="form-control">
                    <label class="label" for="select-role">
                        <span class="label-text font-medium">Role <span class="text-error">*</span></span>
                        <span class="label-text-alt text-xs opacity-40">Role Spatie RBAC</span>
                    </label>
                    <select id="select-role" name="role_id"
                            class="select select-bordered select-sm w-full @error('role_id') select-error @enderror"
                            data-req="Vui lòng chọn role"></select>
                    @error('role_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="divider my-1 text-xs opacity-30">Phạm vi áp dụng</div>

                <div class="alert alert-info py-2.5 px-4 text-sm">
                    <div>
                        <p class="font-medium text-xs">Logic phạm vi:</p>
                        <ul class="list-disc list-inside text-xs mt-1 space-y-0.5 opacity-80">
                            <li>Không chọn gì → Toàn tổ chức</li>
                            <li>Chọn chi nhánh, không chọn phòng ban → Toàn chi nhánh đó</li>
                            <li>Chọn cả chi nhánh + phòng ban → Chỉ phòng ban đó</li>
                        </ul>
                    </div>
                </div>

                {{-- Branch --}}
                <div class="form-control">
                    <label class="label" for="select-branch">
                        <span class="label-text font-medium">Chi nhánh áp dụng</span>
                        <span class="label-text-alt text-xs opacity-40">Để trống = toàn tổ chức</span>
                    </label>
                    <select id="select-branch" name="scope_branch_id"
                            class="select select-bordered select-sm w-full @error('scope_branch_id') select-error @enderror"></select>
                    @error('scope_branch_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                {{-- Department (cascade) --}}
                <div class="form-control">
                    <label class="label" for="select-dept">
                        <span class="label-text font-medium">Phòng ban áp dụng</span>
                        <span class="label-text-alt text-xs opacity-40">Cần chọn chi nhánh trước</span>
                    </label>
                    <select id="select-dept" name="scope_dept_id"
                            :disabled="!selectedBranchId"
                            class="select select-bordered select-sm w-full @error('scope_dept_id') select-error @enderror"></select>
                    @error('scope_dept_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    <p x-show="!selectedBranchId" class="mt-1 text-xs opacity-40">
                        Chọn chi nhánh trước để lọc phòng ban
                    </p>
                </div>

            </div>
        </div>

        {{-- ── Sidebar ──────────────────────────────────────────────────────── --}}
        <aside class="xl:sticky xl:top-4 space-y-4">

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4 space-y-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">
                        Thời hạn & Ghi chú
                    </p>

                    <div class="form-control">
                        <label class="label py-0 pb-1" for="fp-expires-at">
                            <span class="label-text text-xs font-medium">Hết hạn vào</span>
                            <span class="label-text-alt text-xs opacity-40">Để trống = vĩnh viễn</span>
                        </label>
                        <input type="text" name="expires_at" id="fp-expires-at"
                               value="{{ old('expires_at') }}"
                               class="input input-bordered input-sm w-full @error('expires_at') input-error @enderror"
                               placeholder="DD/MM/YYYY HH:MM" readonly>
                        @error('expires_at')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1" for="note">
                            <span class="label-text text-xs font-medium">Lý do cấp</span>
                        </label>
                        <textarea id="note" name="note" rows="3"
                                  class="jodit-editor textarea textarea-bordered textarea-sm w-full @error('note') textarea-error @enderror"
                                  data-jodit-preset="compact"
                                  placeholder="Ghi chú lý do cấp quyền này...">{{ old('note') }}</textarea>
                        @error('note')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex flex-col gap-2 pt-1 border-t border-base-200">
                        <button type="submit" class="btn btn-primary btn-sm w-full">Cấp quyền</button>
                        <a href="{{ route('backend.role-scopes.index') }}"
                           class="btn btn-ghost btn-sm w-full">Hủy</a>
                    </div>

                    <p class="text-center text-xs opacity-30">
                        <span class="text-error">*</span> là trường bắt buộc
                    </p>

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
    'resources/js/modules/tom-select.js',
    'resources/js/modules/flatpickr.js',
    'resources/js/modules/jodit.js',
    'Modules/RoleScope/resources/assets/sass/role-scope.scss',
    'Modules/RoleScope/resources/assets/js/role-scope.js',
], 'build/backend')
@endpush
