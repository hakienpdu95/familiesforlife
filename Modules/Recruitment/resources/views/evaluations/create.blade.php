@extends('layouts.backend')

@section('title', 'Nộp đánh giá phỏng vấn')


@section('content')

{{-- Dữ liệu ban đầu cho Alpine component (không dùng <script> PHP logic) --}}
@php
    $existingCriteria = $existing?->criteria->map(fn($c) => [
        'criterion_name' => $c->criterion_name,
        'score'          => $c->score,
        'comment'        => $c->comment ?? '',
    ])->values()->toArray() ?? [];
@endphp
<script type="application/json" id="eval-initial-data">
{!! json_encode([
    'overall_score'  => $existing?->overall_score ?? 7,
    'verdict'        => $existing?->verdict?->value ?? '',
    'strengths'      => $existing?->strengths ?? '',
    'weaknesses'     => $existing?->weaknesses ?? '',
    'recommendation' => $existing?->recommendation ?? '',
    'criteria'       => $existingCriteria,
]) !!}
</script>

<div
    x-data="rcEvaluationCreate"
    data-submit-url="{{ route('backend.recruitment.interviews.evaluations.store', $interview) }}"
    data-redirect="{{ route('backend.recruitment.interviews.show', $interview) }}"
    class="p-6 max-w-2xl"
>
    <div class="mb-5">
        <h1 class="text-xl font-bold">Đánh giá phỏng vấn</h1>
        <p class="text-sm opacity-60 mt-0.5">
            Ứng viên: <strong class="text-base-content">{{ $interview->application?->candidate?->full_name }}</strong>
            · {{ $interview->title ?: $interview->interview_type?->label() }}
            · {{ $interview->scheduled_at?->format('d/m/Y H:i') }}
        </p>
    </div>

    @if($existing?->is_submitted)
    <div class="alert alert-info mb-4">
        <span class="text-sm">Bạn đã nộp đánh giá cho buổi phỏng vấn này. Nộp lại sẽ ghi đè đánh giá cũ.</span>
    </div>
    @endif

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-5 space-y-5">

            {{-- Overall score --}}
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-medium">Điểm tổng thể (1–10)</span>
                    <span class="label-text-alt text-2xl font-bold" x-text="form.overall_score + '/10'"></span>
                </label>
                <input type="range" min="1" max="10"
                       x-model="form.overall_score"
                       class="range range-primary range-sm">
                <div class="flex justify-between text-xs opacity-40 mt-1 px-0.5">
                    @for($i = 1; $i <= 10; $i++)
                    <span>{{ $i }}</span>
                    @endfor
                </div>
            </div>

            {{-- Verdict --}}
            <div class="form-control">
                <label class="label">
                    <span class="label-text font-medium">Kết luận <span class="text-error">*</span></span>
                </label>
                <div class="flex flex-wrap gap-2">
                    @foreach($verdicts as $v)
                    @php
                        $vClass = match($v['value']) {
                            'strong_yes' => 'btn-success',
                            'yes'        => 'btn-primary',
                            'neutral'    => 'btn-ghost',
                            'no'         => 'btn-warning',
                            'strong_no'  => 'btn-error',
                            default      => 'btn-ghost',
                        };
                    @endphp
                    <label class="cursor-pointer">
                        <input type="radio" class="hidden" value="{{ $v['value'] }}" x-model="form.verdict">
                        <span class="btn btn-sm {{ $vClass }}"
                              :class="form.verdict === '{{ $v['value'] }}' ? 'ring-2 ring-offset-1' : 'btn-outline'">
                            {{ $v['text'] }}
                        </span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Strengths / Weaknesses --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium text-success">Điểm mạnh</span>
                    </label>
                    <textarea x-model="form.strengths"
                              class="textarea textarea-bordered textarea-sm"
                              rows="3"
                              placeholder="Kỹ năng tốt, kinh nghiệm phù hợp..."></textarea>
                </div>
                <div class="form-control">
                    <label class="label">
                        <span class="label-text font-medium text-error">Điểm yếu</span>
                    </label>
                    <textarea x-model="form.weaknesses"
                              class="textarea textarea-bordered textarea-sm"
                              rows="3"
                              placeholder="Còn thiếu kinh nghiệm về..."></textarea>
                </div>
            </div>

            <div class="form-control">
                <label class="label">
                    <span class="label-text font-medium">Khuyến nghị</span>
                </label>
                <textarea x-model="form.recommendation"
                          class="textarea textarea-bordered textarea-sm"
                          rows="2"
                          placeholder="Đề xuất chuyển sang vòng tiếp theo / đề nghị offer..."></textarea>
            </div>

            {{-- Criteria --}}
            <div>
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-medium">Tiêu chí đánh giá chi tiết</p>
                    <button type="button" @click="addCriterion()"
                            class="btn btn-ghost btn-xs">+ Thêm tiêu chí</button>
                </div>

                <template x-for="(c, idx) in form.criteria" :key="idx">
                    <div class="flex gap-2 mb-2 items-start">
                        <div class="flex-1 space-y-1">
                            <input type="text" x-model="c.criterion_name"
                                   placeholder="VD: Kỹ năng kỹ thuật"
                                   class="input input-bordered input-sm w-full"
                                   maxlength="100">
                            <textarea x-model="c.comment"
                                      placeholder="Nhận xét..."
                                      rows="1"
                                      class="textarea textarea-bordered textarea-xs w-full"></textarea>
                        </div>
                        <div class="flex flex-col items-center gap-1 shrink-0 w-20">
                            <input type="number" x-model.number="c.score"
                                   min="1" max="10"
                                   class="input input-bordered input-sm w-full text-center">
                            <span class="text-xs opacity-40">/10</span>
                        </div>
                        <button type="button" @click="removeCriterion(idx)"
                                class="btn btn-ghost btn-xs text-error mt-1 shrink-0">✕</button>
                    </div>
                </template>

                <p x-show="form.criteria.length === 0"
                   class="text-xs text-base-content/40">Chưa có tiêu chí nào (tùy chọn)</p>
            </div>

        </div>
    </div>

    <div class="flex gap-3 mt-5">
        <button type="button" @click="submitEvaluation()"
                class="btn btn-primary btn-sm">Nộp đánh giá</button>
        <a href="{{ route('backend.recruitment.interviews.show', $interview) }}"
           class="btn btn-ghost btn-sm">Hủy</a>
    </div>
</div>
@endsection

@push('scripts')
@vite([
    'resources/js/modules/toastify.js',
    'Modules/Recruitment/resources/assets/sass/recruitment.scss',
    'Modules/Recruitment/resources/assets/js/recruitment.js',
], 'build/backend')
@endpush
