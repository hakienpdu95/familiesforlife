@extends('layouts.backend')

@section('title', 'Đơn ứng tuyển — ' . $application->candidate?->full_name)


@section('content')
<div x-data="rcApplicationShow" class="p-6 space-y-5 max-w-5xl">

    {{-- Header --}}
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-xl font-bold">{{ $application->candidate?->full_name }}</h1>
            <p class="text-sm opacity-60 mt-0.5">{{ $application->candidate?->email }} · {{ $application->candidate?->current_title ?? 'Chưa có chức danh' }}</p>
            <div class="flex items-center gap-2 mt-2">
                @php
                    $statusColor = match($application->status?->value) {
                        'active'    => 'badge-primary',
                        'hired'     => 'badge-success',
                        'rejected'  => 'badge-error',
                        'withdrawn' => 'badge-ghost',
                        default     => 'badge-outline',
                    };
                @endphp
                <span class="badge {{ $statusColor }} badge-sm">{{ $application->status?->label() }}</span>

                @if($application->currentStage)
                <span class="badge badge-outline badge-sm">
                    @if($application->currentStage->color_hex)
                    <span class="w-2 h-2 rounded-full mr-1" style="background: {{ $application->currentStage->color_hex }}"></span>
                    @endif
                    {{ $application->currentStage->name }}
                </span>
                @endif

                @if($application->is_disqualified)
                <span class="badge badge-warning badge-sm">Disqualified</span>
                @endif
            </div>
        </div>

        @can('update', $application)
        <div class="flex gap-2" x-show="canMove">
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-primary btn-sm">
                    Chuyển stage
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </label>
                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box z-50 w-56 p-2 shadow border border-base-200">
                    @foreach($stages as $stage)
                    @if($stage->id !== $application->current_stage_id)
                    <li>
                        <button @click="moveToStage({{ $stage->id }}, '{{ $stage->name }}')"
                                class="flex items-center gap-2 text-sm">
                            @if($stage->color_hex)
                            <span class="w-2 h-2 rounded-full shrink-0" style="background: {{ $stage->color_hex }}"></span>
                            @endif
                            {{ $stage->name }}
                        </button>
                    </li>
                    @endif
                    @endforeach
                </ul>
            </div>

            <button @click="rejectApplication()" class="btn btn-error btn-outline btn-sm"
                    x-show="{{ in_array($application->status?->value, ['active', 'on_hold']) ? 'true' : 'false' }}">
                Từ chối
            </button>
        </div>
        @endcan
    </div>

    <div class="grid grid-cols-3 gap-5">

        {{-- Left panel --}}
        <div class="col-span-1 space-y-4">

            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4 space-y-2">
                    <h3 class="font-semibold text-sm uppercase tracking-wide opacity-60">Thông tin đơn</h3>

                    <div class="flex justify-between text-sm">
                        <span class="opacity-60">Ngày nộp</span>
                        <span>{{ $application->applied_at?->format('d/m/Y') }}</span>
                    </div>

                    <div class="flex justify-between text-sm">
                        <span class="opacity-60">Nguồn</span>
                        <span>{{ $application->apply_source?->label() }}</span>
                    </div>

                    @if($application->expected_salary)
                    <div class="flex justify-between text-sm">
                        <span class="opacity-60">Lương kỳ vọng</span>
                        <span>{{ number_format($application->expected_salary) }} ₫</span>
                    </div>
                    @endif

                    @if($application->notice_period_days !== null)
                    <div class="flex justify-between text-sm">
                        <span class="opacity-60">Thông báo</span>
                        <span>{{ $application->notice_period_days }} ngày</span>
                    </div>
                    @endif

                    @if($application->assignedTo)
                    <div class="flex justify-between text-sm">
                        <span class="opacity-60">Recruiter</span>
                        <span>{{ $application->assignedTo->name }}</span>
                    </div>
                    @endif

                    @if($application->jp_job_post_id)
                    <div class="text-sm">
                        <span class="opacity-60 block">Tin tuyển dụng</span>
                        <code class="text-xs font-mono">{{ $application->jp_job_post_id }}</code>
                    </div>
                    @endif
                </div>
            </div>

            @if($application->cover_letter)
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <h3 class="font-semibold text-sm uppercase tracking-wide opacity-60 mb-2">Thư xin việc</h3>
                    <p class="text-sm whitespace-pre-wrap opacity-80">{{ $application->cover_letter }}</p>
                </div>
            </div>
            @endif

        </div>

        {{-- Right: Tabs --}}
        <div class="col-span-2" x-data="{ appTab: 'logs' }">

            <div class="tabs tabs-bordered mb-4">
                <button class="tab" @click="appTab = 'logs'" :class="{'tab-active': appTab === 'logs'}">
                    Lịch sử stage
                </button>
                <button class="tab" @click="appTab = 'interviews'" :class="{'tab-active': appTab === 'interviews'}">
                    Phỏng vấn ({{ $application->interviews->count() }})
                </button>
                <button class="tab" @click="appTab = 'offers'" :class="{'tab-active': appTab === 'offers'}">
                    Offers ({{ $application->offers->count() }})
                </button>
                @if($application->answers->isNotEmpty())
                <button class="tab" @click="appTab = 'answers'" :class="{'tab-active': appTab === 'answers'}">
                    Câu trả lời sàng lọc
                    @if($application->is_disqualified)
                    <span class="badge badge-warning badge-xs ml-1">Disqualified</span>
                    @endif
                </button>
                @endif
            </div>

            {{-- Stage Logs --}}
            <div x-show="appTab === 'logs'">
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4">
                        <h3 class="font-semibold mb-3">Lịch sử di chuyển stage</h3>

                        <div class="space-y-3">
                            @forelse($application->stageLogs->sortByDesc('actioned_at') as $log)
                            <div class="flex gap-3 items-start border-b border-base-200 pb-3 last:border-0">
                                <div class="w-7 h-7 rounded-full bg-base-200 flex items-center justify-center shrink-0 mt-0.5">
                                    @php
                                        $icon = match($log->result?->value) {
                                            'passed'    => '✓',
                                            'failed'    => '✗',
                                            'skipped'   => '→',
                                            'moved_back'=> '←',
                                            default     => '·',
                                        };
                                    @endphp
                                    <span class="text-xs font-bold">{{ $icon }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-medium text-sm">{{ $log->stage?->name }}</span>
                                        @php
                                            $resultColor = match($log->result?->value) {
                                                'passed'    => 'badge-success',
                                                'failed'    => 'badge-error',
                                                'moved_back'=> 'badge-warning',
                                                default     => 'badge-ghost',
                                            };
                                        @endphp
                                        <span class="badge {{ $resultColor }} badge-xs">{{ $log->result?->label() }}</span>
                                    </div>
                                    @if($log->note)
                                    <p class="text-xs opacity-60 mt-0.5">{{ $log->note }}</p>
                                    @endif
                                    <p class="text-xs opacity-40 mt-1">
                                        {{ $log->actionedBy?->name }} · {{ $log->actioned_at?->format('d/m/Y H:i') }}
                                    </p>
                                </div>
                            </div>
                            @empty
                            <p class="text-sm opacity-50 text-center py-4">Chưa có lịch sử</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            {{-- Interviews --}}
            <div x-show="appTab === 'interviews'">
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-semibold">Lịch phỏng vấn</h3>
                            @can('update', $application)
                            @if(in_array($application->status?->value, ['active', 'on_hold']))
                            <a href="{{ route('backend.recruitment.interviews.create', ['application_id' => $application->id]) }}"
                               class="btn btn-primary btn-xs">+ Lên lịch phỏng vấn</a>
                            @endif
                            @endcan
                        </div>

                        @forelse($application->interviews->sortByDesc('scheduled_at') as $iv)
                        @php
                            $ivBadge = match($iv->status?->value) {
                                'scheduled'  => 'badge-info',
                                'confirmed'  => 'badge-primary',
                                'completed'  => 'badge-success',
                                'cancelled'  => 'badge-ghost',
                                'no_show'    => 'badge-error',
                                default      => 'badge-outline',
                            };
                        @endphp
                        <div class="border border-base-200 rounded-lg p-3 mb-3">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 flex-wrap mb-1">
                                        <span class="font-medium text-sm">
                                            {{ $iv->title ?: $iv->interview_type?->label() }}
                                        </span>
                                        <span class="badge {{ $ivBadge }} badge-xs">{{ $iv->status?->label() }}</span>
                                        @if($iv->panelists->isNotEmpty())
                                        <span class="badge badge-outline badge-xs">{{ $iv->panelists->count() }} panelist</span>
                                        @endif
                                    </div>
                                    <p class="text-xs opacity-60">
                                        📅 {{ $iv->scheduled_at?->format('d/m/Y H:i') }}
                                        · {{ $iv->duration_minutes }} phút
                                        @if($iv->location) · 📍 {{ $iv->location }} @endif
                                    </p>
                                    @if($iv->panelists->isNotEmpty())
                                    <p class="text-xs opacity-50 mt-1">
                                        Panel: {{ $iv->panelists->map(fn($p) => $p->user?->name)->filter()->implode(', ') }}
                                    </p>
                                    @endif
                                    @if($iv->evaluations->where('is_submitted', true)->count() > 0)
                                    <p class="text-xs text-success mt-1">
                                        ✓ {{ $iv->evaluations->where('is_submitted', true)->count() }} đánh giá đã nộp
                                    </p>
                                    @endif
                                </div>
                                <a href="{{ route('backend.recruitment.interviews.show', $iv) }}"
                                   class="btn btn-ghost btn-xs shrink-0">Xem →</a>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-8 text-sm opacity-50">Chưa có lịch phỏng vấn nào</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Offers --}}
            <div x-show="appTab === 'offers'">
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="font-semibold">Offer Letters</h3>
                            @can('update', $application)
                            @if(in_array($application->status?->value, ['active', 'on_hold']))
                            <a href="{{ route('backend.recruitment.offers.create', ['application_id' => $application->id]) }}"
                               class="btn btn-primary btn-xs">+ Tạo offer</a>
                            @endif
                            @endcan
                        </div>

                        @forelse($application->offers->sortByDesc('created_at') as $offer)
                        @php $offerBadge = $offer->status?->badgeClass() ?? 'badge-ghost'; @endphp
                        <div class="border border-base-200 rounded-lg p-3 mb-3">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-medium text-sm">{{ number_format($offer->salary_offered) }} {{ $offer->currency }}</span>
                                        <span class="badge {{ $offerBadge }} badge-xs">{{ $offer->status?->label() }}</span>
                                    </div>
                                    <p class="text-xs opacity-60">
                                        Bắt đầu: {{ $offer->start_date?->format('d/m/Y') }}
                                        · Thử việc {{ $offer->probation_days }} ngày
                                        @if($offer->expire_at) · Hạn: {{ $offer->expire_at?->format('d/m/Y') }} @endif
                                    </p>
                                </div>
                                <a href="{{ route('backend.recruitment.offers.show', $offer) }}"
                                   class="btn btn-ghost btn-xs shrink-0">Xem →</a>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-8 text-sm opacity-50">Chưa có offer nào</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Screening Answers --}}
            @if($application->answers->isNotEmpty())
            <div x-show="appTab === 'answers'">
                <div class="card bg-base-100 shadow-sm border border-base-200">
                    <div class="card-body p-4">
                        <h3 class="font-semibold mb-3">Câu trả lời sàng lọc</h3>

                        @if($application->is_disqualified)
                        <div class="alert alert-warning mb-4">
                            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                            <span class="text-sm">Ứng viên bị disqualify: {{ $application->disqualify_reason }}</span>
                        </div>
                        @endif

                        <div class="space-y-3">
                            @foreach($application->answers as $answer)
                            <div class="border rounded-lg p-3 {{ $answer->is_disqualifying ? 'border-warning bg-warning/5' : 'border-base-200' }}">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium">{{ $answer->question_text }}</p>
                                        <p class="text-xs opacity-50 mt-0.5">Loại: {{ $answer->question_type }}</p>
                                        <div class="mt-2">
                                            @if($answer->answer_bool !== null)
                                            <span class="badge {{ $answer->answer_bool ? 'badge-success' : 'badge-error' }} badge-sm">
                                                {{ $answer->answer_bool ? 'Có / Yes' : 'Không / No' }}
                                            </span>
                                            @elseif($answer->answer_choices)
                                            <p class="text-sm">{{ $answer->answer_choices }}</p>
                                            @elseif($answer->answer_text)
                                            <p class="text-sm">{{ $answer->answer_text }}</p>
                                            @else
                                            <span class="text-xs opacity-40">Không có câu trả lời</span>
                                            @endif
                                        </div>
                                    </div>
                                    @if($answer->is_disqualifying)
                                    <span class="badge badge-warning badge-xs shrink-0">Disqualifying</span>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif

        </div>{{-- /col-span-2 --}}
    </div>
</div>

{{-- Move modal --}}
<dialog id="move-modal" class="modal">
    <div class="modal-box max-w-md">
        <h3 class="font-bold text-lg mb-4">Chuyển sang: <span x-text="moveTarget.name"></span></h3>
        <div class="form-control mb-4">
            <label class="label"><span class="label-text font-medium">Kết quả</span></label>
            <select x-model="moveTarget.result" class="select select-bordered">
                <option value="passed">Đạt — chuyển lên</option>
                <option value="skipped">Bỏ qua</option>
                <option value="moved_back">Trả về</option>
            </select>
        </div>
        <div class="form-control mb-5">
            <label class="label"><span class="label-text font-medium">Ghi chú (tùy chọn)</span></label>
            <textarea x-model="moveTarget.note" class="textarea textarea-bordered" rows="3"></textarea>
        </div>
        <div class="modal-action">
            <button class="btn btn-ghost" onclick="document.getElementById('move-modal').close()">Hủy</button>
            <button class="btn btn-primary" @click="confirmMove()">Xác nhận chuyển</button>
        </div>
    </div>
</dialog>

{{-- Reject modal --}}
<dialog id="reject-modal" class="modal">
    <div class="modal-box max-w-md">
        <h3 class="font-bold text-lg mb-4 text-error">Từ chối ứng viên</h3>
        <div class="form-control mb-5">
            <label class="label"><span class="label-text font-medium">Lý do từ chối</span></label>
            <textarea x-model="rejectReason" class="textarea textarea-bordered" rows="3"
                      placeholder="Không phù hợp với vị trí..."></textarea>
        </div>
        <div class="modal-action">
            <button class="btn btn-ghost" onclick="document.getElementById('reject-modal').close()">Hủy</button>
            <button class="btn btn-error" @click="confirmReject()">Từ chối</button>
        </div>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', function() {
    var MOVE_URL   = '{{ route('backend.recruitment.applications.move', $application) }}';
    var REJECT_URL = '{{ route('backend.recruitment.applications.reject', $application) }}';
    var CSRF       = '{{ csrf_token() }}';

    Alpine.data('rcApplicationShow', function() {
        return {
            canMove: {{ in_array($application->status?->value, ['active', 'on_hold']) ? 'true' : 'false' }},
            moveTarget: { id: null, name: '', result: 'passed', note: '' },
            rejectReason: '',

            moveToStage: function(stageId, stageName) {
                this.moveTarget = { id: stageId, name: stageName, result: 'passed', note: '' };
                document.getElementById('move-modal').showModal();
            },

            confirmMove: function() {
                var self = this;
                fetch(MOVE_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ stage_id: self.moveTarget.id, result: self.moveTarget.result, note: self.moveTarget.note }),
                })
                .then(function(r) { return r.json(); })
                .then(function() { document.getElementById('move-modal').close(); window.location.reload(); })
                .catch(function(e) { console.error(e); alert('Lỗi khi chuyển stage'); });
            },

            rejectApplication: function() {
                this.rejectReason = '';
                document.getElementById('reject-modal').showModal();
            },

            confirmReject: function() {
                var self = this;
                fetch(REJECT_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ reason: self.rejectReason }),
                })
                .then(function(r) { return r.json(); })
                .then(function() { document.getElementById('reject-modal').close(); window.location.reload(); })
                .catch(function(e) { console.error(e); alert('Lỗi'); });
            },
        };
    });
});
</script>
@endpush
