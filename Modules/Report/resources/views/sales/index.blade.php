@extends('layouts.backend')
@section('title', 'Báo cáo Sales')

@section('breadcrumb')
<nav class="breadcrumb-nav">
    <a href="{{ route('report.index') }}" class="breadcrumb-item">Báo cáo</a>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-item">Sales</span>
</nav>
@endsection

@section('content')
<div class="p-6">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-base-content">Báo cáo Sales</h1>
        <p class="text-sm text-base-content/60 mt-1">Tổng quan hiệu suất kinh doanh & chuyển đổi</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- Pipeline --}}
        <a href="{{ route('report.sales.pipeline') }}"
           class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-md transition-all hover:border-primary/50 p-5 flex flex-row items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-base-content">Pipeline & Funnel</p>
                <p class="text-sm text-base-content/60">Phân tích phễu bán hàng theo giai đoạn, giá trị cơ hội</p>
            </div>
        </a>

        {{-- Conversion --}}
        <a href="{{ route('report.sales.conversion') }}"
           class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-md transition-all hover:border-success/50 p-5 flex flex-row items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-success/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-base-content">Tỷ lệ chuyển đổi</p>
                <p class="text-sm text-base-content/60">Conversion rate theo nguồn, cohort tháng, nhiệt độ lead</p>
            </div>
        </a>

        {{-- Activity --}}
        <a href="{{ route('report.sales.activity') }}"
           class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-md transition-all hover:border-warning/50 p-5 flex flex-row items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-warning/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-base-content">Hoạt động Sales</p>
                <p class="text-sm text-base-content/60">Thống kê cuộc gọi, email, cuộc hẹn theo nhân viên & giai đoạn</p>
            </div>
        </a>

    </div>

</div>
@endsection
