@extends('layouts.backend')
@section('title', 'Báo cáo HR')
@section('breadcrumb')
<nav class="breadcrumb-nav">
    <a href="{{ route('report.index') }}" class="breadcrumb-item">Báo cáo</a>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-item">Nhân sự (HR)</span>
</nav>
@endsection
@section('content')
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-base-content">Báo cáo Nhân sự</h1>
        <p class="text-sm text-base-content/60 mt-1">Tổng quan HR toàn tổ chức</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="{{ route('report.hr.headcount') }}" class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-md transition-all hover:border-primary/50 p-5 flex flex-row items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
                <p class="font-semibold text-base-content">Biến động nhân sự</p>
                <p class="text-sm text-base-content/60">Headcount, tuyển dụng, nghỉ việc theo thời gian</p>
            </div>
        </a>
    </div>
</div>
@endsection
