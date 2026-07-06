@extends('layouts.backend')
@section('title', 'Lịch sử KPI')

@section('breadcrumb')
<nav class="breadcrumb-nav">
    <a href="{{ route('report.index') }}" class="breadcrumb-item">Báo cáo</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ route('report.kpi.index') }}" class="breadcrumb-item">KPI</a>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-item">Lịch sử</span>
</nav>
@endsection

@section('content')
<div
    x-data="reportKpiSnapshot"
    x-init="init()"
    class="space-y-5"
>

    {{-- ── Page header ──────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Lịch sử KPI</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Xu hướng điểm KPI và phân bổ xếp loại qua các chu kỳ</p>
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

    {{-- ── Trend chart ──────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-base-content">Xu hướng điểm KPI trung bình theo cycle</h2>
                <span class="badge badge-sm badge-ghost">Line chart</span>
            </div>
            <div x-show="loading" class="flex items-center justify-center h-64">
                <span class="loading loading-spinner loading-md text-primary"></span>
            </div>
            <div x-show="!loading" id="chart-ks-trend" style="height: 260px;"></div>
        </div>
    </div>

    {{-- ── Cycles summary + distribution ───────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Cycles list --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-base-content">Tổng hợp theo cycle</h2>
                    <span class="badge badge-sm badge-primary" x-text="cycles.length + ' cycle'"></span>
                </div>

                <div x-show="loading" class="flex items-center justify-center py-12">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                </div>

                <div x-show="!loading && cycles.length === 0"
                     class="flex flex-col items-center justify-center py-12 text-base-content/40">
                    <svg class="w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                    </svg>
                    <p class="text-sm">Không có dữ liệu</p>
                </div>

                <div x-show="!loading && cycles.length > 0" class="overflow-x-auto">
                    <table class="table table-sm w-full">
                        <thead>
                            <tr class="text-xs text-base-content/60 border-b border-base-200">
                                <th class="font-medium py-2 pl-0">Cycle</th>
                                <th class="font-medium py-2 text-right">Nhân viên</th>
                                <th class="font-medium py-2 text-right">TB KPI Score</th>
                                <th class="font-medium py-2">Xu hướng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, index) in cycles" :key="row.cycle_label ?? index">
                                <tr class="border-b border-base-100 hover:bg-base-50 transition-colors"
                                    :class="selectedCycle === row.cycle_label ? 'bg-primary/5' : ''"
                                    @click="selectedCycle = row.cycle_label"
                                    style="cursor: pointer;">
                                    <td class="py-2.5 pl-0">
                                        <span class="font-medium text-sm" x-text="row.cycle_label"></span>
                                    </td>
                                    <td class="py-2.5 text-right font-semibold" x-text="row.employee_count"></td>
                                    <td class="py-2.5 text-right">
                                        <span class="badge badge-sm font-semibold"
                                              :class="{
                                                  'badge-success': row.avg_kpi_score >= 80,
                                                  'badge-info':    row.avg_kpi_score >= 60 && row.avg_kpi_score < 80,
                                                  'badge-warning': row.avg_kpi_score >= 40 && row.avg_kpi_score < 60,
                                                  'badge-error':   row.avg_kpi_score < 40
                                              }"
                                              x-text="row.avg_kpi_score"></span>
                                    </td>
                                    <td class="py-2.5 w-28">
                                        <template x-if="index > 0">
                                            <div class="flex items-center gap-1 text-xs"
                                                 :class="row.avg_kpi_score >= cycles[index-1].avg_kpi_score ? 'text-success' : 'text-error'">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                     :style="row.avg_kpi_score >= cycles[index-1].avg_kpi_score ? '' : 'transform: rotate(180deg)'">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                                </svg>
                                                <span x-text="Math.abs(Math.round((row.avg_kpi_score - cycles[index-1].avg_kpi_score) * 10) / 10)"></span>
                                            </div>
                                        </template>
                                        <template x-if="index === 0">
                                            <span class="text-xs text-base-content/30">—</span>
                                        </template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Score distribution per selected cycle --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-base-content">Phân bổ xếp loại</h2>
                    <span class="badge badge-sm badge-ghost"
                          x-text="selectedCycle ? selectedCycle : 'Chọn cycle'"></span>
                </div>

                <div x-show="loading" class="flex items-center justify-center py-12">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                </div>

                <div x-show="!loading && !selectedCycle"
                     class="flex flex-col items-center justify-center py-12 text-base-content/40">
                    <svg class="w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"/>
                    </svg>
                    <p class="text-sm">Chọn một cycle để xem phân bổ</p>
                </div>

                <div x-show="!loading && selectedCycle" class="overflow-x-auto">
                    <table class="table table-sm w-full">
                        <thead>
                            <tr class="text-xs text-base-content/60 border-b border-base-200">
                                <th class="font-medium py-2 pl-0">Xếp loại</th>
                                <th class="font-medium py-2">Mô tả</th>
                                <th class="font-medium py-2 text-right">Số lượng</th>
                                <th class="font-medium py-2 text-right">Tỉ lệ</th>
                                <th class="font-medium py-2">Phân phối</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="band in currentDistribution" :key="band.band">
                                <tr class="border-b border-base-100 hover:bg-base-50 transition-colors">
                                    <td class="py-2.5 pl-0">
                                        <span class="badge badge-sm font-bold"
                                              :class="{
                                                  'badge-success': band.band === 'A',
                                                  'badge-info':    band.band === 'B',
                                                  'badge-warning': band.band === 'C',
                                                  'badge-error':   band.band === 'D'
                                              }"
                                              x-text="band.band"></span>
                                    </td>
                                    <td class="py-2.5 text-xs text-base-content/60">
                                        <span x-text="{A:'Xuất sắc (>=80)',B:'Tốt (60-79)',C:'Đạt (40-59)',D:'Chưa đạt (<40)'}[band.band] ?? band.band"></span>
                                    </td>
                                    <td class="py-2.5 text-right font-semibold" x-text="band.count"></td>
                                    <td class="py-2.5 text-right text-base-content/60" x-text="band.pct + '%'"></td>
                                    <td class="py-2.5 w-28">
                                        <progress class="progress w-full h-1.5"
                                                  :class="{
                                                      'progress-success': band.band === 'A',
                                                      'progress-info':    band.band === 'B',
                                                      'progress-warning': band.band === 'C',
                                                      'progress-error':   band.band === 'D'
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

</div>
@endsection

@push('scripts')
    @vite([
        'resources/js/modules/echarts.js',
    ], 'build/backend')
    @vite([
        'Modules/Report/resources/assets/js/report.js',
    ], 'build/backend')

    <script>
        window.API_URL = "{{ route('api.report.kpi.snapshot') }}";
    </script>
@endpush
