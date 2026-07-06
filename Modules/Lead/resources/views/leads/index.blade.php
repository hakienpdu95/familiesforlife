@extends('layouts.backend')
@section('title', 'Danh sách cơ hội')


@section('content')
<div x-data="leadListPage">

    {{-- ── Page header ──────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Cơ hội bán hàng</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Quản lý và theo dõi tất cả cơ hội trong tổ chức</p>
        </div>
        <div class="flex items-center gap-2">

            {{-- View toggle --}}
            <div class="join">
                <button class="join-item btn btn-sm"
                        :class="view === 'list' ? 'btn-primary' : 'btn-ghost'"
                        @click="view = 'list'" title="Danh sách">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                </button>
                <button class="join-item btn btn-sm"
                        :class="view === 'kanban' ? 'btn-primary' : 'btn-ghost'"
                        @click="switchToKanban()" title="Kanban">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </button>
            </div>

            {{-- Column toggle (list only) --}}
            <div class="dropdown dropdown-end" x-show="view === 'list'" x-transition>
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

            @can('create', \Modules\Lead\Models\Lead::class)
            <a href="{{ route('lead.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm cơ hội
            </a>
            @endcan

        </div>
    </div>

    {{-- ── Flash messages ───────────────────────────────────────────── --}}
    @foreach(['success','info','error'] as $type)
    @if(session($type))
    <div class="alert alert-{{ $type }} text-sm py-2 px-4 rounded-lg mb-4">{{ session($type) }}</div>
    @endif
    @endforeach

    {{-- ── Filter bar ───────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3 px-4 space-y-3">

            <div class="flex flex-wrap gap-3 items-end">

                {{-- Search --}}
                <div class="form-control flex-1 min-w-52">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Tìm kiếm</span>
                        <span class="label-text-alt text-xs text-base-content/40">Tên, SĐT, Công ty, Tiêu đề</span>
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

                {{-- Stage --}}
                <div class="form-control w-48">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Tình trạng</span></label>
                    <select id="filter-stage" class="select select-sm select-bordered w-full"></select>
                </div>

                {{-- Source --}}
                <div class="form-control w-44">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Nguồn</span></label>
                    <select id="filter-source" class="select select-sm select-bordered w-full"></select>
                </div>

                {{-- Status --}}
                <div class="form-control w-40">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Trạng thái</span></label>
                    <select id="filter-status" class="select select-sm select-bordered w-full"></select>
                </div>

            </div>

            <div class="flex flex-wrap gap-3 items-end">

                {{-- Closing date range --}}
                <div class="form-control w-64">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Ngày chốt dự kiến</span>
                    </label>
                    <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                        <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <input id="filter-closing" type="text" readonly
                               placeholder="Chọn khoảng ngày..."
                               class="grow bg-transparent outline-none text-sm cursor-pointer"/>
                        <button x-show="filters.closing_after" @click="clearClosing()"
                                class="text-base-content/30 hover:text-base-content transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Quick presets --}}
                <div class="form-control">
                    <label class="label py-0.5 invisible"><span class="label-text text-xs">.</span></label>
                    <div class="flex gap-1">
                        <button @click="setClosingPreset('week')"
                                :class="closingPreset === 'week' ? 'btn-primary' : 'btn-ghost'"
                                class="btn btn-xs">7 ngày</button>
                        <button @click="setClosingPreset('month')"
                                :class="closingPreset === 'month' ? 'btn-primary' : 'btn-ghost'"
                                class="btn btn-xs">Tháng này</button>
                        <button @click="setClosingPreset('overdue')"
                                :class="closingPreset === 'overdue' ? 'btn-warning' : 'btn-ghost'"
                                class="btn btn-xs">Quá hạn</button>
                    </div>
                </div>

                {{-- Reset --}}
                <div class="form-control ml-auto">
                    <label class="label py-0.5 invisible"><span class="label-text text-xs">.</span></label>
                    <button @click="reset()" x-show="hasFilters" x-transition
                            class="btn btn-ghost btn-sm gap-1.5 text-error">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
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

    {{-- ── LIST VIEW: Tabulator ──────────────────────────────────────── --}}
    <div x-show="view === 'list'" x-transition>
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-0 overflow-hidden rounded-2xl tabulator-daisy">
                <div id="lead-table"></div>
            </div>
        </div>
    </div>

    {{-- ── KANBAN VIEW ───────────────────────────────────────────────── --}}
    <div x-show="view === 'kanban'" x-transition>

        <div x-show="kanbanLoading" class="flex justify-center items-center py-24">
            <span class="loading loading-spinner loading-lg text-primary"></span>
        </div>

        <div x-show="!kanbanLoading" class="flex gap-4 overflow-x-auto pb-4">
            <template x-for="col in kanbanBoard" :key="col.stage.id">
                <div class="flex-none w-72">

                    {{-- Column header --}}
                    <div class="flex items-center justify-between mb-3 px-1">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full"
                                  :style="'background:' + (col.stage.color || '#94a3b8')"></span>
                            <span class="font-semibold text-sm" x-text="col.stage.label"></span>
                        </div>
                        <span class="badge badge-ghost badge-sm" x-text="col.total"></span>
                    </div>

                    {{-- Cards --}}
                    <div class="space-y-2 min-h-16">
                        <template x-for="lead in col.leads" :key="lead.id">
                            <a :href="lead.show_url || ('/leads/' + lead.id)"
                               class="block card bg-base-100 border border-base-200 shadow-sm hover:shadow-md hover:border-primary/30 transition-all p-3">
                                <p class="font-medium text-sm line-clamp-2 mb-2" x-text="lead.title || lead.contact_name"></p>
                                <p class="text-xs text-base-content/50 mb-2" x-text="lead.contact_company || ''"></p>
                                <div class="flex items-center justify-between gap-1 mt-auto">
                                    <span class="text-xs font-medium text-success" x-show="lead.expected_value"
                                          x-text="formatCurrency(lead.expected_value, lead.currency)"></span>
                                    <span class="ml-auto">
                                        <template x-if="lead.lead_score >= 70">
                                            <span class="badge badge-xs badge-error" x-text="lead.lead_score + 'đ'"></span>
                                        </template>
                                        <template x-if="lead.lead_score >= 40 && lead.lead_score < 70">
                                            <span class="badge badge-xs badge-warning" x-text="lead.lead_score + 'đ'"></span>
                                        </template>
                                        <template x-if="lead.lead_score < 40 && lead.lead_score != null">
                                            <span class="badge badge-xs badge-ghost" x-text="lead.lead_score + 'đ'"></span>
                                        </template>
                                    </span>
                                </div>
                                <div class="flex items-center justify-between mt-2 pt-2 border-t border-base-200">
                                    <span class="text-xs text-base-content/40" x-text="lead.assignee_name || 'Chưa phân công'"></span>
                                    <span class="text-xs text-base-content/40" x-show="lead.expected_close_date"
                                          x-text="formatDate(lead.expected_close_date)"></span>
                                </div>
                            </a>
                        </template>

                        <div x-show="col.leads.length === 0"
                             class="rounded-xl border-2 border-dashed border-base-200 p-6 text-center">
                            <p class="text-xs text-base-content/25">Không có cơ hội</p>
                        </div>
                    </div>

                </div>
            </template>

            <div x-show="kanbanBoard.length === 0 && !kanbanLoading"
                 class="flex-1 flex items-center justify-center py-24">
                <p class="text-base-content/30 text-sm">Không có dữ liệu kanban</p>
            </div>
        </div>

    </div>

</div>

{{-- ── Delete confirm modal ──────────────────────────────────────────────── --}}
<dialog id="deleteModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xóa</h3>
        <p class="py-3 text-sm text-base-content/70">
            Bạn có chắc muốn xóa cơ hội
            <strong id="deleteItemName" class="text-base-content"></strong>?
        </p>
        <p class="text-xs text-error/70">Toàn bộ lịch sử và hoạt động liên quan sẽ bị xóa theo.</p>
        <div class="modal-action mt-4">
            <button id="confirmDeleteBtn" class="btn btn-error btn-sm">Xóa</button>
            <button class="btn btn-ghost btn-sm" onclick="deleteModal.close()">Hủy</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

@endsection

@push('styles')
<x-tabulator-theme />
@endpush

@push('scripts')
@php
    $leadListData = json_encode([
        'apiListing'  => route('api.api.lead.listing'),
        'apiKanban'   => route('api.api.lead.kanban'),
        'csrf'        => csrf_token(),
        'stages'      => $stages->values(),
        'sources'     => $sources->values(),
        'maskContact' => $maskContact,
        'canDelete'   => auth()->user()->can('delete', new \Modules\Lead\Models\Lead),
    ]);
@endphp
<script id="lead-list-data" type="application/json">{!! $leadListData !!}</script>
@vite([
    'resources/js/modules/tabulator.js',
    'resources/js/modules/tom-select.js',
    'resources/js/modules/flatpickr.js',
    'Modules/Lead/resources/assets/js/lead.js',
], 'build/backend')
{{-- Không còn inline script — toàn bộ logic nằm trong lead-index.js --}}
@endpush
