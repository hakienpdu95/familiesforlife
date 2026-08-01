@extends('layouts.backend')
@section('title', 'Thống kê traffic')

@section('content')
@php
    $pctBadge = function (?float $pct) {
        if ($pct === null) {
            return ['label' => '—', 'class' => 'badge-ghost'];
        }

        $sign = $pct > 0 ? '+' : '';

        return [
            'label' => $sign . $pct . '%',
            'class' => $pct > 0 ? 'badge-success' : ($pct < 0 ? 'badge-error' : 'badge-ghost'),
        ];
    };

    // spec/ga-dashboard-statistics.md §2.1 — cùng key thật của package (pageReferrer/country/
    // browser/deviceCategory/newVsReturning), KHÔNG đổi tên tuỳ ý.
    $trafficSections = [
        ['title' => 'Nguồn dẫn (Referrer)',        'key' => 'pageReferrer',   'metric' => 'screenPageViews', 'data' => $overview['referrers'] ?? collect()],
        ['title' => 'Quốc gia',                     'key' => 'country',        'metric' => 'screenPageViews', 'data' => $overview['countries'] ?? collect()],
        ['title' => 'Trình duyệt',                  'key' => 'browser',        'metric' => 'screenPageViews', 'data' => $overview['browsers'] ?? collect()],
        ['title' => 'Thiết bị',                     'key' => 'deviceCategory', 'metric' => 'screenPageViews', 'data' => $overview['devices'] ?? collect()],
        ['title' => 'Người dùng mới / quay lại',    'key' => 'newVsReturning', 'metric' => 'activeUsers',     'data' => $overview['userTypes'] ?? collect()],
    ];
@endphp

