@extends('layouts.backend')
@section('title', 'Workforce — Tổng quan năng lực')

@push('styles')
    @vite(['Modules/Assessment/resources/assets/sass/assessment.scss'], 'build/backend')
@endpush

@section('content')
<div x-data="workforceAdminPage({{ Js::from([
    'apiUrl'        => route('backend.workforce.api'),
    'maturityLevels'=> $maturityLevels,
]) }})">

{{-- ── Page header ─────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Workforce — Năng lực số</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Tổng quan Digital Twin của toàn tổ chức</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.workforce.pdf.organization') }}"
           class="btn btn-outline btn-sm gap-1.5"
           title="Xuất báo cáo PDF toàn tổ chức">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            Xuất PDF
        </a>
        <a href="{{ route('backend.workforce.export.organization') }}"
           class="btn btn-outline btn-sm gap-1.5"
           title="Xuất báo cáo năng lực số toàn tổ chức (Excel 4 sheets)">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
            </svg>
            Xuất Excel
        </a>
        <a href="{{ route('backend.workforce.me') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Hồ sơ của tôi
        </a>
    </div>
</div>

@php
    $labels = ['DIGITAL_BEGINNER'=>'Khởi đầu','DIGITAL_AWARE'=>'Nhận thức','DIGITAL_PRACTITIONER'=>'Thực hành','DIGITAL_PROFESSIONAL'=>'Chuyên nghiệp','DIGITAL_LEADER'=>'Dẫn dắt'];
    $colors = ['DIGITAL_BEGINNER'=>'text-base-content/50','DIGITAL_AWARE'=>'text-info','DIGITAL_PRACTITIONER'=>'text-warning','DIGITAL_PROFESSIONAL'=>'text-success','DIGITAL_LEADER'=>'text-accent'];
@endphp

{{-- ── Stat cards ───────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3 mb-5">
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-3 shadow-sm sm:col-span-2 md:col-span-1">
        <div class="stat-title text-xs">Tổng hồ sơ</div>
        <div class="stat-value text-xl">{{ number_format($total) }}</div>
    </div>
    @foreach($maturityLevels as $lvl)
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-3 shadow-sm">
        <div class="stat-title text-xs">{{ $labels[$lvl] ?? $lvl }}</div>
        <div class="stat-value text-xl {{ $colors[$lvl] ?? '' }}">{{ $byLevel[$lvl] ?? 0 }}</div>
    </div>
    @endforeach
</div>

{{-- ── Analytics row: distribution + domain averages ──────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">

    {{-- Maturity distribution chart --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h3 class="font-semibold text-sm mb-3 text-base-content/70">Phân bổ cấp độ trưởng thành</h3>
            @php
                $distColors = ['DIGITAL_BEGINNER'=>'#94a3b8','DIGITAL_AWARE'=>'#38bdf8','DIGITAL_PRACTITIONER'=>'#fbbf24','DIGITAL_PROFESSIONAL'=>'#34d399','DIGITAL_LEADER'=>'#a78bfa'];
                $distLabels = ['DIGITAL_BEGINNER'=>'Khởi đầu','DIGITAL_AWARE'=>'Nhận thức','DIGITAL_PRACTITIONER'=>'Thực hành','DIGITAL_PROFESSIONAL'=>'Chuyên nghiệp','DIGITAL_LEADER'=>'Dẫn dắt'];
                $distTotal  = array_sum($byLevel->toArray()) ?: 1;
            @endphp
            <div class="space-y-2">
                @foreach($maturityLevels as $lvl)
                @php $cnt = $byLevel[$lvl] ?? 0; $pct = round($cnt / $distTotal * 100); @endphp
                <div class="flex items-center gap-2 text-xs">
                    <span class="w-28 shrink-0 text-base-content/60">{{ $distLabels[$lvl] }}</span>
                    <div class="flex-1 h-4 bg-base-200 rounded-full overflow-hidden">
                        <div class="h-4 rounded-full transition-all duration-500"
                             style="width: {{ $pct }}%; background: {{ $distColors[$lvl] }};"></div>
                    </div>
                    <span class="w-8 text-right font-semibold text-base-content/70">{{ $cnt }}</span>
                    <span class="w-8 text-right text-base-content/30">{{ $pct }}%</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Average domain scores --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h3 class="font-semibold text-sm mb-3 text-base-content/70">Điểm trung bình 6 năng lực (toàn tổ chức)</h3>
            @php
                $avgDomains = [
                    ['D1 — Số cơ bản', $domainAvgs?->d1 ?? 0, '#3b82f6'],
                    ['D2 — Dữ liệu',   $domainAvgs?->d2 ?? 0, '#6366f1'],
                    ['D3 — AI',        $domainAvgs?->d3 ?? 0, '#8b5cf6'],
                    ['D4 — Quy trình', $domainAvgs?->d4 ?? 0, '#a855f7'],
                    ['D5 — Đổi mới',   $domainAvgs?->d5 ?? 0, '#d946ef'],
                    ['D6 — Hiệu suất', $domainAvgs?->d6 ?? 0, '#ec4899'],
                ];
            @endphp
            @if(array_sum(array_column($avgDomains, 1)) > 0)
            <div class="space-y-2">
                @foreach($avgDomains as [$dLabel, $dAvg, $dHex])
                <div class="flex items-center gap-2 text-xs">
                    <span class="w-24 shrink-0 text-base-content/60">{{ $dLabel }}</span>
                    <div class="flex-1 h-4 bg-base-200 rounded-full overflow-hidden">
                        <div class="h-4 rounded-full transition-all duration-500"
                             style="width: {{ min($dAvg, 100) }}%; background: {{ $dHex }}; opacity: 0.75"></div>
                    </div>
                    <span class="w-10 text-right font-semibold text-base-content/70">{{ number_format($dAvg, 1) }}</span>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-xs text-base-content/30 py-4 text-center">Chưa có dữ liệu đánh giá năng lực.</p>
            @endif
        </div>
    </div>

</div>

{{-- ── Filter bar ───────────────────────────────────────────────────────────── --}}
<div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
    <div class="card-body py-3 px-4">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="form-control flex-1 min-w-52">
                <label class="label py-0.5">
                    <span class="label-text text-xs font-medium">Tìm kiếm nhân viên</span>
                </label>
                <input type="text" x-model.debounce.400ms="filters.search"
                       class="input input-bordered input-sm w-full"
                       placeholder="VD: Nguyễn Văn A">
            </div>
            <div class="form-control w-48">
                <label class="label py-0.5">
                    <span class="label-text text-xs font-medium">Cấp độ</span>
                </label>
                <select x-model="filters.maturity_level" class="select select-bordered select-sm w-full">
                    <option value="">— Tất cả cấp độ —</option>
                    @foreach($maturityLevels as $lvl)
                    <option value="{{ $lvl }}">{{ $labels[$lvl] ?? $lvl }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" @click="resetFilters()" class="btn btn-ghost btn-sm self-end">Xóa lọc</button>
        </div>
    </div>
</div>

{{-- ── Table ────────────────────────────────────────────────────────────────── --}}
<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body p-0">
        <div id="workforce-table"></div>
    </div>
</div>

</div>
@endsection

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'Modules/Assessment/resources/assets/js/assessment.js',
    ], 'build/backend')
@endpush
