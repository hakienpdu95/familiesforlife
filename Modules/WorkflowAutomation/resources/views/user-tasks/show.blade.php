@extends('layouts.backend')

@section('title', $task->title)

@section('content')
<div class="max-w-2xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('workflow.tasks.my') }}" class="btn btn-ghost btn-sm btn-square">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-base-content">{{ $task->title }}</h1>
            <p class="text-xs text-base-content/50 mt-0.5">
                Workflow: <strong>{{ $task->execution->workflow->name ?? '—' }}</strong>
                · Tạo {{ $task->created_at->diffForHumans() }}
            </p>
        </div>
    </div>

    {{-- Status / due --}}
    @if($task->due_at)
    <div class="alert {{ $task->due_at->isPast() ? 'alert-error' : ($task->due_at->diffInHours(now()) <= 2 ? 'alert-warning' : 'alert-info') }} py-3 text-sm">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>
            @if($task->due_at->isPast())
                Task này đã quá hạn ({{ $task->due_at->format('d/m/Y H:i') }}).
            @else
                Hạn xử lý: <strong>{{ $task->due_at->format('d/m/Y H:i') }}</strong> ({{ $task->due_at->diffForHumans() }})
            @endif
        </span>
    </div>
    @endif

    {{-- Description --}}
    @if($task->description)
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4">
            <h3 class="font-semibold text-sm mb-2">Mô tả</h3>
            <p class="text-sm text-base-content/70 whitespace-pre-line">{{ $task->description }}</p>
        </div>
    </div>
    @endif

    {{-- Context data (if available) --}}
    @if(!empty($task->context_snapshot))
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4">
            <h3 class="font-semibold text-sm mb-2">Dữ liệu liên quan</h3>
            <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-xs">
                @foreach($task->context_snapshot as $key => $val)
                <span class="text-base-content/40 font-mono">{{ $key }}</span>
                <span class="font-medium break-all">{{ is_array($val) ? json_encode($val) : $val }}</span>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Response form --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-5">
            <h3 class="font-semibold text-base mb-4">Phản hồi của bạn</h3>

            @if(session('success'))
            <div class="alert alert-success text-sm mb-4">{{ session('success') }}</div>
            @endif

            @if($errors->any())
            <div class="alert alert-error text-sm mb-4">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('workflow.tasks.respond', $task->token) }}">
                @csrf

                {{-- Decision --}}
                <div class="form-control mb-4">
                    <label class="label py-0 pb-2">
                        <span class="label-text font-medium">Quyết định <span class="text-error">*</span></span>
                    </label>

                    @php
                        $decisions = (array) ($task->allowed_decisions ?? ['approve', 'reject']);
                    @endphp

                    <div class="flex flex-wrap gap-2">
                        @foreach($decisions as $decision)
                        <label class="cursor-pointer">
                            <input type="radio" name="decision" value="{{ $decision }}" class="sr-only peer"
                                   {{ old('decision') === $decision ? 'checked' : '' }}>
                            <div class="px-4 py-2 rounded-lg border border-base-200 text-sm font-medium
                                        peer-checked:bg-primary peer-checked:text-primary-content peer-checked:border-primary
                                        hover:border-primary/50 transition-all cursor-pointer select-none">
                                {{ match($decision) {
                                    'approve'   => '✓ Phê duyệt',
                                    'reject'    => '✕ Từ chối',
                                    'more_info' => '? Cần thêm thông tin',
                                    'escalate'  => '↑ Báo cáo cấp trên',
                                    default     => $decision,
                                } }}
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('decision')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                {{-- Comment --}}
                <div class="form-control mb-5">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Ghi chú</span>
                        <span class="label-text-alt text-xs text-base-content/40">Tuỳ chọn</span>
                    </label>
                    <textarea name="comment" rows="3"
                              class="textarea textarea-bordered textarea-sm w-full"
                              placeholder="Lý do quyết định, ghi chú thêm...">{{ old('comment') }}</textarea>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-1">
                        Gửi phản hồi
                    </button>
                    <a href="{{ route('workflow.tasks.my') }}" class="btn btn-ghost btn-sm">
                        Để sau
                    </a>
                </div>

            </form>
        </div>
    </div>

</div>
@endsection
