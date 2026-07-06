@extends('layouts.backend')

@section('title', 'Task chờ phê duyệt của tôi')

@section('content')
<div class="space-y-4">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Task của tôi</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Các workflow đang chờ bạn phê duyệt hoặc xử lý.</p>
        </div>
    </div>

    @if($tasks->isEmpty())
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body py-16 text-center">
            <svg class="w-12 h-12 mx-auto text-base-content/20 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            <p class="text-base-content/40 text-sm font-medium">Không có task nào đang chờ</p>
            <p class="text-xs text-base-content/30 mt-1">Khi workflow cần bạn phê duyệt, task sẽ xuất hiện ở đây.</p>
        </div>
    </div>
    @else
    <div class="space-y-3">
        @foreach($tasks as $task)
        <div class="card bg-base-100 shadow-sm border border-base-200 hover:border-primary/30 transition-colors">
            <div class="card-body p-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="badge badge-warning badge-sm">Chờ xử lý</span>
                            @if($task->due_at && $task->due_at->isPast())
                            <span class="badge badge-error badge-sm">Quá hạn</span>
                            @elseif($task->due_at && $task->due_at->diffInHours(now()) <= 2)
                            <span class="badge badge-warning badge-outline badge-sm">Sắp hết hạn</span>
                            @endif
                        </div>
                        <h3 class="font-semibold text-sm">{{ $task->title }}</h3>
                        @if($task->description)
                        <p class="text-xs text-base-content/50 mt-0.5 line-clamp-2">{{ $task->description }}</p>
                        @endif
                        <div class="flex flex-wrap items-center gap-3 mt-2 text-xs text-base-content/40">
                            <span>Workflow: <strong class="text-base-content/60">{{ $task->execution->workflow->name ?? '—' }}</strong></span>
                            @if($task->due_at)
                            <span>Hạn: <strong class="text-base-content/60">{{ $task->due_at->diffForHumans() }}</strong></span>
                            @endif
                            <span>Tạo: {{ $task->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                    <a href="{{ route('workflow.tasks.show', $task->token) }}"
                       class="btn btn-primary btn-sm shrink-0">
                        Xử lý
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{ $tasks->links() }}
    @endif

</div>
@endsection
