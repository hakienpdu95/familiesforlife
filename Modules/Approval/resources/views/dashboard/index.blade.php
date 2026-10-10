@extends('layouts.backend')
@section('title', 'Chờ duyệt của tôi')

@section('content')
<div x-data="approvalPendingPage({{ Js::from([
    'apiUrl' => route('backend.api.approval.pending'),
]) }})">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Chờ duyệt của tôi</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Danh sách nội dung đang chờ duyệt mà bạn có quyền xử lý</p>
        </div>
        @can('viewApprovalHistory')
        <a href="{{ route('backend.approval.history') }}" class="btn btn-ghost btn-sm">Lịch sử duyệt</a>
        @endcan
    </div>

    <div class="section-page">
        <div class="card bg-base-100 mb-4">
            <div class="card-body filter-bar py-3 px-3">
                <div class="filter-grid">

                    <div class="form-control filter-grid-wide">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">Tìm kiếm</span>
                            <span class="label-text-alt text-xs text-base-content/40">Tên, mã, tổ chức</span>
                        </label>
                        <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                            <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" x-model="filters.search" @input.debounce.250ms="onFilterChange()"
                                   placeholder="Nhập từ khoá..." class="grow bg-transparent outline-none text-sm">
                            <button x-show="filters.search" x-cloak @click="clearSearch()"
                                    class="text-base-content/30 hover:text-base-content transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Loại nội dung</span></label>
                        <select id="ts-subject-type" x-model="filters.subject_type" @change="onFilterChange()"
                                data-ts-placeholder="Tất cả loại"
                                class="select select-sm select-bordered w-full">
                            <option value="">Tất cả</option>
                            @foreach ($subjectTypes as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                            @endforeach
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

        <div class="card">
            <div class="card-body p-0 overflow-hidden tabulator-daisy">
                <div id="approval-pending-table"></div>
            </div>
        </div>
    </div>

</div>
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
