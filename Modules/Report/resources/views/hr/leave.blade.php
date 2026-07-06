@extends('layouts.backend')
@section('title', 'Báo cáo nghỉ phép')

@section('breadcrumb')
<nav class="breadcrumb-nav">
    <a href="{{ route('report.index') }}" class="breadcrumb-item">Báo cáo</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ route('report.hr.index') }}" class="breadcrumb-item">Nhân sự</a>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-item active">Nghỉ phép</span>
</nav>
@endsection

@section('content')

<div x-data="reportHrLeave">

    {{-- ── Page header ──────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Báo cáo Nghỉ phép</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Thống kê nghỉ phép theo loại, phòng ban và nhân viên</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="exportCsv()" class="btn btn-ghost btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Xuất CSV
            </button>
        </div>
    </div>

    {{-- ── Filter bar ────────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
        <div class="card-body py-3 px-4">
            <div class="flex flex-wrap gap-3 items-end">

                {{-- Year selector --}}
                <div class="form-control w-32">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Năm</span>
                    </label>
                    <select id="filter-year"
                            x-model="filters.year"
                            @change="onFilterChange()"
                            class="select select-sm select-bordered w-full">
                        @php $currentYear = now()->year; @endphp
                        @for ($y = $currentYear; $y >= $currentYear - 2; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                {{-- Leave type TomSelect --}}
                <div class="form-control w-52">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Loại nghỉ phép</span>
                    </label>
                    <select id="filter-leave-type" class="select select-sm select-bordered w-full"></select>
                </div>

                {{-- Branch TomSelect remote --}}
                <div class="form-control w-52">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Chi nhánh</span>
                    </label>
                    <select id="filter-branch" class="select select-sm select-bordered w-full"></select>
                </div>

                {{-- Department TomSelect remote --}}
                <div class="form-control w-52">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Phòng ban</span>
                    </label>
                    <select id="filter-dept" class="select select-sm select-bordered w-full"></select>
                </div>

                {{-- Reset button --}}
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
                 class="flex flex-wrap gap-2 pt-2 border-t border-base-200 mt-2">
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

    {{-- ── Loading overlay ───────────────────────────────────────────────── --}}
    <div x-show="loading" x-transition.opacity class="flex items-center justify-center py-10">
        <span class="loading loading-spinner loading-md text-primary"></span>
        <span class="ml-2 text-sm text-base-content/50">Đang tải dữ liệu...</span>
    </div>

    <div x-show="!loading" x-transition>

        {{-- ── Summary cards ──────────────────────────────────────────────── --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">

            <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
                <div class="stat-title text-xs">Tổng đơn nghỉ phép</div>
                <div class="stat-value text-2xl text-primary" x-text="stats.total_requests ?? '—'"></div>
                <div class="stat-desc text-xs">trong năm <span x-text="filters.year"></span></div>
            </div>

            <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
                <div class="stat-title text-xs">Tổng ngày đã nghỉ</div>
                <div class="stat-value text-2xl text-warning" x-text="stats.total_days ?? '—'"></div>
                <div class="stat-desc text-xs">ngày làm việc</div>
            </div>

            <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
                <div class="stat-title text-xs">Đơn chờ duyệt</div>
                <div class="stat-value text-2xl text-error" x-text="stats.pending_requests ?? '—'"></div>
                <div class="stat-desc text-xs">cần xử lý</div>
            </div>

            <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
                <div class="stat-title text-xs">TB ngày/nhân viên</div>
                <div class="stat-value text-2xl text-success" x-text="stats.avg_days_per_employee ? Number(stats.avg_days_per_employee).toFixed(1) : '—'"></div>
                <div class="stat-desc text-xs">ngày trung bình</div>
            </div>

        </div>

        {{-- ── Charts row ──────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-5 mb-5">

            {{-- Donut: leave type breakdown --}}
            <div class="lg:col-span-2 card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <h2 class="font-semibold text-sm mb-1">Phân bổ theo loại nghỉ phép</h2>
                    <div id="chart-leave-type" class="w-full" style="height:260px;"></div>
                </div>
            </div>

            {{-- Bar: monthly trend --}}
            <div class="lg:col-span-3 card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">
                    <h2 class="font-semibold text-sm mb-1">Số ngày nghỉ theo tháng</h2>
                    <div id="chart-leave-monthly" class="w-full" style="height:260px;"></div>
                </div>
            </div>

        </div>

        {{-- ── Top requesters table ────────────────────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-0">
                <div class="px-5 pt-4 pb-3 border-b border-base-200 flex items-center justify-between">
                    <h2 class="font-semibold text-sm">Top nhân viên nghỉ phép nhiều nhất</h2>
                    <span class="badge badge-ghost badge-sm" x-text="'Top ' + (topRequesters.length || 0)"></span>
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead class="bg-base-200/50">
                            <tr class="text-xs text-base-content/60">
                                <th>#</th>
                                <th>Nhân viên</th>
                                <th>Phòng ban</th>
                                <th>Chi nhánh</th>
                                <th class="text-right">Tổng đơn</th>
                                <th class="text-right">Tổng ngày</th>
                                <th>Loại nghỉ nhiều nhất</th>
                                <th>Đơn gần nhất</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-if="topRequesters.length === 0">
                                <tr>
                                    <td colspan="8" class="text-center text-sm text-base-content/40 py-10">
                                        Chưa có dữ liệu nghỉ phép cho kỳ này.
                                    </td>
                                </tr>
                            </template>
                            <template x-for="(row, idx) in topRequesters" :key="row.employee_id ?? idx">
                                <tr class="hover:bg-base-200/30">
                                    <td class="text-sm text-base-content/40 font-mono" x-text="idx + 1"></td>
                                    <td>
                                        <div class="font-medium text-sm text-base-content" x-text="row.employee_name"></div>
                                        <div class="text-xs text-base-content/40 font-mono" x-text="row.employee_code ?? ''"></div>
                                    </td>
                                    <td class="text-sm text-base-content/70" x-text="row.department ?? '—'"></td>
                                    <td class="text-sm text-base-content/70" x-text="row.branch ?? '—'"></td>
                                    <td class="text-right text-sm font-semibold" x-text="row.total_requests"></td>
                                    <td class="text-right">
                                        <span class="badge badge-warning badge-sm" x-text="row.total_days + ' ngày'"></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-ghost badge-sm capitalize" x-text="row.top_leave_type_label ?? row.top_leave_type ?? '—'"></span>
                                    </td>
                                    <td class="text-xs text-base-content/50" x-text="row.last_request_date ?? '—'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>{{-- /x-show="!loading" --}}

</div>{{-- /x-data --}}

@endsection

@push('scripts')
@vite([
    'resources/js/modules/echarts.js',
    'resources/js/modules/flatpickr.js',
    'resources/js/modules/tom-select.js',
    'Modules/Report/resources/assets/js/report.js',
], 'build/backend')
<script>
window.REPORT_HR_LEAVE_CONFIG = {
    API_URL:          '{{ route('api.report.hr.leave') }}',
    BRANCH_OPTIONS_URL: '{{ route('api.branches.options') }}',
    DEPT_OPTIONS_URL:   '{{ route('api.departments.options') }}',
    currentYear:      {{ now()->year }},
};
</script>
@endpush
