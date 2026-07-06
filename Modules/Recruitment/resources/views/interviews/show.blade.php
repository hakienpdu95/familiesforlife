@extends('layouts.backend')

@section('title', 'Chi tiết phỏng vấn')


@section('content')
<div x-data="rcInterviewShow" class="p-6 space-y-5 max-w-4xl">

    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-xl font-bold">{{ $interview->title ?: $interview->interview_type?->label() }}</h1>
            <p class="text-sm opacity-60 mt-0.5">
                Ứng viên: <strong>{{ $interview->application?->candidate?->full_name }}</strong>
                · Stage: {{ $interview->stage?->name }}
            </p>
            <div class="flex items-center gap-2 mt-2">
                @php $ivBadge = $interview->status?->badgeClass() ?? 'badge-ghost'; @endphp
                <span class="badge {{ $ivBadge }} badge-sm">{{ $interview->status?->label() }}</span>
                <span class="badge badge-outline badge-sm">{{ $interview->interview_type?->label() }}</span>
            </div>
        </div>

        @can('update', $interview->application)
        <div class="flex gap-2">
            @if(!$interview->isTerminal())
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-ghost btn-sm">
                    Đổi trạng thái
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </label>
                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-50 w-48 p-2 shadow border border-base-200">
                    @foreach($statuses as $s)
                    @if($s['value'] !== $interview->status?->value)
                    <li><button @click="updateStatus('{{ $s['value'] }}', '{{ $s['text'] }}')">{{ $s['text'] }}</button></li>
                    @endif
                    @endforeach
                </ul>
            </div>
            @endif

            @php
                $isPanelist = $interview->panelists->contains('user_id', auth()->id());
            @endphp
            @if($isPanelist || auth()->user()->hasRole('HR_Admin'))
            <a href="{{ route('backend.recruitment.interviews.evaluations.create', $interview) }}"
               class="btn btn-primary btn-sm">Nộp đánh giá</a>
            @endif
        </div>
        @endcan
    </div>

    <div class="grid grid-cols-3 gap-5">

        {{-- Left: Info --}}
        <div class="col-span-1 space-y-4">

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4 space-y-2">
                    <h3 class="font-semibold text-sm uppercase tracking-wide opacity-60">Thông tin</h3>

                    <div class="flex justify-between text-sm">
                        <span class="opacity-60">Thời gian</span>
                        <span class="font-medium">{{ $interview->scheduled_at?->format('d/m/Y H:i') }}</span>
                    </div>

                    <div class="flex justify-between text-sm">
                        <span class="opacity-60">Thời lượng</span>
                        <span>{{ $interview->duration_minutes }} phút</span>
                    </div>

                    @if($interview->location)
                    <div class="flex justify-between text-sm">
                        <span class="opacity-60">Địa điểm</span>
                        <span class="text-right max-w-[140px]">{{ $interview->location }}</span>
                    </div>
                    @endif

                    @if($interview->meeting_url)
                    <div class="text-sm">
                        <span class="opacity-60 block">Meeting URL</span>
                        <a href="{{ $interview->meeting_url }}" target="_blank" class="link link-primary text-xs break-all">
                            {{ Str::limit($interview->meeting_url, 40) }}
                        </a>
                    </div>
                    @endif

                    @if($interview->meeting_id)
                    <div class="flex justify-between text-sm">
                        <span class="opacity-60">Meeting ID</span>
                        <code class="text-xs">{{ $interview->meeting_id }}</code>
                    </div>
                    @endif

                    <div class="flex justify-between text-sm">
                        <span class="opacity-60">Tạo bởi</span>
                        <span>{{ $interview->createdBy?->name }}</span>
                    </div>
                </div>
            </div>

            {{-- Panel --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <h3 class="font-semibold text-sm uppercase tracking-wide opacity-60 mb-3">Panel ({{ $interview->panelists->count() }})</h3>
                    @forelse($interview->panelists as $p)
                    <div class="flex items-center justify-between mb-2 last:mb-0">
                        <div>
                            <p class="text-sm font-medium">{{ $p->user?->name }}</p>
                            <p class="text-xs opacity-50">{{ $p->role?->label() }}</p>
                        </div>
                        @php
                            $rsBadge = match($p->response_status?->value) {
                                'accepted' => 'badge-success',
                                'declined' => 'badge-error',
                                default    => 'badge-ghost',
                            };
                        @endphp
                        <span class="badge {{ $rsBadge }} badge-xs">{{ $p->response_status?->label() }}</span>
                    </div>
                    @empty
                    <p class="text-xs opacity-40">Chưa có panelist</p>
                    @endforelse
                </div>
            </div>

            @if($interview->interviewer_note)
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <h3 class="font-semibold text-sm uppercase tracking-wide opacity-60 mb-2">Ghi chú nội bộ</h3>
                    <p class="text-sm whitespace-pre-wrap opacity-80">{{ $interview->interviewer_note }}</p>
                </div>
            </div>
            @endif
        </div>

        {{-- Right: Evaluations --}}
        <div class="col-span-2">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold">Đánh giá ({{ $interview->evaluations->where('is_submitted', true)->count() }}/{{ $interview->panelists->count() }})</h3>
                        <button class="btn btn-ghost btn-xs" @click="loadSummary()">Tổng hợp</button>
                    </div>

                    {{-- Summary panel --}}
                    <div x-show="summary !== null" class="bg-base-200 rounded-lg p-3 mb-4">
                        <div class="flex items-center gap-4 text-sm">
                            <div class="text-center">
                                <p class="text-2xl font-bold" x-text="summary?.avg_score ?? '—'"></p>
                                <p class="text-xs opacity-50">Điểm TB</p>
                            </div>
                            <div class="flex-1">
                                <template x-for="(count, verdict) in (summary?.verdict_counts ?? {})" :key="verdict">
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span x-text="verdict"></span>
                                        <span class="font-medium" x-text="count"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Individual evaluations --}}
                    @forelse($interview->evaluations->where('is_submitted', true) as $eval)
                    @php $evalBadge = $eval->verdict?->badgeClass() ?? 'badge-ghost'; @endphp
                    <div class="border border-base-200 rounded-lg p-3 mb-3">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <p class="font-medium text-sm">{{ $eval->evaluator?->name }}</p>
                                <p class="text-xs opacity-40">{{ $eval->submitted_at?->format('d/m/Y H:i') }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-lg font-bold">{{ $eval->overall_score }}<span class="text-xs opacity-50">/10</span></span>
                                <span class="badge {{ $evalBadge }} badge-sm">{{ $eval->verdict?->label() }}</span>
                            </div>
                        </div>

                        @if($eval->strengths)
                        <p class="text-xs mb-1"><span class="text-success font-medium">Điểm mạnh:</span> {{ $eval->strengths }}</p>
                        @endif
                        @if($eval->weaknesses)
                        <p class="text-xs mb-1"><span class="text-error font-medium">Điểm yếu:</span> {{ $eval->weaknesses }}</p>
                        @endif
                        @if($eval->recommendation)
                        <p class="text-xs opacity-70">{{ $eval->recommendation }}</p>
                        @endif

                        @if($eval->criteria->isNotEmpty())
                        <div class="mt-2 border-t border-base-200 pt-2">
                            <div class="grid grid-cols-2 gap-1">
                                @foreach($eval->criteria as $c)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="opacity-60">{{ $c->criterion_name }}</span>
                                    <span class="font-medium">{{ $c->score }}/10</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    @empty
                    <div class="text-center py-8 text-sm opacity-50">Chưa có đánh giá nào được nộp</div>
                    @endforelse

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', function() {
    var STATUS_URL  = '{{ route('backend.recruitment.interviews.update-status', $interview) }}';
    var SUMMARY_URL = '{{ route('backend.recruitment.interviews.evaluations.summary', $interview) }}';
    var CSRF = '{{ csrf_token() }}';

    Alpine.data('rcInterviewShow', function() {
        return {
            summary: null,

            updateStatus: function(statusValue, statusLabel) {
                fetch(STATUS_URL, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ status: statusValue }),
                })
                .then(function(r) { return r.json(); })
                .then(function() { window.location.reload(); })
                .catch(function(e) { console.error(e); alert('Lỗi khi cập nhật trạng thái'); });
            },

            loadSummary: function() {
                var self = this;
                fetch(SUMMARY_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.json(); })
                .then(function(data) { self.summary = data; })
                .catch(function(e) { console.error(e); });
            },
        };
    });
});
</script>
@endpush
