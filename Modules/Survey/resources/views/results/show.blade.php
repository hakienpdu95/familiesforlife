@extends('layouts.backend')

@section('title', 'Kết quả #' . $response->id . ' — ' . $survey->title)


@section('content')
<div class="space-y-5 max-w-4xl" x-data="resultShowPage({{ $response->id }}, {{ $survey->id }})">

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Kết quả chấm điểm</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Response #{{ $response->id }}
                @if($response->respondent_ref)
                    · <span class="font-medium text-base-content/70">{{ $response->respondent_ref }}</span>
                @endif
            </p>
        </div>
        <div class="flex items-center gap-2">
            @can('survey.update')
            <button type="button" @click="recalculate()" :disabled="recalculating"
                    class="btn btn-warning btn-sm gap-1.5">
                <svg class="w-4 h-4" :class="{'animate-spin': recalculating}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span x-text="recalculating ? 'Đang tính...' : 'Tính lại'"></span>
            </button>
            @endcan
            <a href="{{ route('backend.surveys.responses.show', [$survey, $response]) }}"
               class="btn btn-ghost btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                </svg>
                Xem response
            </a>
        </div>
    </div>

    {{-- ── Recalculate flash ────────────────────────────────────────────── --}}
    <div x-show="flashMsg" x-transition class="alert text-sm py-2 px-4 rounded-lg"
         :class="flashSuccess ? 'alert-success' : 'alert-error'" x-text="flashMsg"></div>

    @if(!$result)
    {{-- ── No result yet ────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body text-center py-12">
            <svg class="w-10 h-10 mx-auto text-base-content/20 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 4h16v16H4z"/>
            </svg>
            <p class="text-base-content/50 text-sm">Kết quả chấm điểm chưa sẵn sàng.</p>
            @can('survey.update')
            <button type="button" @click="recalculate()" :disabled="recalculating"
                    class="btn btn-primary btn-sm mt-4 mx-auto">
                <span x-text="recalculating ? 'Đang tính...' : 'Tính điểm ngay'"></span>
            </button>
            @endcan
        </div>
    </div>
    @else

    {{-- ── Overall score card ───────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-5">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-5 text-center">
                <div class="sm:col-span-1">
                    <p class="text-xs text-base-content/50 mb-1">Điểm tổng</p>
                    <p class="text-4xl font-bold text-primary">{{ round($result->overall_score, 1) }}</p>
                    <p class="text-xs text-base-content/40 mt-0.5">/ 100</p>
                </div>
                <div>
                    <p class="text-xs text-base-content/50 mb-1">Mức độ trưởng thành</p>
                    <span class="badge badge-lg badge-soft badge-info font-semibold">{{ $result->maturity_level }}</span>
                </div>
                <div>
                    <p class="text-xs text-base-content/50 mb-1">Assessment</p>
                    <p class="font-mono text-sm font-medium text-base-content">{{ $result->assessment_code }}</p>
                </div>
                <div>
                    <p class="text-xs text-base-content/50 mb-1">Tính lúc</p>
                    <p class="text-sm font-medium">{{ $result->calculated_at?->format('d/m/Y H:i') ?? '—' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Domain scores ────────────────────────────────────────────────── --}}
    @if($result->domainScores->isNotEmpty())
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="px-5 py-3 border-b border-base-200 font-semibold text-sm">Điểm theo domain</div>
        <div class="divide-y divide-base-200">
            @foreach($result->domainScores as $ds)
            <div class="px-5 py-3 flex items-center gap-4">
                <div class="w-40 shrink-0">
                    <p class="text-sm font-medium text-base-content">{{ $domainLabels[$ds->domain_code] ?? $ds->domain_code }}</p>
                    <p class="text-xs font-mono text-base-content/40">{{ $ds->domain_code }}</p>
                </div>
                <div class="flex-1">
                    <div class="flex items-center gap-3">
                        <div class="flex-1 bg-base-200 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full bg-primary transition-all"
                                 style="width: {{ min(100, round($ds->normalized_score)) }}%"></div>
                        </div>
                        <span class="text-sm font-bold text-primary w-10 text-right">{{ round($ds->normalized_score, 1) }}</span>
                    </div>
                </div>
                <div class="text-xs text-base-content/40 w-20 text-right">
                    Raw: {{ $ds->raw_score }}
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Signal flags ─────────────────────────────────────────────────── --}}
    @if($result->signalFlags->isNotEmpty())
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="px-5 py-3 border-b border-base-200 font-semibold text-sm">Signal Flags</div>
        <div class="px-5 py-3 flex flex-wrap gap-2">
            @foreach($result->signalFlags as $flag)
            <span class="badge badge-sm badge-soft {{ $flag->flag_value ? 'badge-success' : 'badge-neutral' }} gap-1">
                <span class="font-mono">{{ $flag->flag_code }}</span>
                <span>{{ $flag->flag_value ? 'true' : 'false' }}</span>
            </span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Pain points ──────────────────────────────────────────────────── --}}
    @if($result->painPoints->isNotEmpty())
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="px-5 py-3 border-b border-base-200 font-semibold text-sm">Pain Points</div>
        <div class="px-5 py-3 flex flex-wrap gap-2">
            @foreach($result->painPoints as $pp)
            <span class="badge badge-sm badge-soft badge-warning font-mono">{{ $pp->pain_point_code }}</span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Recommendations ──────────────────────────────────────────────── --}}
    @if($result->recommendations->isNotEmpty())
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="px-5 py-3 border-b border-base-200 font-semibold text-sm">Đề xuất</div>
        <div class="divide-y divide-base-200">
            @foreach($result->recommendations as $rec)
            <div class="px-5 py-3 flex items-center gap-3">
                <span class="badge badge-sm badge-soft badge-ghost font-mono text-xs w-8 text-center shrink-0">
                    #{{ $rec->priority }}
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-base-content">
                        {{ $recLabels[$rec->recommendation_code] ?? $rec->recommendation_code }}
                    </p>
                    <p class="text-xs font-mono text-base-content/40">{{ $rec->recommendation_code }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Roadmap ───────────────────────────────────────────────────────── --}}
    @if($result->roadmapPhases->isNotEmpty())
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="px-5 py-3 border-b border-base-200 font-semibold text-sm">Lộ trình</div>
        <div class="divide-y divide-base-200">
            @foreach($result->roadmapPhases as $rp)
            <div class="px-5 py-4">
                <div class="flex items-start gap-3">
                    <div class="badge badge-sm badge-soft badge-primary mt-0.5 shrink-0 font-mono">
                        {{ $rp->phase?->phase_code ?? '?' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-sm text-base-content">{{ $rp->phase?->title ?? '—' }}</p>
                        @if($rp->phase?->duration_weeks)
                        <p class="text-xs text-base-content/50 mt-0.5">{{ $rp->phase->duration_weeks }} tuần</p>
                        @endif
                        @if($rp->phase?->milestones->isNotEmpty())
                        <ul class="mt-2 space-y-1">
                            @foreach($rp->phase->milestones as $ms)
                            <li class="flex items-start gap-1.5 text-xs text-base-content/70">
                                <svg class="w-3.5 h-3.5 text-success mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                {{ $ms->title }}
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Phản hồi thực tế (T6 / Module 170) ───────────────────────── --}}
    @can('survey.update')
    @if($result)
    @php
        $feedbackUrl = route('backend.surveys.responses.result.feedback', [$survey, $response]);
        $predictedBand = $feedback?->predicted_band ?? $result->maturity_level ?? '—';
        $actualBand    = $feedback?->actual_band ?? '';
        $isProcessed   = $feedback?->is_processed ?? false;
    @endphp
    <div class="card bg-base-100 border border-base-300 shadow-sm"
         x-data="{
             band: {{ Js::from($actualBand) }},
             submitting: false,
             saved: {{ Js::from((bool) $actualBand) }},
             msg: '',
             ok: true,
             async submit() {
                 if (!this.band) return;
                 this.submitting = true; this.msg = '';
                 try {
                     const res = await fetch({{ Js::from($feedbackUrl) }}, {
                         method: 'PATCH',
                         headers: {
                             'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                             'Content-Type': 'application/json',
                             'Accept': 'application/json',
                         },
                         body: JSON.stringify({ actual_band: this.band }),
                     });
                     const data = await res.json();
                     this.ok = data.success ?? res.ok;
                     this.msg = data.message ?? (res.ok ? 'Đã lưu.' : 'Lỗi.');
                     if (res.ok) this.saved = true;
                 } catch { this.ok = false; this.msg = 'Lỗi kết nối.'; }
                 finally   { this.submitting = false; }
             }
         }">
        <div class="px-5 py-3 border-b border-base-200 font-semibold text-sm flex items-center gap-2">
            <svg class="w-4 h-4 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Phản hồi thực tế
            <span class="badge badge-xs ml-1"
                  :class="saved ? 'badge-success' : 'badge-ghost'"
                  x-text="saved ? 'Đã xác nhận' : 'Chưa xác nhận'"></span>
        </div>
        <div class="px-5 py-4 space-y-4">

            {{-- Predicted vs Actual ---------------------------------------- --}}
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-xs text-base-content/50 mb-1">Predicted (hệ thống tự tính)</p>
                    <span class="badge badge-md badge-soft badge-info font-mono">{{ $predictedBand }}</span>
                </div>
                <div>
                    <p class="text-xs text-base-content/50 mb-1">Actual (admin xác nhận)</p>
                    <span x-show="saved" class="badge badge-md badge-soft badge-success font-mono" x-text="band"></span>
                    <span x-show="!saved" class="text-xs italic text-base-content/40">Chưa xác nhận</span>
                </div>
            </div>

            {{-- Dropdown + nút -------------------------------------------- --}}
            @if($bands->isNotEmpty())
            <div class="flex items-end gap-3">
                <div class="flex-1">
                    <label class="text-xs text-base-content/50 mb-1 block">Cập nhật actual band</label>
                    <select x-model="band" class="select select-bordered select-sm w-full">
                        <option value="">-- Chọn band --</option>
                        @foreach($bands as $b)
                        <option value="{{ $b['code'] }}">{{ $b['label'] }} ({{ $b['code'] }})</option>
                        @endforeach
                    </select>
                </div>
                <button @click="submit()" :disabled="!band || submitting"
                        class="btn btn-sm btn-primary min-w-20"
                        :class="{ 'loading': submitting }">
                    <span x-text="submitting ? 'Đang lưu...' : 'Xác nhận'"></span>
                </button>
            </div>
            @else
            <p class="text-xs text-base-content/40 italic">Chưa cấu hình bands — vào Scoring Config để thêm.</p>
            @endif

            {{-- Flash message -------------------------------------------- --}}
            <p x-show="msg" x-transition class="text-xs font-medium"
               :class="ok ? 'text-success' : 'text-error'" x-text="msg"></p>

            {{-- Tuning status -------------------------------------------- --}}
            <p class="text-xs text-base-content/30">
                {{ $isProcessed ? 'Đã được tuning cycle xử lý.' : 'Chưa được tuning cycle xử lý.' }}
            </p>
        </div>
    </div>
    @endif
    @endcan

    @endif {{-- end $result --}}
</div>

@push('scripts')
<script>
function resultShowPage(responseId, surveyId) {
    return {
        recalculating: false,
        flashMsg: '',
        flashSuccess: true,

        async recalculate() {
            this.recalculating = true;
            this.flashMsg = '';
            try {
                const res = await fetch(
                    `/dashboard/surveys/${surveyId}/responses/${responseId}/recalculate`,
                    {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    }
                );
                const data = await res.json();
                this.flashSuccess = data.success ?? res.ok;
                this.flashMsg = data.message ?? (res.ok ? 'Đã tính lại thành công.' : 'Lỗi.');
                if (res.ok) {
                    setTimeout(() => location.reload(), 1200);
                }
            } catch (e) {
                this.flashSuccess = false;
                this.flashMsg = 'Lỗi kết nối.';
            } finally {
                this.recalculating = false;
            }
        }
    };
}
</script>
@endpush
@endsection
