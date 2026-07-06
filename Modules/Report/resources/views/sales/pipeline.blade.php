@extends('layouts.backend')
@section('title', 'Pipeline & Funnel — Sales')

@section('breadcrumb')
<nav class="breadcrumb-nav">
    <a href="{{ route('report.index') }}" class="breadcrumb-item">Báo cáo</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ route('report.sales.index') }}" class="breadcrumb-item">Sales</a>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-item">Pipeline & Funnel</span>
</nav>
@endsection

@section('content')
<div
    x-data="reportSalesPipeline"
    class="p-6 space-y-6"
    data-source-options-url="{{ route('api.lead-source.list') }}"
>

    {{-- ── Page header ─────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Pipeline & Funnel</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Phân tích pipeline cơ hội bán hàng theo giai đoạn</p>
        </div>
        <button @click="load()" :disabled="loading"
                class="btn btn-ghost btn-sm gap-1.5"
                :class="loading ? 'loading loading-spinner' : ''">
            <svg x-show="!loading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Làm mới
        </button>
    </div>

    {{-- ── Filter bar ───────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body py-3 px-4">
            <div class="flex flex-wrap gap-3 items-end">

                {{-- Date from --}}
                <div class="form-control">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Từ ngày</span>
                    </label>
                    <input id="filter-date-from"
                           type="text"
                           x-ref="dateFrom"
                           class="input input-sm input-bordered w-36 bg-base-100"
                           placeholder="dd/mm/yyyy"
                           autocomplete="off"
                           readonly/>
                </div>

                {{-- Date to --}}
                <div class="form-control">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Đến ngày</span>
                    </label>
                    <input id="filter-date-to"
                           type="text"
                           x-ref="dateTo"
                           class="input input-sm input-bordered w-36 bg-base-100"
                           placeholder="dd/mm/yyyy"
                           autocomplete="off"
                           readonly/>
                </div>

                {{-- Source --}}
                <div class="form-control min-w-48">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Nguồn lead</span>
                    </label>
                    <select id="filter-source"
                            x-ref="sourceSelect"
                            class="select select-sm select-bordered w-full bg-base-100"
                            placeholder="Tất cả nguồn...">
                    </select>
                </div>

                {{-- Apply / Reset --}}
                <div class="flex gap-2 pb-0.5">
                    <button @click="applyFilters()"
                            class="btn btn-primary btn-sm gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                        </svg>
                        Lọc
                    </button>
                    <button @click="resetFilters()"
                            class="btn btn-ghost btn-sm">
                        Xóa lọc
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Summary cards ────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">

        {{-- Total Leads --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-base-content leading-none"
                   x-text="summary.total_leads !== undefined ? summary.total_leads.toLocaleString('vi-VN') : '—'"></p>
                <p class="text-xs text-base-content/50 mt-0.5">Tổng số Lead</p>
            </div>
        </div>

        {{-- Total Expected Value --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-success/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-xl font-bold text-base-content leading-none truncate"
                   x-text="summary.total_value !== undefined ? formatVnd(summary.total_value) : '—'"></p>
                <p class="text-xs text-base-content/50 mt-0.5">Giá trị kỳ vọng</p>
            </div>
        </div>

        {{-- Hot Leads --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-error/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-base-content leading-none"
                   x-text="summary.hot_leads !== undefined ? summary.hot_leads.toLocaleString('vi-VN') : '—'"></p>
                <p class="text-xs text-base-content/50 mt-0.5">Hot Leads</p>
            </div>
        </div>

        {{-- Win Rate --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-warning/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-base-content leading-none"
                   x-text="summary.win_rate !== undefined ? summary.win_rate.toFixed(1) + '%' : '—'"></p>
                <p class="text-xs text-base-content/50 mt-0.5">Tỷ lệ Win Rate</p>
            </div>
        </div>

    </div>

    {{-- ── Charts ───────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">

        {{-- Funnel chart --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4 pb-3">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <h2 class="font-semibold text-sm text-base-content">Funnel theo giai đoạn</h2>
                    <span class="badge badge-xs badge-ghost">Pipeline</span>
                </div>
                <div id="chart-pipeline-funnel" class="w-full" style="height: 300px;">
                    <div x-show="loading" class="flex items-center justify-center h-full">
                        <span class="loading loading-spinner loading-md text-primary"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Trend chart --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4 pb-3">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <h2 class="font-semibold text-sm text-base-content">Xu hướng theo thời gian</h2>
                    <span class="badge badge-xs badge-ghost">Trend</span>
                </div>
                <div id="chart-pipeline-trend" class="w-full" style="height: 300px;">
                    <div x-show="loading" class="flex items-center justify-center h-full">
                        <span class="loading loading-spinner loading-md text-primary"></span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── By Source table ──────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-4">
            <h2 class="font-semibold text-sm text-base-content mb-3">Phân tích theo Nguồn Lead</h2>

            <div class="overflow-x-auto rounded-lg border border-base-200">
                <table class="table table-sm table-zebra w-full">
                    <thead class="bg-base-200/60">
                        <tr>
                            <th class="text-xs font-semibold text-base-content/70">Nguồn</th>
                            <th class="text-xs font-semibold text-base-content/70 text-right">Tổng Lead</th>
                            <th class="text-xs font-semibold text-base-content/70 text-right">Giá trị (VND)</th>
                            <th class="text-xs font-semibold text-base-content/70 text-right">Win Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="!loading && bySource.length === 0">
                            <tr>
                                <td colspan="4" class="text-center py-8 text-base-content/40 text-sm italic">
                                    Không có dữ liệu
                                </td>
                            </tr>
                        </template>
                        <template x-for="(row, idx) in bySource" :key="idx">
                            <tr class="hover:bg-base-200/40 transition-colors">
                                <td>
                                    <span class="font-medium text-sm text-base-content" x-text="row.source_name || '—'"></span>
                                </td>
                                <td class="text-right">
                                    <span class="font-mono text-sm" x-text="(row.total_leads ?? 0).toLocaleString('vi-VN')"></span>
                                </td>
                                <td class="text-right">
                                    <span class="font-mono text-sm" x-text="formatVnd(row.total_value ?? 0)"></span>
                                </td>
                                <td class="text-right">
                                    <span class="badge badge-sm"
                                          :class="(row.win_rate ?? 0) >= 50 ? 'badge-success' : (row.win_rate ?? 0) >= 25 ? 'badge-warning' : 'badge-ghost'"
                                          x-text="(row.win_rate ?? 0).toFixed(1) + '%'"></span>
                                </td>
                            </tr>
                        </template>
                        <template x-if="loading">
                            <tr>
                                <td colspan="4" class="text-center py-6">
                                    <span class="loading loading-dots loading-sm text-primary"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot x-show="!loading && bySource.length > 0" class="bg-base-200/40 font-semibold">
                        <tr>
                            <td class="text-xs text-base-content/70">Tổng cộng</td>
                            <td class="text-right text-xs font-mono"
                                x-text="bySource.reduce((s, r) => s + (r.total_leads ?? 0), 0).toLocaleString('vi-VN')"></td>
                            <td class="text-right text-xs font-mono"
                                x-text="formatVnd(bySource.reduce((s, r) => s + (r.total_value ?? 0), 0))"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- ── By Assignee table ────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-4">
            <h2 class="font-semibold text-sm text-base-content mb-3">Phân tích theo Người phụ trách</h2>

            <div class="overflow-x-auto rounded-lg border border-base-200">
                <table class="table table-sm table-zebra w-full">
                    <thead class="bg-base-200/60">
                        <tr>
                            <th class="text-xs font-semibold text-base-content/70">Người phụ trách</th>
                            <th class="text-xs font-semibold text-base-content/70 text-right">Tổng Lead</th>
                            <th class="text-xs font-semibold text-base-content/70 text-right">Giá trị (VND)</th>
                            <th class="text-xs font-semibold text-base-content/70 text-right">Win Rate</th>
                            <th class="text-xs font-semibold text-base-content/70 text-right">Hot Leads</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="!loading && byAssignee.length === 0">
                            <tr>
                                <td colspan="5" class="text-center py-8 text-base-content/40 text-sm italic">
                                    Không có dữ liệu
                                </td>
                            </tr>
                        </template>
                        <template x-for="(row, idx) in byAssignee" :key="idx">
                            <tr class="hover:bg-base-200/40 transition-colors">
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="avatar placeholder">
                                            <div class="w-7 h-7 rounded-full bg-primary/10 text-primary text-xs font-bold flex items-center justify-center">
                                                <span x-text="(row.assignee_name || '?').charAt(0).toUpperCase()"></span>
                                            </div>
                                        </div>
                                        <span class="font-medium text-sm text-base-content" x-text="row.assignee_name || '—'"></span>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <span class="font-mono text-sm" x-text="(row.total_leads ?? 0).toLocaleString('vi-VN')"></span>
                                </td>
                                <td class="text-right">
                                    <span class="font-mono text-sm" x-text="formatVnd(row.total_value ?? 0)"></span>
                                </td>
                                <td class="text-right">
                                    <span class="badge badge-sm"
                                          :class="(row.win_rate ?? 0) >= 50 ? 'badge-success' : (row.win_rate ?? 0) >= 25 ? 'badge-warning' : 'badge-ghost'"
                                          x-text="(row.win_rate ?? 0).toFixed(1) + '%'"></span>
                                </td>
                                <td class="text-right">
                                    <span class="font-mono text-sm text-error font-semibold"
                                          x-text="(row.hot_leads ?? 0).toLocaleString('vi-VN')"></span>
                                </td>
                            </tr>
                        </template>
                        <template x-if="loading">
                            <tr>
                                <td colspan="5" class="text-center py-6">
                                    <span class="loading loading-dots loading-sm text-primary"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot x-show="!loading && byAssignee.length > 0" class="bg-base-200/40 font-semibold">
                        <tr>
                            <td class="text-xs text-base-content/70">Tổng cộng</td>
                            <td class="text-right text-xs font-mono"
                                x-text="byAssignee.reduce((s, r) => s + (r.total_leads ?? 0), 0).toLocaleString('vi-VN')"></td>
                            <td class="text-right text-xs font-mono"
                                x-text="formatVnd(byAssignee.reduce((s, r) => s + (r.total_value ?? 0), 0))"></td>
                            <td></td>
                            <td class="text-right text-xs font-mono text-error"
                                x-text="byAssignee.reduce((s, r) => s + (r.hot_leads ?? 0), 0).toLocaleString('vi-VN')"></td>
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
    'Modules/Report/resources/assets/js/report.js',
], 'build/backend')

<script>
window.API_URL = '{{ route('api.report.sales.pipeline') }}';
</script>
@endpush
