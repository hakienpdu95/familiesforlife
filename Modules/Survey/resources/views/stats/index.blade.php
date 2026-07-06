@extends('layouts.backend')

@section('title', 'Thống kê — ' . $survey->title)


@section('content')
@php
    $totalByDaySum  = collect($byDay)->sum('count');
    $peakDay        = collect($byDay)->sortByDesc('count')->first();
    $avgPerDay      = $totalByDaySum > 0 ? round($totalByDaySum / 30, 1) : 0;
    $totalFields    = count($stats['fields']);

    // Group fields by section, ordered by section_order then by original field order
    $fieldsBySectionRaw = collect($stats['fields'])
        ->groupBy(fn ($f) => $f['section_title'] ?? 'Chung');
    $orderedSections = $fieldsBySectionRaw
        ->map(fn ($g) => $g)
        ->sortBy(fn ($g) => $g->first()['section_order'] ?? 0);
    $allSections = $orderedSections->keys()->all();

    $typeBadgeMap = [
        'Text'     => ['cls' => 'badge-info',      'label' => 'Văn bản'],
        'Textarea' => ['cls' => 'badge-info',      'label' => 'Đoạn văn'],
        'Select'   => ['cls' => 'badge-secondary', 'label' => 'Chọn 1'],
        'Radio'    => ['cls' => 'badge-secondary', 'label' => 'Radio'],
        'Checkbox' => ['cls' => 'badge-secondary', 'label' => 'Checkbox'],
        'Number'   => ['cls' => 'badge-accent',    'label' => 'Số'],
        'Rating'   => ['cls' => 'badge-warning',   'label' => 'Đánh giá'],
        'Boolean'  => ['cls' => 'badge-primary',   'label' => 'Có/Không'],
        'Date'     => ['cls' => 'badge-ghost',     'label' => 'Ngày'],
    ];
@endphp

