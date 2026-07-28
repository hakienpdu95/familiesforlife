@extends('layouts.backend')
@section('title', 'Thống kê click — ' . $title)

@section('content')
@php
    $byDay      = $stats['byDay'];
    $last30Sum  = $byDay->sum('count');
    $peakDay    = $byDay->sortByDesc('count')->first();
    $avgPerDay  = $byDay->count() > 0 ? round($last30Sum / $byDay->count(), 1) : 0;
@endphp

<div class="space-y-6">

    {{-- ── Header ─────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-base-content">Thống kê click</h1>
            <p class="text-sm text-base-content/50 mt-0.5 truncate max-w-lg">{{ $title }}</p>
            <a href="{{ $article->redirect_url }}" target="_blank" rel="noopener"
               class="text-xs text-primary hover:underline break-all">{{ $article->redirect_url }} ↗</a>
        </div>
        <a href="{{ route('backend.post.articles.edit', $article) }}" class="btn btn-ghost btn-sm gap-1.5 shrink-0">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
            </svg>
            Quay lại
        </a>
    </div>

    {{-- ── Summary cards ───────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 010 5.656l-4 4a4 4 0 01-5.656-5.656l1.5-1.5m5.656-5.656l1.5-1.5a4 4 0 115.656 5.656l-4 4a4 4 0 01-5.656 0"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-base-content leading-none">{{ number_format($stats['total']) }}</p>
                <p class="text-xs text-base-content/50 mt-0.5">Tổng số click</p>
            </div>
        </div>

        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-info/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-base-content leading-none">{{ number_format($last30Sum) }}</p>
                <p class="text-xs text-base-content/50 mt-0.5">30 ngày gần nhất</p>
            </div>
        </div>

        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-success/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-base-content leading-none">{{ $avgPerDay }}</p>
                <p class="text-xs text-base-content/50 mt-0.5">Trung bình/ngày</p>
            </div>
        </div>

        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-warning/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-base-content leading-none">{{ $peakDay ? $peakDay['count'] : 0 }}</p>
                <p class="text-xs text-base-content/50 mt-0.5">
                    @if($peakDay && $peakDay['count'] > 0)
                        Cao nhất {{ \Carbon\Carbon::parse($peakDay['day'])->format('d/m') }}
                    @else
                        Cao nhất (30 ngày)
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- ── Daily chart ─────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-4 pb-3">
            <h2 class="font-semibold text-base-content mb-3">Lượt click theo ngày (30 ngày gần nhất)</h2>
            <div id="clicksDailyChart" class="w-full" style="height:260px"></div>
        </div>
    </div>

    {{-- ── Top referrers ───────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-3">
            <h2 class="font-semibold text-base-content mb-1">Top nguồn dẫn tới click (30 ngày gần nhất)</h2>
            <p class="text-xs text-base-content/40 mb-3">Đường dẫn (vd "/", "/bai-viet/danh-muc/...") = trang trên chính site này; tên domain = nguồn ngoài (Facebook, Google...).</p>
            @if($stats['topReferrers']->isEmpty())
            <p class="text-sm text-base-content/40 italic py-4 text-center">Chưa có dữ liệu referrer (người dùng vào trực tiếp hoặc trình duyệt không gửi Referer).</p>
            @else
            @php $maxRefCount = $stats['topReferrers']->max('count') ?: 1; @endphp
            <div class="space-y-2.5">
                @foreach($stats['topReferrers'] as $ref)
                @php $isInternal = str_starts_with($ref['referrer'], '/'); @endphp
                <div class="flex items-center gap-3">
                    <span class="badge badge-xs {{ $isInternal ? 'badge-ghost' : 'badge-info' }} shrink-0" title="{{ $isInternal ? 'Trang trên site này' : 'Nguồn ngoài' }}">
                        {{ $isInternal ? 'Nội bộ' : 'Ngoài' }}
                    </span>
                    <span class="text-sm text-base-content/80 w-40 truncate shrink-0 font-mono" title="{{ $ref['referrer'] }}">{{ $ref['referrer'] }}</span>
                    <div class="flex-1 h-2 bg-base-200 rounded-full overflow-hidden">
                        <div class="h-full bg-primary rounded-full" style="width: {{ $ref['count'] / $maxRefCount * 100 }}%"></div>
                    </div>
                    <span class="text-xs font-mono text-base-content/50 w-10 text-right shrink-0">{{ $ref['count'] }}</span>
                </div>
                @endforeach
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
        const byDay = @json($byDay->values());

        function render() {
            const dom = document.getElementById('clicksDailyChart');
            if (!dom || !window.ECharts || !byDay.length) return;

            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            const chart  = window.ECharts.init(dom, isDark ? 'dark' : null, { renderer: 'canvas' });

            const labels = byDay.map(d => {
                const [, m, day] = d.day.split('-');
                return `${day}/${m}`;
            });
            const counts = byDay.map(d => d.count);

            chart.setOption({
                tooltip: {
                    trigger: 'axis',
                    formatter (params) {
                        const i = params[0].dataIndex;
                        return `<div style="font-weight:600;margin-bottom:4px">${byDay[i].day}</div>Click: <strong>${params[0].value}</strong>`;
                    },
                },
                grid: { left: 40, right: 12, top: 12, bottom: 32 },
                xAxis: {
                    type: 'category',
                    data: labels,
                    axisLabel: { interval: Math.ceil(byDay.length / 10) - 1, fontSize: 11 },
                    axisTick: { show: false },
                },
                yAxis: { type: 'value', minInterval: 1, min: 0, splitLine: { lineStyle: { type: 'dashed' } } },
                series: [{
                    name: 'Click',
                    type: 'bar',
                    data: counts,
                    barMaxWidth: 18,
                    itemStyle: { color: '#6366f1', borderRadius: [4, 4, 0, 0] },
                }],
            });

            new ResizeObserver(() => chart.resize()).observe(dom);
        }

        if (window.ECharts) render();
        else document.addEventListener('echarts:ready', render, { once: true });
    })();
    </script>
@endpush
