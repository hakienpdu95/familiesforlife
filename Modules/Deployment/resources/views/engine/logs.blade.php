@extends('layouts.backend')
@section('title', 'Deploy Logs — ' . $deployment->organizationSolution->name)

@section('content')
<div>
    @foreach(['success','error'] as $type)
        @if(session($type))
        <div class="alert alert-{{ $type }} mb-4 text-sm"><span>{{ session($type) }}</span></div>
        @endif
    @endforeach

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">{{ $deployment->organizationSolution->name }}</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                @php
                    $status = \Modules\Deployment\Enums\DeploymentStatus::from($deployment->status);
                @endphp
                <span class="badge {{ $status->badgeClass() }} badge-sm align-middle">{{ $status->label() }}</span>
                <span class="align-middle ml-1">Blueprint v{{ $deployment->blueprintVersion?->version }}</span>
                @if ($deployment->project)
                <span class="align-middle ml-1">· Project #{{ $deployment->project->id }} ({{ $deployment->project->name }})</span>
                @endif
            </p>
        </div>
        <a href="{{ route('deployments.snapshots', $deployment) }}" class="btn btn-ghost btn-sm">Xem snapshot</a>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4">
            <ul class="space-y-3">
                @forelse ($deployment->logs as $log)
                <li class="flex items-start gap-3 border-b border-base-200 pb-3 last:border-0">
                    @php
                        $badge = match($log->level) {
                            'error'   => 'badge-error',
                            'warning' => 'badge-warning',
                            default   => 'badge-ghost',
                        };
                    @endphp
                    <span class="badge {{ $badge }} badge-sm mt-0.5">{{ $log->level }}</span>
                    <div class="flex-1">
                        <div class="font-mono text-xs text-base-content/50">{{ $log->step }}</div>
                        <div class="text-sm">{{ $log->message }}</div>
                    </div>
                    <span class="text-xs text-base-content/40 whitespace-nowrap">{{ $log->created_at?->format('d/m/Y H:i:s') }}</span>
                </li>
                @empty
                <li class="text-sm text-base-content/40">Chưa có log nào.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
