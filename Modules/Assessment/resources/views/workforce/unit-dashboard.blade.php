@extends('layouts.backend')
@section('title', 'Dashboard năng lực đơn vị')

@push('styles')
    @vite(['Modules/Assessment/resources/assets/sass/assessment.scss'], 'build/backend')
@endpush

@section('content')

@php
    $maturityLabels = [
        'DIGITAL_BEGINNER'     => 'Khởi đầu',
        'DIGITAL_AWARE'        => 'Nhận thức',
        'DIGITAL_PRACTITIONER' => 'Thực hành',
        'DIGITAL_PROFESSIONAL' => 'Chuyên nghiệp',
        'DIGITAL_LEADER'       => 'Dẫn dắt',
    ];
    $maturityColors = [
        'DIGITAL_BEGINNER'     => ['bar' => '#94a3b8', 'badge' => 'badge-ghost'],
        'DIGITAL_AWARE'        => ['bar' => '#38bdf8', 'badge' => 'badge-info'],
        'DIGITAL_PRACTITIONER' => ['bar' => '#fbbf24', 'badge' => 'badge-warning'],
        'DIGITAL_PROFESSIONAL' => ['bar' => '#34d399', 'badge' => 'badge-success'],
        'DIGITAL_LEADER'       => ['bar' => '#a78bfa', 'badge' => 'badge-accent'],
    ];
    $domainLabels = [
        'D1' => 'D1 — Số cơ bản',
        'D2' => 'D2 — Dữ liệu',
        'D3' => 'D3 — AI',
        'D4' => 'D4 — Quy trình',
        'D5' => 'D5 — Đổi mới',
        'D6' => 'D6 — Hiệu suất',
    ];
    $domainColors = ['#3b82f6','#6366f1','#8b5cf6','#a855f7','#d946ef','#ec4899'];
    $selectedDeptName = $deptId
        ? $departments->firstWhere('id', $deptId)?->name ?? 'Đơn vị đã chọn'
        : 'Toàn tổ chức';
@endphp

