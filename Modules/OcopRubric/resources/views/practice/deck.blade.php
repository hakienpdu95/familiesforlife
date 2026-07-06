@extends('layouts.backend')
@section('title', 'Luyện tập — ' . ($session->product?->name ?? 'OCOP'))

@section('content')
<div x-data="ocopPracticeDeck" class="max-w-2xl mx-auto">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <h1 class="text-xl font-bold text-base-content">{{ $session->product?->name ?? 'Luyện tập' }}</h1>
            <p class="text-xs text-base-content/50">
                {{ $session->mode === 'self_assessment' ? 'Tự đánh giá (Mẫu 02)' : 'Luyện tập' }}
                — <span x-text="progress.criteria_answered"></span>/<span x-text="progress.criteria_total"></span> tiêu chí
            </p>
        </div>
        <form method="POST" action="{{ route('ocop.practice.abandon', $session) }}" onsubmit="return confirm('Bỏ dở phiên này? Toàn bộ câu trả lời hiện tại sẽ bị khoá lại, không xoá.')">
            @csrf
            <button type="submit" class="btn btn-ghost btn-xs text-error">Bỏ dở phiên này</button>
        </form>
    </div>

    {{-- Progress bar --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body p-4">
            <div class="flex items-center justify-between text-sm mb-2">
                <span class="font-bold">Tổng điểm: <span x-text="progress.total_score"></span>đ</span>
                <span class="badge badge-primary badge-sm" x-show="progress.star_rank">
                    <span x-text="progress.star_rank"></span>★
                </span>
            </div>
            <progress class="progress progress-primary w-full" :value="progress.criteria_answered" :max="progress.criteria_total || 1"></progress>
            <p class="text-xs text-base-content/50 mt-2" x-show="progress.next_band">
                Cần thêm <span x-text="progress.points_to_next"></span>đ để chạm
                <span x-text="progress.next_band?.star_rank"></span>★ (<span x-text="progress.next_band?.min_score"></span>đ)
            </p>
            <div class="grid grid-cols-3 gap-2 mt-3 text-xs text-center">
                <div><div class="opacity-50">Phần A</div><div class="font-bold" x-text="progress.score_section_a + 'đ'"></div></div>
                <div><div class="opacity-50">Phần B</div><div class="font-bold" x-text="progress.score_section_b + 'đ'"></div></div>
                <div><div class="opacity-50">Phần C</div><div class="font-bold" x-text="progress.score_section_c + 'đ'"></div></div>
            </div>
        </div>
    </div>

    {{-- Card hiện tại --}}
    <template x-if="!done">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <div class="flex items-center gap-2 mb-2">
                    <span class="badge badge-ghost badge-sm font-mono" x-text="criterion?.code"></span>
                    <span class="font-bold" x-text="criterion?.label"></span>
                </div>
                <p class="text-sm text-base-content/60 italic mb-4" x-show="criterion?.requirement_note" x-text="criterion?.requirement_note"></p>

                <div class="flex flex-col gap-2">
                    <template x-for="opt in (criterion?.options || [])" :key="opt.id">
                        <button type="button" @click="answer(opt.id)"
                                class="btn btn-outline btn-block justify-between normal-case">
                            <span x-text="opt.label"></span>
                            <span class="badge badge-primary" x-text="opt.points + 'đ'"></span>
                        </button>
                    </template>
                </div>

                <div class="flex justify-end mt-4">
                    <button type="button" @click="skip()" class="btn btn-ghost btn-xs">Bỏ qua tiêu chí này</button>
                </div>

                <p x-show="errorMessage" x-text="errorMessage" class="text-error text-sm mt-2"></p>
            </div>
        </div>
    </template>

    {{-- Đã trả lời hết --}}
    <template x-if="done">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body text-center py-10">
                <p class="text-base-content/70 mb-4">Đã trả lời hết các tiêu chí.</p>
                <form method="POST" action="{{ route('ocop.practice.complete', $session) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">Hoàn thành</button>
                </form>
            </div>
        </div>
    </template>

    @if ($disqualifiers->isNotEmpty())
    <div class="card bg-base-100 shadow-sm border border-base-200 mt-4">
        <div class="card-body p-4">
            <h2 class="font-bold text-sm mb-2">Tự đánh dấu rủi ro loại hồ sơ</h2>
            <div class="flex flex-col gap-2">
                @foreach ($disqualifiers as $d)
                <label class="label cursor-pointer justify-start gap-2">
                    <input type="checkbox" class="checkbox checkbox-sm"
                           @change="toggleDisqualifier({{ $d->id }}, $event.target.checked)"
                           @checked($flags[$d->id] ?? false)>
                    <span class="label-text text-xs">{{ $d->label }}</span>
                </label>
                @endforeach
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', function () {
    var ANSWER_URL = '{{ route('ocop.practice.answer', $session) }}';
    var SKIP_URL   = '{{ route('ocop.practice.skip', $session) }}';
    var FLAG_URL   = '{{ route('ocop.practice.flag', $session) }}';
    var CSRF_TOKEN = '{{ csrf_token() }}';
    var INITIAL_CRITERION = @json($criterionJson);
    var INITIAL_PROGRESS = @json($progress);

    function postJson(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        }).then(function (r) { return r.json(); });
    }

    Alpine.data('ocopPracticeDeck', function () {
        return {
            criterion: INITIAL_CRITERION,
            progress: INITIAL_PROGRESS,
            done: INITIAL_CRITERION === null,
            errorMessage: '',

            applyResponse: function (data) {
                if (data.error) {
                    this.errorMessage = data.error;
                    return;
                }
                this.errorMessage = '';
                this.progress = data.progress;
                this.criterion = data.next_criterion;
                this.done = data.done;
            },

            answer: function (optionId) {
                var self = this;
                postJson(ANSWER_URL, { criterion_id: this.criterion.id, option_id: optionId })
                    .then(function (data) { self.applyResponse(data); });
            },

            skip: function () {
                var self = this;
                postJson(SKIP_URL, { criterion_id: this.criterion.id })
                    .then(function (data) { self.applyResponse(data); });
            },

            toggleDisqualifier: function (disqualifierId, checked) {
                postJson(FLAG_URL, { disqualifier_id: disqualifierId, is_flagged: checked });
            },
        };
    });
});
</script>
@endpush
