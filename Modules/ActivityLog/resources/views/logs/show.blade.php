@extends('layouts.backend')
@section('title', 'Log #' . $log->id)


@section('content')
<div class="space-y-5 max-w-4xl">

    {{-- ── Header ─────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-base-content">Log #{{ $log->id }}</h1>
                @if($log->level)
                    <span class="badge badge-sm {{ $log->level->badgeClass() }}">
                        {{ $log->level->label() }}
                    </span>
                @endif
            </div>
            <p class="text-sm text-base-content/50 mt-0.5">
                {{ $log->module }} / {{ $log->action }}
            </p>
        </div>
        <a href="{{ route('activitylog.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
            </svg>
            Danh sách
        </a>
    </div>

    {{-- ── Main info ───────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-5">
            <h2 class="card-title text-base mb-3">Thông tin chung</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3 text-sm">

                <div class="flex flex-col gap-0.5">
                    <dt class="text-xs font-medium text-base-content/50 uppercase tracking-wide">Thời gian</dt>
                    <dd class="font-mono text-base-content">
                        {{ $log->created_at?->format('d/m/Y H:i:s') ?? '—' }}
                    </dd>
                </div>

                <div class="flex flex-col gap-0.5">
                    <dt class="text-xs font-medium text-base-content/50 uppercase tracking-wide">Cấp độ</dt>
                    <dd>
                        @if($log->level)
                            <span class="badge badge-sm {{ $log->level->badgeClass() }}">
                                {{ $log->level->value }} — {{ $log->level->label() }}
                            </span>
                        @else
                            <span class="text-base-content/40">—</span>
                        @endif
                    </dd>
                </div>

                <div class="flex flex-col gap-0.5">
                    <dt class="text-xs font-medium text-base-content/50 uppercase tracking-wide">Module</dt>
                    <dd class="font-medium">{{ $log->module ?: '—' }}</dd>
                </div>

                <div class="flex flex-col gap-0.5">
                    <dt class="text-xs font-medium text-base-content/50 uppercase tracking-wide">Action</dt>
                    <dd class="font-mono text-sm">{{ $log->action ?: '—' }}</dd>
                </div>

                <div class="flex flex-col gap-0.5">
                    <dt class="text-xs font-medium text-base-content/50 uppercase tracking-wide">Actor</dt>
                    <dd>
                        @if($log->actor_name)
                            {{ $log->actor_name }}
                            @if($log->causer_id)
                                <span class="text-base-content/40 text-xs">(ID: {{ $log->causer_id }})</span>
                            @endif
                        @elseif($log->causer_id)
                            <span class="text-base-content/40">ID: {{ $log->causer_id }}</span>
                        @else
                            <span class="text-base-content/40">System</span>
                        @endif
                    </dd>
                </div>

                <div class="flex flex-col gap-0.5">
                    <dt class="text-xs font-medium text-base-content/50 uppercase tracking-wide">IP</dt>
                    <dd class="font-mono text-sm">{{ $log->actor_ip ?: '—' }}</dd>
                </div>

                @if($log->subject_label || $log->subject_type)
                <div class="flex flex-col gap-0.5">
                    <dt class="text-xs font-medium text-base-content/50 uppercase tracking-wide">Subject</dt>
                    <dd>
                        @if($log->subject_label)
                            <span class="font-medium">{{ $log->subject_label }}</span>
                        @endif
                        @if($log->subject_type)
                            <span class="block text-xs text-base-content/40 font-mono mt-0.5">
                                {{ class_basename($log->subject_type) }}
                                @if($log->subject_id) #{{ $log->subject_id }}@endif
                            </span>
                        @endif
                    </dd>
                </div>
                @endif

                @if($log->description)
                <div class="flex flex-col gap-0.5 sm:col-span-2">
                    <dt class="text-xs font-medium text-base-content/50 uppercase tracking-wide">Mô tả</dt>
                    <dd>{{ $log->description }}</dd>
                </div>
                @endif

                @if($log->request_id)
                <div class="flex flex-col gap-0.5 sm:col-span-2">
                    <dt class="text-xs font-medium text-base-content/50 uppercase tracking-wide">Request ID</dt>
                    <dd class="font-mono text-xs text-base-content/70 break-all">{{ $log->request_id }}</dd>
                </div>
                @endif

                @if($log->session_id)
                <div class="flex flex-col gap-0.5 sm:col-span-2">
                    <dt class="text-xs font-medium text-base-content/50 uppercase tracking-wide">Session ID</dt>
                    <dd class="font-mono text-xs text-base-content/70 truncate">{{ $log->session_id }}</dd>
                </div>
                @endif

            </dl>
        </div>
    </div>

    {{-- ── HTTP context ────────────────────────────────────────────────── --}}
    @if($http)
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-5">
            <h2 class="card-title text-base mb-3">HTTP Request</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-3 text-sm">

                <div class="flex flex-col gap-0.5">
                    <dt class="text-xs font-medium text-base-content/50 uppercase tracking-wide">Method</dt>
                    <dd>
                        @php
                            $methodLabel = $http->http_method?->name ?? '—';
                            $methodColor = match($methodLabel) {
                                'GET'    => 'badge-info',
                                'POST'   => 'badge-success',
                                'PUT','PATCH' => 'badge-warning',
                                'DELETE' => 'badge-error',
                                default  => 'badge-ghost',
                            };
                        @endphp
                        <span class="badge badge-sm {{ $methodColor }} font-mono">{{ $methodLabel }}</span>
                    </dd>
                </div>

                <div class="flex flex-col gap-0.5">
                    <dt class="text-xs font-medium text-base-content/50 uppercase tracking-wide">Status</dt>
                    <dd>
                        @if($http->status_code)
                            @php
                                $sc = $http->status_code;
                                $scColor = $sc >= 500 ? 'badge-error'
                                    : ($sc >= 400 ? 'badge-warning'
                                    : ($sc >= 300 ? 'badge-info' : 'badge-success'));
                            @endphp
                            <span class="badge badge-sm {{ $scColor }} font-mono">{{ $sc }}</span>
                        @else
                            <span class="text-base-content/40">—</span>
                        @endif
                    </dd>
                </div>

                <div class="flex flex-col gap-0.5 sm:col-span-2">
                    <dt class="text-xs font-medium text-base-content/50 uppercase tracking-wide">URL</dt>
                    <dd class="font-mono text-xs text-base-content/80 break-all">{{ $http->url ?: '—' }}</dd>
                </div>

                @if($http->route_name)
                <div class="flex flex-col gap-0.5">
                    <dt class="text-xs font-medium text-base-content/50 uppercase tracking-wide">Route</dt>
                    <dd class="font-mono text-xs">{{ $http->route_name }}</dd>
                </div>
                @endif

                @if($http->duration_ms !== null)
                <div class="flex flex-col gap-0.5">
                    <dt class="text-xs font-medium text-base-content/50 uppercase tracking-wide">Thời gian xử lý</dt>
                    <dd class="font-mono text-sm">{{ number_format($http->duration_ms) }} ms</dd>
                </div>
                @endif

                @if($http->user_agent)
                <div class="flex flex-col gap-0.5 sm:col-span-2">
                    <dt class="text-xs font-medium text-base-content/50 uppercase tracking-wide">User Agent</dt>
                    <dd class="text-xs text-base-content/60 break-all">{{ $http->user_agent }}</dd>
                </div>
                @endif

            </dl>
        </div>
    </div>
    @endif

    {{-- ── Context (EAV) ───────────────────────────────────────────────── --}}
    @if($contexts->isNotEmpty())
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-5">
            <h2 class="card-title text-base mb-3">Properties</h2>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th class="w-1/3 text-xs uppercase tracking-wide text-base-content/50">Key</th>
                            <th class="text-xs uppercase tracking-wide text-base-content/50">Value</th>
                            <th class="w-24 text-xs uppercase tracking-wide text-base-content/50">Loại</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contexts as $ctx)
                        <tr>
                            <td class="font-mono text-sm font-medium">{{ $ctx->key_name }}</td>
                            <td class="font-mono text-sm break-all">
                                @php $val = $ctx->typedValue(); @endphp
                                @if(is_bool($val))
                                    <span class="badge badge-sm {{ $val ? 'badge-success' : 'badge-ghost' }}">
                                        {{ $val ? 'true' : 'false' }}
                                    </span>
                                @elseif($val === null)
                                    <span class="text-base-content/30">null</span>
                                @else
                                    {{ $val }}
                                @endif
                            </td>
                            <td class="text-xs text-base-content/40">
                                @if($ctx->val_boolean !== null) boolean
                                @elseif($ctx->val_integer !== null) integer
                                @elseif($ctx->val_decimal !== null) decimal
                                @elseif($ctx->val_datetime !== null) datetime
                                @else string
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Same request ────────────────────────────────────────────────── --}}
    @if($sameRequest->isNotEmpty())
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-5">
            <h2 class="card-title text-base mb-1">Logs cùng request</h2>
            <p class="text-xs text-base-content/40 mb-3 font-mono">{{ $log->request_id }}</p>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th class="text-xs uppercase tracking-wide text-base-content/50">Thời gian</th>
                            <th class="text-xs uppercase tracking-wide text-base-content/50">Cấp độ</th>
                            <th class="text-xs uppercase tracking-wide text-base-content/50">Module / Action</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sameRequest as $rel)
                        <tr>
                            <td class="font-mono text-xs text-base-content/70">
                                {{ $rel->created_at?->format('H:i:s.v') }}
                            </td>
                            <td>
                                @if($rel->level)
                                    <span class="badge badge-xs {{ $rel->level->badgeClass() }}">
                                        {{ $rel->level->label() }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-sm">
                                <span class="font-medium">{{ $rel->module }}</span>
                                <span class="text-base-content/40">/</span>
                                <span class="font-mono text-xs">{{ $rel->action }}</span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('activitylog.show', $rel) }}"
                                   class="btn btn-xs btn-ghost">Xem</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Subject history ─────────────────────────────────────────────── --}}
    @if($subjectHistory->isNotEmpty())
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-5">
            <h2 class="card-title text-base mb-1">Lịch sử subject</h2>
            <p class="text-xs text-base-content/40 mb-3">
                {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                @if($log->subject_label) — {{ $log->subject_label }}@endif
            </p>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th class="text-xs uppercase tracking-wide text-base-content/50">Thời gian</th>
                            <th class="text-xs uppercase tracking-wide text-base-content/50">Cấp độ</th>
                            <th class="text-xs uppercase tracking-wide text-base-content/50">Module / Action</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subjectHistory as $rel)
                        <tr>
                            <td class="font-mono text-xs text-base-content/70">
                                {{ $rel->created_at?->format('d/m/Y H:i:s') }}
                            </td>
                            <td>
                                @if($rel->level)
                                    <span class="badge badge-xs {{ $rel->level->badgeClass() }}">
                                        {{ $rel->level->label() }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-sm">
                                <span class="font-medium">{{ $rel->module }}</span>
                                <span class="text-base-content/40">/</span>
                                <span class="font-mono text-xs">{{ $rel->action }}</span>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('activitylog.show', $rel) }}"
                                   class="btn btn-xs btn-ghost">Xem</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