{{-- ── Page header ─────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Dashboard năng lực đơn vị</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            Tổng hợp năng lực số — <span class="font-medium text-base-content/70">{{ $selectedDeptName }}</span>
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.workforce.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
            Danh sách cá nhân
        </a>
        <a href="{{ route('backend.workforce.export.organization') }}"
           class="btn btn-outline btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
            </svg>
            Xuất Excel
        </a>
    </div>
</div>

{{-- ── Filter bar ───────────────────────────────────────────────────────────── --}}
<div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
    <div class="card-body py-3 px-4">
        <form method="GET" action="{{ route('backend.workforce.unit-dashboard') }}"
              class="flex flex-wrap gap-3 items-end">
            <div class="form-control flex-1 min-w-48">
                <label class="label py-0.5">
                    <span class="label-text text-xs font-medium">Lọc theo phòng ban</span>
                </label>
                <select name="dept_id" class="select select-bordered select-sm w-full">
                    <option value="">— Toàn tổ chức —</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" @selected($deptId == $dept->id)>
                        {{ $dept->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm self-end">Xem</button>
            @if($deptId)
            <a href="{{ route('backend.workforce.unit-dashboard') }}"
               class="btn btn-ghost btn-sm self-end">Xóa lọc</a>
            @endif
        </form>
    </div>
</div>

@if($stats['total'] === 0)
{{-- ── Empty state ──────────────────────────────────────────────────────────── --}}
<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body items-center text-center py-12">
        <svg class="w-12 h-12 text-base-content/20 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <p class="text-base-content/40 text-sm">Chưa có hồ sơ năng lực nào trong đơn vị này.</p>
    </div>
</div>
@else

{{-- ── 4 stat cards ─────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Nhân sự có hồ sơ</div>
        <div class="stat-value text-2xl text-primary">{{ number_format($stats['total']) }}</div>
        <div class="stat-desc text-xs">người</div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">TDWCF trung bình</div>
        <div class="stat-value text-2xl
            @if($stats['avg_tdwcf'] >= 60) text-success
            @elseif($stats['avg_tdwcf'] >= 40) text-warning
            @else text-error @endif">
            {{ number_format($stats['avg_tdwcf'], 1) }}
        </div>
        <div class="stat-desc text-xs">/ 100 điểm</div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Trust Score trung bình</div>
        <div class="stat-value text-2xl
            @if($stats['avg_trust'] >= 60) text-success
            @elseif($stats['avg_trust'] >= 40) text-warning
            @else text-error @endif">
            {{ number_format($stats['avg_trust'], 1) }}
        </div>
        <div class="stat-desc text-xs">/ 100 điểm</div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Có chứng nhận</div>
        <div class="stat-value text-2xl text-info">{{ $stats['certified_pct'] }}%</div>
        <div class="stat-desc text-xs">nhân sự đã có cert</div>
    </div>
</div>

{{-- ── Analytics row: maturity + domains ───────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">

    {{-- Maturity distribution --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h3 class="font-semibold text-sm mb-4 text-base-content/70">
                Phân bổ cấp độ trưởng thành
            </h3>
            @php $distTotal = $stats['maturity_dist']->sum() ?: 1; @endphp
            <div class="space-y-2.5">
                @foreach($maturityLevels as $lvl)
                @php
                    $cnt = $stats['maturity_dist'][$lvl] ?? 0;
                    $pct = round($cnt / $distTotal * 100);
                    $color = $maturityColors[$lvl]['bar'];
                @endphp
                <div class="flex items-center gap-2 text-xs">
                    <span class="w-28 shrink-0 text-base-content/60">{{ $maturityLabels[$lvl] }}</span>
                    <div class="flex-1 h-4 bg-base-200 rounded-full overflow-hidden">
                        <div class="h-4 rounded-full transition-all duration-500"
                             style="width: {{ $pct }}%; background: {{ $color }};"></div>
                    </div>
                    <span class="w-7 text-right font-semibold text-base-content/70">{{ $cnt }}</span>
                    <span class="w-8 text-right text-base-content/30">{{ $pct }}%</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Domain averages --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h3 class="font-semibold text-sm mb-4 text-base-content/70">
                Điểm trung bình 6 năng lực
            </h3>
            <div class="space-y-2.5">
                @foreach($domainLabels as $code => $label)
                @php
                    $avg  = $stats['domain_avgs'][$code] ?? 0;
                    $hex  = $domainColors[array_search($code, array_keys($domainLabels))];
                    $cls  = $avg >= 60 ? 'text-success' : ($avg >= 40 ? 'text-warning' : 'text-error');
                @endphp
                <div class="flex items-center gap-2 text-xs">
                    <span class="w-28 shrink-0 text-base-content/60">{{ $label }}</span>
                    <div class="flex-1 h-4 bg-base-200 rounded-full overflow-hidden">
                        <div class="h-4 rounded-full transition-all duration-500"
                             style="width: {{ min($avg, 100) }}%; background: {{ $hex }}; opacity: 0.75"></div>
                    </div>
                    <span class="w-10 text-right font-semibold {{ $cls }}">{{ number_format($avg, 1) }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

{{-- ── Top 10 leaderboard ────────────────────────────────────────────────────── --}}
@if($stats['top10']->count())
<div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
    <div class="card-body">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-sm text-base-content/70">Top 10 — Trust Score cao nhất</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr class="text-xs text-base-content/50">
                        <th>#</th>
                        <th>Họ tên</th>
                        <th>Phòng ban</th>
                        <th>Chức danh</th>
                        <th class="text-right">TDWCF</th>
                        <th class="text-right">Trust</th>
                        <th>Cấp độ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stats['top10'] as $i => $p)
                    @php
                        $lvl     = $p->tdwcf_maturity_level;
                        $bdgCls  = $maturityColors[$lvl]['badge'] ?? 'badge-ghost';
                        $tdwcfCls = ($p->tdwcf_score ?? 0) >= 60 ? 'text-success font-semibold'
                                  : (($p->tdwcf_score ?? 0) >= 40 ? 'text-warning' : 'text-error');
                    @endphp
                    <tr class="hover">
                        <td class="font-mono text-xs text-base-content/40">{{ $i + 1 }}</td>
                        <td class="font-medium text-sm">
                            {{ $p->employee?->full_name ?? '—' }}
                        </td>
                        <td class="text-xs text-base-content/60">
                            {{ $p->employee?->department?->name ?? '—' }}
                        </td>
                        <td class="text-xs text-base-content/60">
                            {{ $p->employee?->jobTitle?->name ?? '—' }}
                        </td>
                        <td class="text-right font-mono text-sm {{ $tdwcfCls }}">
                            {{ number_format($p->tdwcf_score ?? 0, 1) }}
                        </td>
                        <td class="text-right font-mono text-sm font-semibold text-primary">
                            {{ number_format($p->workforce_trust_score ?? 0, 1) }}
                        </td>
                        <td>
                            @if($lvl)
                            <span class="badge {{ $bdgCls }} badge-xs">{{ $maturityLabels[$lvl] ?? $lvl }}</span>
                            @else
                            <span class="text-xs text-base-content/30">—</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('backend.workforce.show', $p) }}"
                               class="btn btn-ghost btn-xs">Xem →</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- ── Cần hỗ trợ (TDWCF < 40) ─────────────────────────────────────────────── --}}
@if($stats['needs_attention']->count())
<div class="card bg-base-100 shadow-sm border border-error/20 mb-5">
    <div class="card-body">
        <div class="flex items-center gap-2 mb-4">
            <svg class="w-4 h-4 text-error shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <h3 class="font-semibold text-sm text-error">
                Cần hỗ trợ — TDWCF dưới 40 điểm
                <span class="badge badge-error badge-sm ml-1">{{ $stats['needs_attention']->count() }}</span>
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr class="text-xs text-base-content/50">
                        <th>Họ tên</th>
                        <th>Phòng ban</th>
                        <th class="text-right">TDWCF</th>
                        <th>Cấp độ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stats['needs_attention'] as $p)
                    <tr class="hover">
                        <td class="font-medium text-sm">{{ $p->employee?->full_name ?? '—' }}</td>
                        <td class="text-xs text-base-content/60">{{ $p->employee?->department?->name ?? '—' }}</td>
                        <td class="text-right font-mono text-sm text-error font-semibold">
                            {{ number_format($p->tdwcf_score ?? 0, 1) }}
                        </td>
                        <td>
                            @php $lvl = $p->tdwcf_maturity_level; @endphp
                            @if($lvl)
                            <span class="badge {{ $maturityColors[$lvl]['badge'] ?? 'badge-ghost' }} badge-xs">
                                {{ $maturityLabels[$lvl] ?? $lvl }}
                            </span>
                            @else
                            <span class="text-xs text-base-content/30">Chưa đánh giá</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('backend.workforce.show', $p) }}"
                               class="btn btn-ghost btn-xs">Xem →</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@endif {{-- end if total > 0 --}}

@endsection