<div class="space-y-6">

    {{-- ── Header ─────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-base-content">Thống kê traffic</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Dữ liệu Google Analytics 4 cho toàn bộ bài viết.</p>
            <p class="text-xs text-base-content/40 mt-1">Dữ liệu Google Analytics có thể trễ 1-2 ngày so với thời gian thực.</p>
        </div>

        <form method="GET" class="flex items-center gap-2 shrink-0">
            <select name="days" class="select select-bordered select-sm" onchange="this.form.submit()">
                <option value="7" @selected($days === 7)>7 ngày</option>
                <option value="30" @selected($days === 30)>30 ngày</option>
                <option value="90" @selected($days === 90)>90 ngày</option>
            </select>
        </form>
    </div>

    @if($error)
    <div class="alert alert-warning text-sm">
        <span>{{ $error }}</span>
    </div>
    @else
    {{-- ── Summary cards ───────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs text-base-content/50">Active Users</p>
                <span class="badge badge-sm {{ $pctBadge($overview['summary']['activeUsersChangePct'])['class'] }}">{{ $pctBadge($overview['summary']['activeUsersChangePct'])['label'] }}</span>
            </div>
            <p class="text-2xl font-bold text-base-content leading-none mt-2">{{ number_format($overview['summary']['activeUsers']) }}</p>
        </div>
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs text-base-content/50">Screen Page Views</p>
                <span class="badge badge-sm {{ $pctBadge($overview['summary']['pageViewsChangePct'])['class'] }}">{{ $pctBadge($overview['summary']['pageViewsChangePct'])['label'] }}</span>
            </div>
            <p class="text-2xl font-bold text-base-content leading-none mt-2">{{ number_format($overview['summary']['pageViews']) }}</p>
        </div>
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs text-base-content/50">Sessions</p>
                <span class="badge badge-sm {{ $pctBadge($overview['summary']['sessionsChangePct'])['class'] }}">{{ $pctBadge($overview['summary']['sessionsChangePct'])['label'] }}</span>
            </div>
            <p class="text-2xl font-bold text-base-content leading-none mt-2">{{ number_format($overview['summary']['sessions']) }}</p>
        </div>
    </div>

    {{-- ── Time-series chart ───────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-4 pb-3">
            <h2 class="font-semibold text-base-content mb-3">Active Users & Page Views theo ngày</h2>
            <div id="analyticsTimeseriesChart" class="w-full" style="height:280px"></div>
        </div>
    </div>

    {{-- ── Traffic sources ─────────────────────────────────────────────── --}}
    <div class="grid md:grid-cols-2 gap-4">
        @foreach($trafficSections as $section)
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <h2 class="font-semibold text-base-content mb-3">{{ $section['title'] }}</h2>
                @if($section['data']->isEmpty())
                <p class="text-sm text-base-content/40 italic py-4 text-center">Chưa có dữ liệu.</p>
                @else
                @php $max = $section['data']->max($section['metric']) ?: 1; @endphp
                <div class="space-y-2.5">
                    @foreach($section['data'] as $row)
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-base-content/80 w-32 truncate shrink-0" title="{{ $row[$section['key']] }}">{{ $row[$section['key']] ?: '(not set)' }}</span>
                        <div class="flex-1 h-2 bg-base-200 rounded-full overflow-hidden">
                            <div class="h-full bg-primary rounded-full" style="width: {{ $row[$section['metric']] / $max * 100 }}%"></div>
                        </div>
                        <span class="text-xs font-mono text-base-content/50 w-12 text-right shrink-0">{{ number_format($row[$section['metric']]) }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── Top nội dung (luôn hiển thị được — đọc ga_views_30d, không phụ thuộc $error) ──── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-4">
            <h2 class="font-semibold text-base-content mb-3">Top nội dung</h2>
            @if($topArticles->isEmpty())
            <p class="text-sm text-base-content/40 italic py-4 text-center">
                Chưa có dữ liệu — chờ lệnh <code>post:sync-ga-stats</code> chạy lần đầu.
            </p>
            @else
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead class="text-xs uppercase tracking-wide">
                        <tr>
                            <th>Bài viết</th>
                            <th class="text-center w-32">Lượt xem GA</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($topArticles as $translation)
                        <tr class="hover">
                            <td>
                                <a href="{{ route('backend.post.articles.edit', $translation->article) }}?locale={{ $translation->locale }}" class="link link-hover text-sm">
                                    {{ $translation->title }}
                                </a>
                            </td>
                            <td class="text-center font-mono text-sm">{{ number_format($translation->ga_views_30d) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

</div>
@endsection

@push('scripts')
    @vite(['resources/js/modules/echarts.js'], 'build/backend')
    <script>
    (function () {
        const timeseries = @json($overview['timeseries'] ?? []);

        function render() {
            const dom = document.getElementById('analyticsTimeseriesChart');
            if (!dom || !window.ECharts || !timeseries.length) return;

            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const chart  = window.ECharts.init(dom, isDark ? 'dark' : null, { renderer: 'canvas' });

            const labels      = timeseries.map(d => {
                const [, m, day] = d.date.split('-');
                return `${day}/${m}`;
            });
            const activeUsers = timeseries.map(d => d.activeUsers);
            const pageViews   = timeseries.map(d => d.screenPageViews);

            chart.setOption({
                tooltip: { trigger: 'axis' },
                legend: { data: ['Active Users', 'Page Views'], bottom: 0 },
                grid: { left: 40, right: 12, top: 12, bottom: 40 },
                xAxis: {
                    type: 'category',
                    data: labels,
                    axisLabel: { interval: Math.ceil(timeseries.length / 10) - 1, fontSize: 11 },
                    axisTick: { show: false },
                },
                yAxis: { type: 'value', minInterval: 1, min: 0, splitLine: { lineStyle: { type: 'dashed' } } },
                series: [
                    { name: 'Active Users', type: 'line', data: activeUsers, smooth: true, itemStyle: { color: '#6366f1' } },
                    { name: 'Page Views',   type: 'line', data: pageViews,   smooth: true, itemStyle: { color: '#22c55e' } },
                ],
            });

            new ResizeObserver(() => chart.resize()).observe(dom);
        }

        if (window.ECharts) render();
        else document.addEventListener('echarts:ready', render, { once: true });
    })();
    </script>
@endpush
