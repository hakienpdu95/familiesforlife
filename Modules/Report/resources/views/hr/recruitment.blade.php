@extends('layouts.backend')
@section('title', 'Báo cáo Tuyển dụng')

@section('breadcrumb')
<nav class="breadcrumb-nav">
    <a href="{{ route('report.index') }}" class="breadcrumb-item">Báo cáo</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ route('report.hr.index') }}" class="breadcrumb-item">Nhân sự</a>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-item">Tuyển dụng</span>
</nav>
@endsection

@section('content')
<div
    x-data="reportHrRecruitment"
    x-init="init()"
    class="space-y-5"
>

    {{-- ── Page header ──────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div>
                <h1 class="text-2xl font-bold text-base-content">Báo cáo Tuyển dụng</h1>
                <p class="text-sm text-base-content/50 mt-0.5">Vị trí mở, ứng viên, tuyển dụng và thời gian tuyển dụng</p>
            </div>
            {{-- Offer Acceptance Rate badge --}}
            <div class="flex items-center gap-1.5 mt-0.5">
                <span class="text-xs text-base-content/50">Tỷ lệ nhận offer:</span>
                <span
                    class="badge badge-lg font-semibold"
                    :class="{
                        'badge-success': (summary.offer_acceptance_rate ?? 0) >= 70,
                        'badge-warning': (summary.offer_acceptance_rate ?? 0) >= 40 && (summary.offer_acceptance_rate ?? 0) < 70,
                        'badge-error':   (summary.offer_acceptance_rate ?? 0) < 40 && summary.offer_acceptance_rate !== undefined,
                        'badge-ghost':   summary.offer_acceptance_rate === undefined
                    }"
                    x-text="summary.offer_acceptance_rate !== undefined ? summary.offer_acceptance_rate + '%' : '—'"
                ></span>
            </div>
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

                {{-- Date range (flatpickr range picker) --}}
                <div class="form-control w-64">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Khoảng thời gian</span>
                    </label>
                    <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                        <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <input id="rpt-rc-date" type="text" readonly
                               placeholder="Từ ngày — Đến ngày"
                               class="grow bg-transparent outline-none text-sm cursor-pointer"/>
                    </div>
                </div>

                {{-- Department TomSelect --}}
                <div class="form-control w-56">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Phòng ban</span>
                    </label>
                    <select id="rpt-rc-dept" class="select select-sm select-bordered w-full"></select>
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

    {{-- ── Error state ─────────────────────────────────────────────────────── --}}
    <div x-show="error" x-cloak
         class="alert alert-error shadow-sm">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <div>
            <p class="font-semibold">Tải dữ liệu thất bại</p>
            <p class="text-sm" x-text="errorMessage"></p>
        </div>
        <button @click="error = false; load()" class="btn btn-sm btn-ghost">Thử lại</button>
    </div>

    {{-- ── Summary cards ──────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">

        {{-- Open Positions --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body py-4 px-5">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-base-content/60">Vị trí đang mở</span>
                    <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>

                {{-- Loading skeleton --}}
                <div x-show="loading" class="animate-pulse space-y-2 mt-1">
                    <div class="h-8 bg-base-300 rounded w-16"></div>
                    <div class="h-3 bg-base-300 rounded w-24"></div>
                </div>

                <div x-show="!loading">
                    <div class="text-3xl font-bold text-base-content" x-text="summary.open_positions ?? '—'"></div>
                    <div class="text-xs text-base-content/50 mt-1">vị trí chưa lấp đầy</div>
                </div>
            </div>
        </div>

        {{-- Total Applications --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body py-4 px-5">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-base-content/60">Tổng ứng viên</span>
                    <div class="w-8 h-8 rounded-lg bg-info/10 flex items-center justify-center">
                        <svg class="w-4 h-4 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </div>

                <div x-show="loading" class="animate-pulse space-y-2 mt-1">
                    <div class="h-8 bg-base-300 rounded w-16"></div>
                    <div class="h-3 bg-base-300 rounded w-24"></div>
                </div>

                <div x-show="!loading">
                    <div class="text-3xl font-bold text-info" x-text="summary.total_applications ?? '—'"></div>
                    <div class="text-xs text-base-content/50 mt-1">hồ sơ trong kỳ</div>
                </div>
            </div>
        </div>

        {{-- Hired --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body py-4 px-5">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-base-content/60">Đã tuyển dụng</span>
                    <div class="w-8 h-8 rounded-lg bg-success/10 flex items-center justify-center">
                        <svg class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>

                <div x-show="loading" class="animate-pulse space-y-2 mt-1">
                    <div class="h-8 bg-base-300 rounded w-16"></div>
                    <div class="h-3 bg-base-300 rounded w-24"></div>
                </div>

                <div x-show="!loading">
                    <div class="text-3xl font-bold text-success" x-text="summary.hired ?? '—'"></div>
                    <div class="flex gap-1 mt-1 flex-wrap">
                        <template x-if="summary.total_applications > 0 && summary.hired !== undefined">
                            <span class="badge badge-xs badge-success">
                                <span x-text="Math.round(summary.hired / summary.total_applications * 100) + '% tỷ lệ chuyển đổi'"></span>
                            </span>
                        </template>
                        <template x-if="!(summary.total_applications > 0 && summary.hired !== undefined)">
                            <span class="text-xs text-base-content/50">trong kỳ đã chọn</span>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Avg Days to Hire --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body py-4 px-5">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-base-content/60">TB ngày tuyển dụng</span>
                    <div class="w-8 h-8 rounded-lg bg-warning/10 flex items-center justify-center">
                        <svg class="w-4 h-4 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>

                <div x-show="loading" class="animate-pulse space-y-2 mt-1">
                    <div class="h-8 bg-base-300 rounded w-16"></div>
                    <div class="h-3 bg-base-300 rounded w-24"></div>
                </div>

                <div x-show="!loading">
                    <div class="flex items-baseline gap-1">
                        <div class="text-3xl font-bold text-warning" x-text="summary.avg_days_to_hire ?? '—'"></div>
                        <span class="text-sm text-base-content/50" x-show="summary.avg_days_to_hire !== undefined">ngày</span>
                    </div>
                    <div class="text-xs text-base-content/50 mt-1">từ đăng tin đến onboard</div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Charts row ──────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Recruitment funnel (horizontal bar / funnel) --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-base-content">Phễu tuyển dụng</h2>
                    <span class="badge badge-sm badge-ghost">Funnel</span>
                </div>

                <div x-show="loading" class="flex items-center justify-center h-64">
                    <div class="space-y-3 w-full px-4 animate-pulse">
                        <div class="h-6 bg-base-300 rounded w-full"></div>
                        <div class="h-6 bg-base-300 rounded w-4/5"></div>
                        <div class="h-6 bg-base-300 rounded w-3/5"></div>
                        <div class="h-6 bg-base-300 rounded w-2/5"></div>
                        <div class="h-6 bg-base-300 rounded w-1/4"></div>
                    </div>
                </div>

                <div x-show="error" class="flex items-center justify-center h-64 text-base-content/30">
                    <p class="text-sm">Không thể tải dữ liệu biểu đồ</p>
                </div>

                <div x-show="!loading && !error" id="chart-rc-funnel" style="height: 260px;"></div>
            </div>
        </div>

        {{-- Monthly applications bar chart --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-base-content">Ứng viên &amp; Tuyển dụng theo tháng</h2>
                    <span class="badge badge-sm badge-ghost">Bar chart</span>
                </div>

                <div x-show="loading" class="flex items-center justify-center h-64">
                    <div class="space-y-3 w-full px-4 animate-pulse">
                        <div class="flex items-end gap-2 h-40 justify-around">
                            <template x-for="i in [1,2,3,4,5,6]" :key="i">
                                <div class="flex flex-col items-center gap-1 w-full">
                                    <div class="bg-base-300 rounded w-full" :style="'height: ' + (Math.random() * 60 + 40) + '%'"></div>
                                </div>
                            </template>
                        </div>
                        <div class="h-3 bg-base-300 rounded w-full"></div>
                    </div>
                </div>

                <div x-show="error" class="flex items-center justify-center h-64 text-base-content/30">
                    <p class="text-sm">Không thể tải dữ liệu biểu đồ</p>
                </div>

                <div x-show="!loading && !error" id="chart-rc-monthly" style="height: 260px;"></div>
            </div>
        </div>

    </div>

    {{-- ── Open Jobs table ─────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-base-content">Vị trí đang tuyển dụng</h2>
                <span class="badge badge-sm badge-primary" x-text="openJobs.length + ' vị trí'"></span>
            </div>

            {{-- Loading skeleton --}}
            <div x-show="loading" class="space-y-2 animate-pulse">
                <template x-for="i in [1,2,3,4,5]" :key="i">
                    <div class="flex gap-4">
                        <div class="h-4 bg-base-300 rounded w-1/3"></div>
                        <div class="h-4 bg-base-300 rounded w-1/4"></div>
                        <div class="h-4 bg-base-300 rounded w-16"></div>
                    </div>
                </template>
            </div>

            {{-- Error state --}}
            <div x-show="error && !loading"
                 class="flex flex-col items-center justify-center py-12 text-base-content/40">
                <svg class="w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                <p class="text-sm">Không thể tải danh sách vị trí</p>
                <button @click="load()" class="btn btn-xs btn-ghost mt-2">Thử lại</button>
            </div>

            {{-- Empty state --}}
            <div x-show="!loading && !error && openJobs.length === 0"
                 class="flex flex-col items-center justify-center py-12 text-base-content/40">
                <svg class="w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                <p class="text-sm">Không có vị trí nào đang tuyển dụng</p>
            </div>

            {{-- Table --}}
            <div x-show="!loading && !error && openJobs.length > 0" class="overflow-x-auto">
                <table class="table table-sm w-full">
                    <thead>
                        <tr class="text-xs text-base-content/60 border-b border-base-200">
                            <th class="font-medium py-2 pl-0">#</th>
                            <th class="font-medium py-2">Vị trí tuyển dụng</th>
                            <th class="font-medium py-2">Phòng ban</th>
                            <th class="font-medium py-2 text-right">Số ngày mở</th>
                            <th class="font-medium py-2 text-right">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(job, index) in openJobs" :key="job.id ?? index">
                            <tr class="border-b border-base-100 hover:bg-base-50 transition-colors">
                                <td class="py-2.5 pl-0 text-xs text-base-content/40" x-text="index + 1"></td>
                                <td class="py-2.5">
                                    <span class="font-medium text-sm" x-text="job.title"></span>
                                    <template x-if="job.headcount">
                                        <span class="text-xs text-base-content/40 ml-1.5"
                                              x-text="'(' + job.headcount + ' HC)'"></span>
                                    </template>
                                </td>
                                <td class="py-2.5">
                                    <span class="badge badge-sm badge-ghost" x-text="job.department ?? '—'"></span>
                                </td>
                                <td class="py-2.5 text-right">
                                    <span
                                        class="font-semibold text-sm"
                                        :class="{
                                            'text-error':   (job.days_open ?? 0) > 60,
                                            'text-warning': (job.days_open ?? 0) > 30 && (job.days_open ?? 0) <= 60,
                                            'text-success': (job.days_open ?? 0) <= 30
                                        }"
                                        x-text="job.days_open !== undefined ? job.days_open + ' ngày' : '—'"
                                    ></span>
                                </td>
                                <td class="py-2.5 text-right">
                                    <template x-if="(job.days_open ?? 0) > 60">
                                        <span class="badge badge-xs badge-error">Quá hạn</span>
                                    </template>
                                    <template x-if="(job.days_open ?? 0) > 30 && (job.days_open ?? 0) <= 60">
                                        <span class="badge badge-xs badge-warning">Chậm</span>
                                    </template>
                                    <template x-if="(job.days_open ?? 0) <= 30 && job.days_open !== undefined">
                                        <span class="badge badge-xs badge-success">Đúng tiến độ</span>
                                    </template>
                                    <template x-if="job.days_open === undefined">
                                        <span class="badge badge-xs badge-ghost">—</span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-base-200 font-semibold">
                            <td colspan="2" class="py-2.5 pl-0 text-sm">Tổng cộng</td>
                            <td class="py-2.5 text-sm" x-text="openJobs.length + ' vị trí'"></td>
                            <td class="py-2.5 text-right text-sm"
                                x-text="openJobs.reduce((s, j) => s + (j.days_open ?? 0), 0) > 0
                                    ? 'TB ' + Math.round(openJobs.reduce((s, j) => s + (j.days_open ?? 0), 0) / openJobs.length) + ' ngày'
                                    : '—'">
                            </td>
                            <td></td>
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
        window.API_URL          = "{{ route('api.report.hr.recruitment') }}";
        window.DEPT_OPTIONS_URL = "{{ route('api.departments.options') }}";
    </script>
@endpush
