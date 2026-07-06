@extends('layouts.backend')
@section('title', $task->title . ' — ' . $campaign->title)

@section('content')

<div class="flex items-center gap-2 text-sm text-base-content/50 mb-4">
    <a href="{{ route('campaigns.index') }}" class="hover:text-primary">Marketplace</a>
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('campaigns.workspace', $campaign->uuid) }}" class="hover:text-primary">{{ $campaign->title }}</a>
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="truncate max-w-xs">{{ $task->title }}</span>
</div>

@if(session('success'))
<div class="alert alert-success mb-4"><span>{{ session('success') }}</span></div>
@endif
@if(session('info'))
<div class="alert alert-info mb-4"><span>{{ session('info') }}</span></div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

  {{-- Main content --}}
  <div class="lg:col-span-2 space-y-5">

    {{-- Task instruction card --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">

            <div class="flex items-start gap-3 mb-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <h1 class="text-xl font-bold">{{ $task->title }}</h1>
                        @if($campaignTask->is_required)
                        <span class="badge badge-error badge-sm">Bắt buộc</span>
                        @endif
                    </div>
                    <p class="text-sm text-base-content/50">{{ $campaign->organization?->name }}</p>
                </div>
                @if($task->time_limit_minutes)
                <div class="text-center shrink-0">
                    <div class="text-2xl font-bold text-primary">{{ $task->time_limit_minutes }}'</div>
                    <div class="text-xs text-base-content/40">phút</div>
                </div>
                @endif
            </div>

            <div class="divider my-2"></div>

            {{-- Instruction --}}
            @if($task->instruction)
            <div class="mb-4">
                <h3 class="text-sm font-semibold text-base-content/70 uppercase tracking-wide mb-2">Hướng dẫn</h3>
                <div class="prose prose-sm max-w-none text-base-content/80">
                    {!! nl2br(e($task->instruction)) !!}
                </div>
            </div>
            @endif

            {{-- Expected output --}}
            @if($task->expected_output)
            <div class="p-3 bg-primary/5 border border-primary/20 rounded-lg mb-4">
                <h3 class="text-xs font-semibold text-primary uppercase tracking-wide mb-1.5">Đầu ra kỳ vọng</h3>
                <p class="text-sm text-base-content/70">{{ $task->expected_output }}</p>
            </div>
            @endif

            {{-- Scoring rubric --}}
            @if($task->scoringRubricItems())
            <div class="mb-4">
                <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wide mb-2">Tiêu chí chấm điểm</h3>
                <ul class="space-y-1">
                    @foreach($task->scoringRubricItems() as $item)
                    <li class="flex items-start gap-2 text-sm text-base-content/70">
                        <svg class="w-4 h-4 text-primary shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                        {{ $item }}
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Allowed AI tools --}}
            @if($task->allowedAiTools())
            <div class="flex flex-wrap gap-1.5 mt-2">
                <span class="text-xs text-base-content/40">Công cụ AI cho phép:</span>
                @foreach($task->allowedAiTools() as $tool)
                <span class="badge badge-ghost badge-xs">{{ $tool }}</span>
                @endforeach
            </div>
            @endif

        </div>
    </div>

    {{-- Submission area --}}
    @if($session && in_array($session->status, ['completed', 'submitted']))

    {{-- Done state --}}
    <div class="card bg-success/5 border border-success/30 shadow-sm">
        <div class="card-body">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-full bg-success text-success-content flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <p class="font-semibold text-success">Đã hoàn thành task</p>
                    @if($session->final_score !== null)
                    <p class="text-sm text-base-content/60">Điểm tạm: <strong>{{ $session->final_score }}</strong>/100 — tổ chức có thể điều chỉnh sau khi xem xét</p>
                    @endif
                </div>
            </div>
            @if($session->submission)
            <div class="bg-base-200/60 rounded-lg p-3 text-sm text-base-content/70 max-h-48 overflow-y-auto">
                <p class="font-medium text-xs text-base-content/40 uppercase tracking-wide mb-1.5">Bài nộp của bạn</p>
                {!! nl2br(e($session->submission->submitted_content)) !!}
            </div>
            @endif
            <div class="flex gap-2 mt-3">
                <a href="{{ route('campaigns.workspace', $campaign->uuid) }}" class="btn btn-ghost btn-sm">← Về Workspace</a>
            </div>
        </div>
    </div>

    @elseif($session && $session->status === 'in_progress')

    {{-- Submission form --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h2 class="font-semibold mb-1">Nộp bài</h2>
            <p class="text-sm text-base-content/50 mb-4">
                Bắt đầu lúc {{ $session->started_at?->format('H:i d/m/Y') }}
                @if($task->time_limit_minutes)
                · Giới hạn {{ $task->time_limit_minutes }} phút
                @endif
            </p>

            <form method="POST" action="{{ route('campaigns.task.complete', [$campaign->uuid, $task->id]) }}">
                @csrf

                <div class="mb-4">
                    <label class="label label-text text-sm font-medium pb-1">
                        Bài làm <span class="text-error">*</span>
                        <span class="text-xs text-base-content/40 font-normal ml-1">Tối thiểu 20 ký tự</span>
                    </label>
                    <textarea name="submitted_content" rows="12"
                              class="textarea textarea-bordered w-full text-sm leading-relaxed @error('submitted_content') textarea-error @enderror"
                              placeholder="Viết bài làm của bạn ở đây…">{{ old('submitted_content') }}</textarea>
                    @error('submitted_content')
                    <p class="text-xs text-error mt-1">{{ $message }}</p>
                    @enderror
                </div>

                @if($task->allowedAiTools())
                <div class="mb-5">
                    <label class="label label-text text-sm font-medium pb-1">
                        Công cụ AI đã dùng
                        <span class="text-xs text-base-content/40 font-normal ml-1">Không bắt buộc</span>
                    </label>
                    <input type="text" name="ai_tools_used"
                           value="{{ old('ai_tools_used') }}"
                           class="input input-bordered w-full text-sm"
                           placeholder="VD: ChatGPT, Copilot…">
                </div>
                @else
                <input type="hidden" name="ai_tools_used" value="">
                @endif

                <div class="flex items-center gap-3 flex-wrap">
                    <button type="submit" class="btn btn-primary btn-sm gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Nộp bài
                    </button>
                    <a href="{{ route('campaigns.workspace', $campaign->uuid) }}" class="btn btn-ghost btn-sm">← Quay lại workspace</a>
                </div>
            </form>
        </div>
    </div>

    @else

    {{-- Not started state --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body text-center py-8">
            <div class="w-14 h-14 rounded-full bg-primary/10 text-primary flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="font-medium mb-1">Bạn chưa bắt đầu task này</p>
            @if($task->time_limit_minutes)
            <p class="text-sm text-base-content/50 mb-5">Sau khi bắt đầu, đồng hồ bắt đầu tính ({{ $task->time_limit_minutes }} phút)</p>
            @else
            <p class="text-sm text-base-content/50 mb-5">Đọc kỹ hướng dẫn trên trước khi bắt đầu</p>
            @endif

            <form method="POST" action="{{ route('campaigns.task.start', [$campaign->uuid, $task->id]) }}">
                @csrf
                <button class="btn btn-primary gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                    Bắt đầu làm bài
                </button>
            </form>
        </div>
    </div>

    @endif

  </div>

  {{-- Sidebar --}}
  <div class="space-y-4">

    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body py-4">
            <h3 class="text-sm font-semibold mb-3">Trạng thái</h3>
            @if(!$session)
            <div class="flex items-center gap-2 text-sm">
                <div class="w-2 h-2 rounded-full bg-base-300"></div>
                <span class="text-base-content/60">Chưa bắt đầu</span>
            </div>
            @elseif($session->status === 'in_progress')
            <div class="flex items-center gap-2 text-sm">
                <div class="w-2 h-2 rounded-full bg-info animate-pulse"></div>
                <span class="text-info font-medium">Đang làm</span>
            </div>
            <p class="text-xs text-base-content/40 mt-1">Từ {{ $session->started_at?->format('H:i') }}</p>
            @else
            <div class="flex items-center gap-2 text-sm">
                <div class="w-2 h-2 rounded-full bg-success"></div>
                <span class="text-success font-medium">Hoàn thành</span>
            </div>
            @if($session->final_score !== null)
            <p class="text-xs text-base-content/40 mt-1">Điểm: {{ $session->final_score }}/100</p>
            @endif
            @endif
        </div>
    </div>

    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body py-4">
            <p class="text-xs text-base-content/40">
                Campaign: <strong class="text-base-content/70">{{ $campaign->title }}</strong>
            </p>
            <a href="{{ route('campaigns.workspace', $campaign->uuid) }}" class="btn btn-ghost btn-xs w-full mt-2">
                ← Về Workspace
            </a>
        </div>
    </div>

  </div>

</div>

@endsection
