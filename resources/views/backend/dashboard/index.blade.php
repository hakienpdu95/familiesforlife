{{-- resources/views/backend/dashboard/index.blade.php --}}
@extends('layouts.backend')

@section('title', 'Dashboard')

@section('content')
@php
    /** @var \App\Models\User $authUser */
    $authUser  = auth()->user();
    $roleName  = $primary_role ?? 'viewer';
    $roleLabel = \App\Enums\RoleEnum::tryFrom($roleName)?->label()
        ?? (\App\Models\User::platformRoleLabels()[$roleName] ?? ucfirst($roleName));
    $greeting  = $greeting  ?? (function () { $h = (int) now()->format('H'); return $h < 12 ? 'Chào buổi sáng' : ($h < 18 ? 'Chào buổi chiều' : 'Chào buổi tối'); })();
    $today_str = $today_str ?? \Illuminate\Support\Carbon::now()->isoFormat('dddd, D MMMM YYYY');

    $feedIconMap = [
        'task_overdue'      => ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>', 'bg' => 'bg-error/10', 'text' => 'text-error'],
    ];

    $activityEventMap = [
        'created' => ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4"/>',            'color' => 'text-success'],
        'updated' => ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>', 'color' => 'text-info'],
        'deleted' => ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>',    'color' => 'text-error'],
        'login'   => ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>',                                   'color' => 'text-primary'],
    ];
    $defaultActivityIcon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>';

    // Shortcuts
    $shortcutIconPaths = [
        'task'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>',
        'bolt'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
        'users'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'user'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'log'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
    ];

    $visibleModules = \App\Enums\RoleEnum::tryFrom($roleName)?->visibleModules() ?? [];
@endphp

{{-- ── Greeting ──────────────────────────────────────────────────────────── --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content leading-tight">
            {{ $greeting }}, {{ $authUser->name }}
            <span class="wave-emoji">👋</span>
        </h1>
        <p class="text-sm text-base-content/50 mt-0.5 capitalize">{{ $today_str }}</p>
    </div>
    <div class="flex items-center gap-2">
        <span class="badge {{ \App\Enums\RoleEnum::tryFrom($roleName)?->badgeClass() ?? 'badge-ghost' }} badge-sm px-3 py-2">
            {{ $roleLabel }}
        </span>
        <span class="text-xs text-base-content/40">{{ $authUser->email }}</span>
    </div>
</div>

@if($content)
    @include('backend.dashboard.partials.content')
@else
{{-- ════════════════════════════════════════════════════════════════════════
     Phase 1 — Action Feed + Activity
════════════════════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-5">

    {{-- Action Feed --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-0">
            <div class="px-5 py-4 border-b border-base-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h2 class="font-semibold text-sm text-base-content">Cần xử lý</h2>
                    @if(count($action_feed) > 0)
                    <span class="badge badge-error badge-xs">{{ count($action_feed) }}</span>
                    @endif
                </div>
            </div>

            @if(empty($action_feed))
            <div class="flex flex-col items-center justify-center py-14 px-5 text-center">
                <svg class="w-12 h-12 text-base-content/15 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-medium text-base-content/40">Không có gì cần xử lý</p>
                <p class="text-xs text-base-content/25 mt-0.5">Mọi thứ đang ổn 🎉</p>
            </div>
            @else
            <ul class="divide-y divide-base-200">
                @foreach($action_feed as $item)
                @php $fi = $feedIconMap[$item['type']] ?? $feedIconMap['task_overdue']; @endphp
                <li class="px-5 py-3.5 hover:bg-base-50 transition-colors">
                    <a href="{{ $item['link'] }}" class="flex items-start gap-3 group">
                        <div class="w-8 h-8 rounded-lg {{ $fi['bg'] }} flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4 {{ $fi['text'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                {!! $fi['icon'] !!}
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-base-content truncate group-hover:text-primary transition-colors">{{ $item['title'] }}</p>
                            <p class="text-xs text-base-content/50 truncate mt-0.5">{{ $item['subtitle'] }}</p>
                        </div>
                        <span class="badge badge-{{ $item['badge_color'] }} badge-xs shrink-0 mt-0.5">{{ $item['badge'] }}</span>
                    </a>
                </li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-0">
            <div class="px-5 py-4 border-b border-base-200 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h2 class="font-semibold text-sm text-base-content">Hoạt động gần đây</h2>
                </div>
                <a href="{{ route('activitylog.index') }}" class="text-xs text-primary hover:underline">Xem log đầy đủ</a>
            </div>

            @if($recent_activity->isEmpty())
            <div class="flex flex-col items-center justify-center py-14 px-5 text-center">
                <svg class="w-12 h-12 text-base-content/15 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-sm font-medium text-base-content/40">Chưa có hoạt động nào</p>
            </div>
            @else
            <ul class="divide-y divide-base-200">
                @foreach($recent_activity as $log)
                @php
                    $event = $log->event ?? 'updated';
                    $ai    = $activityEventMap[$event] ?? ['icon' => $defaultActivityIcon, 'color' => 'text-base-content/40'];
                    $subjectLabel = $log->subject_type ? class_basename($log->subject_type) : null;
                @endphp
                <li class="px-5 py-3 flex items-start gap-3">
                    <div class="w-7 h-7 rounded-full bg-base-200 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-3.5 h-3.5 {{ $ai['color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            {!! $ai['icon'] !!}
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-base-content leading-snug">
                            @if($log->causer)
                            <span class="font-medium">{{ $log->causer->name }}</span>
                            @else
                            <span class="text-base-content/40">Hệ thống</span>
                            @endif
                            <span class="text-base-content/60"> {{ $log->description }}</span>
                            @if($subjectLabel)
                            <span class="text-base-content/40 text-xs">({{ $subjectLabel }})</span>
                            @endif
                        </p>
                        <p class="text-xs text-base-content/35 mt-0.5">{{ $log->created_at->diffForHumans() }}</p>
                    </div>
                </li>
                @endforeach
            </ul>
            @endif
        </div>
    </div>

</div>

{{-- ── Quick Links ───────────────────────────────────────────────────────── --}}
<div>
    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Truy cập nhanh</p>
    <div class="flex flex-wrap gap-2">
        @php
        $shortcuts = [];
        if (in_array('users', $visibleModules))
            $shortcuts[] = ['label' => 'Người dùng', 'route' => route('backend.users.index'), 'icon' => 'user'];
        $shortcuts[] = ['label' => 'Activity Log', 'route' => route('activitylog.index'),             'icon' => 'log'];
        @endphp
        @foreach($shortcuts as $s)
        <a href="{{ $s['route'] }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-base-200 bg-base-100
                  text-xs font-medium text-base-content/70 hover:text-primary hover:border-primary/30
                  hover:bg-primary/5 transition-all">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                {!! $shortcutIconPaths[$s['icon']] ?? $shortcutIconPaths['task'] !!}
            </svg>
            {{ $s['label'] }}
        </a>
        @endforeach
    </div>
</div>
@endif

@endsection

@push('styles')
<style>
    .wave-emoji { display: inline-block; animation: wave 1.5s ease-in-out 1; }
    @keyframes wave {
        0%,100% { transform: rotate(0deg); }
        20%      { transform: rotate(20deg); }
        40%      { transform: rotate(-10deg); }
        60%      { transform: rotate(15deg); }
        80%      { transform: rotate(-5deg); }
    }
</style>
@endpush
