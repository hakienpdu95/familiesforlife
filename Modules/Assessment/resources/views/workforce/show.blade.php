@extends('layouts.backend')
@section('title', 'Hồ sơ Workforce — ' . ($workforceProfile->employee?->full_name ?? 'N/A'))

@push('styles')
    @vite(['Modules/Assessment/resources/assets/sass/assessment.scss'], 'build/backend')
@endpush

@section('content')

@php
    $emp = $workforceProfile->employee;
    $prof = $workforceProfile;
    $maturityMap = [
        'DIGITAL_BEGINNER'     => ['Khởi đầu số',    'badge-ghost',   '#94a3b8'],
        'DIGITAL_AWARE'        => ['Nhận thức số',   'badge-info',    '#38bdf8'],
        'DIGITAL_PRACTITIONER' => ['Thực hành số',   'badge-warning', '#fbbf24'],
        'DIGITAL_PROFESSIONAL' => ['Chuyên nghiệp',  'badge-success', '#34d399'],
        'DIGITAL_LEADER'       => ['Dẫn dắt số',     'badge-accent',  '#a78bfa'],
    ];
    $ml  = $maturityMap[$prof->tdwcf_maturity_level] ?? ['—', 'badge-ghost', '#94a3b8'];
    $domains = [
        'D1' => ['D1 — Năng lực số cơ bản',  $prof->score_d1_digital_literacy, '#3b82f6'],
        'D2' => ['D2 — Năng lực dữ liệu',    $prof->score_d2_data_literacy,    '#6366f1'],
        'D3' => ['D3 — Năng lực AI',          $prof->score_d3_ai_literacy,      '#8b5cf6'],
        'D4' => ['D4 — Quy trình & TĐH',     $prof->score_d4_workflow,         '#a855f7'],
        'D5' => ['D5 — Đổi mới & Sáng kiến', $prof->score_d5_innovation,       '#d946ef'],
        'D6' => ['D6 — Hiệu suất & KPI',     $prof->score_d6_performance,      '#ec4899'],
    ];
@endphp

