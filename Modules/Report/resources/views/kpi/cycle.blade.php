@extends('layouts.backend')
@section('title', 'KPI theo Cycle')

@section('breadcrumb')
<nav class="breadcrumb-nav">
    <a href="{{ route('report.index') }}" class="breadcrumb-item">Báo cáo</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ route('report.kpi.index') }}" class="breadcrumb-item">KPI</a>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-item">Theo Cycle</span>
</nav>
@endsection

@section('content')
<div
    x-data="reportKpiCycle"
    x-init="init()"
    class="space-y-5"
>

    {{-- ── Page header ──────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-base-content">KPI theo Cycle</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Mức độ đạt mục tiêu, phân phối kết quả và top performers theo chu kỳ đánh giá</p>
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

                {{-- Cycle label --}}
                <div class="form-control w-56">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Chu kỳ đánh giá</span>
                    </label>
                    <select id="filter-cycle" x-model="filters.cycle_label"
                            class="select select-sm select-bordered w-full">
                        <option value="">-- Tất cả cycle --</option>
                        <template x-for="cycle in cycles" :key="cycle">
                            <option :value="cycle" x-text="cycle"></option>
                        </template>
                    </select>
                </div>

                {{-- Department TomSelect --}}
                <div class="form-control w-56">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Phòng ban</span>
                    </label>
                    <select id="rpt-kc-dept" class="select select-sm select-bordered w-full"
                            placeholder="Tất cả phòng ban..."></select>
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
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">

        {{-- Total Goals --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body py-4 px-5">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-base-content/60">Tổng mục tiêu</span>
                    <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-bold text-base-content" x-text="summary.total_goals ?? '—'"></div>
                <div class="text-xs text-base-content/50 mt-1">goals trong kỳ</div>
            </div>
        </div>

        {{-- Achieved --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body py-4 px-5">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-base-content/60">Đạt mục tiêu</span>
                    <div class="w-8 h-8 rounded-lg bg-success/10 flex items-center justify-center">
                        <svg class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-bold text-success" x-text="summary.achieved ?? '—'"></div>
                <div class="text-xs text-base-content/50 mt-1">goals >= 100%</div>
            </div>
        </div>

        {{-- At Risk --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body py-4 px-5">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-base-content/60">Có rủi ro</span>
                    <div class="w-8 h-8 rounded-lg bg-error/10 flex items-center justify-center">
                        <svg class="w-4 h-4 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-bold text-error" x-text="summary.at_risk ?? '—'"></div>
                <div class="text-xs text-base-content/50 mt-1">missed + partial &lt;60%</div>
            </div>
        </div>

        {{-- Avg Achievement % --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body py-4 px-5">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-base-content/60">TB Đạt (%)</span>
                    <div class="w-8 h-8 rounded-lg bg-warning/10 flex items-center justify-center">
                        <svg class="w-4 h-4 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-bold text-warning"
                     x-text="summary.avg_achievement_pct !== undefined ? summary.avg_achievement_pct + '%' : '—'"></div>
                <div class="text-xs text-base-content/50 mt-1">trung bình đạt mục tiêu</div>
            </div>
        </div>

        {{-- Avg Weighted Score --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body py-4 px-5">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-medium text-base-content/60">TB Điểm có trọng số</span>
                    <div class="w-8 h-8 rounded-lg bg-info/10 flex items-center justify-center">
                        <svg class="w-4 h-4 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                        </svg>
                    </div>
                </div>
                <div class="text-3xl font-bold text-info" x-text="summary.avg_weighted_score ?? '—'"></div>
                <div class="text-xs text-base-content/50 mt-1">weighted score trung bình</div>
            </div>
        </div>

    </div>

    {{-- ── Charts + Distribution row ──────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Dept horizontal bar chart --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-base-content">Weighted Score theo phòng ban</h2>
                    <span class="badge badge-sm badge-ghost">Bar chart</span>
                </div>
                <div x-show="loading" class="flex items-center justify-center h-64">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                </div>
                <div x-show="!loading" id="chart-kc-dept" style="height: 260px;"></div>
            </div>
        </div>

        {{-- Achievement distribution table --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-base-content">Phân phối mức đạt</h2>
                    <span class="badge badge-sm badge-primary">4 band</span>
                </div>

                <div x-show="loading" class="flex items-center justify-center py-12">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                </div>

                <div x-show="!loading" class="overflow-x-auto">
                    <table class="table table-sm w-full">
                        <thead>
                            <tr class="text-xs text-base-content/60 border-b border-base-200">
                                <th class="font-medium py-2 pl-0">Xếp band</th>
                                <th class="font-medium py-2 text-right">Số lượng</th>
                                <th class="font-medium py-2 text-right">Tỉ lệ</th>
                                <th class="font-medium py-2">Phân phối</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="band in distribution" :key="band.band">
                                <tr class="border-b border-base-100 hover:bg-base-50 transition-colors">
                                    <td class="py-2.5 pl-0">
                                        <span class="badge badge-sm font-semibold"
                                              :class="{
                                                  'badge-success': band.band === 'A',
                                                  'badge-info': band.band === 'B',
                                                  'badge-warning': band.band === 'C',
                                                  'badge-error': band.band === 'D'
                                              }"
                                              x-text="band.band"></span>
                                    </td>
                                    <td class="py-2.5 text-right font-semibold" x-text="band.count"></td>
                                    <td class="py-2.5 text-right text-base-content/60" x-text="band.pct + '%'"></td>
                                    <td class="py-2.5 w-32">
                                        <progress class="progress w-full h-1.5"
                                                  :class="{
                                                      'progress-success': band.band === 'A',
                                                      'progress-info': band.band === 'B',
                                                      'progress-warning': band.band === 'C',
                                                      'progress-error': band.band === 'D'
                                                  }"
                                                  :value="band.pct"
                                                  max="100"></progress>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Top Performers + At Risk row ────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Top Performers table --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-base-content">Top Performers</h2>
                    <span class="badge badge-sm badge-success">Top 5</span>
                </div>

                <div x-show="loading" class="flex items-center justify-center py-12">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                </div>

                <div x-show="!loading && topList.length === 0"
                     class="flex flex-col items-center justify-center py-10 text-base-content/40">
                    <svg class="w-10 h-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <p class="text-sm">Không có dữ liệu</p>
                </div>

                <div x-show="!loading && topList.length > 0" class="overflow-x-auto">
                    <table class="table table-sm w-full">
                        <thead>
                            <tr class="text-xs text-base-content/60 border-b border-base-200">
                                <th class="font-medium py-2 pl-0">#</th>
                                <th class="font-medium py-2">Nhân viên</th>
                                <th class="font-medium py-2">Phòng ban</th>
                                <th class="font-medium py-2 text-right">Goals</th>
                                <th class="font-medium py-2 text-right">Weighted Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, index) in topList" :key="row.employee_id ?? index">
                                <tr class="border-b border-base-100 hover:bg-base-50 transition-colors">
                                    <td class="py-2.5 pl-0">
                                        <span class="text-xs font-bold"
                                              :class="{
                                                  'text-warning': index === 0,
                                                  'text-base-content/50': index > 0
                                              }"
                                              x-text="index + 1"></span>
                                    </td>
                                    <td class="py-2.5">
                                        <span class="font-medium text-sm" x-text="row.name"></span>
                                    </td>
                                    <td class="py-2.5 text-base-content/60 text-sm" x-text="row.department"></td>
                                    <td class="py-2.5 text-right font-semibold" x-text="row.goals"></td>
                                    <td class="py-2.5 text-right">
                                        <span class="badge badge-sm badge-success font-semibold"
                                              x-text="row.weighted_score"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- At Risk table --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-base-content">Nhân viên có rủi ro</h2>
                    <span class="badge badge-sm badge-error">At Risk</span>
                </div>

                <div x-show="loading" class="flex items-center justify-center py-12">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                </div>

                <div x-show="!loading && atRisk.length === 0"
                     class="flex flex-col items-center justify-center py-10 text-base-content/40">
                    <svg class="w-10 h-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm">Không có nhân viên có rủi ro</p>
                </div>

                <div x-show="!loading && atRisk.length > 0" class="overflow-x-auto">
                    <table class="table table-sm w-full">
                        <thead>
                            <tr class="text-xs text-base-content/60 border-b border-base-200">
                                <th class="font-medium py-2 pl-0">Nhân viên</th>
                                <th class="font-medium py-2">Phòng ban</th>
                                <th class="font-medium py-2 text-right">Đạt (%)</th>
                                <th class="font-medium py-2 text-right">Còn lại</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, index) in atRisk" :key="row.employee_id ?? index">
                                <tr class="border-b border-base-100 hover:bg-base-50 transition-colors">
                                    <td class="py-2.5 pl-0">
                                        <span class="font-medium text-sm" x-text="row.name"></span>
                                    </td>
                                    <td class="py-2.5 text-base-content/60 text-sm" x-text="row.department"></td>
                                    <td class="py-2.5 text-right">
                                        <span class="badge badge-sm font-semibold"
                                              :class="row.achievement_pct < 40 ? 'badge-error' : 'badge-warning'"
                                              x-text="row.achievement_pct + '%'"></span>
                                    </td>
                                    <td class="py-2.5 text-right text-sm text-base-content/60"
                                        x-text="row.days_remaining !== null ? row.days_remaining + ' ngày' : '—'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
    @vite([
        'resources/js/modules/echarts.js',
        'resources/js/modules/tom-select.js',
    ], 'build/backend')
    @vite([
        'Modules/Report/resources/assets/js/report.js',
    ], 'build/backend')

    <script>
        window.API_URL          = "{{ route('api.report.kpi.cycle') }}";
        window.DEPT_OPTIONS_URL = "{{ route('api.departments.options') }}";
    </script>
@endpush
