@extends('layouts.backend')
@section('title', 'Hồ sơ năng lực số của tôi')

@push('styles')
    @vite(['Modules/Assessment/resources/assets/sass/assessment.scss'], 'build/backend')
@endpush

@section('content')

@if(session('success'))
<div class="alert alert-success mb-4 py-2 px-4 text-sm">{{ session('success') }}</div>
@endif

{{-- ── Page header ──────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Hồ sơ năng lực số</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Digital Twin — toàn cảnh năng lực AI của bạn trong tổ chức</p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.ai-impact.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            AI Impact
        </a>
        <a href="{{ route('backend.sandbox.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            AI Sandbox
        </a>
        <a href="{{ route('backend.certifications.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
            Chứng nhận
        </a>
        <a href="{{ route('backend.career-pathway.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
            Lộ trình
        </a>
        <a href="{{ route('passport.index') }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            Career Journal
        </a>
    </div>
</div>

@if(!$profile)
{{-- ── Chưa có profile ───────────────────────────────────────────────────────── --}}
<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body items-center text-center py-16">
        <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold mb-2">Chưa có hồ sơ năng lực</h2>
        <p class="text-base-content/50 text-sm max-w-md mb-6">Hoàn thành một khảo sát TDWCF để hệ thống tự động tạo Digital Twin cho bạn.</p>
        <a href="{{ route('backend.surveys.index') }}" class="btn btn-primary btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            Làm khảo sát ngay
        </a>
    </div>
</div>
@else

{{-- ── Profile completeness bar ─────────────────────────────────────────────── --}}
<div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
    <div class="card-body py-3 px-5">
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <div class="flex justify-between text-xs mb-1">
                    <span class="font-medium text-base-content/60">Độ hoàn thiện hồ sơ</span>
                    <span class="font-bold {{ $completeness >= 80 ? 'text-success' : ($completeness >= 40 ? 'text-warning' : 'text-base-content/50') }}">{{ $completeness }}%</span>
                </div>
                <div class="h-2 bg-base-200 rounded-full overflow-hidden">
                    <div class="h-2 rounded-full transition-all {{ $completeness >= 80 ? 'bg-success' : ($completeness >= 40 ? 'bg-warning' : 'bg-base-content/30') }}"
                         style="width: {{ $completeness }}%"></div>
                </div>
            </div>
            <div class="flex gap-2 shrink-0 text-xs text-base-content/40">
                @foreach([
                    [$profile->tdwcf_score,            'TDWCF'],
                    [$profile->certifications_count,   'Cert'],
                    [$profile->sandbox_sessions_total, 'Sandbox'],
                    [$profile->impact_score,           'Impact'],
                    [$profile->kpi_achievement_avg,    'KPI'],
                ] as [$val, $lbl])
                <div class="flex flex-col items-center gap-0.5">
                    <div class="w-5 h-5 rounded-full {{ $val ? 'bg-success/20 text-success' : 'bg-base-200 text-base-content/20' }} flex items-center justify-center">
                        @if($val)
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                        @else
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        @endif
                    </div>
                    <span>{{ $lbl }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- ── KPI cards (7 slots: +CGI) ──────────────────────────────────────────── --}}
@php
    $maturityLabels = [
        'DIGITAL_BEGINNER'     => ['Khởi đầu số',   'badge-ghost'],
        'DIGITAL_AWARE'        => ['Nhận thức số',  'badge-info'],
        'DIGITAL_PRACTITIONER' => ['Thực hành số',  'badge-warning'],
        'DIGITAL_PROFESSIONAL' => ['Chuyên nghiệp', 'badge-success'],
        'DIGITAL_LEADER'       => ['Dẫn dắt số',    'badge-accent'],
    ];
    $ml = $maturityLabels[$profile->tdwcf_maturity_level] ?? ['—', 'badge-ghost'];
@endphp

<div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 mb-5">
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Điểm TDWCF</div>
        <div class="stat-value text-2xl text-primary">{{ $profile->tdwcf_score ? number_format($profile->tdwcf_score, 1) : '—' }}</div>
        <div class="stat-desc text-xs">Cấp: <span class="badge {{ $ml[1] }} badge-xs">{{ $ml[0] }}</span></div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Trust Score</div>
        <div class="stat-value text-2xl text-secondary">{{ $profile->workforce_trust_score ? number_format($profile->workforce_trust_score, 1) : '—' }}</div>
        <div class="stat-desc text-xs">/ 100</div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">AI Readiness</div>
        <div class="stat-value text-2xl text-accent">{{ $profile->ai_readiness_score ? number_format($profile->ai_readiness_score, 1) : '—' }}</div>
        <div class="stat-desc text-xs">(D3+D4) / 2</div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">AI Impact (AII)</div>
        <div class="stat-value text-2xl {{ ($profile->impact_score ?? 0) >= 50 ? 'text-success' : 'text-base-content/60' }}">
            {{ $profile->impact_score ? number_format($profile->impact_score, 1) : '—' }}
        </div>
        <div class="stat-desc text-xs"><a href="{{ route('backend.ai-impact.index') }}" class="link link-hover text-xs">Xem chi tiết →</a></div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">KPI TB</div>
        <div class="stat-value text-2xl {{ ($profile->kpi_achievement_avg ?? 0) >= 80 ? 'text-success' : 'text-base-content/60' }}">
            {{ $profile->kpi_achievement_avg ? number_format($profile->kpi_achievement_avg, 1).'%' : '—' }}
        </div>
        <div class="stat-desc text-xs">Performance review</div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Sandbox</div>
        <div class="stat-value text-2xl">{{ $profile->sandbox_hours_total ?? 0 }}<span class="text-base font-normal">h</span></div>
        <div class="stat-desc text-xs">{{ $profile->sandbox_sessions_total ?? 0 }} phiên</div>
    </div>
    {{-- CGI — Competency Growth Index --}}
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">CGI</div>
        <div class="stat-value text-2xl {{ $cgi !== null ? ($cgi >= 0 ? 'text-success' : 'text-error') : 'text-base-content/40' }}">
            @if($cgi !== null)
                {{ $cgi >= 0 ? '+' : '' }}{{ number_format($cgi, 1) }}<span class="text-sm font-normal">%</span>
            @else —
            @endif
        </div>
        <div class="stat-desc text-xs" title="Competency Growth Index">Tăng trưởng NL</div>
    </div>
</div>

{{-- ── Row 2: 6 domains (radar + bars) + Trust/Maturity ────────────────────── --}}
@php
    $domains = [
        ['field'=>'score_d1_digital_literacy','label'=>'D1 — Năng lực số cơ bản',      'short'=>'D1','desc'=>'Sử dụng công cụ kỹ thuật số, bảo mật, thiết bị',   'color'=>'bg-blue-500',   'hex'=>'#3b82f6'],
        ['field'=>'score_d2_data_literacy',   'label'=>'D2 — Năng lực dữ liệu',        'short'=>'D2','desc'=>'Đọc, phân tích và trình bày dữ liệu',              'color'=>'bg-indigo-500', 'hex'=>'#6366f1'],
        ['field'=>'score_d3_ai_literacy',     'label'=>'D3 — Năng lực AI',             'short'=>'D3','desc'=>'Hiểu & ứng dụng AI vào công việc',                 'color'=>'bg-violet-500', 'hex'=>'#8b5cf6'],
        ['field'=>'score_d4_workflow',        'label'=>'D4 — Quy trình & Tự động hoá','short'=>'D4','desc'=>'Tự động hoá task, tối ưu quy trình',               'color'=>'bg-purple-500', 'hex'=>'#a855f7'],
        ['field'=>'score_d5_innovation',      'label'=>'D5 — Đổi mới & Sáng kiến',    'short'=>'D5','desc'=>'Tư duy sáng tạo, đề xuất cải tiến',               'color'=>'bg-fuchsia-500','hex'=>'#d946ef'],
        ['field'=>'score_d6_performance',     'label'=>'D6 — Hiệu suất & KPI',         'short'=>'D6','desc'=>'Đạt mục tiêu, đo lường kết quả',                  'color'=>'bg-pink-500',   'hex'=>'#ec4899'],
    ];
    // Radar SVG (200×200 canvas)
    $rd = 72; $cx = 100; $cy = 100; $n = 6;
    $anchors = ['middle', 'start', 'start', 'middle', 'end', 'end'];
    $radarScores = array_map(fn($d) => (float)($profile->{$d['field']} ?? 0), $domains);
    $dataPoints = []; $axisEndpoints = []; $gridRings = [];
    for ($i = 0; $i < $n; $i++) {
        $ang = (M_PI * 2 * $i / $n) - M_PI / 2;
        $s   = min($radarScores[$i], 100) / 100;
        $dataPoints[]   = round($cx + $rd * $s * cos($ang), 2) . ',' . round($cy + $rd * $s * sin($ang), 2);
        $axisEndpoints[] = ['ex' => round($cx + $rd * cos($ang), 2), 'ey' => round($cy + $rd * sin($ang), 2),
                            'lx' => round($cx + ($rd + 16) * cos($ang), 2), 'ly' => round($cy + ($rd + 16) * sin($ang), 2),
                            'anchor' => $anchors[$i], 'short' => $domains[$i]['short'], 'score' => $radarScores[$i]];
    }
    foreach ([100, 75, 50, 25] as $pct) {
        $gpts = [];
        for ($i = 0; $i < $n; $i++) {
            $ang = (M_PI * 2 * $i / $n) - M_PI / 2;
            $gpts[] = round($cx + $rd * $pct / 100 * cos($ang), 2) . ',' . round($cy + $rd * $pct / 100 * sin($ang), 2);
        }
        $gridRings[] = implode(' ', $gpts);
    }
    $dataPolygon = implode(' ', $dataPoints);
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">

    {{-- 6 Domain scores: radar + bars --}}
    <div class="lg:col-span-2 card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body">
            <div class="flex items-center justify-between mb-4">
                <h2 class="card-title text-base">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    6 Năng lực TDWCF
                </h2>
                <p class="text-xs text-base-content/30">Cập nhật {{ $profile->tdwcf_assessed_at?->diffForHumans() ?? 'chưa có' }}</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-5 items-start">
                {{-- Radar chart (SVG, server-rendered) --}}
                <div class="shrink-0 mx-auto sm:mx-0">
                    <svg viewBox="0 0 200 200" width="200" height="200" class="overflow-visible">
                        {{-- grid rings --}}
                        @foreach($gridRings as $ring)
                        <polygon points="{{ $ring }}" fill="none" stroke="currentColor" stroke-opacity="0.1" stroke-width="1"/>
                        @endforeach
                        {{-- axis lines --}}
                        @foreach($axisEndpoints as $ax)
                        <line x1="{{ $cx }}" y1="{{ $cy }}" x2="{{ $ax['ex'] }}" y2="{{ $ax['ey'] }}" stroke="currentColor" stroke-opacity="0.15" stroke-width="1"/>
                        @endforeach
                        {{-- data polygon --}}
                        <polygon points="{{ $dataPolygon }}" fill="#8b5cf6" fill-opacity="0.25" stroke="#8b5cf6" stroke-width="2" stroke-linejoin="round"/>
                        {{-- data dots --}}
                        @foreach($dataPoints as $dp)
                        @php [$dpx, $dpy] = explode(',', $dp); @endphp
                        <circle cx="{{ $dpx }}" cy="{{ $dpy }}" r="3" fill="#8b5cf6"/>
                        @endforeach
                        {{-- axis labels --}}
                        @foreach($axisEndpoints as $ax)
                        <text x="{{ $ax['lx'] }}" y="{{ $ax['ly'] }}" text-anchor="{{ $ax['anchor'] }}"
                              dominant-baseline="middle" font-size="9" fill="currentColor" fill-opacity="0.6"
                              class="font-mono">{{ $ax['short'] }}</text>
                        <text x="{{ $ax['lx'] }}" y="{{ $ax['ly'] + 9 }}" text-anchor="{{ $ax['anchor'] }}"
                              dominant-baseline="middle" font-size="8" fill="currentColor" fill-opacity="0.45">
                            {{ $ax['score'] > 0 ? number_format($ax['score'], 0) : '—' }}
                        </text>
                        @endforeach
                    </svg>
                </div>

                {{-- Bars --}}
                <div class="flex-1 space-y-3 w-full">
                    @foreach($domains as $d)
                    @php $score = $profile->{$d['field']} ?? 0; $hasScore = $profile->{$d['field']} !== null; @endphp
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="font-medium text-base-content/70" title="{{ $d['desc'] }}">{{ $d['label'] }}</span>
                            <span class="font-semibold {{ $score >= 70 ? 'text-success' : ($score >= 40 ? 'text-warning' : ($hasScore ? 'text-error' : 'text-base-content/30')) }}">
                                {{ $hasScore ? number_format($score, 1) : '—' }}
                            </span>
                        </div>
                        <div class="h-2 bg-base-200 rounded-full overflow-hidden">
                            <div class="{{ $d['color'] }} h-2 rounded-full transition-all duration-500"
                                 style="width: {{ min($score, 100) }}%"></div>
                        </div>
                    </div>
                    @endforeach

                    @if(! $profile->tdwcf_score)
                    <div class="mt-2 p-3 bg-info/5 border border-info/20 rounded-lg text-xs text-base-content/60">
                        Chưa có điểm TDWCF. <a href="{{ route('backend.surveys.index') }}" class="link link-primary">Làm khảo sát TDWCF</a> để cập nhật 6 năng lực.
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Right column: Maturity + Trust Breakdown --}}
    <div class="space-y-4">

        {{-- Maturity level card --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Cấp độ trưởng thành</p>
                <div class="text-center py-2">
                    <span class="badge {{ $ml[1] }} badge-lg text-sm font-semibold px-4 py-3">{{ $ml[0] }}</span>
                </div>
                @if($currentPathwayStep)
                <div class="divider my-2 text-xs text-base-content/30">Bước tiếp theo</div>
                <p class="text-sm font-medium text-base-content/80">{{ $currentPathwayStep->title }}</p>
                <p class="text-xs text-base-content/40 mt-1">~{{ $currentPathwayStep->estimated_weeks }} tuần · <a href="{{ route('backend.career-pathway.index') }}" class="link link-hover">Xem lộ trình</a></p>
                @endif
            </div>
        </div>

        {{-- Trust Score breakdown --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
                    Trust Score = {{ $profile->workforce_trust_score ? number_format($profile->workforce_trust_score, 1) : '—' }}
                </p>
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

    </div>
</div>

{{-- ── Career goal (editable) ─────────────────────────────────────────────── --}}
<div class="card bg-base-100 shadow-sm border border-base-200 mb-5"
     x-data="{ editing: false }">
    <div class="card-body p-5">
        <div class="flex items-center justify-between mb-3">
            <h2 class="card-title text-base">
                <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                Mục tiêu nghề nghiệp
            </h2>
            <button @click="editing = !editing" class="btn btn-ghost btn-xs gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <span x-text="editing ? 'Huỷ' : 'Chỉnh sửa'"></span>
            </button>
        </div>

        {{-- Read mode --}}
        <div x-show="!editing">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-base-content/40 mb-1">Mục tiêu</p>
                    <p class="text-sm {{ $profile->career_goal ? 'text-base-content/80' : 'text-base-content/30 italic' }}">
                        {{ $profile->career_goal ?? 'Chưa đặt mục tiêu nghề nghiệp' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-base-content/40 mb-1">Lộ trình đang học</p>
                    <p class="text-sm {{ $profile->current_learning_path ? 'text-base-content/80' : 'text-base-content/30 italic' }}">
                        {{ $profile->current_learning_path ?? 'Chưa có lộ trình học tập' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Edit mode --}}
        <div x-show="editing" x-cloak>
            <form method="POST" action="{{ route('backend.workforce.me.goal') }}">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs font-medium">Mục tiêu nghề nghiệp</span></label>
                        <textarea name="career_goal" rows="3" class="textarea textarea-bordered text-sm"
                                  placeholder="VD: Trở thành AI Practitioner trong 6 tháng...">{{ $profile->career_goal }}</textarea>
                    </div>
                    <div class="form-control">
                        <label class="label py-1"><span class="label-text text-xs font-medium">Lộ trình đang học</span></label>
                        <input type="text" name="current_learning_path" class="input input-bordered input-sm"
                               value="{{ $profile->current_learning_path }}"
                               placeholder="VD: AI for Business — Coursera">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm">Lưu</button>
                    <button type="button" @click="editing = false" class="btn btn-ghost btn-sm">Huỷ</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ── Skill Gap theo Vị trí việc làm ──────────────────────────────────────── --}}
@if(!empty($jobTitleRequirements) && $profile)
<div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
    <div class="card-body p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="card-title text-base">
                <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Skill Gap theo Vị trí — {{ $employee?->jobTitle?->name ?? 'N/A' }}
            </h2>
            <span class="text-xs text-base-content/40">Yêu cầu vị trí việc làm</span>
        </div>
        @php
        $domainReqMap = [
            'D1' => ['score_d1_digital_literacy', 'D1 — Năng lực số cơ bản'],
            'D2' => ['score_d2_data_literacy',    'D2 — Năng lực dữ liệu'],
            'D3' => ['score_d3_ai_literacy',       'D3 — Năng lực AI'],
            'D4' => ['score_d4_workflow',          'D4 — Quy trình & TĐH'],
            'D5' => ['score_d5_innovation',        'D5 — Đổi mới'],
            'D6' => ['score_d6_performance',       'D6 — Hiệu suất'],
        ];
        $totalGap = 0;
        $gapCount = 0;
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($domainReqMap as $dCode => [$field, $label])
            @php
                $req = $jobTitleRequirements[$dCode] ?? null;
                if (!$req) continue;
                $cur = (float)($profile->{$field} ?? 0);
                $gap = max(0, $req - $cur);
                $met = $cur >= $req;
                if ($gap > 0) { $totalGap += $gap; $gapCount++; }
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
        @if($gapCount > 0)
        <p class="text-xs text-base-content/30 mt-3">{{ $gapCount }}/6 năng lực chưa đạt yêu cầu vị trí. Tổng khoảng cách: {{ number_format($totalGap, 1) }} điểm.</p>
        @else
        <p class="text-xs text-success mt-3">Tất cả năng lực đã đáp ứng yêu cầu vị trí việc làm!</p>
        @endif
    </div>
</div>
@endif

{{-- ── Skill Gap Analysis ───────────────────────────────────────────────────── --}}
@if($profile->tdwcf_score)
@php
    $sgTarget     = $skillGapBenchmarks['target'];
    $sgNextLevel  = $skillGapBenchmarks['next_level'];
    $sgLevelNames = ['DIGITAL_AWARE'=>'Nhận thức số','DIGITAL_PRACTITIONER'=>'Thực hành số','DIGITAL_PROFESSIONAL'=>'Chuyên nghiệp','DIGITAL_LEADER'=>'Dẫn dắt số'];
    $sgDomainList = [
        ['D1 — Năng lực số', $profile->score_d1_digital_literacy ?? 0, '#3b82f6'],
        ['D2 — Dữ liệu',     $profile->score_d2_data_literacy    ?? 0, '#6366f1'],
        ['D3 — AI',          $profile->score_d3_ai_literacy       ?? 0, '#8b5cf6'],
        ['D4 — Quy trình',   $profile->score_d4_workflow          ?? 0, '#a855f7'],
        ['D5 — Đổi mới',     $profile->score_d5_innovation        ?? 0, '#d946ef'],
        ['D6 — Hiệu suất',   $profile->score_d6_performance       ?? 0, '#ec4899'],
    ];
    $sgAllMet = collect($sgDomainList)->every(fn($d) => $d[1] >= $sgTarget);
@endphp
<div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
    <div class="card-body p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="card-title text-base">
                <svg class="w-4 h-4 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Phân tích khoảng cách kỹ năng (Skill Gap)
            </h2>
            <div class="text-xs text-base-content/40">
                @if($sgNextLevel)
                    Mục tiêu: <span class="font-semibold text-primary">{{ $sgLevelNames[$sgNextLevel] ?? $sgNextLevel }}</span>
                    (≥ {{ $sgTarget }}/100 mỗi năng lực)
                @else
                    Đang ở cấp cao nhất
                @endif
            </div>
        </div>

        @if($sgAllMet && $sgNextLevel)
        <div class="alert alert-success py-2 px-4 mb-4 text-sm">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Tất cả năng lực đã đạt ngưỡng — bạn đủ điều kiện tiến lên <strong>{{ $sgLevelNames[$sgNextLevel] ?? $sgNextLevel }}</strong>!
        </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($sgDomainList as [$dLabel, $dScore, $dHex])
            @php
                $gap     = max(0, $sgTarget - $dScore);
                $met     = $dScore >= $sgTarget;
                $fillPct = min($dScore, 100);
                $tgtPct  = min($sgTarget, 100);
            @endphp
            <div class="border {{ $met ? 'border-success/30 bg-success/3' : 'border-base-200' }} rounded-xl p-3">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-medium text-base-content/70">{{ $dLabel }}</span>
                    @if($met)
                    <span class="badge badge-success badge-xs">Đạt</span>
                    @else
                    <span class="text-xs text-error font-semibold">-{{ number_format($gap, 1) }}</span>
                    @endif
                </div>
                <div class="h-3 bg-base-200 rounded-full relative overflow-visible">
                    {{-- Current score bar --}}
                    <div class="h-3 rounded-full transition-all absolute top-0 left-0"
                         style="width: {{ $fillPct }}%; background: {{ $dHex }}; opacity: 0.75;"></div>
                    {{-- Target line --}}
                    <div class="absolute top-0 h-3 w-0.5 bg-warning/80 rounded"
                         style="left: {{ $tgtPct }}%;" title="Mục tiêu: {{ $sgTarget }}"></div>
                </div>
                <div class="flex justify-between mt-1 text-xs">
                    <span class="font-mono {{ $met ? 'text-success' : 'text-base-content/50' }}">{{ number_format($dScore, 1) }}</span>
                    <span class="text-base-content/30">mục tiêu: {{ $sgTarget }}</span>
                </div>
            </div>
            @endforeach
        </div>

        <p class="text-xs text-base-content/30 mt-3">
            <span class="inline-block w-3 h-1.5 bg-warning/80 rounded mr-1 align-middle"></span>
            Đường vàng = ngưỡng điểm cần đạt để lên cấp tiếp theo.
            Vùng thiếu hụt (gap) cần được bổ sung qua khảo sát, sandbox và chứng nhận.
        </p>
    </div>
</div>
@endif

{{-- ── AI Gợi ý phát triển ─────────────────────────────────────────────────── --}}
@if($profile)
<div class="card bg-base-100 shadow-sm border border-base-200 mb-5"
     x-data="{
         loading: false,
         recs: {{ $recommendation?->recommendations ? Js::from($recommendation->recommendations) : '[]' }},
         generatedAt: '{{ $recommendation?->generated_at?->diffForHumans() ?? '' }}',
         async generate() {
             this.loading = true;
             try {
                 const res = await fetch('{{ $profile ? route('backend.workforce.recommendations.generate', $profile) : '#' }}', {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/json',
                         'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                         'Accept': 'application/json',
                     },
                 });
                 if (!res.ok) throw new Error('HTTP ' + res.status);
                 const data = await res.json();
                 if (data.recommendations) {
                     this.recs = data.recommendations;
                     this.generatedAt = data.generated_at ?? 'vừa xong';
                 }
             } catch (e) {
                 console.error('AI recommendation error:', e);
             } finally {
                 this.loading = false;
             }
         }
     }">
    <div class="card-body p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="card-title text-base">
                <svg class="w-4 h-4 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                AI Gợi ý phát triển
            </h2>
            <div class="flex items-center gap-2">
                <span class="text-xs text-base-content/30" x-show="generatedAt" x-text="'Tạo: ' + generatedAt"></span>
                <button @click="generate()" :disabled="loading"
                        class="btn btn-primary btn-xs gap-1.5"
                        :class="{ 'loading': loading }">
                    <svg x-show="!loading" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span x-text="loading ? 'Đang tạo...' : 'Tạo gợi ý mới'"></span>
                </button>
            </div>
        </div>

        {{-- Loading skeleton --}}
        <div x-show="loading" class="space-y-3">
            @for($i = 0; $i < 3; $i++)
            <div class="animate-pulse flex gap-3 border border-base-200 rounded-xl p-3">
                <div class="w-8 h-8 bg-base-200 rounded-lg shrink-0"></div>
                <div class="flex-1 space-y-2">
                    <div class="h-3 bg-base-200 rounded w-3/4"></div>
                    <div class="h-2 bg-base-200 rounded w-1/2"></div>
                </div>
            </div>
            @endfor
        </div>

        {{-- Recommendation list --}}
        <div x-show="!loading">
            <template x-if="recs.length === 0">
                <div class="text-center py-8 text-base-content/30">
                    <svg class="w-10 h-10 mx-auto mb-2 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                    <p class="text-sm">Chưa có gợi ý. Nhấn "Tạo gợi ý mới" để AI phân tích hồ sơ của bạn.</p>
                </div>
            </template>
            <template x-if="recs.length > 0">
                <div class="space-y-3">
                    <template x-for="(rec, idx) in recs" :key="idx">
                        <div class="flex gap-3 border border-base-200 rounded-xl p-3 hover:border-base-300 transition-colors">
                            {{-- Resource type icon --}}
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                                 :class="{
                                     'bg-info/10 text-info':    rec.resource_type === 'course',
                                     'bg-accent/10 text-accent': rec.resource_type === 'sandbox',
                                     'bg-success/10 text-success': rec.resource_type === 'certification',
                                     'bg-warning/10 text-warning': rec.resource_type === 'practice',
                                     'bg-base-200 text-base-content/40': !['course','sandbox','certification','practice'].includes(rec.resource_type),
                                 }">
                                {{-- course icon --}}
                                <template x-if="rec.resource_type === 'course'">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                </template>
                                {{-- sandbox icon --}}
                                <template x-if="rec.resource_type === 'sandbox'">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </template>
                                {{-- certification icon --}}
                                <template x-if="rec.resource_type === 'certification'">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </template>
                                {{-- practice icon --}}
                                <template x-if="rec.resource_type === 'practice'">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </template>
                                {{-- fallback icon --}}
                                <template x-if="!['course','sandbox','certification','practice'].includes(rec.resource_type)">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </template>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <p class="text-sm font-medium text-base-content/80 leading-snug" x-text="rec.action"></p>
                                    {{-- Priority badge --}}
                                    <span class="badge badge-xs shrink-0"
                                          :class="{
                                              'badge-error':   rec.priority === 1,
                                              'badge-warning': rec.priority === 2,
                                              'badge-info':    rec.priority === 3,
                                              'badge-ghost':   rec.priority >= 4,
                                          }"
                                          x-text="'P' + rec.priority"></span>
                                </div>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="badge badge-ghost badge-xs font-mono" x-text="rec.domain"></span>
                                    <span class="text-xs text-base-content/40" x-text="rec.resource_name"></span>
                                    <template x-if="rec.estimated_weeks">
                                        <span class="text-xs text-base-content/30" x-text="'~' + rec.estimated_weeks + ' tuần'"></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>
