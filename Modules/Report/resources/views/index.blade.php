@extends('layouts.backend')

@section('title', 'Báo cáo tổng quan')

@section('breadcrumb')
<nav class="breadcrumb-nav">
    <span class="breadcrumb-item">Báo cáo</span>
</nav>
@endsection

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-base-content">Báo cáo & Phân tích</h1>
        <p class="text-sm text-base-content/60 mt-1">Tổng hợp dữ liệu cross-module theo tổ chức</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">

        @if($canHr)
        <div class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-md transition-shadow">
            <div class="card-body">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h2 class="card-title text-base">Nhân sự (HR)</h2>
                </div>
                <p class="text-sm text-base-content/60 mb-4">Headcount, biến động nhân sự, nghỉ phép theo tổ chức</p>
                <div class="flex flex-col gap-2">
                    <a href="{{ route('report.hr.headcount') }}" class="btn btn-sm btn-outline btn-primary w-full">Biến động nhân sự</a>
                    <a href="{{ route('report.hr.leave') }}"     class="btn btn-sm btn-outline w-full">Nghỉ phép</a>
                    <a href="{{ route('report.hr.index') }}"     class="btn btn-sm btn-ghost w-full text-xs">Xem tất cả HR →</a>
                </div>
            </div>
        </div>
        @endif

        @if($canSales)
        <div class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-md transition-shadow">
            <div class="card-body">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-success/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                    </div>
                    <h2 class="card-title text-base">Sales / CRM</h2>
                </div>
                <p class="text-sm text-base-content/60 mb-4">Pipeline, funnel chuyển đổi, doanh thu kỳ vọng</p>
                <div class="flex flex-col gap-2">
                    <a href="{{ route('report.sales.pipeline') }}"   class="btn btn-sm btn-outline btn-success w-full">Pipeline & Funnel</a>
                    <a href="{{ route('report.sales.conversion') }}" class="btn btn-sm btn-outline w-full">Tỷ lệ chuyển đổi</a>
                    <a href="{{ route('report.sales.index') }}"      class="btn btn-sm btn-ghost w-full text-xs">Xem tất cả Sales →</a>
                </div>
            </div>
        </div>
        @endif

        @if(auth()->user()?->hasAnyPermission(['reports.ops','reports.full']))
        <div class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-md transition-shadow">
            <div class="card-body">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-warning/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    </div>
                    <h2 class="card-title text-base">Project & KPI</h2>
                </div>
                <p class="text-sm text-base-content/60 mb-4">Tiến độ dự án, task, KPI cycle</p>
                <div class="flex flex-col gap-2">
                    <a href="{{ route('report.project.index') }}" class="btn btn-sm btn-outline btn-warning w-full">Project Overview</a>
                    <a href="{{ route('report.kpi.cycle') }}" class="btn btn-sm btn-outline w-full">KPI Cycle</a>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
