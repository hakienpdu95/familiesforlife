@extends('layouts.backend')
@section('title', 'Nhiệm vụ — '.$sandboxEnvironment->name)

@section('content')

@if(session('success'))
<div class="alert alert-success mb-4 py-2 px-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-error mb-4 py-2 px-4 text-sm">{{ session('error') }}</div>
@endif

{{-- Header --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div class="flex items-center gap-3">
        <a href="{{ route('backend.sandbox-admin.index') }}" class="btn btn-ghost btn-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div>
            <div class="flex items-center gap-2 flex-wrap">
                <h1 class="text-xl font-bold">Nhiệm vụ: {{ $sandboxEnvironment->name }}</h1>
                @if($sandboxEnvironment->organization_id === null)
                    <span class="badge badge-info badge-xs">Hệ thống — dùng chung</span>
                @else
                    <span class="badge badge-ghost badge-xs">Riêng</span>
                @endif
            </div>
            <p class="text-xs text-base-content/40 mt-0.5">Tier {{ $sandboxEnvironment->tier }} — {{ $sandboxEnvironment->env_code }}</p>
        </div>
    </div>
    @if($canEdit)
    <a href="{{ route('backend.sandbox-admin.task.create', $sandboxEnvironment) }}" class="btn btn-primary btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Thêm nhiệm vụ
    </a>
    @else
    <div class="tooltip tooltip-left" data-tip="Môi trường hệ thống — chỉ super-admin mới thêm/sửa được">
        <button class="btn btn-sm btn-disabled gap-1.5" disabled>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Thêm nhiệm vụ
        </button>
    </div>
    @endif
</div>

@if(! $canEdit)
<div class="alert alert-info mb-4 py-2 px-4 text-sm">
    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    Đây là môi trường hệ thống dùng chung. Bạn có thể xem nhiệm vụ nhưng không thể thêm, sửa hoặc xoá.
</div>
@endif

{{-- Task list --}}
<div class="space-y-3">
    @forelse($tasks as $task)
    <div class="card bg-base-100 shadow-sm border {{ $task->is_active ? 'border-base-200' : 'border-base-300 opacity-60' }}">
        <div class="card-body p-4">
            <div class="flex items-start gap-4">
                <div class="w-7 h-7 rounded-full bg-base-200 text-base-content/40 text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">
                    {{ $task->sort_order ?: $loop->iteration }}
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <h3 class="font-semibold text-sm">{{ $task->title }}</h3>
                        @if(! $task->is_active)
                        <span class="badge badge-ghost badge-xs">Tắt</span>
                        @endif
                    </div>
                    <p class="text-xs text-base-content/50 line-clamp-2 mb-2">{{ $task->instruction }}</p>
                    <div class="flex flex-wrap gap-2 text-xs text-base-content/50">
                        <span class="flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $task->time_limit_minutes }} phút
                        </span>
                        @if($task->ai_tools_allowed)
                        @foreach($task->allowedAiTools() as $tool)
                        <span class="badge badge-ghost badge-xs">{{ $tool }}</span>
                        @endforeach
                        @endif
                    </div>
                </div>

                {{-- Stats --}}
                <div class="text-center shrink-0">
                    <p class="text-xs text-base-content/40">Phiên (org bạn)</p>
                    <p class="font-bold text-sm">{{ $task->completed_count }}</p>
                    <p class="text-xs text-base-content/30">/ {{ $task->sessions_count }} tổng</p>
                </div>

                {{-- Actions --}}
                @if($canEdit)
                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('backend.sandbox-admin.task.edit', $task) }}" class="btn btn-ghost btn-sm">Sửa</a>
                    @if($task->sessions_count === 0)
                    <form method="POST" action="{{ route('backend.sandbox-admin.task.destroy', $task) }}"
                          onsubmit="return confirm('Xoá nhiệm vụ này?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-sm text-error">Xoá</button>
                    </form>
                    @endif
                </div>
                @endif
            </div>

            {{-- Rubric preview --}}
            @if($task->scoring_rubric)
            <div class="mt-3 pt-3 border-t border-base-200">
                <p class="text-xs text-base-content/30 uppercase tracking-wide mb-1">Rubric chấm điểm</p>
                <div class="flex flex-wrap gap-1.5">
                    @foreach($task->scoringRubricItems() as $item)
                    <span class="badge badge-ghost badge-sm">{{ $item }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
    @empty
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body items-center text-center py-10">
            <p class="text-base-content/40 text-sm mb-3">Môi trường này chưa có nhiệm vụ nào.</p>
            @if($canEdit)
            <a href="{{ route('backend.sandbox-admin.task.create', $sandboxEnvironment) }}" class="btn btn-primary btn-sm">Thêm nhiệm vụ đầu tiên</a>
            @endif
        </div>
    </div>
    @endforelse
</div>

@endsection
