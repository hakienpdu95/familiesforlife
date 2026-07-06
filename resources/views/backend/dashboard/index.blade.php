{{-- resources/views/backend/dashboard/index.blade.php --}}
@extends('layouts.backend')

@section('title', 'Dashboard')

@section('content')
@php
    /** @var \App\Models\User $authUser */
    $authUser  = auth()->user();
    $roleName  = $primary_role ?? 'viewer';
    $roleLabel = \App\Enums\RoleEnum::tryFrom($roleName)?->label() ?? ucfirst($roleName);
    $greeting  = $greeting  ?? (function () { $h = (int) now()->format('H'); return $h < 12 ? 'Chào buổi sáng' : ($h < 18 ? 'Chào buổi chiều' : 'Chào buổi tối'); })();
    $today_str = $today_str ?? \Illuminate\Support\Carbon::now()->isoFormat('dddd, D MMMM YYYY');

    // KPI card icon paths (Heroicons outline)
    $iconPaths = [
        'approval'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'task_overdue'=> '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'employees'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'leads'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
        'leads_won'   => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>',
        'workflow'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
        'leave'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
        'recruitment' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
    ];

    $colorMap = [
        'success'   => ['bg' => 'bg-success/10',   'text' => 'text-success',   'icon' => 'text-success'],
        'error'     => ['bg' => 'bg-error/10',     'text' => 'text-error',     'icon' => 'text-error'],
        'warning'   => ['bg' => 'bg-warning/10',   'text' => 'text-warning',   'icon' => 'text-warning'],
        'info'      => ['bg' => 'bg-info/10',       'text' => 'text-info',      'icon' => 'text-info'],
        'secondary' => ['bg' => 'bg-secondary/10', 'text' => 'text-secondary', 'icon' => 'text-secondary'],
        'ghost'     => ['bg' => 'bg-base-200',      'text' => 'text-base-content/60', 'icon' => 'text-base-content/40'],
    ];

    $feedIconMap = [
        'workflow_approval' => ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>', 'bg' => 'bg-warning/10', 'text' => 'text-warning'],
        'task_overdue'      => ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>', 'bg' => 'bg-error/10', 'text' => 'text-error'],
    ];

    $activityEventMap = [
        'created' => ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4"/>',            'color' => 'text-success'],
        'updated' => ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>', 'color' => 'text-info'],
        'deleted' => ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>',    'color' => 'text-error'],
        'login'   => ['icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>',                                   'color' => 'text-primary'],
    ];
    $defaultActivityIcon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>';

    // Role-based chart visibility
    $showLeadFunnel     = in_array($roleName, ['ceo','system_admin','ops','ai_operator','sales','marketing']);
    $showWorkflowHealth = in_array($roleName, ['ceo','system_admin','ops','ai_operator']);
    $showHeadcount      = in_array($roleName, ['ceo','system_admin','hr','ops']);

    // Shortcuts
    $shortcutIconPaths = [
        'task'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>',
        'bolt'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
        'users'    => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'crm'      => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
        'user'     => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'calendar' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
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

{{-- ── KPI Cards ─────────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3 mb-6">
    @foreach($kpi_cards as $card)
    @php $c = $colorMap[$card['color']] ?? $colorMap['ghost']; @endphp
    <a href="{{ $card['link'] }}"
       class="card bg-base-100 border {{ $card['urgent'] ? 'border-' . ($card['color'] === 'error' ? 'error' : 'warning') . '/40' : 'border-base-200' }}
              shadow-sm hover:shadow-md hover:border-primary/30 transition-all group">
        <div class="card-body p-4">
            <div class="flex items-start justify-between mb-3">
                <div class="w-9 h-9 rounded-xl {{ $c['bg'] }} flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 {{ $c['icon'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        {!! $iconPaths[$card['icon']] ?? $iconPaths['task_overdue'] !!}
                    </svg>
                </div>
                @if($card['urgent'])
                <span class="w-2 h-2 rounded-full {{ $card['color'] === 'error' ? 'bg-error' : 'bg-warning' }} animate-pulse mt-1 shrink-0"></span>
                @endif
            </div>
            <p class="text-2xl font-bold {{ $c['text'] }} leading-none tabular-nums">{{ number_format($card['value']) }}</p>
            <p class="text-xs text-base-content/60 mt-1 leading-snug">{{ $card['label'] }}</p>
            @if(!empty($card['hint']))
            <p class="text-xs text-base-content/35 mt-0.5 truncate">{{ $card['hint'] }}</p>
            @endif
        </div>
    </a>
    @endforeach
</div>

{{-- ════════════════════════════════════════════════════════════════════════
     PHASE 2 — Charts
════════════════════════════════════════════════════════════════════════ --}}

{{-- ── Date range pill (persisted via Alpine) ────────────────────────────── --}}
<div x-data="dashboardCharts()" x-init="init()" class="space-y-5 mb-6">

    {{-- Date range controls --}}
    <div class="flex items-center justify-between">
        <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Biểu đồ phân tích</p>
        <div class="flex items-center gap-1 bg-base-200 rounded-lg p-0.5">
            <template x-for="opt in rangeOptions" :key="opt.value">
                <button type="button"
                        @click="setRange(opt.value)"
                        :class="days === opt.value ? 'bg-base-100 text-base-content shadow-sm' : 'text-base-content/50 hover:text-base-content'"
                        class="px-3 py-1 rounded-md text-xs font-medium transition-all"
                        x-text="opt.label">
                </button>
            </template>
        </div>
    </div>

    {{-- ── Row 1: Task Throughput + Headcount ──────────────────────────── --}}
    <div class="grid grid-cols-1 {{ $showHeadcount ? 'lg:grid-cols-3' : '' }} gap-5">

        {{-- Task Throughput — Line chart --}}
        <div class="{{ $showHeadcount ? 'lg:col-span-2' : '' }} card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="font-semibold text-sm text-base-content">Tiến độ Task</h3>
                        <p class="text-xs text-base-content/40 mt-0.5">Tasks tạo mới so với tasks hoàn thành mỗi ngày</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs">
                        <span class="flex items-center gap-1 text-base-content/50">
                            <span class="inline-block w-3 h-0.5 bg-primary rounded"></span>
                            Tạo mới
                        </span>
                        <span class="flex items-center gap-1 text-base-content/50">
                            <span class="inline-block w-3 h-0.5 bg-success rounded"></span>
                            Hoàn thành
                        </span>
                    </div>
                </div>
                <div id="chart-task-throughput"
                     class="w-full"
                     style="height: 220px;"
                     data-chart="task-throughput"
                     :data-days="days">
                    {{-- Skeleton --}}
                    <div class="skeleton w-full h-full rounded-xl" id="skel-task-throughput"></div>
                </div>
            </div>
        </div>

        {{-- Headcount by Dept — Donut --}}
        @if($showHeadcount)
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="mb-3">
                    <h3 class="font-semibold text-sm text-base-content">Cơ cấu nhân sự</h3>
                    <p class="text-xs text-base-content/40 mt-0.5">Nhân viên hoạt động theo phòng ban</p>
                </div>
                <div id="chart-headcount"
                     class="w-full"
                     style="height: 220px;"
                     data-chart="headcount">
                    <div class="skeleton w-full h-full rounded-xl" id="skel-headcount"></div>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- ── Row 2: Lead Funnel + Workflow Health ────────────────────────── --}}
    @if($showLeadFunnel || $showWorkflowHealth)
    <div class="grid grid-cols-1 {{ ($showLeadFunnel && $showWorkflowHealth) ? 'lg:grid-cols-2' : '' }} gap-5">

        {{-- Lead Funnel --}}
        @if($showLeadFunnel)
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="mb-3">
                    <h3 class="font-semibold text-sm text-base-content">Phễu Sales (Lead đang theo dõi)</h3>
                    <p class="text-xs text-base-content/40 mt-0.5">Số lead active theo từng giai đoạn pipeline</p>
                </div>
                <div id="chart-lead-funnel"
                     class="w-full"
                     style="height: 260px;"
                     data-chart="lead-funnel">
                    <div class="skeleton w-full h-full rounded-xl" id="skel-lead-funnel"></div>
                </div>
            </div>
        </div>
        @endif

        {{-- Workflow Health --}}
        @if($showWorkflowHealth)
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="font-semibold text-sm text-base-content">Workflow Health</h3>
                        <p class="text-xs text-base-content/40 mt-0.5">Trạng thái lần chạy theo ngày</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="flex items-center gap-1 text-base-content/50">
                            <span class="inline-block w-2 h-2 rounded-sm bg-success"></span> Thành công
                        </span>
                        <span class="flex items-center gap-1 text-base-content/50">
                            <span class="inline-block w-2 h-2 rounded-sm bg-error"></span> Lỗi
                        </span>
                        <span class="flex items-center gap-1 text-base-content/50">
                            <span class="inline-block w-2 h-2 rounded-sm bg-warning"></span> Halted
                        </span>
                        <span class="flex items-center gap-1 text-base-content/50">
                            <span class="inline-block w-2 h-2 rounded-sm bg-info"></span> Chờ
                        </span>
                    </div>
                </div>
                <div id="chart-workflow-health"
                     class="w-full"
                     style="height: 260px;"
                     data-chart="workflow-health"
                     :data-days="days">
                    <div class="skeleton w-full h-full rounded-xl" id="skel-workflow-health"></div>
                </div>
            </div>
        </div>
        @endif

    </div>
    @endif

</div>{{-- end x-data dashboardCharts --}}

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
                <a href="{{ route('backend.tasks.index') }}" class="text-xs text-primary hover:underline">Xem tất cả</a>
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
        $shortcuts = [
            ['label' => 'Tasks',       'route' => route('backend.tasks.index'),         'icon' => 'task'],
            ['label' => 'Workflows',   'route' => route('workflows.index'),              'icon' => 'bolt'],
            ['label' => 'Nhân viên',   'route' => route('backend.employees.index'),      'icon' => 'users'],
        ];
        if (in_array('crm', $visibleModules))
            $shortcuts[] = ['label' => 'CRM / Leads', 'route' => route('lead.index'), 'icon' => 'crm'];
        if (in_array('users', $visibleModules))
            $shortcuts[] = ['label' => 'Người dùng', 'route' => route('backend.users.index'), 'icon' => 'user'];
        $shortcuts[] = ['label' => 'Nghỉ phép',    'route' => route('backend.leave.requests.index'), 'icon' => 'calendar'];
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

@endsection

@push('scripts')
@vite(['resources/js/modules/echarts.js'], 'build/backend')

<script>
// ── Dashboard charts Alpine component ──────────────────────────────────────
document.addEventListener('alpine:init', () => {
    Alpine.data('dashboardCharts', () => ({
        days:         30,
        rangeOptions: [
            { value: 7,  label: '7 ngày'  },
            { value: 14, label: '14 ngày' },
            { value: 30, label: '30 ngày' },
        ],

        // Chart instances for resize + theme re-init
        _charts: {},

        init() {
            // Restore saved range
            const saved = parseInt(sessionStorage.getItem('dash_days') || '30', 10);
            this.days   = [7, 14, 30].includes(saved) ? saved : 30;

            // Wait for ECharts module to load, then render
            if (window.ECharts) {
                this._renderAll();
            } else {
                document.addEventListener('echarts:ready', () => this._renderAll(), { once: true });
            }

            // Re-render charts when theme changes
            const observer = new MutationObserver(() => this._reTheme());
            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
        },

        setRange(val) {
            this.days = val;
            sessionStorage.setItem('dash_days', String(val));
            // Re-render time-series charts
            this._renderChart('task-throughput',  this._taskThroughputOptions.bind(this));
            this._renderChart('workflow-health',  this._workflowHealthOptions.bind(this));
        },

        _isDark() {
            return document.documentElement.getAttribute('data-theme') === 'dark';
        },

        _renderAll() {
            this._renderChart('task-throughput',  this._taskThroughputOptions.bind(this));
            this._renderChart('headcount',        this._headcountOptions.bind(this));
            this._renderChart('lead-funnel',      this._leadFunnelOptions.bind(this));
            this._renderChart('workflow-health',  this._workflowHealthOptions.bind(this));
        },

        _reTheme() {
            for (const [id, inst] of Object.entries(this._charts)) {
                inst.dispose();
            }
            this._charts = {};
            this._renderAll();
        },

        async _renderChart(id, buildFn) {
            const el   = document.getElementById('chart-' + id);
            if (!el) return;
            const skel = document.getElementById('skel-' + id);

            // Build URL with current days param where applicable
            const needsDays = ['task-throughput', 'workflow-health'].includes(id);
            const baseUrls  = {
                'task-throughput': '{{ route("backend.dashboard.charts.task-throughput") }}',
                'lead-funnel':     '{{ route("backend.dashboard.charts.lead-funnel") }}',
                'workflow-health': '{{ route("backend.dashboard.charts.workflow-health") }}',
                'headcount':       '{{ route("backend.dashboard.charts.headcount") }}',
            };
            const url = (baseUrls[id] ?? '') + (needsDays ? `?days=${this.days}` : '');

            try {
                const r = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!r.ok) throw new Error('HTTP ' + r.status);
                const data = await r.json();

                skel?.remove();
                el.style.display = '';

                // Dispose existing instance before re-init
                if (this._charts[id]) {
                    this._charts[id].dispose();
                    delete this._charts[id];
                }

                const chart = window.ECharts.init(el, this._isDark() ? 'dark' : null, { renderer: 'canvas' });
                chart.setOption(buildFn(data));
                this._charts[id] = chart;

                // Responsive
                new ResizeObserver(() => chart.resize()).observe(el);

            } catch (e) {
                skel?.remove();
                el.style.display = '';
                el.innerHTML = `<div class="h-full flex items-center justify-center text-xs text-error/60">
                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Không thể tải dữ liệu
                </div>`;
            }
        },

        // ── Chart option builders ─────────────────────────────────────────

        _taskThroughputOptions(d) {
            const isDark    = this._isDark();
            const textColor = isDark ? '#94a3b8' : '#64748b';
            const gridColor = isDark ? '#1e293b' : '#f1f5f9';

            return {
                backgroundColor: 'transparent',
                grid: { top: 16, right: 16, bottom: 36, left: 40, containLabel: false },
                tooltip: {
                    trigger: 'axis',
                    backgroundColor: isDark ? '#1e293b' : '#fff',
                    borderColor:     isDark ? '#334155' : '#e2e8f0',
                    textStyle:       { color: isDark ? '#e2e8f0' : '#1e293b', fontSize: 12 },
                    formatter: (params) => {
                        const label  = params[0].axisValue;
                        const rows   = params.map(p =>
                            `<div class="flex justify-between gap-4"><span>${p.marker}${p.seriesName}</span><b>${p.value}</b></div>`
                        ).join('');
                        return `<div class="text-xs"><p class="font-semibold mb-1">${label}</p>${rows}</div>`;
                    },
                },
                xAxis: {
                    type: 'category', data: d.labels, boundaryGap: false,
                    axisLine:  { lineStyle: { color: gridColor } },
                    axisTick:  { show: false },
                    axisLabel: {
                        color: textColor, fontSize: 11,
                        interval: Math.floor(d.labels.length / 6),
                    },
                },
                yAxis: {
                    type: 'value', minInterval: 1,
                    splitLine: { lineStyle: { color: gridColor, type: 'dashed' } },
                    axisLabel: { color: textColor, fontSize: 11 },
                    axisLine: { show: false }, axisTick: { show: false },
                },
                series: [
                    {
                        name: 'Tạo mới', type: 'line', data: d.created,
                        smooth: true, symbol: 'circle', symbolSize: 4,
                        lineStyle: { width: 2, color: '#6366f1' },
                        itemStyle: { color: '#6366f1' },
                        areaStyle: {
                            color: { type: 'linear', x: 0, y: 0, x2: 0, y2: 1,
                                colorStops: [{ offset: 0, color: 'rgba(99,102,241,0.18)' }, { offset: 1, color: 'rgba(99,102,241,0)' }] }
                        },
                    },
                    {
                        name: 'Hoàn thành', type: 'line', data: d.closed,
                        smooth: true, symbol: 'circle', symbolSize: 4,
                        lineStyle: { width: 2, color: '#22c55e' },
                        itemStyle: { color: '#22c55e' },
                        areaStyle: {
                            color: { type: 'linear', x: 0, y: 0, x2: 0, y2: 1,
                                colorStops: [{ offset: 0, color: 'rgba(34,197,94,0.18)' }, { offset: 1, color: 'rgba(34,197,94,0)' }] }
                        },
                    },
                ],
            };
        },

        _headcountOptions(d) {
            const isDark    = this._isDark();
            const textColor = isDark ? '#94a3b8' : '#64748b';
            const palette   = ['#6366f1','#22c55e','#f59e0b','#ef4444','#3b82f6','#8b5cf6','#06b6d4','#ec4899','#10b981','#f97316'];
            const items     = d.departments ?? [];
            const total     = items.reduce((s, i) => s + i.value, 0);

            return {
                backgroundColor: 'transparent',
                tooltip: {
                    trigger: 'item',
                    backgroundColor: isDark ? '#1e293b' : '#fff',
                    borderColor:     isDark ? '#334155' : '#e2e8f0',
                    textStyle:       { color: isDark ? '#e2e8f0' : '#1e293b', fontSize: 12 },
                    formatter: ({ name, value, percent }) =>
                        `<div class="text-xs"><b>${name}</b><br/>${value} người · ${percent}%</div>`,
                },
                legend: {
                    orient: 'vertical', right: 4, top: 'center',
                    icon: 'circle', itemWidth: 8, itemHeight: 8, itemGap: 6,
                    textStyle: { color: textColor, fontSize: 11 },
                    formatter: (name) => {
                        const item = items.find(i => i.name === name);
                        return item ? `${name.length > 14 ? name.slice(0, 14) + '…' : name} (${item.value})` : name;
                    },
                },
                graphic: [{
                    type: 'text', left: '30%', top: 'middle', z: 100,
                    style: {
                        text: `${total}\nngười`,
                        textAlign: 'center', fill: isDark ? '#e2e8f0' : '#1e293b',
                        fontSize: 14, fontWeight: 'bold', lineHeight: 20,
                    },
                }],
                series: [{
                    type: 'pie', radius: ['52%', '75%'],
                    center: ['32%', '50%'],
                    data: items.map((item, i) => ({
                        ...item, itemStyle: { color: palette[i % palette.length] },
                    })),
                    label: { show: false },
                    emphasis: { scale: true, scaleSize: 4 },
                }],
            };
        },

        _leadFunnelOptions(d) {
            const isDark    = this._isDark();
            const textColor = isDark ? '#94a3b8' : '#64748b';
            const stages    = d.stages ?? [];
            const palette   = ['#6366f1','#818cf8','#a5b4fc','#c7d2fe','#ddd6fe','#ede9fe'];

            // Normalize so largest = 100%
            const max = Math.max(...stages.map(s => s.count), 1);

            return {
                backgroundColor: 'transparent',
                tooltip: {
                    trigger: 'item',
                    backgroundColor: isDark ? '#1e293b' : '#fff',
                    borderColor:     isDark ? '#334155' : '#e2e8f0',
                    textStyle:       { color: isDark ? '#e2e8f0' : '#1e293b', fontSize: 12 },
                    formatter: ({ name, value }) =>
                        `<div class="text-xs"><b>${name}</b><br/>${value} lead</div>`,
                },
                series: [{
                    type: 'funnel',
                    left: '5%', width: '90%',
                    top: 10, bottom: 10,
                    min: 0, max: max,
                    minSize: '10%', maxSize: '100%',
                    sort: 'none',
                    gap: 3,
                    label: {
                        show: true, position: 'inside',
                        formatter: ({ name, value }) => `${name}: ${value}`,
                        color: '#fff', fontSize: 12, fontWeight: 'bold',
                    },
                    itemStyle: { borderWidth: 0 },
                    emphasis: { label: { fontSize: 13 } },
                    data: stages.map((s, i) => ({
                        name:      s.label,
                        value:     s.count,
                        itemStyle: {
                            color: s.is_won ? '#22c55e' : (s.is_lost ? '#ef4444' : (s.color || palette[i % palette.length])),
                        },
                    })),
                }],
            };
        },

        _workflowHealthOptions(d) {
            const isDark    = this._isDark();
            const textColor = isDark ? '#94a3b8' : '#64748b';
            const gridColor = isDark ? '#1e293b' : '#f1f5f9';

            return {
                backgroundColor: 'transparent',
                grid: { top: 16, right: 16, bottom: 36, left: 40, containLabel: false },
                tooltip: {
                    trigger: 'axis', axisPointer: { type: 'shadow' },
                    backgroundColor: isDark ? '#1e293b' : '#fff',
                    borderColor:     isDark ? '#334155' : '#e2e8f0',
                    textStyle:       { color: isDark ? '#e2e8f0' : '#1e293b', fontSize: 12 },
                },
                xAxis: {
                    type: 'category', data: d.labels,
                    axisLine:  { lineStyle: { color: gridColor } },
                    axisTick:  { show: false },
                    axisLabel: {
                        color: textColor, fontSize: 11,
                        interval: Math.floor(d.labels.length / 5),
                    },
                },
                yAxis: {
                    type: 'value', minInterval: 1,
                    splitLine: { lineStyle: { color: gridColor, type: 'dashed' } },
                    axisLabel: { color: textColor, fontSize: 11 },
                    axisLine: { show: false }, axisTick: { show: false },
                },
                series: [
                    { name: 'Thành công', type: 'bar', data: d.pass,    stack: 'wf', itemStyle: { color: '#22c55e', borderRadius: [0,0,0,0] }, barMaxWidth: 32 },
                    { name: 'Chờ duyệt', type: 'bar', data: d.waiting, stack: 'wf', itemStyle: { color: '#3b82f6' }, barMaxWidth: 32 },
                    { name: 'Halted',    type: 'bar', data: d.halted,  stack: 'wf', itemStyle: { color: '#f97316' }, barMaxWidth: 32 },
                    { name: 'Lỗi',       type: 'bar', data: d.fail,    stack: 'wf', itemStyle: { color: '#ef4444', borderRadius: [3,3,0,0] }, barMaxWidth: 32 },
                ],
            };
        },

    }));
});
</script>

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

@endpush
