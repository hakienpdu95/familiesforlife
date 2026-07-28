@extends('layouts.backend')

@section('title', 'Báo cáo tổng quan')

@section('breadcrumb')
<nav class="breadcrumb-nav">
    <span class="breadcrumb-item">Báo cáo</span>
</nav>
@endsection

@section('content')
<div class="p-3">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-base-content">Báo cáo & Phân tích</h1>
        <p class="text-sm text-base-content/60 mt-1">Tổng hợp dữ liệu cross-module theo tổ chức</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">

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

    </div>
</div>
@endsection
