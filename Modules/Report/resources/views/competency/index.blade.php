@extends('layouts.backend')
@section('title', 'Báo cáo năng lực số')

@section('content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-bold">Báo cáo năng lực số tổ chức</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Tổng quan TDWCF — Digital Workforce Competency Framework</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('report.competency.heatmap') }}"   class="btn btn-outline btn-sm">Heatmap phòng ban</a>
        <a href="{{ route('report.competency.skill-gap') }}" class="btn btn-outline btn-sm">Phân tích Skill Gap</a>
        <a href="{{ route('report.competency.trends') }}"    class="btn btn-outline btn-sm">Xu hướng 12 tháng</a>
        <a href="{{ route('report.competency.export') }}"    class="btn btn-primary btn-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Xuất Excel
        </a>
    </div>
</div>

{{-- ── 4 stat cards ─────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Tổng nhân sự</div>
        <div class="stat-value text-2xl text-primary">{{ $profiles->count() }}</div>
        <div class="stat-desc">có hồ sơ Digital Twin</div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">TDWCF TB tổ chức</div>
        <div class="stat-value text-2xl {{ $orgAvgTdwcf >= 60 ? 'text-success' : ($orgAvgTdwcf >= 40 ? 'text-warning' : 'text-error') }}">
            {{ $orgAvgTdwcf }}
        </div>
        <div class="stat-desc">/ 100 điểm</div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Trust Score TB</div>
        <div class="stat-value text-2xl {{ $orgAvgTrust >= 60 ? 'text-success' : ($orgAvgTrust >= 40 ? 'text-warning' : 'text-error') }}">
            {{ $orgAvgTrust }}
        </div>
        <div class="stat-desc">workforce trust score</div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Chứng nhận đã đạt</div>
        <div class="stat-value text-2xl text-accent">{{ $totalCerts }}</div>
        <div class="stat-desc">tổng toàn tổ chức</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">

    {{-- ── Domain averages ───────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-5">
            <h2 class="text-sm font-semibold text-base-content/60 uppercase tracking-wide mb-4">Điểm trung bình 6 domain</h2>
            @php
            $domainLabels = ['D1'=>'D1 Số hóa cơ bản','D2'=>'D2 Dữ liệu','D3'=>'D3 AI & Tự động','D4'=>'D4 Quy trình','D5'=>'D5 Đổi mới','D6'=>'D6 Hiệu suất'];
            @endphp
            <div class="space-y-3">
                @foreach($domainAvgs as $key => $avg)
                @php
                    $color = $avg >= 60 ? 'bg-success' : ($avg >= 40 ? 'bg-warning' : 'bg-error');
                    $textColor = $avg >= 60 ? 'text-success' : ($avg >= 40 ? 'text-warning' : 'text-error');
                @endphp
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-base-content/70">{{ $domainLabels[$key] }}</span>
                        <span class="font-semibold {{ $textColor }}">{{ $avg }}</span>
                    </div>
                    <div class="w-full bg-base-200 rounded-full h-2">
                        <div class="{{ $color }} h-2 rounded-full transition-all" style="width: {{ min(100, $avg) }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Maturity distribution ─────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-5">
            <h2 class="text-sm font-semibold text-base-content/60 uppercase tracking-wide mb-4">Phân bổ cấp độ trưởng thành</h2>
            @php
            $maturityOrder = ['DIGITAL_BEGINNER','DIGITAL_AWARE','DIGITAL_PRACTITIONER','DIGITAL_PROFESSIONAL','DIGITAL_LEADER'];
            $maturityVi = [
                'DIGITAL_BEGINNER'     => 'Khởi đầu số',
                'DIGITAL_AWARE'        => 'Nhận thức số',
                'DIGITAL_PRACTITIONER' => 'Thực hành số',
                'DIGITAL_PROFESSIONAL' => 'Chuyên nghiệp số',
                'DIGITAL_LEADER'       => 'Dẫn dắt số',
            ];
            $maturityColor = [
                'DIGITAL_BEGINNER'     => 'bg-base-300',
                'DIGITAL_AWARE'        => 'bg-info',
                'DIGITAL_PRACTITIONER' => 'bg-warning',
                'DIGITAL_PROFESSIONAL' => 'bg-success',
                'DIGITAL_LEADER'       => 'bg-accent',
            ];
            $total = $profiles->count() ?: 1;
            @endphp
            <div class="space-y-3">
                @foreach($maturityOrder as $level)
                @php
                    $cnt = $maturityDist[$level] ?? 0;
                    $pct = round($cnt / $total * 100, 1);
                @endphp
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-base-content/70">{{ $maturityVi[$level] }}</span>
                        <span class="text-base-content/50">{{ $cnt }} người ({{ $pct }}%)</span>
                    </div>
                    <div class="w-full bg-base-200 rounded-full h-2">
                        <div class="{{ $maturityColor[$level] }} h-2 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ── Quick nav cards ───────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <a href="{{ route('report.competency.heatmap') }}"
       class="card bg-base-100 border border-base-200 shadow-sm hover:border-primary/40 hover:shadow-md transition-all group">
        <div class="card-body p-5">
            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center mb-3 group-hover:bg-primary/20 transition-colors">
                <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <h3 class="font-semibold text-sm">Heatmap phòng ban</h3>
            <p class="text-xs text-base-content/50 mt-1">Bảng nhiệt năng lực theo phòng ban × domain</p>
        </div>
    </a>
    <a href="{{ route('report.competency.skill-gap') }}"
       class="card bg-base-100 border border-base-200 shadow-sm hover:border-warning/40 hover:shadow-md transition-all group">
        <div class="card-body p-5">
            <div class="w-10 h-10 rounded-full bg-warning/10 flex items-center justify-center mb-3 group-hover:bg-warning/20 transition-colors">
                <svg class="w-5 h-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
            </div>
            <h3 class="font-semibold text-sm">Phân tích Skill Gap</h3>
            <p class="text-xs text-base-content/50 mt-1">Khoảng cách kỹ năng so với cấp độ tiếp theo</p>
        </div>
    </a>
    <a href="{{ route('report.competency.trends') }}"
       class="card bg-base-100 border border-base-200 shadow-sm hover:border-success/40 hover:shadow-md transition-all group">
        <div class="card-body p-5">
            <div class="w-10 h-10 rounded-full bg-success/10 flex items-center justify-center mb-3 group-hover:bg-success/20 transition-colors">
                <svg class="w-5 h-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
            </div>
            <h3 class="font-semibold text-sm">Xu hướng 12 tháng</h3>
            <p class="text-xs text-base-content/50 mt-1">Tăng trưởng TDWCF score theo thời gian</p>
        </div>
    </a>
</div>

@endsection
