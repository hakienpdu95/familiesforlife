@extends('layouts.backend')
@section('title', 'AI Impact — '.$employee->full_name)

@push('styles')
    @vite(['Modules/Assessment/resources/assets/sass/assessment.scss'], 'build/backend')
@endpush

@section('content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('backend.ai-impact.index') }}" class="btn btn-ghost btn-sm px-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold">{{ $employee->full_name }}</h1>
            <p class="text-sm text-base-content/40">{{ $employee->position ?? $employee->employee_code }} — AI Impact Profile</p>
        </div>
    </div>
    <a href="{{ route('backend.ai-impact.create') }}?employee_id={{ $employee->id }}" class="btn btn-primary btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Thêm bản ghi
    </a>
</div>

{{-- ── Stats ──────────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">AII hiện tại</div>
        <div class="stat-value text-2xl {{ $currentAii >= 50 ? 'text-success' : ($currentAii >= 20 ? 'text-warning' : 'text-base-content') }}">
            {{ number_format($currentAii, 1) }}
        </div>
        <div class="stat-desc text-xs">AI Impact Index</div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Tổng bản ghi</div>
        <div class="stat-value text-2xl text-primary">{{ $snapshots->count() }}</div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Cải thiện TB</div>
        <div class="stat-value text-2xl text-success">
            {{ $snapshots->count() ? number_format($snapshots->avg('improvement_pct'), 1).'%' : '—' }}
        </div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">ROI trung bình</div>
        <div class="stat-value text-2xl text-accent">
            @php $avgRoi = $snapshots->whereNotNull('roi_pct')->where('roi_pct', '>', 0)->avg('roi_pct'); @endphp
            {{ $avgRoi ? number_format($avgRoi / 100, 1).'x' : '—' }}
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">

    {{-- ── AII Trend Chart (Part 4) ──────────────────────────────────────────── --}}
    <div class="lg:col-span-2 card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="card-title text-sm mb-1">Xu hướng AII theo thời gian</h2>
            @if($trend->count() >= 2)
            <div id="aii-trend-chart" class="w-full" style="height:220px;"></div>
            @elseif($trend->count() === 1)
            <div class="flex items-center justify-center h-32 text-base-content/30 text-sm">
                Cần ít nhất 2 kỳ dữ liệu để hiển thị xu hướng.
            </div>
            @else
            <div class="flex items-center justify-center h-32 text-base-content/30 text-sm">
                Chưa có dữ liệu.
            </div>
            @endif
        </div>
    </div>

    {{-- ── Category breakdown ────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="card-title text-sm mb-3">Phân bổ theo danh mục</h2>
            @php
            $catMeta = [
                'learning'     => ['Học tập',     'bg-info',     'text-info'],
                'productivity' => ['Năng suất',   'bg-success',  'text-success'],
                'quality'      => ['Chất lượng',  'bg-accent',   'text-accent'],
                'ai_adoption'  => ['Ứng dụng AI', 'bg-primary',  'text-primary'],
                'business'     => ['Kinh doanh',  'bg-warning',  'text-warning'],
            ];
            $maxImprove = $byCategory->max(fn($v) => abs($v['avg_improve'])) ?: 1;
            @endphp
            <div class="space-y-2.5">
                @foreach($catMeta as $key => [$label, $bgCls, $textCls])
                @php $cat = $byCategory[$key] ?? null; @endphp
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="{{ $textCls }} font-medium">{{ $label }}</span>
                        <span class="text-base-content/40">
                            @if($cat) {{ $cat['count'] }} bản ghi · {{ $cat['avg_improve'] >= 0 ? '+' : '' }}{{ $cat['avg_improve'] }}%
                            @else —
                            @endif
                        </span>
                    </div>
                    <div class="h-1.5 bg-base-200 rounded-full overflow-hidden">
                        @if($cat)
                        <div class="{{ $bgCls }}/70 h-1.5 rounded-full"
                             style="width: {{ min(abs($cat['avg_improve']) / $maxImprove * 100, 100) }}%"></div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

{{-- ── Snapshot table ─────────────────────────────────────────────────────── --}}
<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body p-0">
        @if($snapshots->count())
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr class="text-xs text-base-content/50 border-b border-base-200">
                        <th class="px-5 py-3">Danh mục</th>
                        <th>Chỉ số đo</th>
                        <th class="text-center">Trước</th>
                        <th class="text-center">Sau</th>
                        <th class="text-center">Cải thiện</th>
                        <th class="text-center">ROI</th>
                        <th>Kỳ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($snapshots as $snap)
                    @php
                        $catLabels = [
                            'learning'     => ['Học tập',     'badge-info'],
                            'productivity' => ['Năng suất',   'badge-success'],
                            'quality'      => ['Chất lượng',  'badge-accent'],
                            'ai_adoption'  => ['Ứng dụng AI', 'badge-primary'],
                            'business'     => ['Kinh doanh',  'badge-warning'],
                        ];
                        $cat = $catLabels[$snap->impact_category] ?? [$snap->impact_category, 'badge-ghost'];
                    @endphp
                    <tr class="hover:bg-base-50 border-b border-base-200/50 last:border-0">
                        <td class="px-5 py-3">
                            <span class="badge {{ $cat[1] }} badge-xs">{{ $cat[0] }}</span>
                        </td>
                        <td class="text-sm text-base-content/70 max-w-36 truncate" title="{{ $snap->impact_type }}">
                            {{ $snap->impact_type }}
                        </td>
                        <td class="text-center text-sm font-mono">{{ number_format($snap->baseline_value, 1) }}</td>
                        <td class="text-center text-sm font-mono">{{ number_format($snap->achieved_value, 1) }}</td>
                        <td class="text-center">
                            @if($snap->improvement_pct !== null)
                            <span class="font-semibold {{ $snap->improvement_pct >= 0 ? 'text-success' : 'text-error' }}">
                                {{ $snap->improvement_pct >= 0 ? '+' : '' }}{{ number_format($snap->improvement_pct, 1) }}%
                            </span>
                            @else —
                            @endif
                        </td>
                        <td class="text-center">
                            @if($snap->roi_pct !== null)
                            <span class="font-semibold text-accent">{{ number_format($snap->roi_pct / 100, 1) }}x</span>
                            @else <span class="text-base-content/30">—</span>
                            @endif
                        </td>
                        <td class="text-xs text-base-content/40 whitespace-nowrap">
                            {{ $snap->period_start?->format('d/m/Y') }} – {{ $snap->period_end?->format('d/m/Y') }}
                        </td>
                        <td class="pr-4">
                            <div class="flex gap-1">
                                <a href="{{ route('backend.ai-impact.edit', $snap) }}" class="btn btn-ghost btn-xs">Sửa</a>
                                <form method="POST" action="{{ route('backend.ai-impact.destroy', $snap) }}"
                                      onsubmit="return confirm('Xoá bản ghi này?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-ghost btn-xs text-error">Xoá</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-12">
            <p class="text-base-content/30 text-sm">Nhân viên chưa có bản ghi tác động AI nào.</p>
        </div>
        @endif
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
        const el = document.getElementById('aii-trend-chart');
        if (!el || !window.ECharts) return;

        const chart = window.ECharts.init(el, document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : null, { renderer: 'canvas' });

        chart.setOption({
            tooltip: { trigger: 'axis', formatter: params => `${params[0].axisValue}<br/>AII: <b>${params[0].value}</b>` },
            grid: { left: 40, right: 10, top: 20, bottom: 30 },
            xAxis: {
                type: 'category',
                data: trendData.map(d => d.month),
                axisLabel: { fontSize: 11 },
            },
            yAxis: { type: 'value', axisLabel: { fontSize: 11 } },
            series: [{
                data: trendData.map(d => d.aii),
                type: 'line',
                smooth: true,
                symbol: 'circle',
                symbolSize: 6,
                lineStyle: { width: 2, color: '#6366f1' },
                itemStyle: { color: '#6366f1' },
                areaStyle: { color: { type: 'linear', x: 0, y: 0, x2: 0, y2: 1, colorStops: [{ offset: 0, color: 'rgba(99,102,241,0.3)' }, { offset: 1, color: 'rgba(99,102,241,0)' }] } },
            }],
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
