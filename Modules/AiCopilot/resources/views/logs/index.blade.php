@extends('layouts.backend')
@section('title', 'AI Request Logs')

@section('content')
<div x-data="{}">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-base-content">AI Request Logs</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Lịch sử toàn bộ AI request của tổ chức</p>
        </div>
        <a href="{{ route('ai.usage.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            Dashboard
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success py-3 px-4 mb-4 text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-error py-3 px-4 mb-4 text-sm">{{ session('error') }}</div>
    @endif

    {{-- Filters --}}
    <form method="GET" class="flex flex-wrap gap-2 mb-5 items-center">
        <select name="status" class="select select-bordered select-sm"
                onchange="this.form.submit()">
            <option value="">Tất cả status</option>
            @foreach(['pending','processing','done','failed'] as $s)
            <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>

        <select name="agent_id" class="select select-bordered select-sm"
                onchange="this.form.submit()">
            <option value="">Tất cả agents</option>
            @foreach($agents as $ag)
            <option value="{{ $ag->id }}" @selected($agentId == $ag->id)>
                {{ $ag->name }} ({{ $ag->slug }})
            </option>
            @endforeach
        </select>

        @if($status || $agentId)
        <a href="{{ route('ai.logs.index') }}" class="btn btn-ghost btn-sm">Xóa bộ lọc</a>
        @endif
    </form>

    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <table class="table table-sm">
            <thead class="bg-base-200/60">
                <tr>
                    <th>UUID / Agent</th>
                    <th>Status</th>
                    <th>Provider / Model</th>
                    <th class="text-right">Tokens</th>
                    <th class="text-right">Chi phí</th>
                    <th class="text-right">Thời gian</th>
                    <th>Tạo lúc</th>
                    <th class="w-28"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                <tr class="hover:bg-base-200/30">
                    <td>
                        <div class="font-mono text-xs text-base-content/50">{{ substr($req->uuid, 0, 8) }}…</div>
                        <div class="font-mono text-xs text-primary">{{ $req->agent?->slug ?? '—' }}</div>
                    </td>
                    <td>
                        @php $badge = match($req->status) { 'done' => 'badge-success', 'failed' => 'badge-error', 'processing' => 'badge-info', default => 'badge-warning' }; @endphp
                        <span class="badge {{ $badge }} badge-sm">{{ $req->status }}</span>
                        @if($req->status === 'failed' && $req->error_message)
                        <div class="text-xs text-error mt-0.5 max-w-[180px] truncate" title="{{ $req->error_message }}">
                            {{ Str::limit($req->error_message, 40) }}
                        </div>
                        @endif
                    </td>
                    <td>
                        <div class="text-xs font-semibold uppercase">{{ $req->provider }}</div>
                        <div class="font-mono text-xs text-base-content/50">{{ Str::limit($req->model, 25) }}</div>
                    </td>
                    <td class="text-right text-sm tabular-nums">
                        {{ $req->total_tokens > 0 ? number_format($req->total_tokens) : '—' }}
                    </td>
                    <td class="text-right text-xs tabular-nums">
                        {{ $req->cost_usd > 0 ? '$'.number_format($req->cost_usd, 4) : '—' }}
                    </td>
                    <td class="text-right text-xs text-base-content/60">
                        {{ $req->duration_ms ? $req->duration_ms.'ms' : '—' }}
                    </td>
                    <td class="text-xs text-base-content/50">
                        <div>{{ $req->created_at->format('d/m H:i') }}</div>
                    </td>
                    <td>
                        <div class="flex items-center gap-1">
                            <a href="{{ route('ai.logs.show', $req) }}" class="btn btn-ghost btn-xs">Chi tiết</a>
                            @if($req->status === 'failed')
                            <form action="{{ route('ai.logs.retry', $req) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="btn btn-ghost btn-xs text-warning" title="Retry">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-sm text-base-content/40 py-10">
                        Không có request nào khớp bộ lọc.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>

        @if($requests->hasPages())
        <div class="px-4 py-3 border-t border-base-200">
            {{ $requests->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
