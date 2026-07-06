@extends('layouts.backend')
@section('title', 'Triển khai')

@section('content')

    {{-- Page header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-base-content">Triển khai</h1>
        <p class="text-sm text-base-content/50 mt-1">Tổng quan hệ thống triển khai theo lĩnh vực</p>
    </div>

    @if($verticals->isEmpty())
        {{-- Empty state --}}
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <svg class="w-16 h-16 text-base-content/20 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <p class="text-base-content/40 text-sm font-medium">Chưa có lĩnh vực nào được kích hoạt</p>
            <p class="text-base-content/30 text-xs mt-1">Liên hệ quản trị viên để kích hoạt dịch vụ triển khai</p>
        </div>
    @else
        @php
            // Global KPI aggregates
            $globalTargets    = collect($targetsByVertical)->flatten(1);
            $globalTotal      = $globalTargets->count();
            $globalInProgress = $globalTargets->filter(fn($t) => !in_array($t->current_phase, ['draft', 'completed', 'done']))->count();
            $globalCompleted  = $globalTargets->filter(fn($t) => in_array($t->current_phase, ['completed', 'done']))->count();
            $globalIssues     = array_sum($openIssuesByVertical);
            $globalAvg        = $globalTargets->whereNotNull('readiness_score')->avg('readiness_score');
            $globalAvgInt     = $globalAvg !== null ? (int) round($globalAvg) : null;
        @endphp

        @if($globalTotal > 0)
        {{-- Global KPI bar --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="stat bg-base-100 border border-base-200 rounded-box shadow-sm">
                <div class="stat-figure text-base-content/30">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div class="stat-title text-xs">Tổng targets</div>
                <div class="stat-value text-2xl">{{ $globalTotal }}</div>
            </div>
            <div class="stat bg-base-100 border border-base-200 rounded-box shadow-sm">
                <div class="stat-figure text-info">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <div class="stat-title text-xs">Đang triển khai</div>
                <div class="stat-value text-2xl text-info">{{ $globalInProgress }}</div>
            </div>
            <div class="stat bg-base-100 border border-base-200 rounded-box shadow-sm">
                <div class="stat-figure text-base-content/30">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div class="stat-title text-xs">Readiness TB</div>
                <div class="stat-value text-2xl">{{ $globalAvgInt !== null ? $globalAvgInt : '—' }}</div>
            </div>
            <div class="stat bg-base-100 border border-base-200 rounded-box shadow-sm">
                <div class="stat-figure {{ $globalIssues > 0 ? 'text-error' : 'text-base-content/30' }}">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="stat-title text-xs">Open Issues</div>
                <div class="stat-value text-2xl {{ $globalIssues > 0 ? 'text-error' : '' }}">{{ $globalIssues }}</div>
            </div>
        </div>
        @endif

        {{-- Per-vertical sections --}}
        @foreach($verticals as $vertical)
        @php
            $vTargets  = $targetsByVertical[$vertical->code()] ?? collect();
            $vProjects = $projectsByVertical[$vertical->code()] ?? collect();
            $vIssues   = $openIssuesByVertical[$vertical->code()] ?? 0;
            $avgScore  = $vTargets->whereNotNull('readiness_score')->avg('readiness_score');
            $avgInt    = $avgScore !== null ? (int) round($avgScore) : null;
            $avgColor  = $avgInt !== null ? ($avgInt >= 80 ? 'text-success' : ($avgInt >= 60 ? 'text-info' : ($avgInt >= 40 ? 'text-warning' : 'text-error'))) : '';
            $dashboardUrl = null;
            try {
                if (\Illuminate\Support\Facades\Route::has('deployment.dashboard')) {
                    $dashboardUrl = route('deployment.dashboard', ['vertical' => $vertical->code()]);
                }
            } catch (\Throwable) {}
        @endphp

        <div class="mb-8">
            {{-- Vertical header card (not an anchor — has button inside) --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="flex flex-wrap items-center gap-4">
                        {{-- Icon --}}
                        <div class="flex-shrink-0 w-11 h-11 rounded-lg bg-primary/10 flex items-center justify-center">
                            <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                      d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                            </svg>
                        </div>
                        {{-- Label + code --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-base-content">{{ $vertical->label() }}</span>
                                <span class="badge badge-primary badge-outline badge-xs">{{ $vertical->code() }}</span>
                            </div>
                            <p class="text-xs text-base-content/40 uppercase tracking-wide mt-0.5">{{ $vertical->targetLabel() }}</p>
                        </div>
                        {{-- Stats --}}
                        <div class="flex items-center gap-5 text-sm">
                            <div class="text-center">
                                <div class="font-bold text-lg">{{ $vTargets->count() }}</div>
                                <div class="text-xs text-base-content/40">Targets</div>
                            </div>
                            @if($avgInt !== null)
                            <div class="text-center">
                                <div class="font-bold text-lg {{ $avgColor }}">{{ $avgInt }}</div>
                                <div class="text-xs text-base-content/40">Readiness TB</div>
                            </div>
                            @endif
                            @if($vIssues > 0)
                            <div class="text-center">
                                <div class="font-bold text-lg text-error">{{ $vIssues }}</div>
                                <div class="text-xs text-base-content/40">Open Issues</div>
                            </div>
                            @endif
                        </div>
                        {{-- Dashboard button --}}
                        @if($dashboardUrl)
                        <a href="{{ $dashboardUrl }}" class="btn btn-primary btn-sm">Dashboard →</a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 2-col content --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

                {{-- Left: Target readiness table --}}
                <div class="lg:col-span-2">
                    @if($vTargets->isNotEmpty())
                    <div class="card bg-base-100 border border-base-200 shadow-sm">
                        <div class="card-body p-0">
                            <div class="flex items-center justify-between px-4 py-3 border-b border-base-200">
                                <h3 class="font-semibold text-sm">Readiness — {{ $vertical->targetLabel() }}</h3>
                                <a href="{{ route('deployment.targets.index', ['vertical' => $vertical->code()]) }}"
                                   class="btn btn-ghost btn-xs">Tất cả →</a>
                            </div>
                            <div class="divide-y divide-base-200">
                                @foreach($vTargets as $t)
                                @php
                                    $rs     = $t->readiness_score;
                                    $rColor = $rs !== null ? ($rs >= 80 ? 'success' : ($rs >= 60 ? 'info' : ($rs >= 40 ? 'warning' : 'error'))) : null;
                                    $rBand  = $rs !== null ? ($rs >= 80 ? 'Sẵn sàng' : ($rs >= 60 ? 'Gần sẵn sàng' : ($rs >= 40 ? 'Có hỗ trợ' : 'Chưa sẵn sàng'))) : null;
                                    $bandBadge = $rs !== null ? ($rs >= 80 ? 'badge-success' : ($rs >= 60 ? 'badge-info' : ($rs >= 40 ? 'badge-warning' : 'badge-error'))) : '';
                                @endphp
                                <div class="flex items-center gap-3 px-4 py-2.5 hover:bg-base-50">
                                    <div class="flex-1 min-w-0">
                                        <span class="font-medium text-sm truncate block">{{ $t->targetOrganization?->name ?? '—' }}</span>
                                    </div>
                                    <span class="badge badge-outline badge-xs shrink-0">{{ $t->current_phase }}</span>
                                    @if($rs !== null)
                                    <div class="flex items-center gap-2 shrink-0" style="min-width:120px">
                                        <div class="flex-1 bg-base-200 rounded-full h-1.5">
                                            <div class="h-1.5 rounded-full bg-{{ $rColor }}" style="width: {{ $rs }}%"></div>
                                        </div>
                                        <span class="text-xs font-semibold w-7 text-right">{{ $rs }}</span>
                                    </div>
                                    <span class="badge badge-xs {{ $bandBadge }} shrink-0">{{ $rBand }}</span>
                                    @else
                                    <span class="text-xs text-base-content/30">Chưa đánh giá</span>
                                    @endif
                                    <a href="{{ route('deployment.targets.show', ['vertical' => $vertical->code(), 'target' => $t->id]) }}"
                                       class="btn btn-ghost btn-xs shrink-0">Chi tiết</a>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="card bg-base-100 border border-base-200 shadow-sm">
                        <div class="card-body p-0">
                            <div class="flex items-center justify-between px-4 py-3 border-b border-base-200">
                                <h3 class="font-semibold text-sm">{{ $vertical->targetLabel() }}</h3>
                                <a href="{{ route('deployment.targets.create', ['vertical' => $vertical->code()]) }}"
                                   class="btn btn-primary btn-xs">+ Thêm</a>
                            </div>
                            <div class="px-4 py-8 text-center text-sm text-base-content/40">
                                Chưa có target nào.
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Right: Quick actions + Projects --}}
                <div class="space-y-4">

                    {{-- Quick actions --}}
                    <div class="card bg-base-100 border border-base-200 shadow-sm">
                        <div class="card-body p-4">
                            <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Thao tác nhanh</p>
                            <div class="space-y-1.5">
                                <a href="{{ route('deployment.targets.create', ['vertical' => $vertical->code()]) }}"
                                   class="btn btn-outline btn-sm btn-block justify-start gap-1.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Thêm target
                                </a>
                                <a href="{{ route('deployment.projects.create', ['vertical' => $vertical->code()]) }}"
                                   class="btn btn-outline btn-sm btn-block justify-start gap-1.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    Tạo dự án
                                </a>
                                @if($vIssues > 0)
                                <a href="{{ route('deployment.issues.index', ['vertical' => $vertical->code()]) }}"
                                   class="btn btn-error btn-outline btn-sm btn-block justify-start gap-1.5">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    {{ $vIssues }} issues đang mở
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Projects list --}}
                    @if($vProjects->isNotEmpty())
                    <div class="card bg-base-100 border border-base-200 shadow-sm">
                        <div class="card-body p-0">
                            <div class="flex items-center justify-between px-4 py-3 border-b border-base-200">
                                <h3 class="font-semibold text-sm">Dự án</h3>
                                <a href="{{ route('deployment.projects.index', ['vertical' => $vertical->code()]) }}"
                                   class="btn btn-ghost btn-xs">Tất cả →</a>
                            </div>
                            <div class="divide-y divide-base-200">
                                @foreach($vProjects as $proj)
                                @php
                                    $statusColor = match($proj->status?->value ?? '') {
                                        'active'    => 'badge-success',
                                        'planning'  => 'badge-info',
                                        'on_hold'   => 'badge-warning',
                                        'completed' => 'badge-neutral',
                                        'cancelled' => 'badge-error',
                                        default     => 'badge-ghost',
                                    };
                                    $statusLabel = match($proj->status?->value ?? '') {
                                        'active'    => 'Đang chạy',
                                        'planning'  => 'Lập kế hoạch',
                                        'on_hold'   => 'Tạm dừng',
                                        'completed' => 'Hoàn thành',
                                        'cancelled' => 'Đã hủy',
                                        default     => $proj->status?->value ?? '—',
                                    };
                                @endphp
                                <div class="flex items-center gap-3 px-4 py-2.5">
                                    <div class="flex-1 min-w-0">
                                        <span class="font-medium text-sm truncate block">{{ $proj->name }}</span>
                                        <span class="text-xs text-base-content/40">{{ $proj->code }}</span>
                                    </div>
                                    <span class="badge badge-xs {{ $statusColor }}">{{ $statusLabel }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                </div>{{-- /right sidebar --}}
            </div>{{-- /2-col grid --}}
        </div>{{-- /vertical section --}}
        @endforeach
    @endif

@endsection
