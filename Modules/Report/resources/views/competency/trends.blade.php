@extends('layouts.backend')
@section('title', 'Xu hướng Năng lực 12 tháng')

@section('content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div>
        <div class="text-xs breadcrumbs text-base-content/40 mb-1">
            <ul><li><a href="{{ route('report.competency.index') }}">Năng lực số</a></li><li>Xu hướng</li></ul>
        </div>
        <h1 class="text-xl font-bold">Xu hướng phát triển năng lực số</h1>
        <p class="text-sm text-base-content/50 mt-0.5">TDWCF score trung bình theo tháng — 12 tháng gần nhất</p>
    </div>
    <a href="{{ route('report.competency.index') }}" class="btn btn-ghost btn-sm">← Tổng quan</a>
</div>

@if($trendData->isEmpty())
<div class="card bg-base-100 border border-base-200 shadow-sm">
    <div class="card-body items-center text-center py-16">
        <svg class="w-10 h-10 text-base-content/20 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
        </svg>
        <p class="text-base-content/40 text-sm">Chưa có dữ liệu lịch sử đánh giá trong 12 tháng qua.</p>
        <p class="text-xs text-base-content/30 mt-1">Dữ liệu được tạo khi nhân sự hoàn thành đánh giá TDWCF.</p>
    </div>
</div>
@else

<div class="card bg-base-100 border border-base-200 shadow-sm mb-5">
    <div class="card-body p-5">
        <div id="trends-chart" style="height: 360px;"></div>
    </div>
</div>

{{-- Summary table --}}
<div class="card bg-base-100 border border-base-200 shadow-sm">
    <div class="card-body p-0 overflow-x-auto">
        <table class="table table-sm w-full">
            <thead>
                <tr class="bg-base-200/50 text-xs text-base-content/60">
                    <th>Tháng</th>
                    <th class="text-center">TDWCF TB</th>
                    <th class="text-center">Số lượt đánh giá</th>
                </tr>
            </thead>
            <tbody>
                @foreach($trendData as $row)
                <tr class="border-b border-base-200">
                    <td class="font-mono text-sm">{{ $row->month }}</td>
                    <td class="text-center">
                        <span class="{{ $row->avg_score >= 60 ? 'text-success' : ($row->avg_score >= 40 ? 'text-warning' : 'text-error') }} font-semibold">
                            {{ number_format($row->avg_score, 1) }}
                        </span>
                    </td>
                    <td class="text-center text-sm text-base-content/60">{{ $row->assessments }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
@vite(['resources/js/modules/echarts.js'], 'build/backend')
<script>
(function () {
    const data = @json($trendData);
    const months      = data.map(r => r.month);
    const avgScores   = data.map(r => parseFloat(r.avg_score).toFixed(1));
    const assessments = data.map(r => r.assessments);

    document.addEventListener('echarts:ready', () => {
        const el = document.getElementById('trends-chart');
        if (!el || !window.ECharts) return;
        const chart = window.ECharts.init(el);
        chart.setOption({
            tooltip: { trigger: 'axis', axisPointer: { type: 'cross' } },
            legend: { data: ['TDWCF TB', 'Số đánh giá'], bottom: 0 },
            grid: { left: 40, right: 60, top: 20, bottom: 40 },
            xAxis: { type: 'category', data: months, axisLabel: { fontSize: 11 } },
            yAxis: [
                {
                    type: 'value', name: 'TDWCF', min: 0, max: 100,
                    axisLabel: { fontSize: 11 },
                    splitLine: { lineStyle: { type: 'dashed', opacity: 0.3 } }
                },
                {
                    type: 'value', name: 'Đánh giá', min: 0,
                    axisLabel: { fontSize: 11 },
                    splitLine: { show: false }
                }
            ],
            series: [
                {
                    name: 'TDWCF TB',
                    type: 'line',
                    smooth: true,
                    yAxisIndex: 0,
                    data: avgScores,
                    lineStyle: { width: 3 },
                    itemStyle: { color: '#3b82f6' },
                    areaStyle: { opacity: 0.08 },
                    symbolSize: 6,
                },
                {
                    name: 'Số đánh giá',
                    type: 'bar',
                    yAxisIndex: 1,
                    data: assessments,
                    barMaxWidth: 24,
                    itemStyle: { color: '#10b981', opacity: 0.7 },
                }
            ]
        });
        window.addEventListener('resize', () => chart.resize());
    });
    document.dispatchEvent(new Event('echarts:ready'));
})();
</script>
@endpush

@endif

@endsection
