{{--
    Activity Log Timeline Partial
    Usage: @include('activitylog::partials.timeline', ['logs' => $model->recentActivityLogs()])
    Optional: $title (string), $limit (int), $showEmpty (bool)
--}}
@php
    $title     = $title     ?? 'Lịch sử hoạt động';
    $showEmpty = $showEmpty ?? true;
@endphp

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body p-5">

        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-base text-base-content">{{ $title }}</h3>
            @if($logs->isNotEmpty())
                <span class="badge badge-ghost badge-sm">{{ $logs->count() }} gần nhất</span>
            @endif
        </div>

        @if($logs->isEmpty())
            @if($showEmpty)
            <div class="flex flex-col items-center justify-center py-8 text-base-content/30">
                <svg class="w-8 h-8 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-sm">Chưa có hoạt động nào trong 30 ngày qua</p>
            </div>
            @endif
        @else
            <ol class="relative border-l border-base-200 space-y-0 ml-2">
                @foreach($logs as $log)
                @php
                    $levelColor = match($log->level?->value ?? 0) {
                        1       => 'bg-base-300',
                        2       => 'bg-info',
                        3       => 'bg-warning',
                        4       => 'bg-error',
                        5       => 'bg-error',
                        default => 'bg-base-300',
                    };
                    $badgeClass = match($log->level?->value ?? 0) {
                        1       => 'badge-ghost',
                        2       => 'badge-info',
                        3       => 'badge-warning',
                        4       => 'badge-error',
                        5       => 'badge-error',
                        default => 'badge-ghost',
                    };
                @endphp
                <li class="mb-5 ml-5">
                    {{-- Timeline dot --}}
                    <span class="absolute -left-[9px] flex items-center justify-center w-[18px] h-[18px] rounded-full ring-2 ring-base-100 {{ $levelColor }}"></span>

                    <div class="flex flex-wrap items-start justify-between gap-1">
                        <div class="flex flex-wrap items-center gap-2">
                            {{-- Level badge --}}
                            <span class="badge badge-xs {{ $badgeClass }}">
                                {{ $log->level?->label() ?? 'Log' }}
                            </span>

                            {{-- Module / Action --}}
                            <span class="text-sm font-medium text-base-content">
                                {{ $log->module }}
                                <span class="text-base-content/40 font-normal">/</span>
                                <span class="font-mono text-xs">{{ $log->action }}</span>
                            </span>
                        </div>

                        {{-- Timestamp --}}
                        <time class="text-xs text-base-content/40 whitespace-nowrap"
                              title="{{ $log->created_at?->format('d/m/Y H:i:s') }}">
                            {{ $log->created_at?->diffForHumans() }}
                        </time>
                    </div>

                    {{-- Description --}}
                    @if($log->description)
                        <p class="text-xs text-base-content/60 mt-0.5">{{ $log->description }}</p>
                    @endif

                    {{-- Actor --}}
                    <div class="flex items-center gap-3 mt-1">
                        @if($log->actor_name)
                            <span class="text-xs text-base-content/40">
                                <span class="font-medium text-base-content/60">{{ $log->actor_name }}</span>
                                @if($log->actor_ip)
                                    · {{ $log->actor_ip }}
                                @endif
                            </span>
                        @endif

                        {{-- Link to full log --}}
                        <a href="{{ route('activitylog.show', $log) }}"
                           class="text-xs text-primary hover:underline ml-auto">
                            Chi tiết →
                        </a>
                    </div>
                </li>
                @endforeach
            </ol>
        @endif

    </div>
</div>
