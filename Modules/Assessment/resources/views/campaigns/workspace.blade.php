@extends('layouts.backend')
@section('title', 'Workspace — ' . $campaign->title)

@section('content')

<div class="flex items-center gap-2 text-sm text-base-content/50 mb-4">
    <a href="{{ route('campaigns.index') }}" class="hover:text-primary">Marketplace</a>
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('campaigns.show', $campaign->uuid) }}" class="hover:text-primary">{{ $campaign->title }}</a>
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span>Workspace</span>
</div>

@if(session('success'))
<div class="alert alert-success mb-4"><span>{{ session('success') }}</span></div>
@endif
@if(session('info'))
<div class="alert alert-info mb-4"><span>{{ session('info') }}</span></div>
@endif
@error('submit')
<div class="alert alert-error mb-4"><span>{{ $message }}</span></div>
@enderror

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

  <div class="lg:col-span-2">

    {{-- Header --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm mb-5">
        <div class="card-body py-4 flex-row items-center justify-between">
            <div>
                <h1 class="font-bold text-lg">{{ $campaign->title }}</h1>
                <p class="text-sm text-base-content/50">{{ $campaign->organization?->name }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if($participation->status->value === 'completed')
                <span class="badge badge-success">✓ Đã nộp bài</span>
                @else
                <span class="badge badge-info">Đang làm</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Task list --}}
    <div class="space-y-4">
        @foreach($campaign->sandboxTasks as $ct)
        @php
            $task        = $ct->task;
            $session     = $sessions[$ct->sandbox_task_id] ?? null;
            $isDone      = $session && in_array($session->status, ['completed', 'submitted']);
            $isInProgress= $session && $session->status === 'in_progress';
        @endphp
        <div class="card bg-base-100 border border-base-200 shadow-sm {{ $isDone ? 'border-success/50' : '' }}">
            <div class="card-body">
                <div class="flex items-start gap-4">
                    {{-- Status indicator --}}
                    <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0
                        {{ $isDone ? 'bg-success text-success-content' : ($isInProgress ? 'bg-info/10 text-info' : 'bg-base-200 text-base-content/40') }}">
                        @if($isDone)
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @elseif($isInProgress)
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @else
                        <span class="text-sm font-bold">{{ $loop->iteration }}</span>
                        @endif
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <h3 class="font-semibold">{{ $task?->title ?? 'Task #'.$ct->sandbox_task_id }}</h3>
                            @if($ct->is_required)
                            <span class="badge badge-error badge-xs">Bắt buộc</span>
                            @endif
                            @if($task?->time_limit_minutes)
                            <span class="badge badge-ghost badge-xs">⏱ {{ $task->time_limit_minutes }} phút</span>
                            @endif
                        </div>

                        @if($task?->instruction)
                        <p class="text-sm text-base-content/70 mb-3">{{ Str::limit($task->instruction, 150) }}</p>
                        @endif

                        @if($isDone && $session)
                        <div class="flex items-center gap-4 text-sm">
                            @if($session->final_score !== null)
                            <span class="text-success font-semibold">Điểm: {{ $session->final_score }}</span>
                            @endif
                            @if($session->duration_minutes)
                            <span class="text-base-content/50">{{ $session->duration_minutes }} phút</span>
                            @endif
                        </div>
                        @elseif($isInProgress)
                        <a href="{{ route('campaigns.task', [$campaign->uuid, $ct->sandbox_task_id]) }}"
                           class="btn btn-info btn-sm mt-2">Tiếp tục làm →</a>
                        @elseif($participation->status->value !== 'completed')
                        @if($task)
                        <a href="{{ route('campaigns.task', [$campaign->uuid, $task->id]) }}"
                           class="btn btn-primary btn-sm mt-2">Bắt đầu task →</a>
                        @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

  </div>

  {{-- Sidebar: submit --}}
  <div class="space-y-4">

    <div class="card bg-base-100 border border-base-200 shadow-sm sticky top-4">
        <div class="card-body">
            <h3 class="font-semibold mb-3">Tiến độ</h3>

            @php
                $totalRequired  = $campaign->sandboxTasks->where('is_required', true)->count();
                $doneRequired   = $campaign->sandboxTasks->where('is_required', true)
                    ->filter(fn($ct) => isset($sessions[$ct->sandbox_task_id])
                        && in_array($sessions[$ct->sandbox_task_id]->status, ['completed', 'submitted']))
                    ->count();
                $pct = $totalRequired > 0 ? round($doneRequired / $totalRequired * 100) : 100;
            @endphp

            <div class="flex justify-between text-sm mb-1">
                <span>Task bắt buộc</span>
                <span class="font-semibold">{{ $doneRequired }}/{{ $totalRequired }}</span>
            </div>
            <progress class="progress {{ $pct >= 100 ? 'progress-success' : 'progress-primary' }} w-full mb-4" value="{{ $pct }}" max="100"></progress>

            @if($participation->status->value === 'completed')
            <div class="alert alert-success py-2 mb-3">
                <span class="text-sm font-medium">Đã nộp bài thành công!</span>
            </div>
            @if($participation->result_tdwcf_score)
            <div class="text-center mb-3">
                <div class="text-2xl font-bold text-primary">{{ $participation->result_tdwcf_score }}</div>
                <div class="text-xs text-base-content/50">TDWCF Score</div>
                @if($participation->result_maturity_level)
                <div class="badge badge-primary badge-sm mt-1">{{ $participation->result_maturity_level }}</div>
                @endif
            </div>
            @endif
            <a href="{{ route('passport.index') }}" class="btn btn-ghost btn-sm w-full">Xem trong Passport</a>
            @else
            @if($requiredTasksDone)
            <form method="POST" action="{{ route('campaigns.submit', $campaign->uuid) }}">
                @csrf
                <button class="btn btn-success w-full gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Nộp bài
                </button>
            </form>
            <p class="text-xs text-base-content/40 text-center mt-2">Kết quả sẽ được lưu vào Competency Passport.</p>
            @else
            <button class="btn btn-disabled w-full" disabled>Hoàn thành task để nộp</button>
            @endif
            @endif
        </div>
    </div>

    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body py-3">
            <p class="text-xs text-base-content/50">
                Campaign này <strong>{{ $campaign->is_anonymous_to_org ? 'ẩn danh' : 'không ẩn danh' }}</strong> với tổ chức cho đến khi họ invite bạn.
            </p>
        </div>
    </div>

  </div>

</div>

@endsection
