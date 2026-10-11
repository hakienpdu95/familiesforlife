@extends('layouts.backend')
@section('title', 'Quản lý nhân sự Platform')

@section('content')
<div x-data="platformUserListPage({{ Js::from([
    'apiUrl' => route('backend.api.platform-users'),
]) }})">

    @foreach(['success','error'] as $type)
        @if(session($type))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition.opacity.duration.500ms
             class="alert alert-{{ $type }} mb-4 text-sm">
            <span>{{ session($type) }}</span>
            <button @click="show = false" class="btn btn-ghost btn-xs ml-auto">✕</button>
        </div>
        @endif
    @endforeach

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Quản lý nhân sự Platform</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Tài khoản không thuộc tổ chức nào — đội biên tập/vận hành trung ương của Hà Kiên
                (spec/Platform_RBAC_Phase2_Specification.md §2).
            </p>
        </div>
        <a href="{{ route('backend.platform-users.create') }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Thêm nhân sự
        </a>
    </div>

    <div class="section-page">
        <div class="card bg-base-100 mb-4">
            <div class="card-body py-3 px-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">

                    <div class="form-control lg:col-span-2">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Tìm kiếm</span>
                            <span class="label-text-alt text-xs text-base-content/40">Tên, email</span>
                        </label>
                        <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                            <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" x-model="filters.search" @input.debounce.350ms="onFilterChange()"
                                   placeholder="Nhập tên hoặc email..." class="grow bg-transparent outline-none text-sm">
                            <button x-show="filters.search" @click="clearSearch()"
                                    class="text-base-content/30 hover:text-base-content transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Vai trò</span></label>
                        <select id="ts-platform-user-role" x-model="filters.role" @change="onFilterChange()"
                                data-ts-placeholder="Tất cả vai trò"
                                class="select select-sm select-bordered w-full">
                            <option value="">Tất cả</option>
                            @foreach($labels as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Trạng thái</span></label>
                        <select id="ts-platform-user-status" x-model="filters.status" @change="onFilterChange()"
                                data-ts-placeholder="Tất cả trạng thái"
                                class="select select-sm select-bordered w-full">
                            <option value="">Tất cả</option>
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Đã vô hiệu hoá</option>
                        </select>
                    </div>

                </div>

                <div class="flex justify-end">
                    <button @click="reset()" x-show="hasFilters" x-cloak x-transition
                            class="btn btn-ghost btn-sm gap-1.5 text-error mt-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Đặt lại
                    </button>
                </div>

                <div x-show="activeChips.length > 0" x-cloak x-transition
                     class="flex flex-wrap gap-2 pt-3 mt-3 border-t border-base-200">
                    <span class="text-xs text-base-content/40 self-center">Đang lọc:</span>
                    <template x-for="chip in activeChips" :key="chip.key">
                        <span class="badge badge-sm gap-1 cursor-pointer hover:badge-error transition-colors"
                              @click="removeChip(chip.key)">
                            <span x-text="chip.label"></span>
                            <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </span>
                    </template>
                </div>
            </div>
        </div>

        <div x-show="selectedCount > 0" x-cloak x-transition
             class="flex flex-wrap items-center gap-3 mb-3 px-4 py-2.5 rounded-box bg-primary/10 border border-primary/20">
            <span class="text-sm font-medium">Đã chọn <span x-text="selectedCount"></span> tài khoản</span>
            <div class="flex flex-wrap items-center gap-2 ml-auto">
                <button @click="exportSelected()" class="btn btn-ghost btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Xuất CSV
                </button>
                <button @click="bulkDeactivate()" class="btn btn-error btn-outline btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    Vô hiệu hoá
                </button>
                <button @click="clearSelection()" class="btn btn-ghost btn-sm">Bỏ chọn</button>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0 overflow-hidden tabulator-daisy">
                <div id="platform-user-table"></div>
            </div>
        </div>
    </div>

</div>

<form id="platformUserBulkForm" method="POST" action="{{ route('backend.platform-users.bulk-deactivate') }}" class="hidden">
    @csrf
</form>

<form id="platformUserActionForm" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="_method" value="PATCH">
</form>
@endsection

@push('styles')
    <x-tabulator-theme />
    @vite(['Modules/Approval/resources/assets/sass/approval.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'resources/js/modules/tom-select.js',
        'Modules/Approval/resources/assets/js/approval.js',
    ], 'build/backend')
@endpush
