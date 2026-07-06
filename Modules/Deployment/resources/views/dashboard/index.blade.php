@extends('layouts.backend')
@section('title', $vertical->label())

@section('content')
<div x-data="{ refreshing: false }">

    {{-- Page header --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold">{{ $vertical->label() }}</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Tổng quan triển khai</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('deployment.reports.pm', ['vertical' => $vertical->code()]) }}"
               class="btn btn-ghost btn-sm">Báo cáo PM</a>
            @can('create', \Modules\Deployment\Models\DeploymentTarget::class)
            <a href="{{ route('deployment.targets.create', ['vertical' => $vertical->code()]) }}"
               class="btn btn-primary btn-sm">+ Thêm {{ $vertical->targetLabel() }}</a>
            @endcan
        </div>
    </div>

    {{-- 4 KPI stat cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        {{-- Total targets --}}
        <div class="stat bg-base-100 border border-base-200 rounded-box shadow-sm">
            <div class="stat-figure text-base-content/30">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div class="stat-title text-xs">Tổng {{ $vertical->targetLabel() }}</div>
            <div class="stat-value text-2xl">{{ $totalTargets }}</div>
        </div>

        {{-- In progress --}}
        <div class="stat bg-base-100 border border-base-200 rounded-box shadow-sm">
            <div class="stat-figure text-info">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <div class="stat-title text-xs">Đang triển khai</div>
            <div class="stat-value text-2xl text-info">{{ $inProgress }}</div>
        </div>

        {{-- Completed --}}
        <div class="stat bg-base-100 border border-base-200 rounded-box shadow-sm">
            <div class="stat-figure text-success">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="stat-title text-xs">Hoàn thành</div>
            <div class="stat-value text-2xl text-success">{{ $completed }}</div>
        </div>

        {{-- Open issues --}}
        <div class="stat bg-base-100 border border-base-200 rounded-box shadow-sm">
            <div class="stat-figure {{ $openIssueCount > 0 ? 'text-error' : 'text-base-content/30' }}">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="stat-title text-xs">Issues đang mở</div>
            <div class="stat-value text-2xl {{ $openIssueCount > 0 ? 'text-error' : '' }}">{{ $openIssueCount }}</div>
            @if($openIssueCount > 0)
            <div class="stat-desc">
                <a href="{{ route('deployment.issues.index', ['vertical' => $vertical->code()]) }}"
                   class="link link-error text-xs">Xem ngay</a>
            </div>
            @endif
        </div>

    </div>

    {{-- Main 3-col grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left col: phase distribution + top targets --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Phase distribution --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body pb-4">
                    <h2 class="font-semibold text-sm mb-3">Phân bố theo Phase</h2>
                    @php
                        $phaseColors = ['primary','info','success','warning','error','secondary'];
                    @endphp
                    <div class="space-y-2.5">
                        @foreach($phases as $idx => $phase)
                        @php
                            $cnt      = $byPhase[$phase] ?? 0;
                            $pct      = $totalTargets > 0 ? round($cnt / $totalTargets * 100) : 0;
                            $pColor   = $phaseColors[$idx % count($phaseColors)];
                        @endphp
                        <div class="flex items-center gap-3 text-sm">
                            <span class="w-2 h-2 rounded-full bg-{{ $pColor }} shrink-0"></span>
                            <span class="w-28 text-xs shrink-0 truncate" title="{{ $phaseLabels[$phase] ?? $phase }}">{{ $phaseLabels[$phase] ?? $phase }}</span>
                            <div class="flex-1 bg-base-200 rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full bg-{{ $pColor }} transition-all" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="badge badge-{{ $pColor }} badge-xs">{{ $cnt }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Top targets progress --}}
            @if($topTargets->isNotEmpty())
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-0">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-base-200">
                        <h2 class="font-semibold text-sm">Tiến độ triển khai</h2>
                        <a href="{{ route('deployment.targets.index', ['vertical' => $vertical->code()]) }}"
                           class="btn btn-ghost btn-xs">Tất cả →</a>
                    </div>
                    @foreach($topTargets as $t)
                    <div class="px-4 py-3 border-b border-base-200 last:border-0">
                        <div class="flex items-center justify-between mb-1.5">
                            <a href="{{ route('deployment.targets.show', ['vertical' => $vertical->code(), 'target' => $t->id]) }}"
                               class="text-sm font-medium hover:text-primary truncate max-w-[55%]">
                                {{ $t->targetOrganization?->name ?? '—' }}
                            </a>
                            <div class="flex items-center gap-2 text-xs shrink-0">
                                <span class="badge badge-outline badge-xs">{{ $t->current_phase }}</span>
                                <span class="{{ $t->overall_pct == 100 ? 'text-success' : 'text-base-content/60' }} font-semibold">
                                    {{ $t->overall_pct }}%
                                </span>
                                @if($t->readiness_score !== null)
                                <span class="badge badge-xs
                                    {{ $t->readiness_score >= 80 ? 'badge-success' : ($t->readiness_score >= 60 ? 'badge-info' : ($t->readiness_score >= 40 ? 'badge-warning' : 'badge-error')) }}">
                                    R:{{ $t->readiness_score }}
                                </span>
                                @endif
                            </div>
                        </div>
                        <div class="w-full bg-base-200 rounded-full h-1.5 overflow-hidden">
                            <div class="h-1.5 rounded-full transition-all
                                {{ $t->overall_pct == 100 ? 'bg-success' : ($t->overall_pct >= 60 ? 'bg-info' : 'bg-warning') }}"
                                 style="width: {{ $t->overall_pct }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>

        {{-- Right col --}}
        <div class="space-y-4">

            {{-- Quick actions --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-4">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Thao tác nhanh</p>
                    <div class="space-y-1.5">
                        <a href="{{ route('deployment.targets.index', ['vertical' => $vertical->code()]) }}"
                           class="btn btn-outline btn-sm btn-block justify-start gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                            </svg>
                            Xem tất cả targets
                        </a>
                        @can('create', \Modules\Deployment\Models\DeploymentTarget::class)
                        <a href="{{ route('deployment.targets.create', ['vertical' => $vertical->code()]) }}"
                           class="btn btn-outline btn-sm btn-block justify-start gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Thêm {{ $vertical->targetLabel() }}
                        </a>
                        @endcan
                        <a href="{{ route('deployment.reports.pm', ['vertical' => $vertical->code()]) }}"
                           class="btn btn-outline btn-sm btn-block justify-start gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Báo cáo PM
                        </a>
                        @if($openIssueCount > 0)
                        <a href="{{ route('deployment.issues.index', ['vertical' => $vertical->code()]) }}"
                           class="btn btn-error btn-outline btn-sm btn-block justify-start gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            {{ $openIssueCount }} issues đang mở
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Issues widget --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-0">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-base-200">
                        <h2 class="font-semibold text-sm">Issues đang mở</h2>
                        <a href="{{ route('deployment.issues.index', ['vertical' => $vertical->code()]) }}"
                           class="btn btn-ghost btn-xs">Tất cả →</a>
                    </div>
                    @forelse($recentIssues as $issue)
                    <div class="flex items-start gap-2 px-3 py-2.5 border-b border-base-200 last:border-0">
                        <span class="badge badge-sm {{ $issue->severity?->badgeClass() ?? 'badge-ghost' }} shrink-0 mt-0.5">
                            {{ $issue->severity?->label() ?? $issue->severity }}
                        </span>
                        <div class="min-w-0">
                            <a href="{{ route('deployment.issues.show', ['vertical' => $vertical->code(), 'issue' => $issue->id]) }}"
                               class="text-xs font-medium hover:text-primary line-clamp-1">{{ $issue->title }}</a>
                            <p class="text-xs text-base-content/50 truncate">{{ $issue->target?->targetOrganization?->name }}</p>
                        </div>
                    </div>
                    @empty
                    <div class="flex flex-col items-center py-6 text-center text-base-content/40">
                        <svg class="w-8 h-8 mb-2 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xs">Không có issues</p>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Team members widget --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-0">
                    <div class="px-4 py-3 border-b border-base-200">
                        <h2 class="font-semibold text-sm">Nhân sự tham gia</h2>
                    </div>
                    @forelse($teamMembers->take(5) as $empId => $memberships)
                    @php $first = $memberships->first(); $emp = $first->employee; @endphp
                    <div class="flex items-center gap-3 px-3 py-2.5 border-b border-base-200 last:border-0">
                        <div class="avatar placeholder shrink-0">
                            <div class="bg-neutral text-neutral-content rounded-full w-7">
                                <span class="text-xs">{{ mb_substr($emp?->full_name ?? '?', 0, 1) }}</span>
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-medium truncate">{{ $emp?->full_name ?? '—' }}</p>
                            <p class="text-xs text-base-content/50">{{ $memberships->count() }} dự án</p>
                        </div>
                        <span class="badge badge-ghost badge-xs">{{ $first->role?->label() ?? $first->role }}</span>
                    </div>
                    @empty
                    <div class="p-6 text-center text-base-content/40 text-xs">Chưa có nhân sự nào.</div>
                    @endforelse
                </div>
            </div>

        </div>{{-- /right col --}}
    </div>{{-- /main grid --}}

    {{-- Projects summary --}}
    @if($projects->isNotEmpty())
    <div class="mt-6 card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-0">
            <div class="flex items-center justify-between px-4 py-3 border-b border-base-200">
                <h2 class="font-semibold text-sm">Dự án gần đây</h2>
                <a href="{{ route('deployment.projects.index', ['vertical' => $vertical->code()]) }}"
                   class="btn btn-ghost btn-xs">Tất cả →</a>
            </div>
            <div class="flex flex-wrap gap-3 p-4">
                @foreach($projects as $p)
                <a href="{{ route('deployment.targets.index', ['vertical' => $vertical->code(), 'project_id' => $p->id]) }}"
                   class="badge badge-lg badge-outline gap-1.5 hover:badge-primary">
                    {{ $p->name }}
                    <span class="badge badge-xs {{ $p->status?->value === 'active' ? 'badge-success' : 'badge-ghost' }}">
                        {{ $p->status?->label() ?? $p->status }}
                    </span>
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
