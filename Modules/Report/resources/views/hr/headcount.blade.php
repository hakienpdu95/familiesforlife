@extends('layouts.backend')
@section('title', 'Biến động nhân sự')

@section('breadcrumb')
<nav class="breadcrumb-nav">
    <a href="{{ route('report.index') }}" class="breadcrumb-item">Báo cáo</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ route('report.hr.index') }}" class="breadcrumb-item">Nhân sự</a>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-item">Biến động nhân sự</span>
</nav>
@endsection

@section('content')
<div
    x-data="reportHrHeadcount"
    x-init="init()"
    class="space-y-5"
>

    {{-- ── Page header ──────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Biến động nhân sự</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Headcount, tuyển dụng và nghỉ việc theo thời gian</p>
        </div>
        <button @click="load()" :disabled="loading"
                class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" :class="loading && 'animate-spin'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Làm mới
        </button>
    </div>

    {{-- ── Filter bar ────────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body py-3 px-4">
            <div class="flex flex-wrap gap-3 items-end">

                {{-- Date from --}}
                <div class="form-control w-44">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Từ ngày</span>
                    </label>
                    <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                        <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <input id="filter-date-from" type="text" readonly
                               placeholder="Từ ngày..."
                               class="grow bg-transparent outline-none text-sm cursor-pointer"/>
                    </div>
                </div>

                {{-- Date to --}}
                <div class="form-control w-44">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Đến ngày</span>
                    </label>
                    <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                        <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <input id="filter-date-to" type="text" readonly
                               placeholder="Đến ngày..."
                               class="grow bg-transparent outline-none text-sm cursor-pointer"/>
                    </div>
                </div>

                {{-- Branch --}}
                <div class="form-control w-52">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Chi nhánh</span>
                    </label>
                    <select id="filter-branch" class="select select-sm select-bordered w-full"></select>
                </div>

                {{-- Department --}}
                <div class="form-control w-52">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Phòng ban</span>
                    </label>
                    <select id="filter-department" class="select select-sm select-bordered w-full"></select>
                </div>

                {{-- Date presets --}}
                <div class="form-control">
                    <label class="label py-0.5 invisible"><span class="label-text text-xs">.</span></label>
                    <div class="flex gap-1">
                        <button @click="setPreset('month')"
                                :class="activePreset === 'month' ? 'btn-primary' : 'btn-ghost'"
                                class="btn btn-xs">Tháng này</button>
                        <button @click="setPreset('quarter')"
                                :class="activePreset === 'quarter' ? 'btn-primary' : 'btn-ghost'"
                                class="btn btn-xs">Quý này</button>
                        <button @click="setPreset('year')"
                                :class="activePreset === 'year' ? 'btn-primary' : 'btn-ghost'"
                                class="btn btn-xs">Năm nay</button>
                    </div>
                </div>

                {{-- Apply / Reset --}}
                <div class="form-control">
                    <label class="label py-0.5 invisible"><span class="label-text text-xs">.</span></label>
                    <div class="flex gap-2">
                        <button @click="load()" :disabled="loading" class="btn btn-primary btn-sm gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Xem báo cáo
                        </button>
                        <button @click="reset()" class="btn btn-ghost btn-sm gap-1.5 text-error">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Đặt lại
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Summary cards ──────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">

        {{-- Total Working --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body py-4 px-5">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-base-content/60">Đang làm việc</span>
                    <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-bold text-base-content" x-text="summary.total_working ?? '—'"></div>
                <div class="flex gap-1 mt-1 flex-wrap">
                    <span class="badge badge-xs badge-ghost" x-show="summary.total_active > 0">
                        <span x-text="summary.total_active"></span> chính thức
                    </span>
                    <span class="badge badge-xs badge-warning" x-show="summary.total_probation > 0">
                        <span x-text="summary.total_probation"></span> thử việc
                    </span>
                </div>
            </div>
        </div>

        {{-- New Hires --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body py-4 px-5">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-base-content/60">Tuyển mới</span>
                    <div class="w-8 h-8 rounded-lg bg-success/10 flex items-center justify-center">
                        <svg class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-bold text-success" x-text="summary.new_hires ?? '—'"></div>
                <div class="text-xs text-base-content/50 mt-1">trong kỳ đã chọn</div>
            </div>
        </div>

        {{-- Resigned --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body py-4 px-5">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-base-content/60">Nghỉ việc</span>
                    <div class="w-8 h-8 rounded-lg bg-error/10 flex items-center justify-center">
                        <svg class="w-4 h-4 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6"/>
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-bold text-error" x-text="summary.total_resigned ?? '—'"></div>
                <div class="text-xs text-base-content/50 mt-1">trong kỳ đã chọn</div>
            </div>
        </div>

        {{-- Net Change --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body py-4 px-5">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-base-content/60">Biến động ròng</span>
                    <div class="w-8 h-8 rounded-lg bg-info/10 flex items-center justify-center">
                        <svg class="w-4 h-4 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-bold"
                     :class="(summary.net_change ?? 0) >= 0 ? 'text-success' : 'text-error'"
                     x-text="(summary.net_change !== undefined ? (summary.net_change >= 0 ? '+' : '') + summary.net_change : '—')">
                </div>
                <div class="text-xs text-base-content/50 mt-1">tuyển mới - nghỉ việc</div>
            </div>
        </div>

    </div>

    {{-- ── Charts row ──────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Trend line chart --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-base-content">Xu hướng nhân sự theo tháng</h2>
                    <span class="badge badge-sm badge-ghost">Line chart</span>
                </div>
                <div x-show="loading" class="flex items-center justify-center h-64">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                </div>
                <div x-show="!loading" id="chart-headcount-trend" style="height: 260px;"></div>
            </div>
        </div>

        {{-- Department bar chart --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-base-content">Nhân sự theo phòng ban</h2>
                    <span class="badge badge-sm badge-ghost">Bar chart</span>
                </div>
                <div x-show="loading" class="flex items-center justify-center h-64">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                </div>
                <div x-show="!loading" id="chart-headcount-dept" style="height: 260px;"></div>
            </div>
        </div>

    </div>

    {{-- ── By-department table ─────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-base-content">Chi tiết theo phòng ban</h2>
                <span class="badge badge-sm badge-primary" x-text="byDepartment.length + ' phòng ban'"></span>
            </div>

            <div x-show="loading" class="flex items-center justify-center py-12">
                <span class="loading loading-spinner loading-md text-primary"></span>
            </div>

            <div x-show="!loading && byDepartment.length === 0"
                 class="flex flex-col items-center justify-center py-12 text-base-content/40">
                <svg class="w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <p class="text-sm">Không có dữ liệu</p>
            </div>

            <div x-show="!loading && byDepartment.length > 0" class="overflow-x-auto">
                <table class="table table-sm w-full">
                    <thead>
                        <tr class="text-xs text-base-content/60 border-b border-base-200">
                            <th class="font-medium py-2 pl-0">#</th>
                            <th class="font-medium py-2">Phòng ban</th>
                            <th class="font-medium py-2 text-right">Nhân sự</th>
                            <th class="font-medium py-2 text-right">Hạn mức</th>
                            <th class="font-medium py-2">Tỉ lệ lấp đầy</th>
                            <th class="font-medium py-2 text-right">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, index) in byDepartment" :key="row.department_id">
                            <tr class="border-b border-base-100 hover:bg-base-50 transition-colors">
                                <td class="py-2.5 pl-0 text-xs text-base-content/40" x-text="index + 1"></td>
                                <td class="py-2.5">
                                    <span class="font-medium text-sm" x-text="row.name"></span>
                                </td>
                                <td class="py-2.5 text-right font-semibold" x-text="row.count"></td>
                                <td class="py-2.5 text-right text-base-content/60"
                                    x-text="row.headcount_limit ? row.headcount_limit : '—'"></td>
                                <td class="py-2.5 w-40">
                                    <template x-if="row.headcount_limit > 0">
                                        <div>
                                            <div class="flex justify-between text-xs mb-1">
                                                <span x-text="Math.round(row.count / row.headcount_limit * 100) + '%'"></span>
                                            </div>
                                            <progress class="progress w-full h-1.5"
                                                      :class="row.count >= row.headcount_limit ? 'progress-error' : 'progress-primary'"
                                                      :value="row.count"
                                                      :max="row.headcount_limit"></progress>
                                        </div>
                                    </template>
                                    <template x-if="!row.headcount_limit">
                                        <span class="text-xs text-base-content/30">Chưa đặt hạn mức</span>
                                    </template>
                                </td>
                                <td class="py-2.5 text-right">
                                    <template x-if="row.headcount_limit > 0 && row.count >= row.headcount_limit">
                                        <span class="badge badge-xs badge-error">Đạt hạn mức</span>
                                    </template>
                                    <template x-if="row.headcount_limit > 0 && row.count < row.headcount_limit">
                                        <span class="badge badge-xs badge-success">Còn chỗ</span>
                                    </template>
                                    <template x-if="!row.headcount_limit">
                                        <span class="badge badge-xs badge-ghost">—</span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-base-200 font-semibold">
                            <td colspan="2" class="py-2.5 pl-0 text-sm">Tổng cộng</td>
                            <td class="py-2.5 text-right text-sm" x-text="byDepartment.reduce((s, r) => s + r.count, 0)"></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    @vite([
        'resources/js/modules/echarts.js',
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/tom-select.js',
    ], 'build/backend')
    @vite([
        'Modules/Report/resources/assets/js/report.js',
    ], 'build/backend')

    <script>
        window.__REPORT_HR_HEADCOUNT__ = {
            API_URL:            "{{ route('api.report.hr.headcount') }}",
            BRANCH_OPTIONS_URL: "{{ route('api.branches.options') }}",
            DEPT_OPTIONS_URL:   "{{ route('api.departments.options') }}",
        };
    </script>
@endpush