@endif

{{-- ── Row 3: Certifications ───────────────────────────────────────────────── --}}
@if($certifications->count())
<div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
    <div class="card-body">
        <div class="flex items-center justify-between mb-4">
            <h2 class="card-title text-base">
                <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                Chứng nhận đã đạt ({{ $certifications->count() }})
            </h2>
            <a href="{{ route('backend.certifications.index') }}" class="btn btn-ghost btn-xs">Xem tất cả →</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
            @foreach($certifications->take(6) as $cert)
            @php
                $isActive   = $cert->status === 'active';
                $isExpired  = $cert->status === 'expired';
                $borderCls  = $isActive ? 'border-success text-success' : ($isExpired ? 'border-error text-error' : 'border-base-300 text-base-content/50');
                $levelBadge = match($cert->definition?->level_code ?? '') {
                    'FOUNDATION'   => 'badge-info',
                    'PRACTITIONER' => 'badge-warning',
                    'PROFESSIONAL' => 'badge-success',
                    'LEADER'       => 'badge-accent',
                    default        => 'badge-ghost',
                };
            @endphp
            <div class="border {{ $borderCls }} rounded-xl p-3 flex items-start gap-3">
                <svg class="w-7 h-7 shrink-0 mt-0.5 {{ $borderCls }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <div>
                    <p class="text-sm font-semibold leading-snug">{{ $cert->definition?->name ?? '—' }}</p>
                    <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                        <span class="badge {{ $levelBadge }} badge-xs">{{ $cert->definition?->level_code ?? '—' }}</span>
                        <span class="text-xs text-base-content/40">{{ $cert->issued_at?->format('d/m/Y') }}</span>
                    </div>
                    @if($cert->expires_at)
                    <p class="text-xs {{ $isExpired ? 'text-error' : 'text-base-content/30' }} mt-0.5">
                        HH: {{ $cert->expires_at->format('d/m/Y') }}
                        @if($isActive && $cert->expires_at->diffInDays() < 30)
                        <span class="badge badge-warning badge-xs ml-1">Sắp hết hạn</span>
                        @endif
                    </p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ── Portfolio ─────────────────────────────────────────────────────────────── --}}
