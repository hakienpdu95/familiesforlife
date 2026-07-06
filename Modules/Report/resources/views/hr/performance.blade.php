@extends('layouts.backend')
@section('title', 'Đánh giá hiệu suất')

@section('breadcrumb')
<nav class="breadcrumb-nav">
    <a href="{{ route('report.index') }}" class="breadcrumb-item">Báo cáo</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ route('report.hr.index') }}" class="breadcrumb-item">Nhân sự</a>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-item active">Đánh giá hiệu suất</span>
</nav>
@endsection

@section('content')

<div
    x-data="reportHrPerformance"
    x-init="init()"
    class="space-y-5"
>

    {{-- ── Page header ──────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Đánh giá hiệu suất</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Tổng hợp kết quả đánh giá nhân viên theo kỳ và phòng ban</p>
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

                {{-- Period select --}}
                <div class="form-control w-44">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Kỳ đánh giá</span>
                    </label>
                    <select x-model="filters.period"
                            @change="load()"
                            class="select select-sm select-bordered w-full">
                        @php
                            $currentYear = now()->year;
                        @endphp
                        @for ($y = $currentYear; $y >= $currentYear - 1; $y--)
                            <option value="Q1-{{ $y }}">Q1-{{ $y }}</option>
                            <option value="Q2-{{ $y }}">Q2-{{ $y }}</option>
                            <option value="Q3-{{ $y }}">Q3-{{ $y }}</option>
                            <option value="Q4-{{ $y }}">Q4-{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                {{-- Department TomSelect remote --}}
                <div class="form-control w-52">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Phòng ban</span>
                    </label>
                    <select id="rpt-pf-dept" class="select select-sm select-bordered w-full"></select>
                </div>

                {{-- Reset button --}}
                <div class="form-control">
                    <label class="label py-0.5 invisible"><span class="label-text text-xs">.</span></label>
                    <button @click="reset()"
                            class="btn btn-ghost btn-sm gap-1.5 text-error">
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

    {{-- ── Loading overlay ───────────────────────────────────────────────── --}}
    <div x-show="loading" x-transition.opacity class="flex items-center justify-center py-10">
        <span class="loading loading-spinner loading-md text-primary"></span>
        <span class="ml-2 text-sm text-base-content/50">Đang tải dữ liệu...</span>
    </div>

    <div x-show="!loading" x-transition>

        {{-- ── Summary cards ──────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">

            {{-- Total Reviews --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body py-4 px-5">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-medium text-base-content/60">Tổng đánh giá</span>
                        <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-base-content" x-text="summary.total_reviews ?? '—'"></div>
                    <div class="text-xs text-base-content/50 mt-1">kỳ <span x-text="filters.period"></span></div>
                </div>
            </div>

            {{-- Completed --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body py-4 px-5">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-medium text-base-content/60">Đã hoàn thành</span>
                        <div class="w-8 h-8 rounded-lg bg-success/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-success" x-text="summary.completed ?? '—'"></div>
                    <div class="text-xs text-base-content/50 mt-1">đánh giá hoàn tất</div>
                </div>
            </div>

            {{-- Avg Score --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body py-4 px-5">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-medium text-base-content/60">Điểm TB</span>
                        <div class="w-8 h-8 rounded-lg bg-warning/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-warning"
                         x-text="summary.avg_overall_score != null ? Number(summary.avg_overall_score).toFixed(1) : '—'"></div>
                    <div class="text-xs text-base-content/50 mt-1">điểm trung bình</div>
                </div>
            </div>

            {{-- Completion Rate --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body py-4 px-5">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-medium text-base-content/60">Tỉ lệ hoàn thành</span>
                        <div class="w-8 h-8 rounded-lg bg-info/10 flex items-center justify-center">
                            <svg class="w-4 h-4 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-3xl font-bold text-info"
                         x-text="summary.completion_rate_pct != null ? Number(summary.completion_rate_pct).toFixed(1) + '%' : '—'"></div>
                    <div class="text-xs text-base-content/50 mt-1">hoàn tất / tổng số</div>
                </div>
            </div>

        </div>

        {{-- ── Charts row ──────────────────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 mt-5">

            {{-- Donut: score distribution --}}
            <div class="lg:col-span-2 card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-semibold text-base-content">Phân bổ mức điểm</h2>
                        <span class="badge badge-sm badge-ghost">Donut</span>
                    </div>
                    <div id="chart-pf-distribution" style="height: 260px;"></div>
                </div>
            </div>

            {{-- Line: score trend by period --}}
            <div class="lg:col-span-3 card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-semibold text-base-content">Điểm TB theo kỳ</h2>
                        <span class="badge badge-sm badge-ghost">Line</span>
                    </div>
                    <div id="chart-pf-period" style="height: 260px;"></div>
                </div>
            </div>

        </div>

        {{-- ── By-department table ─────────────────────────────────────────────── --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm mt-5">
            <div class="card-body p-0">
                <div class="px-5 pt-4 pb-3 border-b border-base-200 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-base-content">Chi tiết theo phòng ban</h2>
                    <span class="badge badge-sm badge-primary"
                          x-text="(byDepartment.length || 0) + ' phòng ban'"></span>
                </div>

                <div x-show="byDepartment.length === 0"
                     class="flex flex-col items-center justify-center py-12 text-base-content/40">
                    <svg class="w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <p class="text-sm">Không có dữ liệu</p>
                </div>

                <div x-show="byDepartment.length > 0" class="overflow-x-auto">
                    <table class="table table-sm w-full">
                        <thead class="bg-base-200/50">
                            <tr class="text-xs text-base-content/60">
                                <th class="font-medium py-2 pl-5">#</th>
                                <th class="font-medium py-2">Phòng ban</th>
                                <th class="font-medium py-2 text-right">Tổng đánh giá</th>
                                <th class="font-medium py-2 text-right">Hoàn thành</th>
                                <th class="font-medium py-2 text-right">Điểm TB</th>
                                <th class="font-medium py-2 pr-5">Tỉ lệ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, index) in byDepartment" :key="row.department_id ?? index">
                                <tr class="border-b border-base-100 hover:bg-base-50 transition-colors">
                                    <td class="py-2.5 pl-5 text-xs text-base-content/40" x-text="index + 1"></td>
                                    <td class="py-2.5">
                                        <span class="font-medium text-sm" x-text="row.name"></span>
                                    </td>
                                    <td class="py-2.5 text-right text-sm" x-text="row.total"></td>
                                    <td class="py-2.5 text-right">
                                        <span class="badge badge-sm badge-success" x-text="row.completed"></span>
                                    </td>
                                    <td class="py-2.5 text-right">
                                        <span class="font-semibold text-sm"
                                              :class="{
                                                  'text-success': row.avg_score >= 4,
                                                  'text-warning': row.avg_score >= 3 && row.avg_score < 4,
                                                  'text-error':   row.avg_score < 3
                                              }"
                                              x-text="row.avg_score != null ? Number(row.avg_score).toFixed(1) : '—'">
                                        </span>
                                    </td>
                                    <td class="py-2.5 pr-5 w-36">
                                        <template x-if="row.total > 0">
                                            <div>
                                                <div class="flex justify-between text-xs mb-1">
                                                    <span x-text="Math.round(row.completed / row.total * 100) + '%'"></span>
                                                </div>
                                                <progress class="progress progress-primary w-full h-1.5"
                                                          :value="row.completed"
                                                          :max="row.total"></progress>
                                            </div>
                                        </template>
                                        <template x-if="!row.total">
                                            <span class="text-xs text-base-content/30">—</span>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-base-200 font-semibold">
                                <td colspan="2" class="py-2.5 pl-5 text-sm">Tổng cộng</td>
                                <td class="py-2.5 text-right text-sm"
                                    x-text="byDepartment.reduce((s, r) => s + (r.total || 0), 0)"></td>
                                <td class="py-2.5 text-right text-sm"
                                    x-text="byDepartment.reduce((s, r) => s + (r.completed || 0), 0)"></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Top Performers table ─────────────────────────────────────────────── --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm mt-5">
            <div class="card-body p-0">
                <div class="px-5 pt-4 pb-3 border-b border-base-200 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-base-content">Top nhân viên xuất sắc</h2>
                    <span class="badge badge-sm badge-warning">Top 5</span>
                </div>

                <div x-show="topPerformers.length === 0"
                     class="flex flex-col items-center justify-center py-12 text-base-content/40">
                    <svg class="w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <p class="text-sm">Chưa có dữ liệu</p>
                </div>

                <div x-show="topPerformers.length > 0" class="overflow-x-auto">
                    <table class="table table-sm w-full">
                        <thead class="bg-base-200/50">
                            <tr class="text-xs text-base-content/60">
                                <th class="font-medium py-2 pl-5 w-10">#</th>
                                <th class="font-medium py-2">Nhân viên</th>
                                <th class="font-medium py-2">Phòng ban</th>
                                <th class="font-medium py-2 text-right pr-5">Điểm</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, index) in topPerformers.slice(0, 5)" :key="row.employee_id ?? index">
                                <tr class="border-b border-base-100 hover:bg-base-50 transition-colors">
                                    <td class="py-3 pl-5">
                                        <template x-if="index === 0">
                                            <div class="w-7 h-7 rounded-full bg-warning flex items-center justify-center">
                                                <svg class="w-4 h-4 text-warning-content" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                                </svg>
                                            </div>
                                        </template>
                                        <template x-if="index === 1">
                                            <div class="w-7 h-7 rounded-full bg-base-300 flex items-center justify-center">
                                                <span class="text-xs font-bold text-base-content/70">2</span>
                                            </div>
                                        </template>
                                        <template x-if="index === 2">
                                            <div class="w-7 h-7 rounded-full bg-warning/30 flex items-center justify-center">
                                                <span class="text-xs font-bold text-warning-content/80">3</span>
                                            </div>
                                        </template>
                                        <template x-if="index > 2">
                                            <span class="text-xs text-base-content/40 font-mono" x-text="index + 1"></span>
                                        </template>
                                    </td>
                                    <td class="py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="avatar placeholder">
                                                <div class="w-8 h-8 rounded-full bg-primary/10">
                                                    <span class="text-xs text-primary font-semibold"
                                                          x-text="(row.full_name || '?').charAt(0).toUpperCase()"></span>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="font-medium text-sm text-base-content" x-text="row.full_name"></div>
                                                <div class="text-xs text-base-content/40 font-mono" x-text="row.employee_code ?? ''"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <span class="badge badge-ghost badge-sm" x-text="row.department ?? '—'"></span>
                                    </td>
                                    <td class="py-3 text-right pr-5">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <span class="text-lg font-bold"
                                                  :class="{
                                                      'text-success': row.overall_score >= 4.5,
                                                      'text-primary': row.overall_score >= 3.5 && row.overall_score < 4.5,
                                                      'text-warning': row.overall_score >= 3 && row.overall_score < 3.5,
                                                      'text-error':   row.overall_score < 3
                                                  }"
                                                  x-text="row.overall_score != null ? Number(row.overall_score).toFixed(1) : '—'">
                                            </span>
                                            <span class="text-xs text-base-content/40">/ 5</span>
                                        </div>
                                    </td>
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
    'resources/js/modules/tom-select.js',
    'Modules/Report/resources/assets/js/report.js',
], 'build/backend')
<script>
window.API_URL          = "{{ route('api.report.hr.performance') }}";
window.DEPT_OPTIONS_URL = "{{ route('api.departments.options') }}";
</script>
@endpush