<div class="space-y-6" x-data="{ activeSection: null }">

    {{-- ── Header ─────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Báo cáo thống kê</h1>
            <p class="text-sm text-base-content/50 mt-0.5 max-w-lg truncate">
                {{ $survey->title }}
                <span class="badge badge-xs badge-ghost ml-1 align-middle">v{{ $survey->version }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            @can('survey.export')
            <a href="{{ route('backend.surveys.responses.export', $survey) }}"
               class="btn btn-success btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export Excel
            </a>
            @endcan
            @can('survey.view_responses')
            <a href="{{ route('backend.surveys.responses.index', $survey) }}"
               class="btn btn-outline btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Xem phản hồi
            </a>
            @endcan
            <a href="{{ route('backend.surveys.edit', $survey) }}" class="btn btn-ghost btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                </svg>
                Quay lại
            </a>
        </div>
    </div>

    {{-- ── Summary cards ───────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">

        {{-- Total responses --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-base-content leading-none">{{ number_format($stats['total_responses']) }}</p>
                <p class="text-xs text-base-content/50 mt-0.5">Tổng phản hồi</p>
            </div>
        </div>

        {{-- 30-day submissions --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-info/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-base-content leading-none">{{ number_format($totalByDaySum) }}</p>
                <p class="text-xs text-base-content/50 mt-0.5">30 ngày gần nhất</p>
            </div>
        </div>

        {{-- Daily average --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-success/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-base-content leading-none">{{ $avgPerDay }}</p>
                <p class="text-xs text-base-content/50 mt-0.5">Trung bình/ngày</p>
            </div>
        </div>

        {{-- Peak day --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-warning/10 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-2xl font-bold text-base-content leading-none">{{ $peakDay ? $peakDay['count'] : 0 }}</p>
                <p class="text-xs text-base-content/50 mt-0.5">
                    @if($peakDay && $peakDay['count'] > 0)
                        Cao nhất {{ \Carbon\Carbon::parse($peakDay['day'])->format('d/m') }}
                    @else
                        Cao nhất (30 ngày)
                    @endif
                </p>
            </div>
        </div>

    </div>

    {{-- ── Daily submissions chart ──────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-4 pb-3">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                <h2 class="font-semibold text-base-content">Phân bổ phản hồi theo ngày</h2>
                <div class="flex items-center gap-4 text-xs text-base-content/50">
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded bg-primary/40 inline-block"></span>
                        Hàng ngày
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-4 border-t-2 border-primary inline-block"></span>
                        TB 7 ngày
                    </span>
                </div>
            </div>
            <div id="dailyChart" class="w-full" style="height:260px"></div>
        </div>
    </div>

    {{-- ── Field stats ──────────────────────────────────────────────────── --}}
    <div>
        {{-- Section header + tabs --}}
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <div>
                <h2 class="font-semibold text-base-content">Thống kê theo câu hỏi</h2>
                <p class="text-xs text-base-content/40 mt-0.5">{{ $totalFields }} câu hỏi • {{ $stats['total_responses'] }} phản hồi</p>
            </div>
        </div>

        {{-- Section filter tabs --}}
        @if(count($allSections) > 1)
        <div class="flex gap-1.5 flex-wrap mb-4">
            <button @click="activeSection = null"
                    :class="activeSection === null ? 'btn-primary' : 'btn-ghost text-base-content/60'"
                    class="btn btn-xs rounded-full transition-all">
                Tất cả
                <span class="badge badge-xs ml-1">{{ $totalFields }}</span>
            </button>
            @foreach($allSections as $sectionName)
            <button @click="activeSection = {{ json_encode($sectionName) }}"
                    :class="activeSection === {{ json_encode($sectionName) }} ? 'btn-primary' : 'btn-ghost text-base-content/60'"
                    class="btn btn-xs rounded-full transition-all">
                {{ Str::limit($sectionName, 30) }}
                <span class="badge badge-xs ml-1">{{ $fieldsBySectionRaw->get($sectionName, collect())->count() }}</span>
            </button>
            @endforeach
        </div>
        @endif

        @if($totalFields === 0)
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body text-center py-16">
                <svg class="w-12 h-12 text-base-content/20 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-base-content/40">Chưa có câu hỏi nào hoặc chưa có phản hồi.</p>
            </div>
        </div>
        @else

        {{-- Field cards grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($stats['fields'] as $fieldIdx => $field)
            @php
                $sectionTitle  = $field['section_title'] ?? 'Chung';
                $typeMeta      = $typeBadgeMap[$field['field_type']] ?? ['cls' => 'badge-ghost', 'label' => $field['field_type']];
                $fieldStats    = $field['stats'];
                $statsType     = $fieldStats['type'] ?? null;

                // Compute per-card answer count for header display
                $answerCount = match($statsType) {
                    'choice'        => collect($fieldStats['distribution'] ?? [])->sum('count'),
                    'number',
                    'rating'        => $fieldStats['count'] ?? 0,
                    'boolean'       => $fieldStats['total'] ?? 0,
                    'text'          => $fieldStats['count'] ?? 0,
                    default         => 0,
                };
            @endphp
            <div x-show="activeSection === null || activeSection === {{ json_encode($sectionTitle) }}"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="card bg-base-100 border border-base-200 shadow-sm hover:shadow-md transition-shadow">
                <div class="card-body p-4">

                    {{-- Card header --}}
                    <div class="flex items-start justify-between gap-2 mb-1">
                        <p class="font-medium text-sm text-base-content leading-snug flex-1">{{ $field['label'] }}</p>
                        <div class="flex items-center gap-1.5 shrink-0">
                            @if($answerCount > 0)
                            <span class="text-xs text-base-content/40 font-mono">{{ number_format($answerCount) }}</span>
                            @endif
                            <span class="badge badge-xs {{ $typeMeta['cls'] }} font-medium">{{ $typeMeta['label'] }}</span>
                        </div>
                    </div>
                    <p class="text-xs text-base-content/30 mb-3">{{ $sectionTitle }}</p>

                    {{-- ── Choice (select / radio / checkbox) ── --}}
                    @if($statsType === 'choice')
                        @if(empty($fieldStats['distribution']))
                        <div class="flex items-center gap-2 py-3 text-base-content/40">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                            <span class="text-xs italic">Chưa có câu trả lời.</span>
                        </div>
                        @else
                        @php
                            $sortedDist  = collect($fieldStats['distribution'])->sortByDesc('count')->values();
                            $topCount    = max(1, $sortedDist->first()['count'] ?? 1);
                            $totalChoice = $sortedDist->sum('count');
                        @endphp
                        <div class="space-y-2">
                            @foreach($sortedDist->take(7) as $rank => $opt)
                            @php
                                $barW = $topCount > 0 ? ($opt['count'] / $topCount * 100) : 0;
                            @endphp
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    @if($rank === 0 && $opt['count'] > 0)
                                    <span class="badge badge-xs badge-primary shrink-0 px-1">TOP</span>
                                    @else
                                    <span class="text-xs text-base-content/30 w-5 text-center shrink-0">{{ $rank + 1 }}</span>
                                    @endif
                                    <span class="text-xs text-base-content/80 flex-1 truncate">{{ $opt['label'] }}</span>
                                    <span class="text-xs shrink-0 font-mono text-base-content/60">
                                        {{ $opt['count'] }}<span class="text-base-content/35 ml-0.5">({{ $opt['percent'] }}%)</span>
                                    </span>
                                </div>
                                <div class="h-2 bg-base-200 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-700 {{ $rank === 0 ? 'bg-primary' : ($rank === 1 ? 'bg-primary/65' : 'bg-primary/35') }}"
                                         style="width: {{ $barW }}%"></div>
                                </div>
                            </div>
                            @endforeach
                            @if($sortedDist->count() > 7)
                            <p class="text-xs text-base-content/35 text-center pt-0.5">+{{ $sortedDist->count() - 7 }} lựa chọn khác</p>
                            @endif
                        </div>
                        <div class="flex items-center justify-between mt-3 pt-2.5 border-t border-base-200 text-xs text-base-content/35">
                            <span>{{ number_format($totalChoice) }} lượt chọn</span>
                            <span>{{ count($fieldStats['distribution']) }} lựa chọn</span>
                        </div>
                        @endif

                    {{-- ── Rating ── --}}
                    @elseif($statsType === 'rating')
                        @php
                            $rAvg  = $fieldStats['avg'];
                            $rDist = collect($fieldStats['distribution'] ?? [])->sortByDesc('score');
                            $maxScore = $fieldStats['max'] ?? 5;
                        @endphp
                        @if($fieldStats['count'] === 0)
                        <div class="flex items-center gap-2 py-3 text-base-content/40">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                            <span class="text-xs italic">Chưa có câu trả lời.</span>
                        </div>
                        @else
                        {{-- Average star display --}}
                        <div class="flex items-center gap-3 mb-4">
                            <span class="text-4xl font-bold text-warning leading-none">{{ $rAvg !== null ? number_format($rAvg, 1) : '—' }}</span>
                            <div>
                                <div class="flex gap-0.5 mb-0.5">
                                    @for($s = 1; $s <= 5; $s++)
                                    <svg class="w-4 h-4 {{ $s <= round($rAvg ?? 0) ? 'text-warning' : 'text-base-content/15' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    @endfor
                                </div>
                                <p class="text-xs text-base-content/40">{{ number_format($fieldStats['count']) }} đánh giá</p>
                            </div>
                        </div>
                        {{-- Score distribution --}}
                        @if($rDist->count() > 0)
                        <div class="space-y-1.5">
                            @foreach($rDist as $rd)
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-base-content/50 w-5 text-right shrink-0">{{ $rd['score'] }}★</span>
                                <div class="flex-1 h-2 bg-base-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-warning rounded-full transition-all duration-700"
                                         style="width: {{ $rd['percent'] }}%"></div>
                                </div>
                                <span class="text-xs text-base-content/45 w-20 text-right shrink-0 font-mono">
                                    {{ $rd['count'] }} <span class="text-base-content/30">({{ $rd['percent'] }}%)</span>
                                </span>
                            </div>
                            @endforeach
                        </div>
                        @endif
                        @endif

                    {{-- ── Number ── --}}
                    @elseif($statsType === 'number')
                        @php
                            $nAvg   = $fieldStats['avg'];
                            $nMin   = $fieldStats['min'];
                            $nMax   = $fieldStats['max'];
                            $nRange = ($nMax !== null && $nMin !== null && $nMax > $nMin) ? ($nMax - $nMin) : 0;
                            $nAvgPct = ($nRange > 0 && $nAvg !== null) ? (($nAvg - $nMin) / $nRange * 100) : 50;
                            $nAvgPct = max(2, min(98, $nAvgPct));
                        @endphp
                        @if($fieldStats['count'] === 0)
                        <div class="flex items-center gap-2 py-3 text-base-content/40">
                            <span class="text-xs italic">Chưa có câu trả lời.</span>
                        </div>
                        @else
                        <div class="space-y-3">
                            <div class="flex items-end gap-5">
                                <div>
                                    <p class="text-3xl font-bold text-accent leading-none">
                                        {{ $nAvg !== null ? number_format($nAvg, 1) : '—' }}
                                    </p>
                                    <p class="text-xs text-base-content/45 mt-0.5">Trung bình</p>
                                </div>
                                <div class="pb-0.5">
                                    <p class="text-lg font-semibold text-base-content/70 leading-none">{{ number_format($fieldStats['count']) }}</p>
                                    <p class="text-xs text-base-content/45 mt-0.5">Câu trả lời</p>
                                </div>
                            </div>
                            @if($nMin !== null && $nMax !== null && $nMin !== $nMax)
                            <div>
                                <div class="flex justify-between text-xs text-base-content/35 mb-1">
                                    <span>{{ number_format($nMin, 1) }}</span>
                                    <span class="text-accent font-medium">TB {{ number_format($nAvg, 1) }}</span>
                                    <span>{{ number_format($nMax, 1) }}</span>
                                </div>
                                <div class="h-2.5 bg-base-200 rounded-full relative overflow-visible">
                                    <div class="absolute inset-0 bg-accent/20 rounded-full"></div>
                                    <div class="absolute top-0 bottom-0 w-1.5 bg-accent rounded-full shadow-sm transform -translate-x-1/2"
                                         style="left: {{ $nAvgPct }}%"></div>
                                </div>
                                <div class="flex justify-between text-xs text-base-content/30 mt-1">
                                    <span>Min</span><span>Max</span>
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif

                    {{-- ── Boolean ── --}}
                    @elseif($statsType === 'boolean')
                        @php
                            $bTotal  = $fieldStats['total'];
                            $yesPct  = $bTotal > 0 ? round($fieldStats['yes_count'] / $bTotal * 100) : 0;
                            $noPct   = 100 - $yesPct;
                        @endphp
                        @if($bTotal === 0)
                        <div class="flex items-center gap-2 py-3 text-base-content/40">
                            <span class="text-xs italic">Chưa có câu trả lời.</span>
                        </div>
                        @else
                        <div class="space-y-3">
                            <div class="flex gap-2">
                                <div class="flex-1 bg-success/10 border border-success/20 rounded-xl p-3 text-center">
                                    <p class="text-2xl font-bold text-success leading-none">{{ $yesPct }}%</p>
                                    <p class="text-xs font-semibold text-success/70 mt-0.5">Có</p>
                                    <p class="text-xs text-base-content/40 mt-0.5">{{ number_format($fieldStats['yes_count']) }}</p>
                                </div>
                                <div class="flex-1 bg-error/10 border border-error/20 rounded-xl p-3 text-center">
                                    <p class="text-2xl font-bold text-error leading-none">{{ $noPct }}%</p>
                                    <p class="text-xs font-semibold text-error/70 mt-0.5">Không</p>
                                    <p class="text-xs text-base-content/40 mt-0.5">{{ number_format($fieldStats['no_count']) }}</p>
                                </div>
                            </div>
                            <div class="h-3 rounded-full overflow-hidden flex">
                                @if($yesPct > 0)
                                <div class="bg-success h-full transition-all duration-700" style="width: {{ $yesPct }}%"></div>
                                @endif
                                @if($noPct > 0)
                                <div class="bg-error/60 h-full flex-1"></div>
                                @endif
                            </div>
                            <p class="text-xs text-base-content/35 text-right">{{ number_format($bTotal) }} phản hồi</p>
                        </div>
                        @endif

                    {{-- ── Text / Textarea ── --}}
                    @elseif($statsType === 'text')
                    <div class="flex items-center gap-4 py-3">
                        <div class="w-14 h-14 rounded-2xl bg-info/10 flex items-center justify-center shrink-0">
                            <svg class="w-7 h-7 text-info/70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-base-content leading-none">{{ number_format($fieldStats['count']) }}</p>
                            <p class="text-sm text-base-content/50 mt-0.5">câu trả lời văn bản</p>
                        </div>
                    </div>

                    @else
                    <p class="text-xs text-base-content/35 italic py-3">Không có dữ liệu thống kê.</p>
                    @endif

                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ── Scoring section (chỉ hiện khi survey có assessment_code) ────── --}}
    @if($scoringData)
    <div class="space-y-4 pt-2">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold text-base-content">Kết quả chấm điểm</h2>
            <a href="{{ route('backend.surveys.results.summary', $survey) }}"
               class="btn btn-ghost btn-xs gap-1 text-base-content/50">
                Xem chi tiết
                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
        </div>

        {{-- KPI cards --}}
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm text-center">
                <p class="text-3xl font-bold text-primary">{{ number_format($scoringData['total_scored']) }}</p>
                <p class="text-xs text-base-content/50 mt-1">Đã chấm điểm</p>
            </div>
            <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm text-center">
                <p class="text-3xl font-bold text-secondary">
                    {{ $scoringData['avg_overall'] !== null ? round($scoringData['avg_overall'], 1) : '—' }}
                </p>
                <p class="text-xs text-base-content/50 mt-1">Điểm TB</p>
            </div>
        </div>

        @if($scoringData['total_scored'] > 0)
        {{-- Maturity distribution --}}
        @if($scoringData['maturity_levels']->isNotEmpty())
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="px-5 py-3 border-b border-base-200 text-sm font-semibold">Phân bố mức độ trưởng thành</div>
            <div class="p-5 space-y-3">
                @php $maxCount = $scoringData['maturity_distribution']->max() ?: 1; @endphp
                @foreach($scoringData['maturity_levels'] as $level)
                @php
                    $cnt  = $scoringData['maturity_distribution'][$level->level_code] ?? 0;
                    $pct  = $scoringData['total_scored'] > 0
                        ? round($cnt / $scoringData['total_scored'] * 100, 1) : 0;
                    $barW = round($cnt / $maxCount * 100);
                @endphp
                <div class="flex items-center gap-3">
                    <p class="text-xs font-medium text-base-content w-32 truncate" title="{{ $level->label }}">{{ $level->label }}</p>
                    <div class="flex-1 flex items-center gap-2">
                        <div class="flex-1 bg-base-200 rounded-full h-2.5 overflow-hidden">
                            <div class="h-2.5 rounded-full bg-info" style="width: {{ $barW }}%"></div>
                        </div>
                        <span class="text-xs font-semibold w-6 text-right">{{ $cnt }}</span>
                        <span class="text-xs text-base-content/40 w-10 text-right">{{ $pct }}%</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Domain avg bar chart --}}
        @if($scoringData['domains']->isNotEmpty())
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="px-5 py-3 border-b border-base-200 text-sm font-semibold">Điểm trung bình theo domain</div>
            <div class="p-5">
                <div id="domainAvgChart" class="h-48"></div>
            </div>
        </div>
        @endif

        @endif {{-- total_scored > 0 --}}
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>
<script>
(function () {
    const byDay = @json($byDay);
    const dom   = document.getElementById('dailyChart');
    if (!dom || !byDay.length) return;

    const isDark = document.documentElement.getAttribute('data-theme') !== 'light';
    const chart  = echarts.init(dom, isDark ? 'dark' : null, { renderer: 'canvas' });

    const labels = byDay.map(d => {
        const [, m, day] = d.day.split('-');
        return `${day}/${m}`;
    });

    const counts = byDay.map(d => d.count);

    // 7-day moving average
    const ma7 = counts.map((_, i) => {
        const slice = counts.slice(Math.max(0, i - 6), i + 1);
        return +(slice.reduce((s, v) => s + v, 0) / slice.length).toFixed(1);
    });

    const maxCount = Math.max(...counts, 1);

    chart.setOption({
        tooltip: {
            trigger: 'axis',
            backgroundColor: isDark ? '#1f2937' : '#fff',
            borderColor: isDark ? '#374151' : '#e5e7eb',
            textStyle: { color: isDark ? '#f3f4f6' : '#1f2937', fontSize: 12 },
            axisPointer: { type: 'cross', label: { backgroundColor: '#6366f1' } },
            formatter (params) {
                const i = params[0].dataIndex;
                const day = byDay[i].day;
                let html = `<div style="font-weight:600;margin-bottom:4px">${day}</div>`;
                params.forEach(p => {
                    html += `<div style="display:flex;align-items:center;gap:6px;margin-top:2px">
                        ${p.marker} <span>${p.seriesName}:</span> <strong>${p.value}</strong>
                    </div>`;
                });
                return html;
            },
        },
        legend: { show: false },
        grid: { left: 40, right: 12, top: 12, bottom: 32 },
        xAxis: {
            type: 'category',
            data: labels,
            axisLabel: {
                interval: Math.ceil(byDay.length / 10) - 1,
                fontSize: 11,
                color: isDark ? '#9ca3af' : '#6b7280',
            },
            axisTick: { show: false },
            axisLine: { lineStyle: { color: isDark ? '#374151' : '#e5e7eb' } },
        },
        yAxis: {
            type: 'value',
            minInterval: 1,
            min: 0,
            splitLine: { lineStyle: { type: 'dashed', color: isDark ? '#374151' : '#f0f0f0' } },
            axisLabel: { fontSize: 11, color: isDark ? '#9ca3af' : '#6b7280' },
        },
        series: [
            {
                name: 'Phản hồi',
                type: 'bar',
                data: counts,
                barMaxWidth: 18,
                itemStyle: {
                    color: {
                        type: 'linear', x: 0, y: 0, x2: 0, y2: 1,
                        colorStops: [
                            { offset: 0, color: 'rgba(99,102,241,0.75)' },
                            { offset: 1, color: 'rgba(99,102,241,0.25)' },
                        ],
                    },
                    borderRadius: [4, 4, 0, 0],
                },
                emphasis: { itemStyle: { color: '#6366f1' } },
            },
            {
                name: 'TB 7 ngày',
                type: 'line',
                data: ma7,
                smooth: 0.4,
                symbol: 'none',
                lineStyle: { color: '#6366f1', width: 2, type: 'solid' },
                itemStyle: { color: '#6366f1' },
                z: 10,
            },
        ],
    });

    new ResizeObserver(() => chart.resize()).observe(dom);
})();

// Domain avg chart
(function () {
    @if($scoringData && $scoringData['domains']->isNotEmpty() && $scoringData['total_scored'] > 0)
    const domainDom = document.getElementById('domainAvgChart');
    if (!domainDom) return;

    const isDark2 = document.documentElement.getAttribute('data-theme') !== 'light';
    const domainChart = echarts.init(domainDom, isDark2 ? 'dark' : null, { renderer: 'canvas' });

    const domains   = @json($scoringData['domains']->pluck('label', 'domain_code'));
    const avgScores = @json($scoringData['avg_domain_scores']);

    const labels = Object.keys(domains).map(code => domains[code]);
    const values = Object.keys(domains).map(code => parseFloat(avgScores[code] ?? 0));

    domainChart.setOption({
        tooltip: { trigger: 'axis', formatter: p => `${p[0].name}<br/><strong>${p[0].value}</strong> / 100` },
        grid: { left: 130, right: 20, top: 8, bottom: 8 },
        xAxis: { type: 'value', min: 0, max: 100, splitLine: { lineStyle: { type: 'dashed' } }, axisLabel: { fontSize: 11 } },
        yAxis: { type: 'category', data: labels, axisLabel: { fontSize: 11 } },
        series: [{
            type: 'bar',
            data: values,
            barMaxWidth: 20,
            label: { show: true, position: 'right', fontSize: 11, formatter: p => p.value.toFixed(1) },
            itemStyle: {
                color: { type: 'linear', x: 0, y: 0, x2: 1, y2: 0,
                    colorStops: [
                        { offset: 0, color: 'rgba(236,72,153,0.6)' },
                        { offset: 1, color: 'rgba(236,72,153,1)' },
                    ]
                },
                borderRadius: [0, 4, 4, 0],
            },
        }],
    });

    new ResizeObserver(() => domainChart.resize()).observe(domainDom);
    @endif
})();
</script>
@endpush
