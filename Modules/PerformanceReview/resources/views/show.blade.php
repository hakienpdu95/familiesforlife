@extends('layouts.backend')
@section('title', 'Chi tiết đánh giá — ' . $review->employee?->full_name)


@section('content')

@if(session('success'))
<div class="alert alert-success py-3 px-4 mb-5 text-sm">{{ session('success') }}</div>
@endif

<div class="flex flex-wrap items-start justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-bold text-base-content">
            Đánh giá: {{ $review->employee?->full_name }}
        </h1>
        <p class="text-sm text-base-content/50 mt-0.5">Kỳ {{ $review->period }} · {{ $review->template?->name }}</p>
    </div>
    <div class="flex items-center gap-2">

        @if($review->status->value === 'draft')
        <form method="POST" action="{{ route('backend.performance-reviews.submit', $review) }}">
            @csrf
            <button type="submit" class="btn btn-info btn-sm">Nộp đánh giá</button>
        </form>
        @endif

        @if(in_array($review->status->value, ['submitted', 'acknowledged']))
        @can('finalize', $review)
        <form method="POST" action="{{ route('backend.performance-reviews.finalize', $review) }}">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">Hoàn tất & Tính điểm</button>
        </form>
        @endcan
        @endif

        @can('update', $review)
        <a href="{{ route('backend.performance-reviews.edit', $review) }}" class="btn btn-warning btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Sửa
        </a>
        @endcan

        <a href="{{ route('backend.performance-reviews.index') }}" class="btn btn-ghost btn-sm">Quay lại</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- Left: Main content --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Score summary --}}
        @if($review->overall_score !== null)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h2 class="text-base font-semibold mb-3">Kết quả tổng hợp</h2>
                <div class="flex items-center gap-6">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-primary">{{ number_format($review->overall_score, 2) }}</div>
                        <div class="text-xs text-base-content/40 mt-1">Điểm tổng</div>
                    </div>
                    @if($review->overall_rating)
                    <span class="badge {{ $review->overall_rating->badgeClass() }} badge-lg">
                        {{ $review->overall_rating->label() }}
                    </span>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Criteria scores --}}
        @if($review->scores->count() > 0)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h2 class="text-base font-semibold mb-4">Chi tiết điểm tiêu chí</h2>
                <div class="space-y-3">
                    @foreach($review->scores as $score)
                    <div class="border border-base-200 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm font-semibold">{{ $score->criteria_name }}</span>
                            <span class="text-sm font-bold text-primary">{{ $score->score }} / {{ $score->max_score }}</span>
                        </div>
                        <div class="flex items-center gap-3 mb-2">
                            <div class="flex-1 bg-base-200 rounded-full h-2">
                                <div class="bg-primary h-2 rounded-full" style="width: {{ $score->max_score > 0 ? min(100, ($score->score / $score->max_score) * 100) : 0 }}%"></div>
                            </div>
                            <span class="text-xs text-base-content/40">trọng số {{ $score->weight }}%</span>
                        </div>
                        @if($score->comment)
                        <p class="text-xs text-base-content/60 italic">{{ $score->comment }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Qualitative assessment --}}
        @if($review->strengths || $review->improvements || $review->goals_next_period || $review->employee_comment)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5 space-y-4">
                <h2 class="text-base font-semibold">Nhận xét định tính</h2>

                @if($review->strengths)
                <div>
                    <p class="text-xs font-semibold text-success uppercase tracking-wide mb-1">Điểm mạnh</p>
                    <p class="text-sm text-base-content/70 whitespace-pre-wrap">{{ $review->strengths }}</p>
                </div>
                @endif

                @if($review->improvements)
                <div>
                    <p class="text-xs font-semibold text-warning uppercase tracking-wide mb-1">Cần cải thiện</p>
                    <p class="text-sm text-base-content/70 whitespace-pre-wrap">{{ $review->improvements }}</p>
                </div>
                @endif

                @if($review->goals_next_period)
                <div>
                    <p class="text-xs font-semibold text-info uppercase tracking-wide mb-1">Mục tiêu kỳ sau</p>
                    <p class="text-sm text-base-content/70 whitespace-pre-wrap">{{ $review->goals_next_period }}</p>
                </div>
                @endif

                @if($review->employee_comment)
                <div>
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-1">Phản hồi nhân viên</p>
                    <p class="text-sm text-base-content/70 italic whitespace-pre-wrap">{{ $review->employee_comment }}</p>
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- AI Feedback suggestion --}}
        @can('ai_copilot.use')
        <div class="card bg-base-100 shadow-sm border border-base-200"
             x-data="aiTask({
                 agentSlug:   'hr.feedback_draft',
                 variables:   {
                     employee_name: '{{ addslashes($review->employee?->full_name ?? '') }}',
                     position:      '{{ addslashes($review->snap_job_title ?? '') }}',
                     period:        '{{ addslashes($review->period) }}',
                     overall_score: '{{ $review->overall_score !== null ? number_format($review->overall_score, 2) : '' }}',
                     strengths:     '{{ addslashes($review->strengths ?? '') }}',
                     improvements:  '{{ addslashes($review->improvements ?? '') }}',
                 },
                 subjectType: 'Modules\\PerformanceReview\\Models\\PerformanceReview',
                 subjectId:   {{ $review->id }},
             })">
            <div class="card-body p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-base font-semibold">✨ AI Gợi ý nhận xét</h2>
                    <button type="button" @click="run()" :disabled="loading"
                            class="btn btn-sm btn-outline btn-primary gap-1.5">
                        <svg x-show="!loading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span x-show="loading" class="loading loading-spinner loading-xs"></span>
                        <span x-show="!loading">Tạo gợi ý</span>
                        <span x-show="loading">Đang xử lý…</span>
                    </button>
                </div>
                <p class="text-xs text-base-content/50 mb-2">Gợi ý nhận xét tổng hợp dựa trên dữ liệu đánh giá kỳ này.</p>
                <div x-show="error" class="alert alert-error py-2 px-3 text-xs" x-text="error"></div>
                <div x-show="output" class="mt-2 prose prose-sm max-w-none bg-base-200 rounded-lg p-3 max-h-72 overflow-y-auto whitespace-pre-wrap text-sm" x-text="output"></div>
            </div>
        </div>
        @endcan

    </div>

    {{-- Right: Meta info --}}
    <div class="space-y-4">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5 space-y-3">
                <h2 class="text-base font-semibold mb-1">Trạng thái</h2>
                <span class="badge {{ $review->status->badgeClass() }} badge-md">{{ $review->status->label() }}</span>

                @if($review->reviewed_at)
                <div class="text-xs text-base-content/50">
                    Hoàn tất: {{ $review->reviewed_at->format('d/m/Y H:i') }}
                </div>
                @endif
                @if($review->acknowledged_at)
                <div class="text-xs text-base-content/50">
                    NV xác nhận: {{ $review->acknowledged_at->format('d/m/Y H:i') }}
                </div>
                @endif
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5 space-y-3 text-sm">
                <h2 class="text-base font-semibold mb-1">Người liên quan</h2>

                <div>
                    <p class="text-xs text-base-content/40 uppercase tracking-wide">Nhân viên</p>
                    <p class="font-semibold">{{ $review->employee?->full_name }}</p>
                    <p class="text-xs text-base-content/50 font-mono">{{ $review->employee?->employee_code }}</p>
                    @if($review->snap_dept_name)<p class="text-xs text-base-content/40">{{ $review->snap_dept_name }}</p>@endif
                    @if($review->snap_job_title)<p class="text-xs text-base-content/40">{{ $review->snap_job_title }}@if($review->snap_job_level) · Lv.{{ $review->snap_job_level }}@endif</p>@endif
                </div>

                <div class="divider my-1"></div>

                <div>
                    <p class="text-xs text-base-content/40 uppercase tracking-wide">Người đánh giá</p>
                    <p class="font-semibold">{{ $review->reviewer?->full_name }}</p>
                    <p class="text-xs text-base-content/50 font-mono">{{ $review->reviewer?->employee_code }}</p>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5 space-y-2 text-sm">
                <h2 class="text-base font-semibold mb-1">Thông tin kỳ</h2>
                <div class="flex justify-between">
                    <span class="text-base-content/50">Kỳ</span>
                    <span class="font-mono font-semibold">{{ $review->period }}</span>
                </div>
                @if($review->period_start)
                <div class="flex justify-between">
                    <span class="text-base-content/50">Từ</span>
                    <span>{{ $review->period_start->format('d/m/Y') }}</span>
                </div>
                @endif
                @if($review->period_end)
                <div class="flex justify-between">
                    <span class="text-base-content/50">Đến</span>
                    <span>{{ $review->period_end->format('d/m/Y') }}</span>
                </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-base-content/50">Ngày tạo</span>
                    <span>{{ $review->created_at->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