{{-- ── Page header ─────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-sm font-bold text-primary shrink-0">
            {{ strtoupper(substr($emp?->full_name ?? 'U', 0, 1)) }}
        </div>
        <div>
            <h1 class="text-xl font-bold text-base-content">{{ $emp?->full_name ?? '—' }}</h1>
            <p class="text-sm text-base-content/50">{{ $emp?->snap_job_title ?? '' }}{{ $emp?->snap_dept_name ? ' · '.$emp->snap_dept_name : '' }}</p>
        </div>
        <span class="badge {{ $ml[1] }} badge-sm">{{ $ml[0] }}</span>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.workforce.pdf.profile', $workforceProfile) }}"
           class="btn btn-outline btn-sm gap-1.5"
           title="Xuất hồ sơ PDF cá nhân">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            Xuất PDF
        </a>
        <a href="{{ route('backend.workforce.export.profile', $workforceProfile) }}"
           class="btn btn-outline btn-sm gap-1.5"
           title="Xuất hồ sơ năng lực cá nhân (Excel)">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
            </svg>
            Xuất Excel
        </a>
        <a href="{{ route('backend.workforce.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Workforce Admin
        </a>
    </div>
</div>

{{-- ── Score cards ─────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-5">
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">TDWCF Score</div>
        <div class="stat-value text-2xl text-primary">{{ $prof->tdwcf_score ? number_format($prof->tdwcf_score, 1) : '—' }}</div>
        <div class="stat-desc text-xs">/ 100</div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Trust Score</div>
        <div class="stat-value text-2xl text-success">{{ $prof->workforce_trust_score ? number_format($prof->workforce_trust_score, 1) : '—' }}</div>
        <div class="stat-desc text-xs">/ 100</div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">AI Readiness</div>
        <div class="stat-value text-2xl text-accent">{{ $prof->ai_readiness_score ? number_format($prof->ai_readiness_score, 1) : '—' }}</div>
        <div class="stat-desc text-xs">/ 100</div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Sandbox sessions</div>
        <div class="stat-value text-2xl">{{ $prof->sandbox_sessions_total ?? 0 }}</div>
        <div class="stat-desc text-xs">{{ round(($prof->sandbox_hours_total ?? 0), 1) }}h</div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">CGI</div>
        <div class="stat-value text-2xl {{ $cgi !== null ? ($cgi >= 0 ? 'text-success' : 'text-error') : 'text-base-content/40' }}">
            @if($cgi !== null)
                {{ $cgi >= 0 ? '+' : '' }}{{ number_format($cgi, 1) }}<span class="text-sm font-normal">%</span>
            @else —
            @endif
        </div>
        <div class="stat-desc text-xs">Tăng trưởng NL</div>
    </div>
</div>

@php
    // Radar SVG for show page
    $shRd = 72; $shCx = 100; $shCy = 100; $shN = 6;
    $shAnchors = ['middle', 'start', 'start', 'middle', 'end', 'end'];
    $shScores  = [
        $prof->score_d1_digital_literacy ?? 0,
        $prof->score_d2_data_literacy    ?? 0,
        $prof->score_d3_ai_literacy      ?? 0,
        $prof->score_d4_workflow         ?? 0,
        $prof->score_d5_innovation       ?? 0,
        $prof->score_d6_performance      ?? 0,
    ];
    $shLabels  = ['D1','D2','D3','D4','D5','D6'];
    $shDataPts = []; $shAxes = []; $shRings = [];
    for ($i = 0; $i < $shN; $i++) {
        $ang = (M_PI * 2 * $i / $shN) - M_PI / 2;
        $s   = min($shScores[$i], 100) / 100;
        $shDataPts[] = round($shCx + $shRd * $s * cos($ang), 2) . ',' . round($shCy + $shRd * $s * sin($ang), 2);
        $shAxes[] = [
            'ex' => round($shCx + $shRd * cos($ang), 2), 'ey' => round($shCy + $shRd * sin($ang), 2),
            'lx' => round($shCx + ($shRd + 16) * cos($ang), 2), 'ly' => round($shCy + ($shRd + 16) * sin($ang), 2),
            'anchor' => $shAnchors[$i], 'label' => $shLabels[$i], 'score' => $shScores[$i],
        ];
    }
    foreach ([100, 75, 50, 25] as $pct) {
        $gpts = [];
        for ($i = 0; $i < $shN; $i++) {
            $ang = (M_PI * 2 * $i / $shN) - M_PI / 2;
            $gpts[] = round($shCx + $shRd * $pct / 100 * cos($ang), 2) . ',' . round($shCy + $shRd * $pct / 100 * sin($ang), 2);
        }
        $shRings[] = implode(' ', $gpts);
    }
    $shDataPolygon = implode(' ', $shDataPts);
@endphp

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    {{-- Left: 6-domain breakdown with radar chart --}}
    <div class="xl:col-span-2 card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <h2 class="card-title text-base mb-4">6 Miền TDWCF</h2>

            <div class="flex flex-col sm:flex-row gap-5 items-start">
                {{-- Radar SVG --}}
                <div class="shrink-0 mx-auto sm:mx-0">
                    <svg viewBox="0 0 200 200" width="180" height="180" class="overflow-visible">
                        @foreach($shRings as $ring)
                        <polygon points="{{ $ring }}" fill="none" stroke="currentColor" stroke-opacity="0.1" stroke-width="1"/>
                        @endforeach
                        @foreach($shAxes as $ax)
                        <line x1="{{ $shCx }}" y1="{{ $shCy }}" x2="{{ $ax['ex'] }}" y2="{{ $ax['ey'] }}" stroke="currentColor" stroke-opacity="0.15" stroke-width="1"/>
                        @endforeach
                        <polygon points="{{ $shDataPolygon }}" fill="#8b5cf6" fill-opacity="0.25" stroke="#8b5cf6" stroke-width="2" stroke-linejoin="round"/>
                        @foreach($shDataPts as $dp)
                        @php [$dpx, $dpy] = explode(',', $dp); @endphp
                        <circle cx="{{ $dpx }}" cy="{{ $dpy }}" r="3" fill="#8b5cf6"/>
                        @endforeach
                        @foreach($shAxes as $ax)
                        <text x="{{ $ax['lx'] }}" y="{{ $ax['ly'] }}" text-anchor="{{ $ax['anchor'] }}" dominant-baseline="middle" font-size="9" fill="currentColor" fill-opacity="0.6">{{ $ax['label'] }}</text>
                        <text x="{{ $ax['lx'] }}" y="{{ $ax['ly'] + 9 }}" text-anchor="{{ $ax['anchor'] }}" dominant-baseline="middle" font-size="8" fill="currentColor" fill-opacity="0.45">{{ $ax['score'] > 0 ? number_format($ax['score'], 0) : '—' }}</text>
                        @endforeach
                    </svg>
                </div>

                {{-- Bars --}}
                <div class="flex-1 space-y-3 w-full">
                    @foreach($domains as $key => [$label, $score, $color])
                    <div>
                        <div class="flex justify-between text-xs mb-1.5">
                            <span class="font-medium">{{ $label }}</span>
                            <span class="font-semibold">{{ $score ? number_format($score, 1) : '—' }}</span>
                        </div>
                        <div class="h-2 bg-base-200 rounded-full overflow-hidden">
                            <div class="h-2 rounded-full transition-all" style="width: {{ min($score ?? 0, 100) }}%; background:{{ $color }}"></div>
                        </div>
                    </div>
                    @endforeach

                    {{-- Skill Gap summary --}}
                    @php
                        $sgTarget = $skillGapBenchmarks['target'];
                        $sgNext   = $skillGapBenchmarks['next_level'];
                        $sgNames  = ['DIGITAL_AWARE'=>'Nhận thức số','DIGITAL_PRACTITIONER'=>'Thực hành số','DIGITAL_PROFESSIONAL'=>'Chuyên nghiệp','DIGITAL_LEADER'=>'Dẫn dắt số'];
                        $sgMet    = collect($shScores)->filter(fn($v) => $v >= $sgTarget)->count();
                    @endphp
                    @if($sgNext)
                    <div class="mt-3 p-2 bg-base-200/60 rounded-lg text-xs text-base-content/50">
                        <span class="font-medium text-base-content/70">Skill Gap → {{ $sgNames[$sgNext] ?? $sgNext }}</span>:
                        {{ $sgMet }}/6 năng lực đạt ngưỡng {{ $sgTarget }}.
                        @if($sgMet < 6)
                        <span class="text-warning">{{ 6 - $sgMet }} cần cải thiện.</span>
                        @else
                        <span class="text-success">Đủ điều kiện.</span>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Right: Profile metadata + Trust breakdown + History --}}
    <div class="space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body py-4 px-5">
                <h3 class="font-semibold text-sm mb-3">Thông tin hồ sơ</h3>
                <dl class="space-y-2 text-xs">
                    <div class="flex justify-between">
                        <dt class="text-base-content/40">Cấp độ hiện tại</dt>
                        <dd><span class="badge {{ $ml[1] }} badge-xs">{{ $ml[0] }}</span></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/40">Đánh giá cuối</dt>
                        <dd class="font-medium">{{ $prof->tdwcf_assessed_at?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/40">Điểm sandbox TB</dt>
                        <dd class="font-medium">{{ $prof->sandbox_score_avg ? number_format($prof->sandbox_score_avg, 1) : '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/40">AI Impact</dt>
                        <dd class="font-medium">{{ $prof->impact_score ? number_format($prof->impact_score, 1) : '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/40">KPI TB</dt>
                        <dd class="font-medium">{{ $prof->kpi_achievement_avg ? number_format($prof->kpi_achievement_avg, 1).'%' : '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/40">Cập nhật</dt>
                        <dd class="font-medium">{{ $prof->updated_at?->diffForHumans() ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Trust Score breakdown --}}
        @if(count($trustBreakdown))
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body py-4 px-5">
                <h3 class="font-semibold text-sm mb-3">
                    Trust Score = {{ $prof->workforce_trust_score ? number_format($prof->workforce_trust_score, 1) : '—' }}
                </h3>
                <div class="space-y-2">
                    @foreach($trustBreakdown as $item)
                    <div class="flex items-center gap-2 text-xs">
                        <span class="text-base-content/50 w-20 shrink-0">{{ $item['label'] }}</span>
                        <div class="flex-1 h-1.5 bg-base-200 rounded-full overflow-hidden">
                            <div class="h-1.5 bg-secondary/60 rounded-full"
                                 style="width: {{ $item['raw'] > 0 ? min($item['raw'], 100) : 0 }}%"></div>
                        </div>
                        <span class="font-mono text-base-content/60 w-8 text-right">×{{ $item['weight'] }}%</span>
                        <span class="font-semibold w-8 text-right text-secondary">{{ $item['contribution'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        @if($history->count())
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body py-4 px-5">
                <h3 class="font-semibold text-sm mb-3">Lịch sử điểm TDWCF</h3>
                <div class="space-y-2">
                    @foreach($history->take(8) as $h)
                    @php $hScore = $h->tdwcf_score_after ?? $h->tdwcf_score_before; @endphp
                    @if($hScore)
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-base-content/40">{{ $h->recorded_at?->format('d/m/Y') ?? $h->created_at?->format('d/m/Y') }}</span>
                        <span class="font-mono text-xs text-base-content/40 mx-2">{{ $h->event_type }}</span>
                        <span class="font-semibold {{ $hScore >= 70 ? 'text-success' : ($hScore >= 40 ? 'text-warning' : 'text-error') }}">
                            {{ number_format($hScore, 1) }}
                        </span>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

</div>

{{-- ── Skill Gap theo Vị trí việc làm (admin read-only) ───────────────────── --}}
@if(!empty($jobTitleRequirements))
<div class="card bg-base-100 shadow-sm border border-base-200 mt-5">
    <div class="card-body p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="card-title text-base">
                <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Skill Gap theo Vị trí — {{ $emp?->jobTitle?->name ?? 'N/A' }}
            </h2>
            <span class="text-xs text-base-content/40">Yêu cầu vị trí việc làm</span>
        </div>
        @php
        $showDomainReqMap = [
            'D1' => ['score_d1_digital_literacy', 'D1 — Năng lực số cơ bản'],
            'D2' => ['score_d2_data_literacy',    'D2 — Năng lực dữ liệu'],
            'D3' => ['score_d3_ai_literacy',       'D3 — Năng lực AI'],
            'D4' => ['score_d4_workflow',          'D4 — Quy trình & TĐH'],
            'D5' => ['score_d5_innovation',        'D5 — Đổi mới'],
            'D6' => ['score_d6_performance',       'D6 — Hiệu suất'],
        ];
        $showTotalGap = 0;
        $showGapCount = 0;
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($showDomainReqMap as $dCode => [$field, $label])
            @php
                $req = $jobTitleRequirements[$dCode] ?? null;
                if (!$req) continue;
                $cur = (float)($prof->{$field} ?? 0);
                $gap = max(0, $req - $cur);
                $met = $cur >= $req;
                if ($gap > 0) { $showTotalGap += $gap; $showGapCount++; }
            @endphp
            <div class="border {{ $met ? 'border-success/30' : ($gap > 15 ? 'border-error/30' : 'border-warning/30') }} rounded-xl p-3">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-xs font-medium text-base-content/70">{{ $label }}</span>
                    @if($met)
                    <span class="badge badge-success badge-xs">✓ Đạt</span>
                    @elseif($gap > 15)
                    <span class="badge badge-error badge-xs">-{{ number_format($gap, 1) }}</span>
                    @else
                    <span class="badge badge-warning badge-xs">-{{ number_format($gap, 1) }}</span>
                    @endif
                </div>
                <div class="h-2 bg-base-200 rounded-full relative overflow-visible mb-1">
                    <div class="h-2 rounded-full {{ $met ? 'bg-success/70' : 'bg-warning/70' }}"
                         style="width: {{ min($cur, 100) }}%"></div>
                    @if($req <= 100)
                    <div class="absolute top-0 h-2 w-0.5 bg-red-400 rounded"
                         style="left: {{ min($req, 100) }}%;"></div>
                    @endif
                </div>
                <div class="flex justify-between text-xs text-base-content/40">
                    <span>{{ number_format($cur, 0) }}</span>
                    <span>yêu cầu: {{ number_format($req, 0) }}</span>
                </div>
            </div>
            @endforeach
        </div>
        @if($showGapCount > 0)
        <p class="text-xs text-base-content/30 mt-3">{{ $showGapCount }}/6 năng lực chưa đạt yêu cầu vị trí. Tổng khoảng cách: {{ number_format($showTotalGap, 1) }} điểm.</p>
        @else
        <p class="text-xs text-success mt-3">Tất cả năng lực đã đáp ứng yêu cầu vị trí việc làm!</p>
        @endif
    </div>
</div>
@endif

{{-- ── AI Gợi ý phát triển (admin read-only) ──────────────────────────────── --}}
@if(!empty($recommendation) && !empty($recommendation->recommendations))
<div class="card bg-base-100 shadow-sm border border-base-200 mt-5">
    <div class="card-body p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="card-title text-base">
                <svg class="w-4 h-4 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                AI Gợi ý phát triển
            </h2>
            @if($recommendation->generated_at)
            <span class="text-xs text-base-content/40">Tạo: {{ $recommendation->generated_at->diffForHumans() }}</span>
            @endif
        </div>
        <div class="space-y-3">
            @foreach($recommendation->recommendations as $rec)
            @php
                $recType   = $rec['resource_type'] ?? '';
                $recPrio   = $rec['priority'] ?? 5;
                $prioBadge = match((int)$recPrio) {
                    1       => 'badge-error',
                    2       => 'badge-warning',
                    3       => 'badge-info',
                    default => 'badge-ghost',
                };
                $typeIconPath = match($recType) {
                    'course'        => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
                    'sandbox'       => 'M13 10V3L4 14h7v7l9-11h-7z',
                    'certification' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                    'practice'      => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
                    default         => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                };
                $typeBg = match($recType) {
                    'course'        => 'bg-info/10 text-info',
                    'sandbox'       => 'bg-accent/10 text-accent',
                    'certification' => 'bg-success/10 text-success',
                    'practice'      => 'bg-warning/10 text-warning',
                    default         => 'bg-base-200 text-base-content/40',
                };
            @endphp
            <div class="flex gap-3 border border-base-200 rounded-xl p-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0 {{ $typeBg }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $typeIconPath }}"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-2 mb-1">
                        <p class="text-sm font-medium text-base-content/80 leading-snug">{{ $rec['action'] ?? '' }}</p>
                        <span class="badge {{ $prioBadge }} badge-xs shrink-0">P{{ $recPrio }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 flex-wrap">
                        @if(!empty($rec['domain']))
                        <span class="badge badge-ghost badge-xs font-mono">{{ $rec['domain'] }}</span>
                        @endif
                        @if(!empty($rec['resource_name']))
                        <span class="text-xs text-base-content/40">{{ $rec['resource_name'] }}</span>
                        @endif
                        @if(!empty($rec['estimated_weeks']))
                        <span class="text-xs text-base-content/30">~{{ $rec['estimated_weeks'] }} tuần</span>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
    @vite(['Modules/Assessment/resources/assets/js/assessment.js'], 'build/backend')
@endpush
