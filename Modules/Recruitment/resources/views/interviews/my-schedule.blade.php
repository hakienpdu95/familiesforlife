@extends('layouts.backend')

@section('title', 'Lịch phỏng vấn của tôi')


@section('content')
<div class="p-6 max-w-3xl">

    <div class="flex items-center justify-between mb-5">
        <h1 class="text-xl font-bold">Lịch phỏng vấn của tôi</h1>
        <span class="badge badge-outline">{{ $panelAssignments->count() }} buổi sắp tới</span>
    </div>

    @forelse($panelAssignments as $assignment)
    @php
        $iv       = $assignment->interview;
        $ivBadge  = match($iv->status?->value) {
            'scheduled' => 'badge-info',
            'confirmed' => 'badge-primary',
            default     => 'badge-ghost',
        };
        $roleBadge = match($assignment->role?->value) {
            'interviewer' => 'badge-primary',
            'observer'    => 'badge-ghost',
            default       => 'badge-outline',
        };
    @endphp
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body p-4">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <span class="font-semibold">{{ $iv->title ?: $iv->interview_type?->label() }}</span>
                        <span class="badge {{ $ivBadge }} badge-xs">{{ $iv->status?->label() }}</span>
                        <span class="badge {{ $roleBadge }} badge-xs">Vai trò: {{ $assignment->role?->label() }}</span>
                    </div>

                    <p class="text-sm font-medium">
                        Ứng viên: {{ $iv->application?->candidate?->full_name }}
                    </p>
                    <p class="text-xs opacity-60 mt-1">
                        📅 {{ $iv->scheduled_at?->format('d/m/Y H:i') }}
                        · {{ $iv->duration_minutes }} phút
                        @if($iv->location) · 📍 {{ $iv->location }} @endif
                    </p>

                    @if($iv->meeting_url)
                    <a href="{{ $iv->meeting_url }}" target="_blank" class="link link-primary text-xs mt-1 block">
                        🔗 Tham gia meeting
                    </a>
                    @endif

                    @php
                        $myEval = $iv->evaluations->where('evaluator_id', auth()->id())->first();
                    @endphp
                    @if($myEval?->is_submitted)
                    <p class="text-xs text-success mt-1">✓ Đã nộp đánh giá (điểm {{ $myEval->overall_score }}/10)</p>
                    @endif
                </div>

                <div class="flex flex-col gap-2 shrink-0 ml-4">
                    <a href="{{ route('backend.recruitment.interviews.show', $iv) }}"
                       class="btn btn-ghost btn-xs">Xem chi tiết</a>
                    @if(!$myEval?->is_submitted)
                    <a href="{{ route('backend.recruitment.interviews.evaluations.create', $iv) }}"
                       class="btn btn-primary btn-xs">Đánh giá</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-16">
        <div class="text-4xl mb-3">📅</div>
        <p class="text-sm opacity-50">Không có lịch phỏng vấn nào sắp tới</p>
    </div>
    @endforelse

</div>
@endsection
