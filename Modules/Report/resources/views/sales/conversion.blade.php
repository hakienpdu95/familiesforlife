@extends('layouts.backend')
@section('title', 'Tỷ lệ chuyển đổi — Sales')

@section('breadcrumb')
<nav class="breadcrumb-nav">
    <a href="{{ route('report.index') }}" class="breadcrumb-item">Báo cáo</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ route('report.sales.index') }}" class="breadcrumb-item">Sales</a>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-item">Tỷ lệ chuyển đổi</span>
</nav>
@endsection

@section('content')
<div x-data="reportSalesConversion"
     x-init="init()"
     class="space-y-5">

    {{-- ── Page header ─────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Tỷ lệ chuyển đổi</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Phân tích conversion rate theo nguồn & cohort tháng</p>
        </div>
        <div class="flex items-center gap-2">
            {{-- Date range filter --}}
            <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100 w-64">
                <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <input id="filter-date-range" type="text" readonly
                       placeholder="Chọn khoảng thời gian..."
                       class="grow bg-transparent outline-none text-sm cursor-pointer"/>
                <button x-show="filters.date_from"
                        @click="clearDate()"
                        class="text-base-content/30 hover:text-base-content transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
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
            <button @click="load()" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Tải lại
            </button>
        </div>
    </div>

    {{-- ── Loading skeleton ────────────────────────────────────────────── --}}
    <div x-show="loading" x-transition class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <template x-for="i in 4">
            <div class="bg-base-100 border border-base-200 rounded-xl p-4 shadow-sm animate-pulse">
                <div class="h-3 bg-base-300 rounded w-2/3 mb-3"></div>
                <div class="h-7 bg-base-300 rounded w-1/2"></div>
            </div>
        </template>
    </div>

    {{-- ── Summary cards ───────────────────────────────────────────────── --}}
    <div x-show="!loading" x-transition class="grid grid-cols-2 sm:grid-cols-4 gap-3">

        {{-- Total Leads --}}
        <div class="bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Tổng Leads</div>
            <div class="stat-value text-2xl text-base-content" x-text="fmt(summary.total_leads)">—</div>
            <div class="stat-desc text-xs" x-text="summary.period_label || ''"></div>
        </div>

        {{-- Converted --}}
        <div class="bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Đã chuyển đổi</div>
            <div class="stat-value text-2xl text-success" x-text="fmt(summary.converted)">—</div>
            <div class="stat-desc text-xs">Won / Closed</div>
        </div>

        {{-- Conversion Rate --}}
        <div class="bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Tỷ lệ chuyển đổi</div>
            <div class="stat-value text-2xl text-primary"
                 x-text="summary.conversion_rate !== undefined ? summary.conversion_rate + '%' : '—'">—</div>
            <div class="stat-desc text-xs">Converted / Total</div>
        </div>

        {{-- Avg Days to Convert --}}
        <div class="bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">TB ngày chuyển đổi</div>
            <div class="stat-value text-2xl text-warning"
                 x-text="summary.avg_days_to_convert !== undefined ? summary.avg_days_to_convert + ' ngày' : '—'">—</div>
            <div class="stat-desc text-xs">Avg days from lead to won</div>
        </div>

    </div>

    {{-- ── Charts row ──────────────────────────────────────────────────── --}}
    <div x-show="!loading" x-transition class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Bar chart: conversion by source --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4 pb-3">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <h2 class="font-semibold text-sm text-base-content">Chuyển đổi theo nguồn</h2>
                    <span class="text-xs text-base-content/40">Lead source</span>
                </div>
                <div id="chart-conversion-source" class="w-full" style="height: 280px"></div>
            </div>
        </div>

        {{-- Line chart: monthly cohort --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4 pb-3">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <h2 class="font-semibold text-sm text-base-content">Cohort chuyển đổi theo tháng</h2>
                    <span class="text-xs text-base-content/40">Tỷ lệ %</span>
                </div>
                <div id="chart-conversion-cohort" class="w-full" style="height: 280px"></div>
            </div>
        </div>

    </div>

    {{-- ── Score band table ─────────────────────────────────────────────── --}}
    <div x-show="!loading" x-transition class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-0 overflow-hidden rounded-2xl">

            <div class="px-5 py-3 border-b border-base-200 flex items-center justify-between">
                <h2 class="font-semibold text-sm text-base-content">Chuyển đổi theo nhiệt độ Lead</h2>
                <span class="text-xs text-base-content/40">Score band</span>
            </div>

            <div class="overflow-x-auto">
                <table class="table table-sm w-full">
                    <thead>
                        <tr class="text-xs text-base-content/50 border-b border-base-200">
                            <th class="px-5 py-2 font-medium text-left">Nhóm</th>
                            <th class="px-5 py-2 font-medium text-right">Tổng leads</th>
                            <th class="px-5 py-2 font-medium text-right">Đã convert</th>
                            <th class="px-5 py-2 font-medium text-right">Tỷ lệ</th>
                            <th class="px-5 py-2 font-medium text-right">TB ngày</th>
                        </tr>
                    </thead>
                    <tbody>

                        {{-- Hot --}}
                        <template x-if="bands.hot">
                            <tr class="border-b border-base-200 hover:bg-base-50 transition-colors">
                                <td class="px-5 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-error shrink-0"></span>
                                        <span class="font-medium text-sm">Hot</span>
                                    </div>
                                </td>
                                <td class="px-5 py-2.5 text-right font-mono text-sm" x-text="fmt(bands.hot.total_leads)"></td>
                                <td class="px-5 py-2.5 text-right font-mono text-sm text-success" x-text="fmt(bands.hot.converted)"></td>
                                <td class="px-5 py-2.5 text-right">
                                    <span class="badge badge-sm badge-error font-semibold"
                                          x-text="bands.hot.rate + '%'"></span>
                                </td>
                                <td class="px-5 py-2.5 text-right text-sm text-base-content/60"
                                    x-text="bands.hot.avg_days + ' ngày'"></td>
                            </tr>
                        </template>

                        {{-- Warm --}}
                        <template x-if="bands.warm">
                            <tr class="border-b border-base-200 hover:bg-base-50 transition-colors">
                                <td class="px-5 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-warning shrink-0"></span>
                                        <span class="font-medium text-sm">Warm</span>
                                    </div>
                                </td>
                                <td class="px-5 py-2.5 text-right font-mono text-sm" x-text="fmt(bands.warm.total_leads)"></td>
                                <td class="px-5 py-2.5 text-right font-mono text-sm text-success" x-text="fmt(bands.warm.converted)"></td>
                                <td class="px-5 py-2.5 text-right">
                                    <span class="badge badge-sm badge-warning font-semibold"
                                          x-text="bands.warm.rate + '%'"></span>
                                </td>
                                <td class="px-5 py-2.5 text-right text-sm text-base-content/60"
                                    x-text="bands.warm.avg_days + ' ngày'"></td>
                            </tr>
                        </template>

                        {{-- Cold --}}
                        <template x-if="bands.cold">
                            <tr class="hover:bg-base-50 transition-colors">
                                <td class="px-5 py-2.5">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-info shrink-0"></span>
                                        <span class="font-medium text-sm">Cold</span>
                                    </div>
                                </td>
                                <td class="px-5 py-2.5 text-right font-mono text-sm" x-text="fmt(bands.cold.total_leads)"></td>
                                <td class="px-5 py-2.5 text-right font-mono text-sm text-success" x-text="fmt(bands.cold.converted)"></td>
                                <td class="px-5 py-2.5 text-right">
                                    <span class="badge badge-sm badge-info font-semibold"
                                          x-text="bands.cold.rate + '%'"></span>
                                </td>
                                <td class="px-5 py-2.5 text-right text-sm text-base-content/60"
                                    x-text="bands.cold.avg_days + ' ngày'"></td>
                            </tr>
                        </template>

                        {{-- Empty state --}}
                        <tr x-show="!bands.hot && !bands.warm && !bands.cold">
                            <td colspan="5" class="px-5 py-10 text-center text-sm text-base-content/40">
                                Chưa có dữ liệu. Chọn khoảng thời gian và nhấn Tải lại.
                            </td>
                        </tr>

                    </tbody>
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
    'Modules/Report/resources/assets/js/report.js',
], 'build/backend')
<script>
window.API_URL = '{{ route('api.report.sales.conversion') }}';
</script>
@endpush
