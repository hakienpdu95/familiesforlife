@extends('layouts.backend')
@section('title', 'Hoạt động Sales')

@section('breadcrumb')
<nav class="breadcrumb-nav">
    <a href="{{ route('report.index') }}" class="breadcrumb-item">Báo cáo</a>
    <span class="breadcrumb-sep">/</span>
    <a href="{{ route('report.sales.index') }}" class="breadcrumb-item">Sales</a>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-item">Hoạt động</span>
</nav>
@endsection

@section('content')
<div
    x-data="reportSalesActivity"
    x-init="init()"
    class="p-6 space-y-6"
>

    {{-- ── Page header ─────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Hoạt động Sales</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Thống kê hoạt động bán hàng theo loại và nhân viên phụ trách</p>
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
                    <input id="rpt-sa-date-from"
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
                    <input id="rpt-sa-date-to"
                           type="text"
                           x-ref="dateTo"
                           class="input input-sm input-bordered w-36 bg-base-100"
                           placeholder="dd/mm/yyyy"
                           autocomplete="off"
                           readonly/>
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
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">

        {{-- Total Activities --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-base-content leading-none"
                   x-text="summary.total_activities !== undefined ? summary.total_activities.toLocaleString('vi-VN') : '—'"></p>
                <p class="text-xs text-base-content/50 mt-0.5">Tổng hoạt động</p>
            </div>
        </div>

        {{-- Calls --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-info/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-base-content leading-none"
                   x-text="summary.total_calls !== undefined ? summary.total_calls.toLocaleString('vi-VN') : '—'"></p>
                <p class="text-xs text-base-content/50 mt-0.5">Cuộc gọi</p>
            </div>
        </div>

        {{-- Emails --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-warning/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-base-content leading-none"
                   x-text="summary.total_emails !== undefined ? summary.total_emails.toLocaleString('vi-VN') : '—'"></p>
                <p class="text-xs text-base-content/50 mt-0.5">Email</p>
            </div>
        </div>

        {{-- Meetings --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-success/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-base-content leading-none"
                   x-text="summary.total_meetings !== undefined ? summary.total_meetings.toLocaleString('vi-VN') : '—'"></p>
                <p class="text-xs text-base-content/50 mt-0.5">Cuộc họp</p>
            </div>
        </div>

        {{-- Demos --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-secondary/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-base-content leading-none"
                   x-text="summary.total_demos !== undefined ? summary.total_demos.toLocaleString('vi-VN') : '—'"></p>
                <p class="text-xs text-base-content/50 mt-0.5">Demo</p>
            </div>
        </div>

    </div>

    {{-- ── Charts ───────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">

        {{-- Donut: by activity type --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4 pb-3">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <h2 class="font-semibold text-sm text-base-content">Phân bổ theo loại hoạt động</h2>
                    <span class="badge badge-xs badge-ghost">Donut</span>
                </div>
                <div id="chart-sa-type" class="w-full" style="height: 300px;">
                    <div x-show="loading" class="flex items-center justify-center h-full">
                        <span class="loading loading-spinner loading-md text-primary"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bar: by day --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4 pb-3">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <h2 class="font-semibold text-sm text-base-content">Hoạt động theo ngày</h2>
                    <span class="badge badge-xs badge-ghost">Bar</span>
                </div>
                <div id="chart-sa-daily" class="w-full" style="height: 300px;">
                    <div x-show="loading" class="flex items-center justify-center h-full">
                        <span class="loading loading-spinner loading-md text-primary"></span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── By Assignee table ────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-4">
            <div class="flex items-center justify-between gap-2 mb-3">
                <h2 class="font-semibold text-sm text-base-content">Top 10 nhân viên theo hoạt động</h2>
                <span class="badge badge-xs badge-ghost">Top 10</span>
            </div>

            <div class="overflow-x-auto rounded-lg border border-base-200">
                <table class="table table-sm table-zebra w-full">
                    <thead class="bg-base-200/60">
                        <tr>
                            <th class="text-xs font-semibold text-base-content/70 w-8">#</th>
                            <th class="text-xs font-semibold text-base-content/70">Tên nhân viên</th>
                            <th class="text-xs font-semibold text-base-content/70 text-right">Tổng</th>
                            <th class="text-xs font-semibold text-base-content/70 text-right">Cuộc gọi</th>
                            <th class="text-xs font-semibold text-base-content/70 text-right">Email</th>
                            <th class="text-xs font-semibold text-base-content/70 text-right">Cuộc họp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-if="!loading && byAssignee.length === 0">
                            <tr>
                                <td colspan="6" class="text-center py-8 text-base-content/40 text-sm italic">
                                    Không có dữ liệu
                                </td>
                            </tr>
                        </template>
                        <template x-for="(row, idx) in byAssignee" :key="idx">
                            <tr class="hover:bg-base-200/40 transition-colors">
                                <td>
                                    <span class="text-xs font-mono text-base-content/40" x-text="idx + 1"></span>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="avatar placeholder">
                                            <div class="w-7 h-7 rounded-full bg-primary/10 text-primary text-xs font-bold flex items-center justify-center">
                                                <span x-text="(row.name || '?').charAt(0).toUpperCase()"></span>
                                            </div>
                                        </div>
                                        <span class="font-medium text-sm text-base-content" x-text="row.name || '—'"></span>
                                    </div>
                                </td>
                                <td class="text-right">
                                    <span class="font-mono text-sm font-semibold text-primary"
                                          x-text="(row.total ?? 0).toLocaleString('vi-VN')"></span>
                                </td>
                                <td class="text-right">
                                    <span class="font-mono text-sm text-info"
                                          x-text="(row.calls ?? 0).toLocaleString('vi-VN')"></span>
                                </td>
                                <td class="text-right">
                                    <span class="font-mono text-sm text-warning"
                                          x-text="(row.emails ?? 0).toLocaleString('vi-VN')"></span>
                                </td>
                                <td class="text-right">
                                    <span class="font-mono text-sm text-success"
                                          x-text="(row.meetings ?? 0).toLocaleString('vi-VN')"></span>
                                </td>
                            </tr>
                        </template>
                        <template x-if="loading">
                            <tr>
                                <td colspan="6" class="text-center py-6">
                                    <span class="loading loading-dots loading-sm text-primary"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot x-show="!loading && byAssignee.length > 0" class="bg-base-200/40 font-semibold">
                        <tr>
                            <td></td>
                            <td class="text-xs text-base-content/70">Tổng cộng</td>
                            <td class="text-right text-xs font-mono text-primary"
                                x-text="byAssignee.reduce((s, r) => s + (r.total ?? 0), 0).toLocaleString('vi-VN')"></td>
                            <td class="text-right text-xs font-mono text-info"
                                x-text="byAssignee.reduce((s, r) => s + (r.calls ?? 0), 0).toLocaleString('vi-VN')"></td>
                            <td class="text-right text-xs font-mono text-warning"
                                x-text="byAssignee.reduce((s, r) => s + (r.emails ?? 0), 0).toLocaleString('vi-VN')"></td>
                            <td class="text-right text-xs font-mono text-success"
                                x-text="byAssignee.reduce((s, r) => s + (r.meetings ?? 0), 0).toLocaleString('vi-VN')"></td>
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
    'Modules/Report/resources/assets/js/report.js',
], 'build/backend')

<script>
window.API_URL = "{{ route('api.report.sales.activity') }}";

document.addEventListener('alpine:init', () => {
    Alpine.data('reportSalesActivity', () => ({
        loading: false,
        filters: {
            date_from: '',
            date_to:   '',
        },

        summary:    {},
        byAssignee: [],
        dailyData:  [],
        typeData:   [],

        _typeChart:  null,
        _dailyChart: null,
        _fpFrom:     null,
        _fpTo:       null,

        init() {
            this._initFlatpickr();
            this.load();
        },

        _initFlatpickr() {
            if (!window.flatpickr) return;

            const opts = {
                dateFormat: 'd/m/Y',
                locale:     'vi',
                allowInput: false,
            };

            this._fpFrom = flatpickr(this.$refs.dateFrom, {
                ...opts,
                onChange: (dates, dateStr) => { this.filters.date_from = dateStr; },
            });
            this._fpTo = flatpickr(this.$refs.dateTo, {
                ...opts,
                onChange: (dates, dateStr) => { this.filters.date_to = dateStr; },
            });
        },

        applyFilters() {
            this.load();
        },

        resetFilters() {
            this.filters = { date_from: '', date_to: '' };
            this._fpFrom?.clear();
            this._fpTo?.clear();
            this.load();
        },

        async load() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.filters.date_from) params.set('date_from', this.filters.date_from);
                if (this.filters.date_to)   params.set('date_to',   this.filters.date_to);

                const url = window.API_URL + (params.toString() ? '?' + params.toString() : '');

                const res  = await fetch(url, {
                    headers: {
                        'Accept':           'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await res.json();

                this.summary    = data.summary    ?? {};
                this.byAssignee = (data.by_assignee ?? []).slice(0, 10);
                this.dailyData  = data.daily       ?? [];
                this.typeData   = data.by_type     ?? [];

                this.$nextTick(() => {
                    this._renderTypeChart(this.typeData);
                    this._renderDailyChart(this.dailyData);
                });
            } catch (e) {
                console.error('[reportSalesActivity] load error', e);
            } finally {
                this.loading = false;
            }
        },

        _renderTypeChart(typeData) {
            const el = document.getElementById('chart-sa-type');
            if (!el || !window.ECharts) return;

            if (!this._typeChart) {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                this._typeChart = window.ECharts.init(el, isDark ? 'dark' : null, { renderer: 'canvas' });
                new ResizeObserver(() => this._typeChart?.resize()).observe(el);
            }

            if (!typeData.length) {
                this._typeChart.clear();
                return;
            }

            const colors = ['#6366f1', '#0ea5e9', '#f59e0b', '#22c55e', '#a855f7', '#ef4444'];

            this._typeChart.setOption({
                tooltip: {
                    trigger: 'item',
                    formatter: p => `<b>${p.name}</b><br/>Số lượng: <strong>${(p.value ?? 0).toLocaleString('vi-VN')}</strong><br/>Tỷ lệ: <strong>${p.percent}%</strong>`,
                },
                legend: {
                    orient:    'vertical',
                    right:     '5%',
                    top:       'center',
                    textStyle: { fontSize: 11 },
                },
                color: colors,
                series: [{
                    type:         'pie',
                    radius:       ['42%', '70%'],
                    center:       ['38%', '50%'],
                    avoidLabelOverlap: true,
                    itemStyle: { borderRadius: 6, borderColor: '#fff', borderWidth: 2 },
                    label: { show: false },
                    emphasis: {
                        label: { show: true, fontSize: 13, fontWeight: 'bold' },
                        itemStyle: { shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0,0,0,0.1)' },
                    },
                    data: typeData.map((d, i) => ({
                        name:      d.type_label ?? d.type,
                        value:     d.count ?? 0,
                        itemStyle: { color: colors[i % colors.length] },
                    })),
                }],
            });
        },

        _renderDailyChart(dailyData) {
            const el = document.getElementById('chart-sa-daily');
            if (!el || !window.ECharts) return;

            if (!this._dailyChart) {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                this._dailyChart = window.ECharts.init(el, isDark ? 'dark' : null, { renderer: 'canvas' });
                new ResizeObserver(() => this._dailyChart?.resize()).observe(el);
            }

            if (!dailyData.length) {
                this._dailyChart.clear();
                return;
            }

            this._dailyChart.setOption({
                tooltip: {
                    trigger: 'axis',
                    axisPointer: { type: 'shadow' },
                    formatter: params => {
                        let html = `<div style="font-weight:600;margin-bottom:4px">${params[0]?.axisValue}</div>`;
                        params.forEach(p => {
                            html += `<div style="display:flex;align-items:center;gap:6px;margin-top:2px">${p.marker}<span>${p.seriesName}:</span><strong>${(p.value ?? 0).toLocaleString('vi-VN')}</strong></div>`;
                        });
                        return html;
                    },
                },
                legend: {
                    data:      ['Cuộc gọi', 'Email', 'Cuộc họp', 'Demo'],
                    bottom:    0,
                    textStyle: { fontSize: 11 },
                },
                grid: { left: 50, right: 15, top: 15, bottom: 40 },
                xAxis: {
                    type:      'category',
                    data:      dailyData.map(d => d.date),
                    axisLabel: { fontSize: 11, color: '#6b7280', rotate: dailyData.length > 14 ? 30 : 0 },
                    axisTick:  { show: false },
                },
                yAxis: {
                    type:        'value',
                    minInterval: 1,
                    splitLine:   { lineStyle: { type: 'dashed', color: '#f0f0f0' } },
                    axisLabel:   { fontSize: 11, color: '#6b7280' },
                },
                series: [
                    {
                        name:      'Cuộc gọi',
                        type:      'bar',
                        stack:     'total',
                        data:      dailyData.map(d => d.calls ?? 0),
                        itemStyle: { color: '#0ea5e9', borderRadius: [0, 0, 0, 0] },
                        emphasis:  { focus: 'series' },
                    },
                    {
                        name:      'Email',
                        type:      'bar',
                        stack:     'total',
                        data:      dailyData.map(d => d.emails ?? 0),
                        itemStyle: { color: '#f59e0b' },
                        emphasis:  { focus: 'series' },
                    },
                    {
                        name:      'Cuộc họp',
                        type:      'bar',
                        stack:     'total',
                        data:      dailyData.map(d => d.meetings ?? 0),
                        itemStyle: { color: '#22c55e' },
                        emphasis:  { focus: 'series' },
                    },
                    {
                        name:      'Demo',
                        type:      'bar',
                        stack:     'total',
                        data:      dailyData.map(d => d.demos ?? 0),
                        itemStyle: { color: '#a855f7', borderRadius: [4, 4, 0, 0] },
                        emphasis:  { focus: 'series' },
                    },
                ],
            });
        },
    }));
});
</script>
@endpush
