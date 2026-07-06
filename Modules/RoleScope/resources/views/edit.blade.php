@extends('layouts.backend')
@section('title', 'Chỉnh sửa phân quyền')


@section('content')
<div class="p-6">

    <div class="mb-5">
        <h1 class="text-xl font-bold">Chỉnh sửa phân quyền</h1>
        <p class="text-sm opacity-60 mt-0.5">Chỉ có thể thay đổi thời hạn và ghi chú. Để thay đổi phạm vi, cần thu hồi và cấp mới.</p>
    </div>

    {{-- Error banner --}}
    @if($errors->any())
    <div class="alert alert-error mb-5">
        <ul class="list-disc pl-4 text-sm space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('backend.role-scopes.update', $roleScope) }}" novalidate data-role-scope-form>
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

            {{-- ── Card chính ───────────────────────────────────────────────────── --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-5 space-y-4">

                    {{-- Readonly info block --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-base-200/50 rounded-xl">
                        <div>
                            <p class="text-xs opacity-50 mb-1">User</p>
                            <p class="font-medium text-sm">{{ $roleScope->user?->name ?? '—' }}</p>
                            <p class="text-xs opacity-40">{{ $roleScope->user?->email ?? '' }}</p>
                        </div>
                        <div>
                            <p class="text-xs opacity-50 mb-1">Role</p>
                            <span class="badge badge-outline badge-sm">{{ $roleScope->role?->name ?? '—' }}</span>
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-xs opacity-50 mb-1">Phạm vi</p>
                            <p class="text-sm">{{ $roleScope->scope_label }}</p>
                        </div>
                    </div>

                    <div class="divider my-1 text-xs opacity-30">Chỉnh sửa được</div>

                    <div class="form-control">
                        <label class="label" for="fp-expires-at">
                            <span class="label-text font-medium">Hết hạn vào</span>
                            <span class="label-text-alt text-xs opacity-40">Để trống = vĩnh viễn</span>
                        </label>
                        <input type="text" name="expires_at" id="fp-expires-at"
                               value="{{ old('expires_at', $roleScope->expires_at?->format('Y-m-d H:i') ?? '') }}"
                               class="input input-bordered input-sm w-full @error('expires_at') input-error @enderror"
                               placeholder="DD/MM/YYYY HH:MM" readonly>
                        @error('expires_at')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label" for="note">
                            <span class="label-text font-medium">Lý do / Ghi chú</span>
                            <span class="label-text-alt text-xs opacity-40">Không bắt buộc</span>
                        </label>
                        <textarea id="note" name="note" rows="4"
                                  class="jodit-editor textarea textarea-bordered textarea-sm w-full @error('note') textarea-error @enderror"
                                  data-jodit-preset="compact"
                                  placeholder="Ghi chú...">{{ old('note', $roleScope->note) }}</textarea>
                        @error('note')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>
            </div>

            {{-- ── Sidebar ──────────────────────────────────────────────────────── --}}
            <aside class="xl:sticky xl:top-4 space-y-4">

                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4 space-y-3">

                        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Xuất bản</p>

                        <div class="text-xs text-base-content/40 space-y-1">
                            <p>Tạo: {{ $roleScope->created_at->format('d/m/Y') }}</p>
                            <p>Cập nhật: {{ $roleScope->updated_at->diffForHumans() }}</p>
                        </div>

                        <div class="flex flex-col gap-2 pt-1 border-t border-base-200">
                            <button type="submit" class="btn btn-primary btn-sm w-full">Lưu lại</button>
                            <a href="{{ route('backend.role-scopes.show', $roleScope) }}"
                               class="btn btn-ghost btn-sm w-full">Hủy</a>
                        </div>

                    </div>
                </div>

            </aside>

        </div>
    </form>

    {{-- Danger zone — nằm ngoài form update để tránh nested form --}}
    @can('delete', $roleScope)
    <div class="mt-6 border border-error/30 rounded-xl p-4">
        <p class="text-sm font-semibold text-error mb-1">Vùng nguy hiểm</p>
        <p class="text-xs opacity-50 mb-3">Thu hồi quyền này sẽ xóa vĩnh viễn phân quyền. Thao tác không thể hoàn tác.</p>
        <form method="POST" action="{{ route('backend.role-scopes.destroy', $roleScope) }}"
              onsubmit="return confirm('Thu hồi quyền này không thể hoàn tác. Xác nhận?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-error btn-outline btn-sm">
                Thu hồi quyền
            </button>
        </form>
    </div>
    @endcan

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
