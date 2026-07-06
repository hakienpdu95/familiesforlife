@extends('layouts.backend')
@section('title', 'Lộ trình nghề nghiệp')

@push('styles')
    @vite(['Modules/Assessment/resources/assets/sass/assessment.scss'], 'build/backend')
@endpush

@section('content')

@if(session('success'))
<div class="alert alert-success mb-4 py-2 px-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('flash_success'))
<div class="alert alert-success mb-4 py-2 px-4 text-sm">{{ session('flash_success') }}</div>
@endif
@if(session('info'))
<div class="alert alert-info mb-4 py-2 px-4 text-sm">{{ session('info') }}</div>
@endif

{{-- ── Header ─────────────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Lộ trình nghề nghiệp số</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            {{ $profile ? 'Hành trình cá nhân của bạn theo chuẩn TDWCF' : 'Tổng quan lộ trình chuyển đổi số' }}
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('backend.sandbox.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            AI Sandbox
        </a>
        <a href="{{ route('backend.workforce.me') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Hồ sơ của tôi
        </a>
        @can('assessment.config')
        <a href="{{ route('backend.career-pathway-admin.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Quản lý
        </a>
        @endcan
    </div>
</div>

{{-- ── Current level banner ─────────────────────────────────────────────────── --}}
@if($profile)
@php
$maturityLabels = [
    'DIGITAL_BEGINNER'     => ['Khởi đầu số',    'alert-info',    'text-info'],
    'DIGITAL_AWARE'        => ['Nhận thức số',   'alert-info',    'text-info'],
    'DIGITAL_PRACTITIONER' => ['Thực hành số',   'alert-warning', 'text-warning'],
    'DIGITAL_PROFESSIONAL' => ['Chuyên nghiệp',  'alert-success', 'text-success'],
    'DIGITAL_LEADER'       => ['Dẫn dắt số',     'alert-success', 'text-success'],
];
$ml = $maturityLabels[$currentLevel] ?? ['—', 'alert-info', 'text-info'];
@endphp

<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
    {{-- Level card --}}
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Cấp độ hiện tại</div>
        <div class="stat-value text-xl {{ $ml[2] }}">{{ $ml[0] }}</div>
        <div class="stat-desc">{{ $currentLevel }}</div>
    </div>
    {{-- TDWCF Score --}}
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Điểm TDWCF</div>
        <div class="stat-value text-xl text-primary">{{ $profile->tdwcf_score ? number_format($profile->tdwcf_score, 1) : '—' }}</div>
        <div class="stat-desc">/ 100 điểm</div>
    </div>
    {{-- Sandbox avg --}}
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Sandbox TB</div>
        <div class="stat-value text-xl text-accent">{{ $profile->sandbox_score_avg ? number_format($profile->sandbox_score_avg, 1) : '—' }}</div>
        <div class="stat-desc">{{ $profile->sandbox_sessions_total ?? 0 }} phiên đã thực hành</div>
    </div>
</div>

{{-- ── Readiness banner for current step ──────────────────────────────────── --}}
@if($readiness && $readiness['step'] && count($readiness['conditions']) > 0)
<div class="card mb-6 border {{ $readiness['ready'] ? 'border-success/30 bg-success/5' : 'border-warning/30 bg-warning/5' }}">
    <div class="card-body py-4 px-5">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <p class="text-sm font-semibold {{ $readiness['ready'] ? 'text-success' : 'text-warning' }} mb-2">
                    {{ $readiness['ready'] ? '✓ Đủ điều kiện thăng cấp!' : 'Điều kiện thăng cấp — Bước hiện tại' }}
                </p>
                <div class="flex flex-wrap gap-3">
                    @foreach($readiness['conditions'] as $cond)
                    <div class="flex items-center gap-1.5 text-xs">
                        @if($cond['met'])
                        <svg class="w-4 h-4 text-success shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        @else
                        <svg class="w-4 h-4 text-base-content/30 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                        <span class="{{ $cond['met'] ? 'text-success' : 'text-base-content/50' }}">
                            {{ $cond['type'] === 'cert' ? 'Cert: ' : 'Sandbox: ' }}
                            <span class="font-mono font-medium">{{ $cond['code'] }}</span>
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>
            @if($readiness['ready'])
            <form method="POST" action="{{ route('backend.career-pathway.check-level') }}">
                @csrf
                <button type="submit" class="btn btn-success btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    Thăng cấp ngay
                </button>
            </form>
            @endif
        </div>
    </div>
</div>
@endif

@else
<div class="alert alert-warning mb-6 py-3 px-5">
    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    <span class="text-sm">Bạn chưa có hồ sơ Digital Twin. Hãy hoàn thành bài đánh giá TDWCF để bắt đầu lộ trình.</span>
</div>
@endif

{{-- ── Steps timeline ───────────────────────────────────────────────────────── --}}
<div class="space-y-4">
    @foreach($steps as $step)
    @php
        $fromOrder = $levelOrder[$step->from_level] ?? 0;
        $isDone    = $fromOrder < $currentOrder;
        $isCurrent = $step->from_level === $currentLevel;
        $isLocked  = $fromOrder > $currentOrder;

        $cardClass = match(true) {
            $isDone    => 'border-success/30 bg-success/5',
            $isCurrent => 'border-primary shadow-md',
            default    => 'border-base-200 opacity-55',
        };
        $dotClass = match(true) {
            $isDone    => 'bg-success text-success-content',
            $isCurrent => 'bg-primary text-primary-content ring-4 ring-primary/20',
            default    => 'bg-base-300 text-base-content/30',
        };

        $envName  = $sandboxEnvs[$step->recommended_sandbox_env_code]?->name ?? $step->recommended_sandbox_env_code;
        $certName = $certDefs[$step->required_cert_code]?->name ?? $step->required_cert_code;
    @endphp
    <div class="relative">
        {{-- Connector line --}}
        @if(! $loop->last)
        <div class="absolute left-5 top-12 w-0.5 h-full {{ $isDone ? 'bg-success/40' : 'bg-base-200' }} -translate-x-1/2 z-0"></div>
        @endif

        <div class="card border {{ $cardClass }} bg-base-100 relative z-10">
            <div class="card-body p-5">
                <div class="flex items-start gap-4">
                    {{-- Step dot --}}
                    <div class="w-10 h-10 rounded-full {{ $dotClass }} flex items-center justify-center shrink-0 font-bold text-sm mt-0.5">
                        @if($isDone)
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        @else
                        {{ $step->step_order }}
                        @endif
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-3 flex-wrap">
                            <div>
                                <h3 class="font-semibold text-base-content {{ $isLocked ? 'text-base-content/40' : '' }}">
                                    {{ $step->title }}
                                </h3>
                                <div class="flex items-center gap-2 mt-1 flex-wrap">
                                    <span class="badge badge-ghost badge-xs">{{ $step->from_level }}</span>
                                    @if($step->from_level !== $step->to_level)
                                    <svg class="w-3 h-3 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    <span class="badge badge-ghost badge-xs">{{ $step->to_level }}</span>
                                    @endif
                                    @if($step->estimated_weeks)
                                    <span class="text-xs text-base-content/40">~{{ $step->estimated_weeks }} tuần</span>
                                    @endif
                                </div>
                            </div>
                            @if($isCurrent)
                            <span class="badge badge-primary badge-sm shrink-0">Bước hiện tại</span>
                            @elseif($isDone)
                            <span class="badge badge-success badge-sm shrink-0">Hoàn thành</span>
                            @endif
                        </div>

                        @if($step->description)
                        <p class="text-sm text-base-content/60 mt-2">{{ $step->description }}</p>
                        @endif

                        {{-- Resources: cert, sandbox, kc --}}
                        @if(! $isLocked && ($step->required_cert_code || $step->recommended_sandbox_env_code || $step->recommended_kc_tag))
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4">
                            @if($step->required_cert_code)
                            <a href="{{ route('backend.certifications.index') }}"
                               class="flex items-center gap-2 text-xs group hover:text-warning transition-colors">
                                <svg class="w-3.5 h-3.5 text-warning shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                                <span class="text-base-content/60 group-hover:text-warning">
                                    Cert cần đạt:<br>
                                    <span class="font-medium">{{ $certName }}</span>
                                </span>
                            </a>
                            @endif

                            @if($step->recommended_sandbox_env_code)
                            <a href="{{ route('backend.sandbox.index') }}?env_code={{ $step->recommended_sandbox_env_code }}"
                               class="flex items-center gap-2 text-xs group hover:text-info transition-colors">
                                <svg class="w-3.5 h-3.5 text-info shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                <span class="text-base-content/60 group-hover:text-info">
                                    Sandbox thực hành:<br>
                                    <span class="font-medium">{{ $envName }}</span>
                                </span>
                            </a>
                            @endif

                            @if($step->recommended_kc_tag)
                            <div class="flex items-start gap-2 text-xs">
                                <svg class="w-3.5 h-3.5 text-accent shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                <span class="text-base-content/60">
                                    Tài liệu KC:<br>
                                    @if($isCurrent && $currentStepKcItems->isNotEmpty())
                                        <span class="font-medium text-accent">{{ $currentStepKcItems->count() }} tài liệu</span>
                                    @else
                                        <span class="font-medium font-mono">{{ Str::limit($step->recommended_kc_tag, 35) }}</span>
                                    @endif
                                </span>
                            </div>
                            @endif
                        </div>
                        @endif

                        {{-- KC Items list — current step only --}}
                        @if($isCurrent && $currentStepKcItems->isNotEmpty())
                        @php
                            $completedCount = $kcProgress->where('status','completed')->count();
                            $totalKc = $currentStepKcItems->count();
                            $pct = $totalKc ? round($completedCount / $totalKc * 100) : 0;
                            $diffLabels = [1=>'Cơ bản',2=>'Trung cấp',3=>'Nâng cao'];
                            $diffBadge  = [1=>'badge-info',2=>'badge-warning',3=>'badge-error'];
                        @endphp
                        <div class="mt-4 pt-4 border-t border-base-200">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs font-semibold text-base-content/60 uppercase tracking-wide">
                                    Tài liệu KC bước này
                                </p>
                                <span class="text-xs text-base-content/50">{{ $completedCount }}/{{ $totalKc }} hoàn thành</span>
                            </div>
                            <div class="w-full bg-base-200 rounded-full h-1.5 mb-3">
                                <div class="bg-accent h-1.5 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                            </div>
                            <div class="space-y-2">
                                @foreach($currentStepKcItems as $kc)
                                @php
                                    $prog     = $kcProgress->get($kc->id);
                                    $isDone   = $prog?->status === 'completed';
                                    $inProg   = $prog?->status === 'in_progress';
                                @endphp
                                <div class="flex items-center justify-between gap-2 rounded-lg border {{ $isDone ? 'border-success/30 bg-success/5' : 'border-base-200 bg-base-50' }} px-3 py-2">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm leading-snug {{ $isDone ? 'line-through text-base-content/40' : '' }}">{{ $kc->title }}</p>
                                        <div class="flex gap-1 mt-0.5 flex-wrap">
                                            @if($kc->domain_code)
                                            <span class="badge badge-primary badge-xs">{{ $kc->domain_code }}</span>
                                            @endif
                                            @if($kc->difficulty)
                                            <span class="badge {{ $diffBadge[$kc->difficulty] ?? 'badge-ghost' }} badge-xs">{{ $diffLabels[$kc->difficulty] ?? '' }}</span>
                                            @endif
                                            @if($isDone)
                                            <span class="badge badge-success badge-xs">Hoàn thành</span>
                                            @elseif($inProg)
                                            <span class="badge badge-warning badge-xs">Đang học</span>
                                            @endif
                                        </div>
                                    </div>
                                    @if(! $isDone)
                                    <div class="flex gap-1 shrink-0">
                                        @if(! $inProg)
                                        <form method="POST" action="{{ route('backend.kc-progress.start', $kc) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-ghost btn-xs text-info hover:bg-info/10" title="Bắt đầu học">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            </button>
                                        </form>
                                        @endif
                                        <form method="POST" action="{{ route('backend.kc-progress.complete', $kc) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-ghost btn-xs text-success hover:bg-success/10" title="Đánh dấu hoàn thành">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                    @else
                                    <svg class="w-5 h-5 text-success shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- CTA for CURRENT step only --}}
                        @if($isCurrent && $step->recommended_sandbox_env_code)
                        <div class="mt-4 pt-4 border-t border-base-200 flex flex-wrap gap-2">
                            <a href="{{ route('backend.sandbox.index') }}?env_code={{ $step->recommended_sandbox_env_code }}"
                               class="btn btn-primary btn-sm gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Bắt đầu luyện tập — {{ $envName }}
                            </a>
                            @if($step->required_cert_code)
                            <a href="{{ route('backend.certifications.index') }}"
                               class="btn btn-outline btn-sm gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                Xem chứng nhận
                            </a>
                            @endif
                        </div>
                        @elseif($isCurrent && ! $step->recommended_sandbox_env_code)
                        <div class="mt-4 pt-4 border-t border-base-200">
                            <a href="{{ route('backend.sandbox.index') }}" class="btn btn-primary btn-sm gap-1.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                                Vào AI Sandbox
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    @if($steps->isEmpty())
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body items-center text-center py-12">
            <p class="text-base-content/40 text-sm mb-2">Chưa có bước lộ trình nào được thiết lập.</p>
            @can('assessment.config')
            <a href="{{ route('backend.career-pathway-admin.create') }}" class="btn btn-primary btn-sm mt-2">Thiết lập lộ trình</a>
            @endcan
        </div>
    </div>
    @endif
</div>

@endsection

@push('scripts')
    @vite(['Modules/Assessment/resources/assets/js/assessment.js'], 'build/backend')
@endpush
