@extends('layouts.backend')
@section('title', 'Danh sách nhân viên')


@section('content')
<div x-data="employeeListPage({{ Js::from([
    'apiUrl'         => route('backend.api.employees'),
    'statuses'       => $statuses,
    'employmentTypes'=> $employmentTypes,
    'branches'       => $branches,
    'departments'    => $departments,
    'canDelete'      => auth()->user()->can('delete', \Modules\Employee\Models\Employee::class),
]) }})">

    {{-- ── Page header ──────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Nhân viên</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Quản lý hồ sơ nhân sự của tổ chức</p>
        </div>
        <div class="flex items-center gap-2">

            {{-- Column visibility dropdown --}}
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-ghost btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                    </svg>
                    Cột
                </label>
                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box shadow-lg border border-base-200 w-56 z-50 p-2">
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

            @can('create', \Modules\Employee\Models\Employee::class)
            <a href="{{ route('backend.employees.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm nhân viên
            </a>
            @endcan

        </div>
    </div>

    {{-- ── Stat cards ────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-4">
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Tổng</div>
            <div class="stat-value text-2xl">{{ number_format($totalAll) }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Đang làm việc</div>
            <div class="stat-value text-2xl text-success">{{ number_format($totalActive) }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Thử việc</div>
            <div class="stat-value text-2xl text-info">{{ number_format($totalProbation) }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Nghỉ phép</div>
            <div class="stat-value text-2xl text-warning">{{ number_format($totalOnLeave) }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Hợp đồng</div>
            <div class="stat-value text-2xl text-accent">{{ number_format($totalContractor) }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Đã nghỉ việc</div>
            <div class="stat-value text-2xl text-error">{{ number_format($totalInactive) }}</div>
        </div>
    </div>

    {{-- ── Cảnh báo hợp đồng sắp hết hạn ───────────────────────────────── --}}
    @if($contractAlerts90 > 0)
    <div x-data="{ open: true }" class="mb-4">
        <div class="card bg-base-100 shadow-sm border {{ $contractAlerts30 > 0 ? 'border-error/40' : 'border-warning/40' }}">
            <div class="card-body p-0">
                <button type="button" @click="open = !open"
                        class="flex items-center justify-between w-full px-4 py-3 text-left hover:bg-base-200/50 transition-colors rounded-t-2xl">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 {{ $contractAlerts30 > 0 ? 'text-error' : 'text-warning' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span class="font-semibold text-sm">Hợp đồng sắp hết hạn</span>
                        @if($contractAlerts30 > 0)
                        <span class="badge badge-error badge-sm">{{ $contractAlerts30 }} trong 30 ngày</span>
                        @endif
                        <span class="badge badge-warning badge-sm">{{ $contractAlerts90 }} trong 90 ngày</span>
                    </div>
                    <svg class="w-4 h-4 text-base-content/40 transition-transform"
                         :class="open ? 'rotate-180' : ''"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" x-transition class="border-t border-base-200">
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead>
                                <tr class="text-xs text-base-content/50">
                                    <th>Nhân viên</th>
                                    <th>Phòng ban</th>
                                    <th>Chức danh</th>
                                    <th>Hết hạn HĐ</th>
                                    <th>Còn lại</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($contractAlerts as $alert)
                                @php $daysLeft = now()->diffInDays($alert->contract_end, false); @endphp
                                <tr class="{{ $daysLeft <= 30 ? 'bg-error/5' : '' }}">
                                    <td>
                                        <a href="{{ route('backend.employees.show', $alert->id) }}"
                                           class="font-medium text-primary hover:underline text-sm">{{ $alert->full_name }}</a>
                                        <p class="text-xs font-mono text-base-content/40">{{ $alert->employee_code }}</p>
                                    </td>
                                    <td class="text-sm text-base-content/70">{{ $alert->snap_dept_name ?? '—' }}</td>
                                    <td class="text-sm text-base-content/70">{{ $alert->snap_job_title ?? '—' }}</td>
                                    <td class="text-sm font-medium {{ $daysLeft <= 30 ? 'text-error' : 'text-warning' }}">
                                        {{ \Carbon\Carbon::parse($alert->contract_end)->format('d/m/Y') }}
                                    </td>
                                    <td>
                                        <span class="badge badge-sm {{ $daysLeft <= 30 ? 'badge-error' : 'badge-warning' }}">
                                            {{ $daysLeft }} ngày
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Filter bar ────────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3 px-4 space-y-3">

            {{-- Row 1: search + dropdowns --}}
            <div class="flex flex-wrap gap-3 items-end">

                <div class="form-control flex-1 min-w-52">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Tìm kiếm</span>
                        <span class="label-text-alt text-xs text-base-content/40">Tên, Mã, Email, SĐT, CCCD</span>
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

                <div class="form-control w-44">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Trạng thái</span>
                    </label>
                    <select id="filter-status" class="select select-sm select-bordered w-full"></select>
                </div>

                <div class="form-control w-44">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Loại hợp đồng</span>
                    </label>
                    <select id="filter-employment-type" class="select select-sm select-bordered w-full"></select>
                </div>

                <div class="form-control w-52">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Chi nhánh</span>
                    </label>
                    <select id="filter-branch" class="select select-sm select-bordered w-full"></select>
                </div>

                <div class="form-control w-52">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Phòng ban</span>
                    </label>
                    <select id="filter-department" class="select select-sm select-bordered w-full"></select>
                </div>

            </div>

            {{-- Row 2: date range + presets + reset --}}
            <div class="flex flex-wrap gap-3 items-end">

                <div class="form-control w-64">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Ngày vào làm</span>
                    </label>
                    <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                        <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <input id="filter-date" type="text" readonly
                               placeholder="Chọn khoảng ngày..."
                               class="grow bg-transparent outline-none text-sm cursor-pointer"/>
                        <button x-show="filters.date_from" @click="clearDate()"
                                class="text-base-content/30 hover:text-base-content transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label py-0.5 invisible"><span class="label-text text-xs">.</span></label>
                    <div class="flex gap-1">
                        <button @click="setDatePreset('today')"
                                :class="activeDatePreset === 'today' ? 'btn-primary' : 'btn-ghost'"
                                class="btn btn-xs">Hôm nay</button>
                        <button @click="setDatePreset('month')"
                                :class="activeDatePreset === 'month' ? 'btn-primary' : 'btn-ghost'"
                                class="btn btn-xs">Tháng này</button>
                        <button @click="setDatePreset('year')"
                                :class="activeDatePreset === 'year' ? 'btn-primary' : 'btn-ghost'"
                                class="btn btn-xs">Năm nay</button>
                    </div>
                </div>

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

            {{-- Active filter chips --}}
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

    {{-- ── Tabulator table ───────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-hidden rounded-2xl tabulator-daisy">
            <div id="employee-table"></div>
        </div>
    </div>

</div>

{{-- ── Delete confirm modal ─────────────────────────────────────────────── --}}
<dialog id="employeeDeleteModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xóa</h3>
        <p class="py-3 text-sm text-base-content/70">
            Bạn có chắc muốn xóa nhân viên
            <strong id="employeeDeleteName" class="text-base-content"></strong>?
        </p>
        <p class="text-xs text-error/70">Thao tác này không thể hoàn tác.</p>
        <div class="modal-action mt-4">
            <button id="employeeDeleteConfirmBtn" class="btn btn-error btn-sm">Xóa</button>
            <button class="btn btn-ghost btn-sm" onclick="employeeDeleteModal.close()">Hủy</button>
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
        'resources/js/modules/flatpickr.js',
        'Modules/Employee/resources/assets/js/employee.js',
    ], 'build/backend')
@endpush
