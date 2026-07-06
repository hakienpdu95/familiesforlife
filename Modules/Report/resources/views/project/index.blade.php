@extends('layouts.backend')
@section('title', 'Báo cáo Dự án')

@section('breadcrumb')
<nav class="breadcrumb-nav">
    <a href="{{ route('report.index') }}" class="breadcrumb-item">Báo cáo</a>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-item">Dự án</span>
</nav>
@endsection

@section('content')
<div
    x-data="reportProjectOverview"
    class="p-6 space-y-6"
>

    {{-- ── Page header ─────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Báo cáo Dự án</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Tổng quan tiến độ, ngân sách và rủi ro dự án</p>
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

                {{-- Status --}}
                <div class="form-control">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Trạng thái</span>
                    </label>
                    <select id="filter-pj-status"
                            x-model="filters.status"
                            class="select select-sm select-bordered w-44 bg-base-100">
                        <option value="">Tất cả</option>
                        <option value="active">Đang thực hiện</option>
                        <option value="completed">Hoàn thành</option>
                        <option value="on_hold">Tạm dừng</option>
                    </select>
                </div>

                {{-- Department TomSelect --}}
                <div class="form-control min-w-52">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Phòng ban</span>
                    </label>
                    <select id="rpt-pj-dept"
                            class="select select-sm select-bordered w-full bg-base-100"
                            placeholder="Tất cả phòng ban...">
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
    <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">

        {{-- Total --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-base-content leading-none"
                   x-text="summary.total_projects !== undefined ? summary.total_projects.toLocaleString('vi-VN') : '—'"></p>
                <p class="text-xs text-base-content/50 mt-0.5">Tổng dự án</p>
                <p class="text-xs text-base-content/40 mt-1 truncate"
                   x-show="summary.total_budget !== undefined"
                   x-text="'NS: ' + formatVnd(summary.total_budget ?? 0)"></p>
            </div>
        </div>

        {{-- Active --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-info/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-base-content leading-none"
                   x-text="summary.active !== undefined ? summary.active.toLocaleString('vi-VN') : '—'"></p>
                <p class="text-xs text-base-content/50 mt-0.5">Đang thực hiện</p>
            </div>
        </div>

        {{-- Completed --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-success/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-base-content leading-none"
                   x-text="summary.completed !== undefined ? summary.completed.toLocaleString('vi-VN') : '—'"></p>
                <p class="text-xs text-base-content/50 mt-0.5">Hoàn thành</p>
            </div>
        </div>

        {{-- On Hold --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-warning/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-base-content leading-none"
                   x-text="summary.on_hold !== undefined ? summary.on_hold.toLocaleString('vi-VN') : '—'"></p>
                <p class="text-xs text-base-content/50 mt-0.5">Tạm dừng</p>
            </div>
        </div>

        {{-- Overdue --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-error/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-error leading-none"
                   x-text="summary.overdue_count !== undefined ? summary.overdue_count.toLocaleString('vi-VN') : '—'"></p>
                <p class="text-xs text-base-content/50 mt-0.5">Quá hạn</p>
            </div>
        </div>

    </div>

    {{-- ── Charts ───────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">

        {{-- Donut: by status --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4 pb-3">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <h2 class="font-semibold text-sm text-base-content">Phân bổ theo trạng thái</h2>
                    <span class="badge badge-xs badge-ghost">Donut</span>
                </div>
                <div id="chart-pj-status" class="w-full" style="height: 300px;">
                    <div x-show="loading" class="flex items-center justify-center h-full">
                        <span class="loading loading-spinner loading-md text-primary"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bar: by dept --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4 pb-3">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <h2 class="font-semibold text-sm text-base-content">Số dự án theo phòng ban</h2>
                    <span class="badge badge-xs badge-ghost">Bar</span>
                </div>
                <div id="chart-pj-dept" class="w-full" style="height: 300px;">
                    <div x-show="loading" class="flex items-center justify-center h-full">
                        <span class="loading loading-spinner loading-md text-primary"></span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Projects at risk table ───────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-4">
            <div class="flex items-center gap-2 mb-3">
                <svg class="w-4 h-4 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <h2 class="font-semibold text-sm text-base-content">Dự án có rủi ro</h2>
                <span class="badge badge-sm badge-error badge-outline ml-auto"
                      x-show="atRisk.length > 0"
                      x-text="atRisk.length + ' dự án'"></span>
            </div>

            <div class="overflow-x-auto rounded-lg border border-base-200">
                <table class="table table-sm table-zebra w-full">
                    <thead class="bg-base-200/60">
                        <tr>
                            <th class="text-xs font-semibold text-base-content/70">Tên dự án</th>
                            <th class="text-xs font-semibold text-base-content/70">Ngày kết thúc</th>
                            <th class="text-xs font-semibold text-base-content/70 text-right">Còn lại (ngày)</th>
                            <th class="text-xs font-semibold text-base-content/70 text-right">Hoàn thành</th>
                            <th class="text-xs font-semibold text-base-content/70 text-center">Tình trạng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="!loading && atRisk.length === 0">
                            <tr>
                                <td colspan="5" class="text-center py-8 text-base-content/40 text-sm italic">
                                    Không có dự án nào có rủi ro
                                </td>
                            </tr>
                        </template>
                        <template x-for="(row, idx) in atRisk" :key="idx">
                            <tr class="hover:bg-base-200/40 transition-colors">
                                {{-- Name --}}
                                <td>
                                    <span class="font-medium text-sm text-base-content" x-text="row.name || '—'"></span>
                                </td>
                                {{-- End Date --}}
                                <td>
                                    <span class="text-sm text-base-content/70" x-text="row.end_date || '—'"></span>
                                </td>
                                {{-- Days Remaining --}}
                                <td class="text-right">
                                    <span class="font-mono text-sm"
                                          :class="(row.days_remaining ?? 0) < 0 ? 'text-error font-bold' : (row.days_remaining ?? 0) <= 7 ? 'text-warning font-semibold' : 'text-base-content'"
                                          x-text="row.days_remaining !== undefined ? row.days_remaining : '—'"></span>
                                </td>
                                {{-- Completion % --}}
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="w-20 bg-base-200 rounded-full h-1.5 overflow-hidden">
                                            <div class="h-full rounded-full transition-all"
                                                 :class="(row.completion_pct ?? 0) >= 80 ? 'bg-success' : (row.completion_pct ?? 0) >= 50 ? 'bg-warning' : 'bg-error'"
                                                 :style="'width:' + Math.min(row.completion_pct ?? 0, 100) + '%'"></div>
                                        </div>
                                        <span class="font-mono text-sm tabular-nums"
                                              x-text="(row.completion_pct ?? 0).toFixed(0) + '%'"></span>
                                    </div>
                                </td>
                                {{-- Behind badge --}}
                                <td class="text-center">
                                    <template x-if="row.is_behind_schedule">
                                        <span class="badge badge-sm badge-error gap-1">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                      d="M12 9v2m0 4h.01"/>
                                            </svg>
                                            Chậm tiến độ
                                        </span>
                                    </template>
                                    <template x-if="!row.is_behind_schedule">
                                        <span class="badge badge-sm badge-warning gap-1">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                      d="M12 8v4l3 3"/>
                                            </svg>
                                            Sắp đến hạn
                                        </span>
                                    </template>
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
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
@vite([
    'resources/js/modules/echarts.js',
    'resources/js/modules/tom-select.js',
    'Modules/Report/resources/assets/js/report.js',
], 'build/backend')

<script>
window.API_URL          = "{{ route('api.report.project.overview') }}";
window.DEPT_OPTIONS_URL = "{{ route('api.departments.options') }}";
</script>
@endpush
