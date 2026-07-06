@extends('layouts.backend')
@section('title', 'SOP chờ phê duyệt')


@section('content')
<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-xl font-bold text-base-content">SOP chờ phê duyệt</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Danh sách phiên bản SOP đang chờ bạn xem xét</p>
    </div>
    <span class="badge badge-warning badge-lg">{{ $pendingFlows->count() }}</span>
</div>

@if($pendingFlows->isEmpty())
<div class="card bg-base-100 border border-base-200 shadow-sm">
    <div class="card-body py-14 flex flex-col items-center text-base-content/30">
        <svg class="w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-sm font-medium">Không có SOP nào đang chờ duyệt</p>
    </div>
</div>
@else
<div class="space-y-3">
    @foreach($pendingFlows as $flow)
    @php $ver = $flow->version; $sop = $ver->sop; @endphp
    <div class="card bg-base-100 border border-base-200 shadow-sm hover:border-primary/30 transition-colors">
        <div class="card-body p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <a href="{{ route('backend.sop.show', $sop) }}"
                           class="font-mono font-bold text-primary hover:underline">{{ $sop->code }}</a>
                        <span class="badge badge-sm badge-neutral">v{{ $ver->version_number }}</span>
                        <span class="badge badge-sm badge-warning">Chờ duyệt — Bước {{ $flow->step_order }}</span>
                    </div>
                    <p class="font-medium text-base-content truncate">{{ $sop->title }}</p>
                    @if($ver->change_summary)
                    <p class="text-sm text-base-content/50 mt-1 leading-relaxed line-clamp-2">{{ $ver->change_summary }}</p>
                    @endif
                    <div class="flex flex-wrap gap-x-4 mt-2 text-xs text-base-content/40">
                        <span>Gửi bởi {{ $ver->createdBy?->name ?? '—' }}</span>
                        <span>{{ $ver->created_at?->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
                <a href="{{ route('backend.sop.versions.review', [$sop, $ver]) }}"
                   class="btn btn-warning btn-sm gap-1.5 shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Xem & Duyệt
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
