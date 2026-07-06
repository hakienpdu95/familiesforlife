@extends('layouts.backend')
@section('title', 'Execution ' . substr($execution->run_id, 0, 8))


@section('content')
@php
$status = $execution->status_enum;
@endphp

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <div class="flex items-center gap-2">
            <h1 class="text-xl font-bold text-base-content font-mono">{{ substr($execution->run_id, 0, 16) }}…</h1>
            <span class="badge badge-soft {{ $status->badge() }}">{{ $status->label() }}</span>
        </div>
        <p class="text-sm text-base-content/50 mt-0.5">
            Trigger: <span class="font-mono">{{ $execution->trigger_type }}</span>
            · Nguồn: {{ $execution->source_module }}
        </p>
    </div>
    <button onclick="history.back()" class="btn btn-ghost btn-sm">← Quay lại</button>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">

    {{-- Stat cards --}}
    <div class="stats stats-vertical shadow-sm border border-base-200 lg:col-span-1">
        <div class="stat py-3 px-4">
            <div class="stat-title text-xs">Steps</div>
            <div class="stat-value text-2xl">{{ $execution->steps_total }}</div>
            <div class="stat-desc text-xs">
                <span class="text-success">✓ {{ $execution->steps_success }}</span>
                · <span class="text-error">✗ {{ $execution->steps_failed }}</span>
                @if($execution->steps_scheduled > 0)· ⏱ {{ $execution->steps_scheduled }}@endif
            </div>
        </div>
        <div class="stat py-3 px-4">
            <div class="stat-title text-xs">Thời gian thực thi</div>
            <div class="stat-value text-2xl">{{ number_format($execution->duration_ms) }}<span class="text-sm font-normal ml-1">ms</span></div>
        </div>
        <div class="stat py-3 px-4">
            <div class="stat-title text-xs">Kích hoạt lúc</div>
            <div class="stat-value text-base font-normal">{{ $execution->triggered_at?->format('d/m/Y H:i:s') ?? '—' }}</div>
        </div>
        <div class="stat py-3 px-4">
            <div class="stat-title text-xs">Hoàn thành lúc</div>
            <div class="stat-value text-base font-normal">{{ $execution->finished_at?->format('d/m/Y H:i:s') ?? '—' }}</div>
        </div>
    </div>

    {{-- Detail info --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 lg:col-span-2">
        <div class="card-body py-3 px-4">
            <h3 class="font-semibold text-sm mb-3">Thông tin payload</h3>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
                <div>
                    <dt class="text-xs text-base-content/40 uppercase tracking-wide mb-0.5">Subject type</dt>
                    <dd class="font-mono text-xs">{{ $execution->subject_type ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-base-content/40 uppercase tracking-wide mb-0.5">Subject ID</dt>
                    <dd class="font-mono text-xs">{{ $execution->subject_id ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-base-content/40 uppercase tracking-wide mb-0.5">Actor ID</dt>
                    <dd class="font-mono text-xs">{{ $execution->actor_id ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-base-content/40 uppercase tracking-wide mb-0.5">Condition result</dt>
                    <dd>
                        @if($execution->condition_result === null)
                            <span class="text-base-content/30 text-xs">—</span>
                        @elseif($execution->condition_result)
                            <span class="text-success text-xs">✓ Pass</span>
                        @else
                            <span class="text-error text-xs">✗ Fail</span>
                        @endif
                    </dd>
                </div>
                @if($execution->skip_reason)
                <div class="col-span-2">
                    <dt class="text-xs text-base-content/40 uppercase tracking-wide mb-0.5">Lý do skip</dt>
                    <dd class="text-xs text-warning">{{ $execution->skip_reason }}</dd>
                </div>
                @endif
                <div class="col-span-2">
                    <dt class="text-xs text-base-content/40 uppercase tracking-wide mb-0.5">Run ID</dt>
                    <dd class="font-mono text-xs break-all">{{ $execution->run_id }}</dd>
                </div>
            </dl>
        </div>
    </div>

</div>

{{-- Step logs --}}
@if($execution->steps->isNotEmpty())
<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body p-0">
        <div class="px-5 py-3 border-b border-base-200">
            <h3 class="font-semibold text-sm">Chi tiết từng bước</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr class="text-xs text-base-content/50 uppercase tracking-wide">
                        <th class="w-10">#</th>
                        <th>Action type</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-right">Thời gian</th>
                        <th class="text-center">Attempts</th>
                        <th>Lỗi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($execution->steps as $step)
                    @php
                        $stepBadge = match($step->status) {
                            1 => 'badge-success',
                            2 => 'badge-warning',
                            3 => 'badge-error',
                            4 => 'badge-info',
                            default => 'badge-ghost',
                        };
                        $stepLabel = match($step->status) {
                            1 => 'Pass', 2 => 'Skip', 3 => 'Fail', 4 => 'Scheduled', default => '?',
                        };
                    @endphp
                    <tr class="hover">
                        <td class="text-base-content/40 font-mono text-xs">{{ $step->sort_order + 1 }}</td>
                        <td class="font-mono text-xs">{{ $step->action_type }}</td>
                        <td class="text-center">
                            <span class="badge badge-sm badge-soft {{ $stepBadge }}">{{ $stepLabel }}</span>
                        </td>
                        <td class="text-right font-mono text-xs">{{ number_format($step->duration_ms) }} ms</td>
                        <td class="text-center text-xs">{{ $step->attempts }}</td>
                        <td class="text-xs text-error max-w-xs truncate">{{ $step->error_message ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection
