@extends('layouts.backend')
@section('title', 'Kho tri thức')


@section('content')
<div x-data="kcItemListPage({{ Js::from([
    'apiUrl'      => route('backend.api.kc-items'),
    'statuses'    => $statuses,
    'types'       => $types,
    'categories'  => $categories,
    'visibilities'=> $visibilities,
    'tagsApiUrl'  => $tagsApiUrl,
    'canDelete'   => auth()->user()->can('delete', \Modules\KcItem\Models\KcItem::class),
]) }})">

    {{-- ── Page header ──────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Kho tri thức</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Quản lý toàn bộ tài liệu, quy trình và tri thức tổ chức</p>
        </div>
        <div class="flex items-center gap-2">

            {{-- Column visibility --}}
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-ghost btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                    </svg>
                    Cột
                </label>
                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box shadow-lg border border-base-200 w-52 z-50 p-2">
                    <template x-for="col in toggleableCols" :key="col.field">
                        <li>
                            <label class="flex items-center gap-2 cursor-pointer py-1.5 px-2 rounded-lg hover:bg-base-200">
                                <input type="checkbox" class="checkbox checkbox-xs"
                                       :checked="!hiddenCols.includes(col.field)"
                                       @change="toggleCol(col.field)"/>
                                <span x-text="col.title" class="text-sm"></span>
                            </label>
                        </li>
                    </template>
                </ul>
            </div>

            @can('create', \Modules\KcItem\Models\KcItem::class)
            <a href="{{ route('backend.kc-items.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tạo tài liệu
            </a>
            @endcan

        </div>
    </div>

    {{-- ── Stat cards ────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Tổng tài liệu</div>
            <div class="stat-value text-2xl">{{ number_format($totalAll) }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Bản nháp</div>
            <div class="stat-value text-2xl text-base-content/50">{{ number_format($totalDraft) }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Chờ duyệt</div>
            <div class="stat-value text-2xl text-warning">{{ number_format($totalPending) }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Đã duyệt</div>
            <div class="stat-value text-2xl text-success">{{ number_format($totalApproved) }}</div>
        </div>
    </div>

    {{-- ── Filter bar ────────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3 px-4 space-y-3">

            <div class="flex flex-wrap gap-3 items-end">

                {{-- Search --}}
                <div class="form-control flex-1 min-w-52">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Tìm kiếm</span>
                        <span class="label-text-alt text-xs text-base-content/40">Tiêu đề, tóm tắt</span>
                    </label>
                    <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                        <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input id="filter-search" type="text"
                               x-model="filters.search"
                               @input.debounce.350ms="onFilterChange()"
                               placeholder="Nhập từ khóa..."
                               class="grow bg-transparent outline-none text-sm"/>
                        <button x-show="filters.search" @click="clearSearch()"
                                class="text-base-content/30 hover:text-base-content transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Loại --}}
                <div class="form-control w-40">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Loại</span>
                    </label>
                    <select id="filter-type" class="select select-sm select-bordered w-full"></select>
                </div>

                {{-- Trạng thái --}}
                <div class="form-control w-40">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Trạng thái</span>
                    </label>
                    <select id="filter-status" class="select select-sm select-bordered w-full"></select>
                </div>

                {{-- Danh mục --}}
                <div class="form-control w-48">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Danh mục</span>
                    </label>
                    <select id="filter-category" class="select select-sm select-bordered w-full"></select>
                </div>

                {{-- Tag --}}
                <div class="form-control w-40">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Tag</span>
                    </label>
                    <select id="filter-tag" class="select select-sm select-bordered w-full"></select>
                </div>

                {{-- Reset --}}
                <div class="form-control ml-auto">
                    <label class="label py-0.5 invisible"><span class="label-text text-xs">.</span></label>
                    <button @click="reset()" x-show="hasFilters" x-transition
                            class="btn btn-ghost btn-sm gap-1.5 text-error">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Đặt lại
                    </button>
                </div>

            </div>

            {{-- Active chips --}}
            <div x-show="activeChips.length > 0" x-transition
                 class="flex flex-wrap gap-2 pt-1 border-t border-base-200">
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

    {{-- ── Table ─────────────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-hidden rounded-2xl tabulator-daisy">
            <div id="kc-item-table"></div>
        </div>
    </div>

</div>

{{-- Delete modal --}}
<dialog id="kcItemDeleteModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xóa</h3>
        <p class="py-3 text-sm text-base-content/70">
            Bạn có chắc muốn xóa tài liệu
            <strong id="kcItemDeleteTitle" class="text-base-content"></strong>?
        </p>
        <div class="modal-action mt-4">
            <button id="kcItemDeleteConfirmBtn" class="btn btn-error btn-sm">Xóa</button>
            <button class="btn btn-ghost btn-sm" onclick="kcItemDeleteModal.close()">Hủy</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endsection

@push('styles')
    <x-tabulator-theme />
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'resources/js/modules/tom-select.js',
        'Modules/KcItem/resources/assets/js/kc-item.js',
    ], 'build/backend')
@endpush
