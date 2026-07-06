@extends('layouts.backend')
@section('title', 'AI Copilot — Usage Dashboard')

@section('content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">AI Usage Dashboard</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Theo dõi mức độ sử dụng và chi phí AI tháng này</p>
    </div>
    @can('ai_logs.full')
    <a href="{{ route('ai.logs.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Request Logs
    </a>
    @endcan
</div>

{{-- ── Stat cards ────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">

    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Requests tháng này</div>
        <div class="stat-value text-2xl text-primary">{{ number_format($requestsThisMonth) }}</div>
        <div class="stat-desc text-xs">{{ number_format($requestsRemaining) }} còn lại</div>
    </div>

    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Tokens tháng này</div>
        <div class="stat-value text-2xl text-secondary">{{ number_format($tokensThisMonth) }}</div>
        <div class="stat-desc text-xs">{{ number_format($tokensRemaining) }} còn lại</div>
    </div>

    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Chi phí tháng này</div>
        <div class="stat-value text-2xl text-warning">${{ number_format($costThisMonth, 2) }}</div>
        <div class="stat-desc text-xs">USD (ước tính)</div>
    </div>

    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Tỷ lệ thành công</div>
        @php $successRate = $requestsThisMonth > 0 ? round($successThisMonth / $requestsThisMonth * 100) : 0; @endphp
        <div class="stat-value text-2xl {{ $successRate >= 90 ? 'text-success' : ($successRate >= 70 ? 'text-warning' : 'text-error') }}">
            {{ $successRate }}%
        </div>
        <div class="stat-desc text-xs">{{ number_format($successThisMonth) }} / {{ number_format($requestsThisMonth) }}</div>
    </div>

</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">

    {{-- ── Quota bars ────────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body gap-5">
            <h2 class="font-semibold text-sm">Quota tháng này</h2>

            @include('subscription::partials.quota-bar', [
                'label' => 'AI Requests',
                'used'  => $requestsThisMonth,
                'limit' => $requestsLimit,
                'slug'  => 'quota.ai_requests',
            ])

            @include('subscription::partials.quota-bar', [
                'label' => 'AI Tokens',
                'used'  => $tokensThisMonth,
                'limit' => $tokensLimit,
                'slug'  => 'quota.ai_tokens',
            ])

            <div class="divider my-0"></div>

            {{-- Status breakdown --}}
            <div class="space-y-1.5 text-sm">
                @foreach(['done' => ['success','Thành công'], 'failed' => ['error','Thất bại'], 'pending' => ['warning','Đang chờ'], 'processing' => ['info','Đang xử lý']] as $s => [$color, $label])
                @php $cnt = $statusCounts[$s] ?? 0; @endphp
                <div class="flex justify-between items-center">
                    <span class="text-base-content/60">{{ $label }}</span>
                    <span class="badge badge-{{ $color }} badge-sm">{{ number_format($cnt) }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── 6-month trend chart ───────────────────────────────────────────── --}}
    <div class="lg:col-span-2 card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="font-semibold text-sm mb-2">Xu hướng 6 tháng</h2>
            @if($trend->count() >= 2)
            <div id="ai-trend-chart" class="w-full" style="height:220px;"></div>
            @elseif($trend->count() === 1)
            <div class="flex items-center justify-center h-32 text-base-content/30 text-sm">Cần ít nhất 2 tháng dữ liệu.</div>
            @else
            <div class="flex items-center justify-center h-32 text-base-content/30 text-sm">Chưa có dữ liệu.</div>
            @endif
        </div>
    </div>

</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">

    {{-- ── Top agents ────────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0">
            <div class="px-5 pt-4 pb-3 border-b border-base-200">
                <h2 class="font-semibold text-sm">Top Agents tháng này</h2>
            </div>
            <table class="table table-xs">
                <thead class="bg-base-200/50">
                    <tr>
                        <th>Agent</th>
                        <th class="text-right">Requests</th>
                        <th class="text-right">Tokens</th>
                        <th class="text-right">Chi phí</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($byAgent as $row)
                    <tr class="hover:bg-base-200/30">
                        <td>
                            <div class="font-mono text-xs text-primary">{{ $row['agent_slug'] }}</div>
                            <div class="text-xs text-base-content/60">{{ $row['task_type'] }}</div>
                        </td>
                        <td class="text-right text-sm">{{ number_format($row['requests']) }}</td>
                        <td class="text-right text-xs text-base-content/70">{{ number_format($row['tokens']) }}</td>
                        <td class="text-right text-xs">${{ number_format($row['cost'], 3) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-sm text-base-content/40 py-6">Chưa có dữ liệu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Recent requests ───────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0">
            <div class="px-5 pt-4 pb-3 border-b border-base-200 flex items-center justify-between">
                <h2 class="font-semibold text-sm">Requests gần đây</h2>
                @can('ai_logs.full')
                <a href="{{ route('ai.logs.index') }}" class="text-xs link link-primary">Xem tất cả</a>
                @endcan
            </div>
            <table class="table table-xs">
                <thead class="bg-base-200/50">
                    <tr>
                        <th>Agent</th>
                        <th>Status</th>
                        <th class="text-right">Tokens</th>
                        <th>Thời gian</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentRequests as $req)
                    <tr class="hover:bg-base-200/30">
                        <td>
                            <div class="font-mono text-xs text-primary">{{ $req->agent?->slug ?? '—' }}</div>
                            <div class="text-xs text-base-content/50">{{ $req->provider }}</div>
                        </td>
                        <td>
                            @php
                                $badge = match($req->status) {
                                    'done'       => 'badge-success',
                                    'failed'     => 'badge-error',
                                    'processing' => 'badge-info',
                                    default      => 'badge-warning',
                                };
                            @endphp
                            <span class="badge {{ $badge }} badge-xs">{{ $req->status }}</span>
                        </td>
                        <td class="text-right text-xs">{{ $req->total_tokens > 0 ? number_format($req->total_tokens) : '—' }}</td>
                        <td class="text-xs text-base-content/50">{{ $req->created_at->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-sm text-base-content/40 py-6">Chưa có request nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection

@push('scripts')
@vite(['resources/js/modules/echarts.js'], 'build/backend')
<script>
(function () {
    const trendData = @json($trend);
    if (!trendData || trendData.length < 2) return;

    function render() {
        const el = document.getElementById('ai-trend-chart');
        if (!el || !window.ECharts) return;

        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const chart  = window.ECharts.init(el, isDark ? 'dark' : null, { renderer: 'canvas' });

        chart.setOption({
            tooltip: {
                trigger: 'axis',
                formatter: params => {
                    const p = params[0];
                    return `${p.axisValue}<br/>Requests: <b>${p.data.requests}</b><br/>Tokens: <b>${p.data.tokens.toLocaleString()}</b><br/>Chi phí: <b>$${p.data.cost}</b>`;
                }
            },
            legend: {
                data: ['Requests', 'Tokens'],
                bottom: 0,
                textStyle: { fontSize: 11 },
            },
            grid: { left: 50, right: 15, top: 15, bottom: 40 },
            xAxis: {
                type: 'category',
                data: trendData.map(d => d.month),
                axisLabel: { fontSize: 11 },
            },
            yAxis: [
                { type: 'value', name: 'Requests', nameTextStyle: { fontSize: 10 }, axisLabel: { fontSize: 10 } },
                { type: 'value', name: 'Tokens',   nameTextStyle: { fontSize: 10 }, axisLabel: { fontSize: 10 } },
            ],
            series: [
                {
                    name: 'Requests',
                    type: 'bar',
                    data: trendData.map(d => ({ value: d.requests, requests: d.requests, tokens: d.tokens, cost: d.cost })),
                    itemStyle: { color: '#6366f1' },
                    barMaxWidth: 40,
                },
                {
                    name: 'Tokens',
                    type: 'line',
                    yAxisIndex: 1,
                    data: trendData.map(d => d.tokens),
                    smooth: true,
                    symbol: 'circle',
                    symbolSize: 5,
                    lineStyle: { color: '#f59e0b', width: 2 },
                    itemStyle: { color: '#f59e0b' },
                },
            ],
        });

        window.addEventListener('resize', () => chart.resize());
    }

    if (window.ECharts) {
        render();
    } else {
        document.addEventListener('echarts:ready', render, { once: true });
    }
})();
</script>
@endpush
