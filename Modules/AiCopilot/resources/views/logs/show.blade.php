@extends('layouts.backend')
@section('title', 'AI Request — ' . substr($aiRequest->uuid, 0, 8))

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-base-content">Chi tiết AI Request</h1>
        <p class="font-mono text-sm text-base-content/50 mt-0.5">{{ $aiRequest->uuid }}</p>
    </div>
    <div class="flex gap-2">
        @if($aiRequest->status === 'failed')
        <form action="{{ route('ai.logs.retry', $aiRequest) }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="btn btn-warning btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Retry
            </button>
        </form>
        @endif
        <a href="{{ route('ai.logs.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Quay lại
        </a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success py-3 px-4 mb-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-error py-3 px-4 mb-4 text-sm">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- ── Metrics sidebar ──────────────────────────────────────────────────── --}}
    <div class="flex flex-col gap-4">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body gap-3">
                <h3 class="font-semibold text-sm">Trạng thái</h3>
                @php $badge = match($aiRequest->status) { 'done' => 'badge-success', 'failed' => 'badge-error', 'processing' => 'badge-info', default => 'badge-warning' }; @endphp
                <span class="badge {{ $badge }}">{{ strtoupper($aiRequest->status) }}</span>

                @if($aiRequest->error_message)
                <div class="alert alert-error py-2 px-3 text-xs mt-1">
                    <p class="font-semibold">Lỗi:</p>
                    <p class="font-mono break-all">{{ $aiRequest->error_message }}</p>
                </div>
                @endif
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body gap-2">
                <h3 class="font-semibold text-sm">Metrics</h3>
                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Input tokens</dt>
                        <dd class="tabular-nums font-mono">{{ number_format($aiRequest->input_tokens) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Output tokens</dt>
                        <dd class="tabular-nums font-mono">{{ number_format($aiRequest->output_tokens) }}</dd>
                    </div>
                    <div class="flex justify-between font-medium">
                        <dt>Total tokens</dt>
                        <dd class="tabular-nums font-mono">{{ number_format($aiRequest->total_tokens) }}</dd>
                    </div>
                    <div class="divider my-0"></div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Chi phí</dt>
                        <dd class="font-mono">{{ $aiRequest->costFormatted() }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Thời gian</dt>
                        <dd class="font-mono">{{ $aiRequest->duration_ms ? $aiRequest->duration_ms.'ms' : '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Finish reason</dt>
                        <dd class="font-mono">{{ $aiRequest->finish_reason ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body gap-2">
                <h3 class="font-semibold text-sm">Provider</h3>
                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Provider</dt>
                        <dd class="font-semibold uppercase">{{ $aiRequest->provider }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Model</dt>
                        <dd class="font-mono text-xs">{{ $aiRequest->model }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">Agent</dt>
                        <dd class="font-mono text-xs text-primary">{{ $aiRequest->agent?->slug ?? '—' }}</dd>
                    </div>
                    @if($aiRequest->user)
                    <div class="flex justify-between">
                        <dt class="text-base-content/50">User</dt>
                        <dd class="text-xs">{{ $aiRequest->user->name ?? $aiRequest->user->email }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body gap-2">
                <h3 class="font-semibold text-sm">Timeline</h3>
                <dl class="space-y-1.5 text-xs text-base-content/60">
                    <div><dt class="font-medium text-base-content/80">Tạo lúc</dt><dd>{{ $aiRequest->created_at->format('d/m/Y H:i:s') }}</dd></div>
                    @if($aiRequest->queued_at)
                    <div><dt class="font-medium text-base-content/80">Queue lúc</dt><dd>{{ $aiRequest->queued_at->format('d/m/Y H:i:s') }}</dd></div>
                    @endif
                    @if($aiRequest->started_at)
                    <div><dt class="font-medium text-base-content/80">Bắt đầu</dt><dd>{{ $aiRequest->started_at->format('d/m/Y H:i:s') }}</dd></div>
                    @endif
                    @if($aiRequest->completed_at)
                    <div><dt class="font-medium text-base-content/80">Hoàn thành</dt><dd>{{ $aiRequest->completed_at->format('d/m/Y H:i:s') }}</dd></div>
                    @endif
                </dl>
            </div>
        </div>

    </div>

    {{-- ── Content panes ────────────────────────────────────────────────────── --}}
    <div class="lg:col-span-2 flex flex-col gap-5">

        @if($aiRequest->input_variables)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body gap-2">
                <h3 class="font-semibold text-sm">Input Variables</h3>
                <div class="overflow-x-auto">
                    <table class="table table-xs">
                        <thead><tr><th>Key</th><th>Value</th></tr></thead>
                        <tbody>
                            @foreach($aiRequest->input_variables as $key => $val)
                            <tr>
                                <td class="font-mono text-xs text-secondary w-40">{{ $key }}</td>
                                <td class="text-sm whitespace-pre-wrap break-words">{{ $val }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if($aiRequest->rendered_prompt)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body gap-2">
                <h3 class="font-semibold text-sm">Rendered Prompt (User Message)</h3>
                <pre class="text-xs bg-base-200 rounded-lg p-3 whitespace-pre-wrap break-words max-h-60 overflow-y-auto">{{ $aiRequest->rendered_prompt }}</pre>
            </div>
        </div>
        @endif

        @if($aiRequest->prompt)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body gap-2">
                <h3 class="font-semibold text-sm">System Prompt ({{ $aiRequest->prompt->name }} v{{ $aiRequest->prompt->version }})</h3>
                <pre class="text-xs bg-base-200 rounded-lg p-3 whitespace-pre-wrap break-words max-h-40 overflow-y-auto">{{ $aiRequest->prompt->system_prompt }}</pre>
            </div>
        </div>
        @endif

        @if($aiRequest->ai_output)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body gap-2">
                <h3 class="font-semibold text-sm">AI Output</h3>
                <div class="prose prose-sm max-w-none bg-base-200 rounded-lg p-3 max-h-96 overflow-y-auto">
                    {!! nl2br(e($aiRequest->ai_output)) !!}
                </div>
            </div>
        </div>
        @endif

    </div>

</div>

@endsection
