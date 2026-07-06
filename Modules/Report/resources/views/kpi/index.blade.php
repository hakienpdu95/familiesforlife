@extends('layouts.backend')
@section('title', 'Báo cáo KPI')

@section('breadcrumb')
<nav class="breadcrumb-nav">
    <a href="{{ route('report.index') }}" class="breadcrumb-item">Báo cáo</a>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-item">KPI</span>
</nav>
@endsection

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-base-content">Báo cáo KPI</h1>
        <p class="text-sm text-base-content/60 mt-1">Phân tích hiệu suất mục tiêu theo cycle và lịch sử KPI</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <a href="{{ route('report.kpi.cycle') }}"
           class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-md transition-all hover:border-warning/50 p-5 flex flex-row items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-warning/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-base-content">KPI theo Cycle</p>
                <p class="text-sm text-base-content/60">Mức độ đạt mục tiêu, phân phối kết quả và top performers theo chu kỳ đánh giá</p>
            </div>
        </a>

        <a href="{{ route('report.kpi.snapshot') }}"
           class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-md transition-all hover:border-info/50 p-5 flex flex-row items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-info/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-base-content">Lịch sử KPI</p>
                <p class="text-sm text-base-content/60">Xu hướng điểm KPI qua các kỳ, phân bổ xếp loại A/B/C/D theo thời gian</p>
            </div>
        </a>

    </div>
</div>
@endsection
