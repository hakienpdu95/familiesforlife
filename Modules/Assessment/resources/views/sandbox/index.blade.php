@extends('layouts.backend')
@section('title', 'AI Sandbox — Thực hành năng lực')

@push('styles')
    @vite(['Modules/Assessment/resources/assets/sass/assessment.scss'], 'build/backend')
@endpush

@section('content')

{{-- ── Page header ─────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">AI Sandbox</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Thực hành ứng dụng AI trong môi trường mô phỏng an toàn</p>
    </div>
    <a href="{{ route('backend.workforce.me') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Hồ sơ của tôi
    </a>
</div>

{{-- ── Stat cards ───────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Phiên hoàn thành</div>
        <div class="stat-value text-2xl text-primary">{{ $stats['total'] }}</div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Tổng giờ luyện tập</div>
        <div class="stat-value text-2xl">{{ number_format($stats['hours'], 1) }}<span class="text-base font-normal">h</span></div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Điểm trung bình</div>
        <div class="stat-value text-2xl text-success">{{ $stats['avg'] ? number_format($stats['avg'], 1) : '—' }}</div>
    </div>
    <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
        <div class="stat-title text-xs">Đạt (≥ 60)</div>
        <div class="stat-value text-2xl text-accent">{{ $stats['passed'] }}</div>
    </div>
</div>

{{-- ── Environments + Tasks ─────────────────────────────────────────────────── --}}
<div x-data="{ openEnv: null }" class="space-y-3 mb-6">

    <h2 class="text-sm font-semibold text-base-content/50 uppercase tracking-wide">Môi trường thực hành</h2>

    @forelse($environments as $env)
    @php
        $typeIcon = match($env->type ?? '') {
            'office'     => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
            'data'       => 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4',
            'sales'      => 'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6',
            'hr'         => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
            'workflow'   => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z',
            'leadership' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
            default      => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
        };
        $activeTasks   = $env->tasks->where('is_active', true);
        $completedHere = $mySessions->filter(fn($s) => $s->task?->sandbox_env_id === $env->id && $s->status === 'completed')->count();
        $inProgressHere = $mySessions->filter(fn($s) => $s->task?->sandbox_env_id === $env->id && $s->status === 'in_progress')->count();
    @endphp

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden"
         x-bind:class="openEnv === {{ $env->id }} ? 'border-primary/40 shadow-md' : ''">

        {{-- Environment header — clickable --}}
        <div class="card-body p-4 cursor-pointer select-none"
             @click="openEnv = openEnv === {{ $env->id }} ? null : {{ $env->id }}">
            <div class="flex items-center gap-4">
                {{-- Icon --}}
                <div class="w-10 h-10 rounded-xl shrink-0 flex items-center justify-center transition-colors"
                     x-bind:class="openEnv === {{ $env->id }} ? 'bg-primary text-primary-content' : 'bg-primary/10 text-primary'">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $typeIcon }}"/>
                    </svg>
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="font-semibold text-sm">{{ $env->name }}</h3>
                        <span class="badge badge-ghost badge-xs">Tier {{ $env->tier }}</span>
                        @if($inProgressHere > 0)
                        <span class="badge badge-warning badge-xs">{{ $inProgressHere }} đang làm</span>
                        @elseif($completedHere > 0)
                        <span class="badge badge-success badge-xs">{{ $completedHere }} hoàn thành</span>
                        @endif
                    </div>
                    <p class="text-xs text-base-content/50 mt-0.5 line-clamp-1">{{ $env->description }}</p>
                </div>

                {{-- Task count + arrow --}}
                <div class="flex items-center gap-3 shrink-0">
                    <span class="text-xs text-base-content/40">{{ $activeTasks->count() }} nhiệm vụ</span>
                    <svg class="w-4 h-4 text-base-content/30 transition-transform duration-200"
                         x-bind:class="openEnv === {{ $env->id }} ? 'rotate-90' : ''"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Tasks panel — expanded --}}
        <div x-show="openEnv === {{ $env->id }}"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-cloak
             class="border-t border-base-200">

            @if($activeTasks->isEmpty())
            <div class="px-5 py-8 text-center text-sm text-base-content/40">
                Môi trường này chưa có nhiệm vụ nào.
            </div>
            @else
            <div class="divide-y divide-base-200">
                @foreach($activeTasks as $task)
                @php
                    $taskSession = $mySessions->first(fn($s) => $s->sandbox_task_id === $task->id);
                    $taskDone    = $taskSession && $taskSession->status === 'completed';
                    $taskActive  = $taskSession && $taskSession->status === 'in_progress';
                    $taskSubmitted = $taskSession && $taskSession->status === 'submitted';
                @endphp
                <div class="px-5 py-4 flex items-start gap-4 hover:bg-base-50 transition-colors">
                    {{-- Status dot --}}
                    <div class="w-7 h-7 rounded-full shrink-0 mt-0.5 flex items-center justify-center
                        {{ $taskDone ? 'bg-success/15 text-success' : ($taskActive ? 'bg-warning/15 text-warning' : ($taskSubmitted ? 'bg-info/15 text-info' : 'bg-base-200 text-base-content/30')) }}">
                        @if($taskDone)
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        @elseif($taskActive)
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                        @elseif($taskSubmitted)
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @else
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        @endif
                    </div>

                    {{-- Task info --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-4 flex-wrap">
                            <div>
                                <h4 class="font-medium text-sm">{{ $task->title }}</h4>
                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                    @if($task->time_limit_minutes)
                                    <span class="text-xs text-base-content/40 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ $task->time_limit_minutes }} phút
                                    </span>
                                    @endif
                                    @foreach($task->allowedAiTools() as $tool)
                                    <span class="badge badge-ghost badge-xs">{{ $tool }}</span>
                                    @endforeach
                                    @if($taskDone && $taskSession->final_score !== null)
                                    <span class="text-xs font-semibold {{ $taskSession->passed ? 'text-success' : 'text-error' }}">
                                        Điểm: {{ number_format($taskSession->final_score, 1) }}
                                    </span>
                                    @endif
                                </div>
                                @if($task->instruction)
                                <p class="text-xs text-base-content/50 mt-1.5 line-clamp-2">{{ $task->instruction }}</p>
                                @endif
                            </div>

                            {{-- CTA button --}}
                            <div class="shrink-0">
                                @if($taskActive)
                                <a href="{{ route('backend.sandbox.show', $taskSession) }}"
                                   class="btn btn-warning btn-sm gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                                    Tiếp tục
                                </a>
                                @elseif($taskSubmitted)
                                <a href="{{ route('backend.sandbox.show', $taskSession) }}"
                                   class="btn btn-ghost btn-sm gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Xem kết quả
                                </a>
                                @elseif($taskDone)
                                <div class="flex flex-col items-end gap-1">
                                    <a href="{{ route('backend.sandbox.show', $taskSession) }}"
                                       class="btn btn-ghost btn-xs">Xem lại</a>
                                    <form method="POST" action="{{ route('backend.sandbox.task.start', $task) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-ghost btn-xs text-base-content/40">
                                            Làm lại
                                        </button>
                                    </form>
                                </div>
                                @else
                                <form method="POST" action="{{ route('backend.sandbox.task.start', $task) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                                        Bắt đầu
                                    </button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>
    @empty
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body items-center text-center py-12">
            <svg class="w-12 h-12 text-base-content/20 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            <p class="text-base-content/50 text-sm">Chưa có môi trường Sandbox nào được cấu hình.</p>
        </div>
    </div>
    @endforelse

</div>

{{-- ── My sessions history ─────────────────────────────────────────────────── --}}
@if($mySessions->count())
<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body">
        <h2 class="card-title text-base mb-4">
            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Lịch sử phiên thực hành
        </h2>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr class="text-xs text-base-content/50">
                        <th>Nhiệm vụ</th>
                        <th>Môi trường</th>
                        <th class="text-center">Điểm</th>
                        <th class="text-center">Thời gian</th>
                        <th class="text-center">Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mySessions as $session)
                    @php
                        $sc = match($session->status) {
                            'completed'   => $session->passed ? ['Đạt', 'badge-success'] : ['Không đạt', 'badge-error'],
                            'in_progress' => ['Đang làm', 'badge-warning'],
                            'submitted'   => ['Chờ chấm', 'badge-info'],
                            'expired'     => ['Hết hạn', 'badge-ghost'],
                            default       => [$session->status, 'badge-ghost'],
                        };
                    @endphp
                    <tr class="hover:bg-base-50">
                        <td class="font-medium text-sm">{{ $session->task?->title ?? '—' }}</td>
                        <td class="text-xs text-base-content/60">{{ $session->task?->environment?->name ?? '—' }}</td>
                        <td class="text-center">
                            @if($session->final_score !== null)
                            <span class="font-semibold {{ $session->passed ? 'text-success' : 'text-error' }}">
                                {{ number_format($session->final_score, 1) }}
                            </span>
                            @else
                            <span class="text-base-content/30">—</span>
                            @endif
                        </td>
                        <td class="text-center text-xs text-base-content/60">
                            {{ $session->duration_minutes ? $session->duration_minutes . ' phút' : '—' }}
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $sc[1] }} badge-xs">{{ $sc[0] }}</span>
                        </td>
                        <td class="text-right">
                            <a href="{{ route('backend.sandbox.show', $session) }}"
                               class="btn btn-ghost btn-xs">Xem</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
    @vite(['Modules/Assessment/resources/assets/js/assessment.js'], 'build/backend')
@endpush
