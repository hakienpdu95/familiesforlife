@extends('layouts.backend')
@section('title', 'Chi tiết phiên Sandbox')

@push('styles')
    @vite(['Modules/Assessment/resources/assets/sass/assessment.scss'], 'build/backend')
@endpush

@section('content')

@php
    $isOwner    = $session->user_id === auth()->id();
    $canEval    = auth()->user()?->can('assessment.results');
    $isAutoScore = $session->evaluator_user_id === null && $session->final_score !== null;

    $statusMap = [
        'completed'   => $session->passed ? ['Đạt', 'badge-success'] : ['Không đạt', 'badge-error'],
        'in_progress' => ['Đang làm', 'badge-warning'],
        'expired'     => ['Hết hạn', 'badge-ghost'],
        'submitted'   => ['Chờ chấm', 'badge-info'],
    ];
    $st = $statusMap[$session->status] ?? [$session->status, 'badge-ghost'];
@endphp

@if(session('success'))
<div class="alert alert-success mb-4 py-3 px-5">
    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <span class="text-sm">{{ session('success') }}</span>
</div>
@endif
@if(session('error'))
<div class="alert alert-error mb-4 py-3 px-5">
    <span class="text-sm">{{ session('error') }}</span>
</div>
@endif

{{-- Page header --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-bold text-base-content">{{ $session->task?->title ?? 'Phiên Sandbox' }}</h1>
        <div class="flex items-center gap-2 mt-1">
            <span class="text-sm text-base-content/50">{{ $session->task?->environment?->name ?? '—' }}</span>
            <span class="badge {{ $st[1] }} badge-sm">{{ $st[0] }}</span>
            @if($isAutoScore)
            <span class="badge badge-ghost badge-xs gap-1">
                <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                Tự động chấm
            </span>
            @elseif($session->evaluator_user_id)
            <span class="badge badge-info badge-xs">Chấm thủ công</span>
            @endif
        </div>
    </div>
    <a href="{{ route('backend.sandbox.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Sandbox
    </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

    {{-- Left column --}}
    <div class="xl:col-span-2 space-y-5">

        {{-- Task info --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-4">Nhiệm vụ</h2>
                @if($session->task?->instruction)
                <div class="prose prose-sm max-w-none mb-4">
                    <p class="text-base-content/70 whitespace-pre-line">{{ $session->task->instruction }}</p>
                </div>
                @endif

                @if($session->task?->expected_output)
                <div class="bg-base-200/60 rounded-xl p-4 mb-4">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-1">Kết quả mong đợi</p>
                    <p class="text-sm text-base-content/70 whitespace-pre-line">{{ $session->task->expected_output }}</p>
                </div>
                @endif

                @if($session->task && count($session->task->scoringRubricItems()))
                <div>
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-2">Tiêu chí chấm điểm</p>
                    <ul class="space-y-1">
                        @foreach($session->task->scoringRubricItems() as $item)
                        <li class="flex items-start gap-2 text-sm text-base-content/70">
                            <svg class="w-3.5 h-3.5 text-primary shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            {{ $item }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if($session->task && count($session->task->allowedAiTools()))
                <div class="flex flex-wrap gap-1.5 mt-3">
                    <span class="text-xs text-base-content/40">AI cho phép:</span>
                    @foreach($session->task->allowedAiTools() as $tool)
                    <span class="badge badge-ghost badge-xs">{{ $tool }}</span>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- Submit form — only when in_progress and owner --}}
        @if($session->status === 'in_progress' && $isOwner)
        <div class="card bg-base-100 shadow-sm border border-primary/30" x-data="{ submitting: false }">
            <div class="card-body">
                <h2 class="card-title text-base mb-4">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Nộp bài
                </h2>
                <p class="text-xs text-base-content/40 mb-4">Bài sẽ được chấm điểm tự động ngay sau khi nộp.</p>
                <form method="POST" action="{{ route('backend.sandbox.submit', $session) }}"
                      @submit="submitting = true">
                    @csrf
                    <div class="form-control mb-4">
                        <label class="label py-1"><span class="label-text text-xs font-medium">Kết quả / bài làm của bạn</span></label>
                        <textarea name="submitted_content" rows="6"
                                  class="textarea textarea-bordered text-sm"
                                  placeholder="Mô tả cách bạn đã thực hiện nhiệm vụ với AI, dán output hoặc tóm tắt kết quả...">{{ old('submitted_content') }}</textarea>
                    </div>
                    <div class="form-control mb-5">
                        <label class="label py-1"><span class="label-text text-xs font-medium">Công cụ AI đã sử dụng</span></label>
                        <input type="text" name="ai_tools_used"
                               class="input input-bordered input-sm"
                               placeholder="VD: ChatGPT|Copilot|Gemini"
                               value="{{ old('ai_tools_used') }}">
                        <label class="label py-0.5"><span class="label-text-alt text-xs text-base-content/40">Phân cách bằng | nếu dùng nhiều công cụ. Càng nhiều công cụ → điểm AI Adoption càng cao.</span></label>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="submit" class="btn btn-primary btn-sm gap-1.5" :disabled="submitting">
                            <span x-show="!submitting">Nộp bài & Nhận điểm</span>
                            <span x-show="submitting" class="loading loading-spinner loading-xs"></span>
                        </button>
                        <a href="{{ route('backend.sandbox.index') }}" class="btn btn-ghost btn-sm">Quay lại</a>
                    </div>
                </form>
            </div>
        </div>
        @endif

        {{-- Submission content --}}
        @if($session->submission)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-4">Bài nộp</h2>
                @if($session->submission->submitted_content)
                <div class="bg-base-200/50 rounded-xl p-4 text-sm text-base-content/80 whitespace-pre-line font-mono">{{ $session->submission->submitted_content }}</div>
                @else
                <p class="text-sm text-base-content/40 italic">Không có nội dung bài làm.</p>
                @endif

                @php $usedTools = $session->submission->usedAiTools(); @endphp
                @if(count($usedTools))
                <div class="flex flex-wrap items-center gap-1.5 mt-3">
                    <span class="text-xs text-base-content/40">AI đã dùng:</span>
                    @foreach($usedTools as $tool)
                    <span class="badge badge-info badge-xs">{{ $tool }}</span>
                    @endforeach
                </div>
                @endif

                <p class="text-xs text-base-content/30 mt-2">Nộp lúc {{ $session->submission->submitted_at?->format('H:i d/m/Y') ?? '—' }}</p>
            </div>
        </div>
        @endif

        {{-- Admin: manual score override --}}
        @if($canEval && in_array($session->status, ['completed', 'submitted']))
        <div class="card bg-base-100 shadow-sm border border-warning/30" x-data="{ open: false }">
            <div class="card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title text-base">
                        <svg class="w-4 h-4 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Override điểm thủ công
                    </h2>
                    <button @click="open = !open" class="btn btn-ghost btn-xs">
                        <span x-show="!open">Mở</span>
                        <span x-show="open">Đóng</span>
                    </button>
                </div>

                <div x-show="open" x-transition class="mt-4">
                    <p class="text-xs text-base-content/40 mb-4">
                        Điểm hiện tại là <strong>{{ $isAutoScore ? 'tự động' : 'thủ công' }}</strong>.
                        Override sẽ đánh dấu phiên này là chấm thủ công bởi bạn.
                    </p>
                    <form method="POST" action="{{ route('backend.sandbox.evaluate', $session) }}">
                        @csrf
                        <div class="grid grid-cols-3 gap-3 mb-4">
                            <div class="form-control">
                                <label class="label py-0.5"><span class="label-text text-xs">Chất lượng (40%)</span></label>
                                <input type="number" name="quality_score" min="0" max="100" step="0.5"
                                       value="{{ $session->quality_score ?? 0 }}"
                                       class="input input-bordered input-sm">
                            </div>
                            <div class="form-control">
                                <label class="label py-0.5"><span class="label-text text-xs">Năng suất (35%)</span></label>
                                <input type="number" name="productivity_score" min="0" max="100" step="0.5"
                                       value="{{ $session->productivity_score ?? 0 }}"
                                       class="input input-bordered input-sm">
                            </div>
                            <div class="form-control">
                                <label class="label py-0.5"><span class="label-text text-xs">AI Adoption (25%)</span></label>
                                <input type="number" name="ai_adoption_score" min="0" max="100" step="0.5"
                                       value="{{ $session->ai_adoption_score ?? 0 }}"
                                       class="input input-bordered input-sm">
                            </div>
                        </div>
                        <div class="form-control mb-4">
                            <label class="label py-0.5"><span class="label-text text-xs">Nhận xét (tuỳ chọn)</span></label>
                            <textarea name="feedback" rows="3"
                                      class="textarea textarea-bordered textarea-sm text-sm"
                                      placeholder="Nhận xét chi tiết cho người học...">{{ $session->feedback }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-warning btn-sm">Lưu điểm</button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- Activity timeline --}}
        @if($session->activities->count())
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-5">Nhật ký hoạt động ({{ $session->activities->count() }})</h2>
                <div class="space-y-3">
                    @foreach($session->activities as $act)
                    @php
                        $actIcon = match($act->activity_type) {
                            'start'  => ['M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z', 'text-primary'],
                            'ai_use' => ['M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z', 'text-info'],
                            'submit' => ['M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'text-success'],
                            default  => ['M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'text-base-content/30'],
                        };
                    @endphp
                    <div class="flex items-start gap-3">
                        <svg class="w-4 h-4 mt-0.5 shrink-0 {{ $actIcon[1] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $actIcon[0] }}"/>
                        </svg>
                        <div class="flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-sm text-base-content/80">{{ $act->activity_description }}</p>
                                <span class="text-xs text-base-content/30 shrink-0">{{ $act->occurred_at?->format('H:i:s') }}</span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

    </div>

    {{-- Right column: scores + meta --}}
    <div class="space-y-4">

        {{-- Score breakdown --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h3 class="font-semibold text-sm mb-1">Kết quả chấm điểm</h3>
                @if($isAutoScore)
                <p class="text-xs text-base-content/40 mb-4 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    AI tự động chấm
                </p>
                @elseif($session->evaluator_user_id)
                <p class="text-xs text-base-content/40 mb-4">Chấm thủ công lúc {{ $session->evaluated_at?->format('H:i d/m/Y') }}</p>
                @else
                <div class="mb-4"></div>
                @endif

                <div class="text-center mb-5">
                    @if($session->final_score !== null)
                    <span class="text-5xl font-black {{ $session->passed ? 'text-success' : 'text-error' }}">
                        {{ number_format($session->final_score, 1) }}
                    </span>
                    <span class="text-base-content/30 text-sm"> / 100</span>
                    <p class="text-xs mt-1">
                        @if($session->passed)
                        <span class="text-success font-semibold">✓ Đạt</span>
                        @else
                        <span class="text-error font-semibold">✗ Chưa đạt</span>
                        <span class="text-base-content/40"> (cần ≥ 60)</span>
                        @endif
                    </p>
                    @else
                    <span class="text-4xl font-black text-base-content/20">—</span>
                    <p class="text-xs text-base-content/30 mt-1">Chưa có điểm</p>
                    @endif
                </div>

                @if($session->final_score !== null)
                <div class="space-y-3 text-sm">
                    @foreach([
                        ['Chất lượng', $session->quality_score, '40%', '#6366f1'],
                        ['Năng suất',  $session->productivity_score, '35%', '#0ea5e9'],
                        ['AI Adoption', $session->ai_adoption_score, '25%', '#10b981'],
                    ] as [$label, $score, $weight, $color])
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-base-content/60">{{ $label }} <span class="text-base-content/30">({{ $weight }})</span></span>
                            <span class="font-semibold">{{ $score !== null ? number_format($score, 1) : '—' }}</span>
                        </div>
                        <div class="h-1.5 bg-base-200 rounded-full overflow-hidden">
                            <div class="h-1.5 rounded-full transition-all" style="width: {{ min($score ?? 0, 100) }}%; background: {{ $color }}"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                @if($session->feedback)
                <div class="mt-4 pt-4 border-t border-base-200">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-1">Nhận xét</p>
                    <p class="text-sm text-base-content/70 leading-relaxed">{{ $session->feedback }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Scoring rubric explanation --}}
        @if($session->status === 'in_progress' || $session->final_score !== null)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h3 class="font-semibold text-sm mb-3">Cách tính điểm</h3>
                <div class="space-y-2 text-xs text-base-content/60">
                    <div class="flex items-start gap-2">
                        <div class="w-2 h-2 rounded-full bg-indigo-500 shrink-0 mt-1"></div>
                        <p><strong>Chất lượng (40%):</strong> Độ chi tiết và đầy đủ của bài làm</p>
                    </div>
                    <div class="flex items-start gap-2">
                        <div class="w-2 h-2 rounded-full bg-sky-500 shrink-0 mt-1"></div>
                        <p><strong>Năng suất (35%):</strong> Hoàn thành trong giới hạn thời gian</p>
                    </div>
                    <div class="flex items-start gap-2">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 shrink-0 mt-1"></div>
                        <p><strong>AI Adoption (25%):</strong> Số lượng công cụ AI đã sử dụng</p>
                    </div>
                    @if($session->task?->time_limit_minutes)
                    <div class="mt-2 pt-2 border-t border-base-200 text-base-content/40">
                        Giới hạn: {{ $session->task->time_limit_minutes }} phút
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Session meta --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h3 class="font-semibold text-sm mb-3">Thông tin phiên</h3>
                <dl class="space-y-2 text-xs">
                    <div class="flex justify-between">
                        <dt class="text-base-content/40">Bắt đầu</dt>
                        <dd>{{ $session->started_at?->format('H:i d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/40">Nộp bài</dt>
                        <dd>{{ $session->submitted_at?->format('H:i d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/40">Thời gian làm</dt>
                        <dd>{{ $session->duration_minutes ? $session->duration_minutes . ' phút' : '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-base-content/40">Hoạt động</dt>
                        <dd>{{ $session->activities->count() }} bước</dd>
                    </div>
                </dl>

                {{-- Retry button for failed/completed sessions --}}
                @if($isOwner && $session->status === 'completed' && ! $session->passed)
                <div class="mt-4 pt-3 border-t border-base-200">
                    @if($session->task)
                    <form method="POST" action="{{ route('backend.sandbox.task.start', $session->task) }}">
                        @csrf
                        <button type="submit" class="btn btn-outline btn-sm w-full gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            Thử lại
                        </button>
                    </form>
                    @endif
                </div>
                @endif
            </div>
        </div>

    </div>
</div>

@endsection

@push('scripts')
    @vite(['Modules/Assessment/resources/assets/js/assessment.js'], 'build/backend')
@endpush