@if($portfolios->count())
<div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
    <div class="card-body">
        <div class="flex items-center justify-between mb-4">
            <h2 class="card-title text-base">
                <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                Portfolio ({{ $portfolios->count() }})
            </h2>
            @php $approvedCount = $portfolios->where('approval_status','approved')->count(); @endphp
            <span class="text-xs text-base-content/40">{{ $approvedCount }} được duyệt</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($portfolios->take(4) as $item)
            @php
                $statusCls = match($item->approval_status) {
                    'approved' => ['badge-success', 'Đã duyệt'],
                    'rejected' => ['badge-error', 'Từ chối'],
                    default    => ['badge-ghost', 'Chờ duyệt'],
                };
                $typeCls = match($item->item_type) {
                    'assessment_result' => 'badge-info',
                    'sandbox_result'    => 'badge-warning',
                    'impact_data'       => 'badge-success',
                    default             => 'badge-ghost',
                };
            @endphp
            <div class="border border-base-200 rounded-xl p-3">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-medium leading-snug">{{ $item->title }}</p>
                    <span class="badge {{ $statusCls[0] }} badge-xs shrink-0">{{ $statusCls[1] }}</span>
                </div>
                <div class="flex gap-1.5 mt-1.5">
                    <span class="badge {{ $typeCls }} badge-xs">{{ $item->item_type }}</span>
                </div>
                @if($item->evidence_url)
                <a href="{{ $item->evidence_url }}" target="_blank" class="text-xs link link-primary mt-1 inline-block">Xem bằng chứng →</a>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ── Recent activity ──────────────────────────────────────────────────────── --}}
