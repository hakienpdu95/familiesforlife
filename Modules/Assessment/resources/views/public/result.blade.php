<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả đánh giá</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'], 'build/backend')
</head>
<body class="bg-base-200 min-h-screen">

<div class="max-w-3xl mx-auto px-4 py-10 space-y-6">

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="text-center space-y-1">
        <p class="text-sm font-medium text-base-content/50 uppercase tracking-widest">Kết quả đánh giá</p>
        <h1 class="text-2xl font-bold text-base-content">{{ $result->assessment_code }}</h1>
        @if($result->calculated_at)
        <p class="text-base-content/50 text-sm">
            Tính lúc {{ $result->calculated_at->format('H:i · d/m/Y') }}
        </p>
        @endif
    </div>

    {{-- ── Overall score ───────────────────────────────────────────────── --}}
    @php
        $score = round($result->overall_score, 1);
        $level = $result->maturity_level;
        $levelLabel = $bandInfo?->label ?? $maturityInfo?->label ?? $level;
        $levelDesc  = $bandInfo?->description ?? $maturityInfo?->description ?? null;

        $scoreColor = match(true) {
            $score >= 70 => ['bg' => 'bg-success/10', 'text' => 'text-success', 'badge' => 'badge-success'],
            $score >= 40 => ['bg' => 'bg-warning/10', 'text' => 'text-warning', 'badge' => 'badge-warning'],
            default      => ['bg' => 'bg-error/10',   'text' => 'text-error',   'badge' => 'badge-error'],
        };
    @endphp

    <div class="card bg-base-100 shadow-sm {{ $scoreColor['bg'] }}">
        <div class="card-body items-center text-center py-10 space-y-3">
            <div class="text-7xl font-black {{ $scoreColor['text'] }}">{{ $score }}</div>
            <div class="text-lg font-semibold text-base-content/70">/ 100 điểm</div>
            @if($levelLabel)
            <span class="badge badge-lg {{ $scoreColor['badge'] }} gap-1 font-semibold">
                {{ $levelLabel }}
            </span>
            @endif
        </div>
    </div>

    {{-- ── Level description ───────────────────────────────────────────── --}}
    @if($levelDesc)
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body py-5">
            <h2 class="card-title text-base">Hồ sơ năng lực</h2>
            <p class="text-sm text-base-content/70 leading-relaxed">{{ $levelDesc }}</p>
        </div>
    </div>
    @endif

    {{-- ── Domain scores ───────────────────────────────────────────────── --}}
    @if($result->domainScores->isNotEmpty())
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body py-5 space-y-4">
            <h2 class="card-title text-base">Điểm theo lĩnh vực</h2>
            @foreach($result->domainScores as $ds)
            @php
                $pct      = min(100, max(0, round($ds->normalized_score)));
                $dsLabel  = $domainLabels[$ds->domain_code] ?? $ds->domain_code;
                $barColor = $pct >= 70 ? 'progress-success' : ($pct >= 40 ? 'progress-warning' : 'progress-error');
                $txtColor = $pct >= 70 ? 'text-success' : ($pct >= 40 ? 'text-warning' : 'text-error');
            @endphp
            <div class="space-y-1">
                <div class="flex justify-between text-sm">
                    <span class="font-medium text-base-content/80">{{ $dsLabel }}</span>
                    <span class="font-semibold {{ $txtColor }}">{{ $pct }}%</span>
                </div>
                <progress class="progress {{ $barColor }} w-full h-3"
                          value="{{ $pct }}" max="100"></progress>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Pain points ─────────────────────────────────────────────────── --}}
    @if($result->painPoints->isNotEmpty())
    <div class="card bg-base-100 shadow-sm border-l-4 border-warning">
        <div class="card-body py-5 space-y-3">
            <h2 class="card-title text-base text-warning">Điểm cần cải thiện</h2>
            <ul class="space-y-2">
                @foreach($result->painPoints as $pp)
                <li class="flex items-start gap-2 text-sm text-base-content/75">
                    <svg class="w-4 h-4 mt-0.5 shrink-0 text-warning" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                              d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                              clip-rule="evenodd"/>
                    </svg>
                    <span>{{ $painLabels[$pp->pain_point_code] ?? $pp->pain_point_code }}</span>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- ── Recommendations ─────────────────────────────────────────────── --}}
    @if($result->recommendations->isNotEmpty())
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body py-5 space-y-3">
            <h2 class="card-title text-base">Đề xuất cải thiện</h2>
            <div class="space-y-3">
                @foreach($result->recommendations as $rec)
                @php
                    $recInfo  = $recLabels[$rec->recommendation_code] ?? null;
                    $recLabel = $recInfo?->label ?? $rec->recommendation_code;
                    $recDesc  = $recInfo?->description ?? null;
                    $priorityBadge  = [1 => 'badge-error',   2 => 'badge-warning', 3 => 'badge-info'];
                    $priorityText   = [1 => 'Ưu tiên cao',   2 => 'Ưu tiên vừa',   3 => 'Ưu tiên thấp'];
                @endphp
                <div class="flex items-start gap-3 p-3 rounded-lg bg-base-200/50">
                    <div class="flex-1 space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold text-sm text-base-content">{{ $recLabel }}</span>
                            @if($rec->priority)
                            <span class="badge badge-xs {{ $priorityBadge[$rec->priority] ?? 'badge-ghost' }}">
                                {{ $priorityText[$rec->priority] ?? "P{$rec->priority}" }}
                            </span>
                            @endif
                        </div>
                        @if($recDesc)
                        <p class="text-xs text-base-content/60 leading-relaxed">{{ $recDesc }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- ── Roadmap ──────────────────────────────────────────────────────── --}}
    @if($result->roadmapPhases->isNotEmpty())
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body py-5 space-y-4">
            <h2 class="card-title text-base">Lộ trình phát triển</h2>
            <ol class="space-y-4">
                @foreach($result->roadmapPhases as $i => $rp)
                @php $phase = $rp->phase; @endphp
                @if($phase)
                <li class="flex gap-3">
                    <div class="flex-none w-7 h-7 rounded-full bg-primary text-primary-content
                                flex items-center justify-center text-xs font-bold">
                        {{ $i + 1 }}
                    </div>
                    <div class="flex-1 pt-0.5 space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="font-semibold text-sm text-base-content">{{ $phase->title }}</p>
                            @if($phase->duration_weeks)
                            <span class="badge badge-xs badge-ghost">{{ $phase->duration_weeks }} tuần</span>
                            @endif
                        </div>
                        @if($phase->description)
                        <p class="text-xs text-base-content/60">{{ $phase->description }}</p>
                        @endif
                        @if($phase->milestones->isNotEmpty())
                        <ul class="mt-2 space-y-0.5 pl-3 border-l-2 border-base-300">
                            @foreach($phase->milestones as $ms)
                            <li class="text-xs text-base-content/60">· {{ $ms->title }}</li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </li>
                @endif
                @endforeach
            </ol>
        </div>
    </div>
    @endif

    {{-- ── Footer ──────────────────────────────────────────────────────── --}}
    <div class="text-center text-xs text-base-content/30 pt-2 pb-6">
        Powered by {{ config('app.name') }}
    </div>

</div>
</body>
</html>
