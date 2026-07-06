@extends('layouts.backend')
@section('title', 'Chứng nhận năng lực AI')

@push('styles')
    @vite(['Modules/Assessment/resources/assets/sass/assessment.scss'], 'build/backend')
@endpush

@section('content')

{{-- ── Page header ─────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Chứng nhận AI Workforce</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Hành trình chứng nhận năng lực AI của bạn</p>
    </div>
    <div class="flex gap-2">
        @can('assessment.config')
        <a href="{{ route('backend.certs-admin.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Quản lý
        </a>
        @endcan
        <a href="{{ route('backend.workforce.me') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Hồ sơ của tôi
        </a>
    </div>
</div>

{{-- ── Earned certifications ────────────────────────────────────────────────── --}}
@if($certifications->count())
<div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
    <div class="card-body">
        <h2 class="card-title text-base mb-5">
            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            Chứng nhận của tôi ({{ $certifications->count() }})
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($certifications as $cert)
            @php
                $isActive  = $cert->status === 'active';
                $isExpired = $cert->status === 'expired';
                $levelColor = match($cert->definition?->level_code ?? '') {
                    'FOUNDATION'   => 'border-info bg-info/5',
                    'PRACTITIONER' => 'border-warning bg-warning/5',
                    'PROFESSIONAL' => 'border-success bg-success/5',
                    'LEADER'       => 'border-accent bg-accent/5',
                    default        => 'border-base-300',
                };
                $statusBadge = $isActive ? ['Hiệu lực', 'badge-success'] : ($isExpired ? ['Hết hạn', 'badge-error'] : ['Chờ xử lý', 'badge-ghost']);
            @endphp
            <div class="border {{ $levelColor }} rounded-xl p-5 flex gap-4">
                <div class="shrink-0">
                    <div class="w-12 h-12 rounded-xl {{ $isActive ? 'bg-success/10' : 'bg-base-200' }} flex items-center justify-center">
                        <svg class="w-6 h-6 {{ $isActive ? 'text-success' : 'text-base-content/30' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-semibold text-sm leading-snug">{{ $cert->definition?->name ?? $cert->definition?->cert_code ?? '—' }}</h3>
                        <span class="badge {{ $statusBadge[1] }} badge-xs shrink-0">{{ $statusBadge[0] }}</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 mt-1.5">
                        @if($cert->definition?->level_code)
                        @php
                            $levelBadge = match($cert->definition->level_code) {
                                'FOUNDATION'   => ['Foundation', 'badge-info'],
                                'PRACTITIONER' => ['Practitioner', 'badge-warning'],
                                'PROFESSIONAL' => ['Professional', 'badge-success'],
                                'LEADER'       => ['Leader', 'badge-accent'],
                                default        => [$cert->definition->level_code, 'badge-ghost'],
                            };
                        @endphp
                        <span class="badge {{ $levelBadge[1] }} badge-xs">{{ $levelBadge[0] }}</span>
                        @endif
                        <span class="text-xs text-base-content/40">{{ $cert->definition?->cert_type_code ?? '—' }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-x-4 mt-3 text-xs text-base-content/50">
                        <div>
                            <span class="block text-base-content/30">Cấp ngày</span>
                            <span class="font-medium text-base-content/70">{{ $cert->issued_at?->format('d/m/Y') ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="block text-base-content/30">Hết hạn</span>
                            <span class="font-medium {{ $isExpired ? 'text-error' : 'text-base-content/70' }}">
                                {{ $cert->expires_at?->format('d/m/Y') ?? 'Không hạn' }}
                            </span>
                        </div>
                    </div>
                    @if($cert->composite_score_at_issue !== null)
                    <div class="mt-2">
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-base-content/40">Điểm tổng hợp khi cấp</span>
                            <span class="font-semibold">{{ number_format($cert->composite_score_at_issue, 1) }}</span>
                        </div>
                        <div class="h-1.5 bg-base-200 rounded-full overflow-hidden">
                            <div class="h-1.5 bg-success rounded-full" style="width: {{ min($cert->composite_score_at_issue, 100) }}%"></div>
                        </div>
                    </div>
                    @endif
                    @if($cert->certificate_number)
                    <div class="flex items-center justify-between mt-2">
                        <p class="text-xs text-base-content/30 font-mono">{{ $cert->certificate_number }}</p>
                        @if($cert->qr_code_url)
                        <a href="{{ route('assessment.cert.verify', $cert->certificate_number) }}"
                           target="_blank"
                           class="btn btn-ghost btn-xs gap-1 text-base-content/40 hover:text-primary">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.24M16.24 12l1.76-1.76M3 3h2.01M3 3v2.01M3 21h2.01M3 21v-2.01M21 3h-2.01M21 3v2.01M21 21h-2.01M21 21v-2.01M7 7h.01M7 17h.01M17 7h.01"/></svg>
                            Xác minh
                        </a>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ── Readiness indicator (Part 3) ────────────────────────────────────────── --}}
@if($profile && count($readiness))
@php
    $unearned = collect($readiness)->filter(fn($r) => ! ($r['earned'] ?? false));
    $almostReady = $unearned->filter(fn($r) => ($r['ready'] ?? false));
    $withConditions = $unearned->filter(fn($r) => ! empty($r['conditions']));
@endphp
@if($withConditions->count())
<div class="card bg-base-100 shadow-sm border border-base-200 mb-5">
    <div class="card-body">
        <h2 class="card-title text-base mb-1">
            <svg class="w-4 h-4 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Tiến độ đạt chứng nhận
        </h2>
        <p class="text-sm text-base-content/40 mb-4">Tình trạng các điều kiện còn thiếu cho từng chứng nhận</p>
        <div class="space-y-4">
            @foreach($available as $def)
            @php
                $r = $readiness[$def->cert_code] ?? null;
                if (! $r || ($r['earned'] ?? false) || empty($r['conditions'])) continue;
                $metCount   = collect($r['conditions'])->where('met', true)->count();
                $totalCount = count($r['conditions']);
                $pct        = $totalCount > 0 ? round($metCount / $totalCount * 100) : 0;
                $isReady    = $r['ready'] ?? false;
            @endphp
            <div class="border {{ $isReady ? 'border-success/30 bg-success/5' : 'border-base-200' }} rounded-xl p-4">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <div>
                        <h3 class="font-medium text-sm">{{ $def->name }}</h3>
                        <div class="flex gap-1.5 mt-0.5">
                            @php
                                $lb = match($def->level_code) {
                                    'FOUNDATION'   => ['Foundation','badge-info'],
                                    'PRACTITIONER' => ['Practitioner','badge-warning'],
                                    'PROFESSIONAL' => ['Professional','badge-success'],
                                    'LEADER'       => ['Leader','badge-accent'],
                                    default        => [$def->level_code,'badge-ghost'],
                                };
                            @endphp
                            <span class="badge {{ $lb[1] }} badge-xs">{{ $lb[0] }}</span>
                            <span class="text-xs text-base-content/30 font-mono">{{ $def->cert_code }}</span>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-lg font-bold {{ $isReady ? 'text-success' : 'text-base-content/50' }}">{{ $metCount }}/{{ $totalCount }}</span>
                        <p class="text-xs text-base-content/30">điều kiện</p>
                    </div>
                </div>
                <div class="h-1.5 bg-base-200 rounded-full overflow-hidden mb-3">
                    <div class="h-1.5 {{ $isReady ? 'bg-success' : 'bg-primary' }} rounded-full transition-all" style="width: {{ $pct }}%"></div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                    @foreach($r['conditions'] as $cond)
                    <div class="flex items-center gap-2 text-xs {{ $cond['met'] ? 'text-success' : 'text-base-content/50' }}">
                        @if($cond['met'])
                        <svg class="w-3.5 h-3.5 shrink-0 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        @else
                        <svg class="w-3.5 h-3.5 shrink-0 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        @endif
                        <span>{{ $cond['label'] }}</span>
                        @if(! $cond['met'] && $cond['current'] !== null && $cond['required'] !== null)
                        <span class="text-base-content/30">(hiện: {{ $cond['current'] }}{{ $cond['unit'] ? ' '.$cond['unit'] : '' }})</span>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endif
@endif

{{-- ── Available certifications ─────────────────────────────────────────────── --}}
<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body">
        <h2 class="card-title text-base mb-5">
            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            Lộ trình chứng nhận
        </h2>
        @php
            $grouped = $available->groupBy('cert_type_code');
            $levels = ['FOUNDATION', 'PRACTITIONER', 'PROFESSIONAL', 'LEADER'];
            $levelLabels = ['FOUNDATION'=>'Foundation','PRACTITIONER'=>'Practitioner','PROFESSIONAL'=>'Professional','LEADER'=>'Leader'];
        @endphp
        @foreach($grouped as $typeCode => $defs)
        <div class="mb-6">
            <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wide mb-3">{{ $typeCode }}</h3>
            <div class="flex flex-wrap items-center gap-2">
                @foreach($levels as $lvl)
                @php
                    $def     = $defs->firstWhere('level_code', $lvl);
                    $earned  = $def && in_array($def->cert_code, $earnedCodes);
                    $r       = $def ? ($readiness[$def->cert_code] ?? null) : null;
                    $isReady = $r && ($r['ready'] ?? false);
                    $btnClass = $earned
                        ? 'bg-success/10 border-success text-success'
                        : ($isReady ? 'bg-warning/10 border-warning text-warning' : 'bg-base-200 border-base-300 text-base-content/40');
                @endphp
                <div class="flex items-center">
                    <div class="border {{ $btnClass }} rounded-lg px-3 py-2 text-center min-w-28 relative">
                        @if($earned)
                        <svg class="w-4 h-4 mx-auto mb-1 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                        @elseif($isReady)
                        <svg class="w-4 h-4 mx-auto mb-1 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        @else
                        <div class="w-4 h-4 mx-auto mb-1 rounded-full border-2 border-current opacity-30"></div>
                        @endif
                        <p class="text-xs font-medium">{{ $levelLabels[$lvl] ?? $lvl }}</p>
                        @if($def)
                        <p class="text-xs opacity-50">≥ {{ $def->min_workforce_score }}</p>
                        @endif
                        @if($isReady && ! $earned)
                        <span class="absolute -top-1.5 -right-1.5 badge badge-warning badge-xs font-medium">Sẵn sàng</span>
                        @endif
                    </div>
                    @if(!$loop->last)
                    <svg class="w-4 h-4 mx-1 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>

@endsection

@push('scripts')
    @vite(['Modules/Assessment/resources/assets/js/assessment.js'], 'build/backend')
@endpush
