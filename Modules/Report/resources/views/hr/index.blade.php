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
        <a href="{{ route('report.hr.leave') }}" class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-md transition-all hover:border-warning/50 p-5 flex flex-row items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-warning/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div>
                <p class="font-semibold text-base-content">Nghỉ phép</p>
                <p class="text-sm text-base-content/60">Thống kê nghỉ phép theo loại, phòng ban, nhân viên</p>
            </div>
        </a>
        <a href="{{ route('report.hr.recruitment') }}" class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-md transition-all hover:border-info/50 p-5 flex flex-row items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-info/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35M11 5a7 7 0 100 14A7 7 0 0011 5zm-2 6h4m-2-2v4"/></svg>
            </div>
            <div>
                <p class="font-semibold text-base-content">Tuyển dụng</p>
                <p class="text-sm text-base-content/60">Thống kê ứng viên, vị trí tuyển dụng, tỷ lệ chuyển đổi</p>
            </div>
        </a>
        <a href="{{ route('report.hr.performance') }}" class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-md transition-all hover:border-success/50 p-5 flex flex-row items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-success/10 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.518 4.674a1 1 0 00.95.69h4.911c.969 0 1.371 1.24.588 1.81l-3.974 2.888a1 1 0 00-.364 1.118l1.518 4.674c.3.921-.755 1.688-1.538 1.118l-3.974-2.888a1 1 0 00-1.176 0l-3.974 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.364-1.118L2.98 10.1c-.783-.57-.38-1.81.588-1.81h4.911a1 1 0 00.95-.69l1.518-4.674z"/></svg>
            </div>
            <div>
                <p class="font-semibold text-base-content">Đánh giá hiệu suất</p>
                <p class="text-sm text-base-content/60">Kết quả đánh giá, xếp hạng, phân bổ theo phòng ban</p>
            </div>
        </a>
    </div>
</div>
@endsection
