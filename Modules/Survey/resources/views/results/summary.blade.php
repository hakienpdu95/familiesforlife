@extends('layouts.backend')

@section('title', 'Tổng hợp kết quả — ' . $survey->title)


@section('content')
<div class="space-y-5 max-w-4xl">

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Tổng hợp kết quả chấm điểm</h1>
            <p class="text-sm text-base-content/50 mt-0.5">{{ $survey->title }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('backend.surveys.responses.index', $survey) }}" class="btn btn-ghost btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Danh sách responses
            </a>
            <a href="{{ route('backend.surveys.stats.index', $survey) }}" class="btn btn-ghost btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Thống kê
            </a>
        </div>
    </div>

    {{-- ── KPIs ─────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4 text-center">
                <p class="text-xs text-base-content/50 mb-1">Số response đã chấm</p>
                <p class="text-3xl font-bold text-primary">{{ number_format($totalScored) }}</p>
            </div>
        </div>
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4 text-center">
                <p class="text-xs text-base-content/50 mb-1">Điểm trung bình</p>
                <p class="text-3xl font-bold text-secondary">
                    {{ $avgOverall !== null ? round($avgOverall, 1) : '—' }}
                </p>
            </div>
        </div>
        <div class="card bg-base-100 border border-base-200 shadow-sm col-span-2 sm:col-span-1">
            <div class="card-body p-4 text-center">
                <p class="text-xs text-base-content/50 mb-1">Assessment</p>
                <p class="font-mono font-semibold text-sm text-base-content">{{ $survey->assessment_code }}</p>
            </div>
        </div>
    </div>

    @if($totalScored === 0)
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body text-center py-12">
            <p class="text-base-content/40 text-sm">Chưa có response nào được chấm điểm.</p>
        </div>
    </div>
    @else

    {{-- ── Maturity distribution ────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="px-5 py-3 border-b border-base-200 font-semibold text-sm">Phân bố mức độ trưởng thành</div>
        <div class="p-5">
            @if($maturityLevels->isNotEmpty())
            <div class="space-y-3">
                @php $maxCount = $maturityDistribution->max() ?: 1; @endphp
                @foreach($maturityLevels as $level)
                @php
                    $count = $maturityDistribution[$level->level_code] ?? 0;
                    $pct   = $totalScored > 0 ? round($count / $totalScored * 100, 1) : 0;
                    $barW  = $maxCount > 0 ? round($count / $maxCount * 100) : 0;
                @endphp
                <div class="flex items-center gap-3">
                    <div class="w-36 shrink-0">
                        <p class="text-sm font-medium text-base-content truncate" title="{{ $level->label }}">{{ $level->label }}</p>
                        <p class="text-xs font-mono text-base-content/40">{{ $level->level_code }}</p>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-base-200 rounded-full h-3 overflow-hidden">
                                <div class="h-3 rounded-full bg-info transition-all" style="width: {{ $barW }}%"></div>
                            </div>
                            <span class="text-sm font-semibold w-8 text-right text-base-content">{{ $count }}</span>
                            <span class="text-xs text-base-content/40 w-12 text-right">{{ $pct }}%</span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-sm text-base-content/40">Chưa có cấu hình maturity level.</p>
            @endif
        </div>
    </div>

    {{-- ── Average domain scores ────────────────────────────────────────── --}}
    @if($domains->isNotEmpty())
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="px-5 py-3 border-b border-base-200 font-semibold text-sm">Điểm trung bình theo domain</div>
        <div class="divide-y divide-base-200">
            @foreach($domains as $domain)
            @php
                $avg = $avgDomainScores[$domain->domain_code] ?? null;
                $barW = $avg !== null ? min(100, round((float)$avg)) : 0;
            @endphp
            <div class="px-5 py-3 flex items-center gap-4">
                <div class="w-44 shrink-0">
                    <p class="text-sm font-medium text-base-content">{{ $domain->label }}</p>
                    <p class="text-xs font-mono text-base-content/40">{{ $domain->domain_code }}</p>
                </div>
                <div class="flex-1">
                    @if($avg !== null)
                    <div class="flex items-center gap-3">
                        <div class="flex-1 bg-base-200 rounded-full h-2.5 overflow-hidden">
                            <div class="h-2.5 rounded-full bg-secondary transition-all" style="width: {{ $barW }}%"></div>
                        </div>
                        <span class="text-sm font-bold text-secondary w-10 text-right">{{ $avg }}</span>
                    </div>
                    @else
                    <span class="text-sm text-base-content/30">—</span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @endif {{-- end $totalScored --}}
</div>
@endsection
