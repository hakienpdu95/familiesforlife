<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả khảo sát — {{ $survey->title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'], 'build/backend')
</head>
<body class="bg-base-200 min-h-screen">

<div class="max-w-3xl mx-auto px-4 py-10 space-y-6"
     x-data="publicResultPage()"
     x-init="init()">

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="text-center space-y-1">
        <p class="text-sm font-medium text-base-content/50 uppercase tracking-widest">Kết quả khảo sát</p>
        <h1 class="text-2xl font-bold text-base-content">{{ $survey->title }}</h1>
        @if($response && $response->respondent_ref)
        <p class="text-base-content/60 text-sm">
            {{ $response->respondent_ref }}
            @if($response->created_at)
                · Nộp {{ $response->created_at->diffForHumans() }}
            @endif
        </p>
        @endif
    </div>

    {{-- ── Not found ───────────────────────────────────────────────────── --}}
    @if($notFound)
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body items-center text-center py-12 space-y-3">
            <svg class="w-12 h-12 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="font-semibold text-base-content/70">Không tìm thấy kết quả</p>
            <p class="text-sm text-base-content/50">
                @if($ref)
                    Chưa có kết quả khảo sát cho <span class="font-medium">{{ $ref }}</span>.
                @else
                    Vui lòng thêm tham số <code class="bg-base-200 px-1 rounded">?ref=</code> vào URL.
                @endif
            </p>
        </div>
    </div>
    @elseif($processing)

    {{-- ── Processing / waiting for score ─────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body items-center text-center py-12 space-y-4">
            <span class="loading loading-spinner loading-lg text-primary"></span>
            <p class="font-semibold text-base-content/70">Đang tính điểm...</p>
            <p class="text-sm text-base-content/50">Vui lòng đợi, kết quả sẽ sẵn sàng trong vài giây.</p>
            <p class="text-xs text-base-content/30" x-text="'Kiểm tra lần ' + pollCount"></p>
        </div>
    </div>

    @push('scripts')
    <script>
    function publicResultPage() {
        return {
            pollCount: 0,
            pollTimer: null,
            init() {
                this.schedulePoll();
            },
            schedulePoll() {
                this.pollTimer = setInterval(() => {
                    this.pollCount++;
                    fetch('/api/v1/surveys/{{ $survey->slug }}/result?ref={{ urlencode($ref) }}', {
                        headers: { 'Authorization': 'Bearer {{ $token }}', 'Accept': 'application/json' }
                    })
                    .then(r => r.ok ? r.json() : null)
                    .then(data => {
                        if (data && data.overall_score !== undefined) {
                            clearInterval(this.pollTimer);
                            window.location.reload();
                        }
                    })
                    .catch(() => {});

                    if (this.pollCount >= 20) {
                        clearInterval(this.pollTimer);
                    }
                }, 3000);
            }
        };
    }
    </script>
    @endpush

    @else

    {{-- ── Overall score hero ──────────────────────────────────────────── --}}
    @php
        $score  = round($result->overall_score, 1);
        $level  = $result->maturity_level;
        $levelColors = [
            'BEGINNER'     => ['bg' => 'bg-error/10',   'text' => 'text-error',   'badge' => 'badge-error'],
            'INTERMEDIATE' => ['bg' => 'bg-warning/10', 'text' => 'text-warning', 'badge' => 'badge-warning'],
            'ADVANCED'     => ['bg' => 'bg-success/10', 'text' => 'text-success', 'badge' => 'badge-success'],
        ];
        $colors = $levelColors[$level] ?? ['bg' => 'bg-primary/10', 'text' => 'text-primary', 'badge' => 'badge-primary'];
        $levelLabel = $maturityInfo?->label ?? $level;
    @endphp

    <div class="card bg-base-100 shadow-sm {{ $colors['bg'] }}">
        <div class="card-body items-center text-center py-10 space-y-3">
            <div class="text-7xl font-black {{ $colors['text'] }}">{{ $score }}</div>
            <div class="text-lg font-semibold text-base-content/70">/ 100 điểm</div>
            <span class="badge badge-lg {{ $colors['badge'] }} gap-1 font-semibold">
                {{ $levelLabel }}
            </span>
            @if($result->calculated_at)
            <p class="text-xs text-base-content/40">
                Tính lúc {{ $result->calculated_at->format('H:i · d/m/Y') }}
            </p>
            @endif
        </div>
    </div>

    {{-- ── Maturity level description ──────────────────────────────────── --}}
    @if($maturityInfo?->description)
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body py-5">
            <h2 class="card-title text-base">Hồ sơ năng lực</h2>
            <p class="text-sm text-base-content/70 leading-relaxed">{{ $maturityInfo->description }}</p>
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
                $pct     = min(100, max(0, round($ds->normalized_score)));
                $dsLabel = $domainLabels[$ds->domain_code] ?? $ds->domain_code;
                $barColor = $pct >= 70 ? 'progress-success' : ($pct >= 40 ? 'progress-warning' : 'progress-error');
            @endphp
            <div class="space-y-1">
                <div class="flex justify-between text-sm">
                    <span class="font-medium text-base-content/80">{{ $dsLabel }}</span>
                    <span class="font-semibold {{ $pct >= 70 ? 'text-success' : ($pct >= 40 ? 'text-warning' : 'text-error') }}">
                        {{ $pct }}%
                    </span>
                </div>
                <progress class="progress {{ $barColor }} w-full h-3" value="{{ $pct }}" max="100"></progress>
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
                @php $ppLabel = $painLabels[$pp->pain_point_code] ?? $pp->pain_point_code; @endphp
                <li class="flex items-start gap-2 text-sm text-base-content/75">
                    <svg class="w-4 h-4 mt-0.5 shrink-0 text-warning" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ $ppLabel }}</span>
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
                    $recInfo = $recLabels[$rec->recommendation_code] ?? null;
                    $recLabel = $recInfo?->label ?? $rec->recommendation_code;
                    $recDesc  = $recInfo?->description ?? null;
                    $priorityColors = [1 => 'badge-error', 2 => 'badge-warning', 3 => 'badge-info'];
                    $priorityLabels = [1 => 'Ưu tiên cao', 2 => 'Ưu tiên vừa', 3 => 'Ưu tiên thấp'];
                @endphp
                <div class="flex items-start gap-3 p-3 rounded-lg bg-base-200/50">
                    <div class="flex-1 space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-semibold text-sm text-base-content">{{ $recLabel }}</span>
                            @if($rec->priority)
                            <span class="badge badge-xs {{ $priorityColors[$rec->priority] ?? 'badge-ghost' }}">
                                {{ $priorityLabels[$rec->priority] ?? "P{$rec->priority}" }}
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

    {{-- ── Roadmap phases ───────────────────────────────────────────────── --}}
    @if($result->roadmapPhases->isNotEmpty())
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body py-5 space-y-4">
            <h2 class="card-title text-base">Lộ trình đào tạo</h2>
            <ol class="space-y-4">
                @foreach($result->roadmapPhases as $i => $rp)
                @php $phase = $rp->phase; @endphp
                @if($phase)
                <li class="flex gap-3">
                    <div class="flex-none w-7 h-7 rounded-full bg-primary text-primary-content flex items-center justify-center text-xs font-bold">
                        {{ $i + 1 }}
                    </div>
                    <div class="flex-1 pt-0.5 space-y-1">
                        <p class="font-semibold text-sm text-base-content">{{ $phase->title }}</p>
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

    @endif {{-- end not-processing/not-notFound --}}

</div>

@stack('scripts')
</body>
</html>