@if($recentHistory->count())
<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body">
        <h2 class="card-title text-base mb-4">
            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Hoạt động gần đây
        </h2>
        <div class="space-y-3">
            @foreach($recentHistory as $event)
            @php
                $iconPath = match($event->event_type) {
                    'assessment'        => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
                    'sandbox'           => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
                    'certification'     => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                    'impact'            => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
                    'performance_review'=> 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                    default             => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                };
                $iconCls = match($event->event_type) {
                    'assessment'         => 'text-info',
                    'sandbox'            => 'text-warning',
                    'certification'      => 'text-success',
                    'impact'             => 'text-accent',
                    'performance_review' => 'text-primary',
                    default              => 'text-base-content/30',
                };
            @endphp
            <div class="flex items-start gap-3">
                <svg class="w-4 h-4 mt-0.5 shrink-0 {{ $iconCls }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $iconPath }}"/>
                </svg>
                <div class="flex-1">
                    <p class="text-sm text-base-content/80">{{ $event->notes }}</p>
                    @if($event->change_delta)
                    <span class="text-xs {{ $event->change_delta >= 0 ? 'text-success' : 'text-error' }}">
                        {{ $event->change_delta >= 0 ? '+' : '' }}{{ number_format($event->change_delta, 1) }} điểm
                    </span>
                    @endif
                    <p class="text-xs text-base-content/30 mt-0.5">{{ $event->recorded_at?->diffForHumans() ?? '—' }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ── Score Trend (lịch sử điểm TDWCF) ───────────────────────────────────── --}}
