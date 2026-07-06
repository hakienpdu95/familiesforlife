@extends('layouts.backend')
@section('title', 'Version ' . $version->version_number . ' — ' . Str::limit($kcItem->title, 40))


@section('content')

<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <span class="badge badge-ghost badge-sm font-mono">v{{ $version->version_number }}</span>
            @if($version->change_summary)
            <span class="text-sm text-base-content/60">{{ $version->change_summary }}</span>
            @endif
        </div>
        <h1 class="text-2xl font-bold text-base-content">{{ $version->title_snapshot }}</h1>
        <p class="text-xs text-base-content/40 mt-1">
            Bởi {{ $version->changedBy?->name }} — {{ $version->changed_at?->format('d/m/Y H:i') }}
        </p>
    </div>

    <div class="flex items-center gap-2 shrink-0">
        <a href="{{ route('backend.kc-items.show', $kcItem) }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Quay lại
        </a>

        @if($canRollback)
        <form method="POST" action="{{ route('backend.kc-items.rollback', [$kcItem, $version->version_number]) }}"
              onsubmit="return confirm('Rollback tài liệu về version {{ $version->version_number }}? Tài liệu sẽ chuyển về draft và cần duyệt lại.')">
            @csrf
            <button type="submit" class="btn btn-warning btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                Rollback về version này
            </button>
        </form>
        @endif
    </div>
</div>

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body">
        <div class="flex items-center gap-2 mb-4">
            <h2 class="card-title text-base">Nội dung tại version {{ $version->version_number }}</h2>
            <div class="badge badge-warning badge-sm">Snapshot lịch sử</div>
        </div>

        @if($version->content_snapshot)
        <div class="prose prose-sm max-w-none text-base-content">
            {!! nl2br(e($version->content_snapshot)) !!}
        </div>
        @else
        <p class="text-sm text-base-content/40 italic">Không có nội dung.</p>
        @endif
    </div>
</div>

@if($version->change_summary)
<div class="alert alert-info mt-4 text-sm">
    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span>Ghi chú thay đổi: {{ $version->change_summary }}</span>
</div>
@endif

@endsection