@if($scoreHistory->count() >= 2)
<div class="card bg-base-100 shadow-sm border border-base-200 mt-5">
    <div class="card-body">
        <h2 class="card-title text-base mb-4">
            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            Xu hướng phát triển TDWCF
        </h2>
        <div id="tdwcf-trend-chart" style="height:160px;"></div>
    </div>
</div>
@endif

{{-- ── Career Journal (Competency Passport preview) ────────────────────────── --}}
<div class="card bg-base-100 shadow-sm border border-base-200 mt-5">
    <div class="card-body">
        <div class="flex items-center justify-between mb-4">
            <h2 class="card-title text-base">
                <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                Career Journal — Nhật ký Nghề nghiệp
            </h2>
            <a href="{{ route('passport.index') }}" class="btn btn-ghost btn-xs gap-1">
                Xem tất cả
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        {{-- Current chapter (this org) --}}
        @if($profile)
        <div class="flex items-center gap-3 p-3 bg-primary/5 border border-primary/20 rounded-xl mb-3">
            <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <p class="text-sm font-semibold text-base-content">{{ $user->organization?->name ?? 'Tổ chức hiện tại' }}</p>
                    <span class="badge badge-primary badge-xs">Đang viết…</span>
                </div>
                <p class="text-xs text-base-content/40">Chương này sẽ được niêm phong khi bạn rời tổ chức</p>
            </div>
            @if($profile->tdwcf_score)
            <div class="text-center shrink-0">
                <div class="text-xl font-bold text-primary">{{ number_format($profile->tdwcf_score, 1) }}</div>
                <div class="text-xs text-base-content/40">TDWCF</div>
            </div>
            @endif
        </div>
        @endif

        {{-- Archived chapters --}}
        @if($passportEntries->isEmpty())
        <div class="text-center py-6 text-base-content/30">
            <p class="text-sm">Chưa có chương nào được lưu. Các chương xuất hiện khi bạn rời tổ chức hoặc hoàn thành một Assessment Campaign.</p>
            <a href="{{ route('campaigns.index') }}" class="btn btn-ghost btn-xs mt-3 gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                Khám phá Assessment Marketplace
            </a>
        </div>
        @else
        <div class="space-y-2">
            @foreach($passportEntries as $entry)
            <div class="flex items-center gap-3 p-3 border border-base-200 rounded-xl hover:border-base-300 transition-colors">
                <div class="w-9 h-9 rounded-lg bg-base-200 flex items-center justify-center shrink-0 text-xs font-bold text-base-content/50">
                    {{ strtoupper(mb_substr($entry->source_org_name ?? '?', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-base-content truncate">{{ $entry->source_org_name ?? 'Tổ chức không xác định' }}</p>
                    <p class="text-xs text-base-content/40">
                        {{ $entry->tenure_start?->format('m/Y') ?? '?' }} → {{ $entry->tenure_end?->format('m/Y') ?? '?' }}
                        @if($entry->tenure_months) · {{ $entry->tenure_months }} tháng @endif
                        <span class="ml-1 badge badge-xs {{ $entry->entry_type === 'campaign_result' ? 'badge-info' : 'badge-ghost' }}">
                            {{ $entry->entry_type === 'campaign_result' ? 'Campaign' : 'Org tenure' }}
                        </span>
                    </p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    @if($entry->tdwcf_score)
                    <div class="text-center">
                        <div class="text-base font-bold text-primary">{{ number_format($entry->tdwcf_score, 1) }}</div>
                        <div class="text-xs text-base-content/40">TDWCF</div>
                    </div>
                    @endif
                    @if($entry->org_verified)
                    <svg class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" title="Đã xác nhận bởi org"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @endif
                    <a href="{{ route('passport.show', $entry->uuid) }}" class="btn btn-ghost btn-xs">Xem</a>
                </div>
            </div>
            @endforeach
        </div>
        @if($passportEntries->count() >= 4)
        <div class="text-center mt-3">
            <a href="{{ route('passport.index') }}" class="btn btn-ghost btn-sm gap-1">
                Xem toàn bộ Career Journal
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
        @endif
        @endif
    </div>
</div>

@endif {{-- end if profile --}}

@endsection

@push('scripts')
    @vite([
        'resources/js/modules/echarts.js',
        'Modules/Assessment/resources/assets/js/assessment.js',
    ], 'build/backend')

    @if(isset($profile) && $profile && $scoreHistory->count() >= 2)
    <script>
    document.addEventListener('echarts:ready', () => {
        const el = document.getElementById('tdwcf-trend-chart');
        if (!el) return;
        const chart = window.ECharts.init(el);
        chart.setOption({
            tooltip: { trigger: 'axis', formatter: p => `${p[0].axisValue}: <b>${p[0].value}</b>` },
            grid: { top: 10, right: 20, bottom: 30, left: 40 },
            xAxis: { type: 'category', data: {!! $scoreHistory->pluck('recorded_at')->map(fn($d) => '"' . $d->format('d/m/y') . '"')->implode(',') !!}, axisLabel: { fontSize: 10 } },
            yAxis: { type: 'value', min: 0, max: 100, axisLabel: { fontSize: 10 } },
            series: [{
                type: 'line', smooth: true, symbol: 'circle', symbolSize: 6,
                data: {!! $scoreHistory->pluck('tdwcf_score_after')->implode(',') !!},
                lineStyle: { color: '#8b5cf6', width: 2 },
                itemStyle: { color: '#8b5cf6' },
                areaStyle: { color: { type: 'linear', x: 0, y: 0, x2: 0, y2: 1, colorStops: [{ offset: 0, color: 'rgba(139,92,246,0.3)' }, { offset: 1, color: 'rgba(139,92,246,0)' }] } },
            }],
        });
        window.addEventListener('resize', () => chart.resize());
    });
    </script>
    @endif
@endpush
