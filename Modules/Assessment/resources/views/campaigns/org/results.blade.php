@extends('layouts.backend')
@section('title', 'Kết quả — ' . $campaign->title)

@section('content')

<div class="flex items-center gap-2 text-sm text-base-content/50 mb-4">
    <a href="{{ route('campaigns.admin.index') }}" class="hover:text-primary">Campaigns</a>
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <a href="{{ route('campaigns.admin.show', $campaign->uuid) }}" class="hover:text-primary">{{ $campaign->title }}</a>
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span>Kết quả</span>
</div>

@if(session('success'))
<div class="alert alert-success mb-4"><span>{{ session('success') }}</span></div>
@endif

<div class="flex items-center justify-between gap-4 mb-5 flex-wrap">
    <div>
        <h1 class="text-xl font-bold">Ranking ứng viên</h1>
        <p class="text-sm text-base-content/50">
            {{ $campaign->is_anonymous_to_org ? '🔒 Ẩn danh cho đến khi bạn mời' : 'Hiển thị tên ứng viên' }}
            · {{ $participations->count() }} kết quả
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('campaigns.admin.results.by-unit', $campaign->uuid) }}"
           class="btn btn-ghost btn-sm">Theo đơn vị</a>
        <a href="{{ route('campaigns.admin.export', $campaign->uuid) }}"
           class="btn btn-outline btn-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Xuất Excel
        </a>
    </div>
</div>

@if($participations->isEmpty())
<div class="card bg-base-100 border border-base-200 shadow-sm">
    <div class="card-body items-center text-center py-16">
        <p class="text-base-content/50">Chưa có ứng viên hoàn thành campaign này.</p>
    </div>
</div>
@else

<div x-data="{
    selected: [],
    toggle(id) {
        const idx = this.selected.indexOf(id);
        idx === -1 ? this.selected.push(id) : this.selected.splice(idx, 1);
    },
    selectAll() { this.selected = {{ json_encode($participations->where('org_action', '!=', 'invited')->pluck('id')->values()) }}; },
    clearAll()  { this.selected = []; }
}">

{{-- Bulk invite toolbar --}}
<div class="flex items-center gap-3 mb-3 flex-wrap">
    <button type="button" @click="selectAll()" class="btn btn-ghost btn-xs">Chọn tất cả</button>
    <button type="button" @click="clearAll()" class="btn btn-ghost btn-xs">Bỏ chọn</button>
    <span class="text-xs text-base-content/40" x-text="selected.length + ' đã chọn'"></span>

    <form method="POST" action="{{ route('campaigns.admin.invite-bulk', $campaign->uuid) }}" class="ml-auto">
        @csrf
        <template x-for="id in selected" :key="id">
            <input type="hidden" name="participation_ids[]" :value="id">
        </template>
        <button type="submit" class="btn btn-primary btn-sm"
                :disabled="selected.length === 0"
                x-bind:class="selected.length === 0 ? 'btn-disabled' : ''">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            Mời đã chọn (<span x-text="selected.length"></span>)
        </button>
    </form>
</div>

<div class="space-y-3">
    @foreach($participations as $i => $participation)
    @php
        $revealed  = !$campaign->is_anonymous_to_org || $participation->isInvited();
        $rank      = $i + 1;
        $canSelect = $participation->org_action !== 'invited';
    @endphp
    <div class="card bg-base-100 border border-base-200 shadow-sm {{ $rank <= 3 ? 'border-l-4 '.($rank===1?'border-l-yellow-400':($rank===2?'border-l-slate-400':'border-l-orange-400')) : '' }}">
        <div class="card-body py-4">
            <div class="flex items-start gap-4 flex-wrap">

                {{-- Checkbox --}}
                @if($canSelect)
                <div class="flex items-center pt-2 shrink-0">
                    <input type="checkbox"
                           class="checkbox checkbox-sm checkbox-primary"
                           :checked="selected.includes({{ $participation->id }})"
                           @change="toggle({{ $participation->id }})">
                </div>
                @else
                <div class="w-5 shrink-0"></div>
                @endif

                {{-- Rank + identity --}}
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg shrink-0
                        {{ $rank === 1 ? 'bg-yellow-100 text-yellow-700' : ($rank === 2 ? 'bg-slate-100 text-slate-600' : ($rank === 3 ? 'bg-orange-100 text-orange-600' : 'bg-base-200 text-base-content/50')) }}">
                        {{ $rank <= 3 ? ['🥇','🥈','🥉'][$rank-1] : '#'.$rank }}
                    </div>
                    <div class="min-w-0">
                        @if($revealed)
                        <p class="font-semibold text-base-content">{{ $participation->user?->name }}</p>
                        <p class="text-sm text-base-content/50">{{ $participation->user?->email }}</p>
                        @if($participation->user?->trust_level >= 2)
                        <span class="badge badge-info badge-xs mt-0.5">📱 Xác minh ĐT</span>
                        @endif
                        @else
                        <p class="font-semibold text-base-content/70">{{ $participation->anonymousLabel() }}</p>
                        <p class="text-xs text-base-content/40">Trust Lv{{ $participation->user?->trust_level }}</p>
                        @endif
                    </div>
                </div>

                {{-- Scores --}}
                <div class="flex items-center gap-6 text-center">
                    <div>
                        <div class="text-2xl font-bold text-primary">{{ $participation->result_tdwcf_score ?? '—' }}</div>
                        <div class="text-xs text-base-content/50">TDWCF</div>
                    </div>
                    @if($participation->result_sandbox_avg)
                    <div>
                        <div class="text-lg font-semibold text-base-content">{{ number_format($participation->result_sandbox_avg, 1) }}</div>
                        <div class="text-xs text-base-content/50">Sandbox avg</div>
                    </div>
                    @endif
                    @if($participation->result_maturity_level)
                    <div class="badge badge-outline badge-sm hidden sm:flex">{{ $participation->result_maturity_level }}</div>
                    @endif
                </div>

                {{-- Domain scores --}}
                @if($participation->scores->count())
                <div class="flex gap-1.5 flex-wrap">
                    @foreach($participation->scores as $s)
                    <div class="text-center px-2 py-1 bg-base-200/60 rounded-lg">
                        <div class="text-xs font-bold text-base-content/60">{{ $s->domain_code }}</div>
                        <div class="text-sm font-bold">{{ $s->score }}</div>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Action --}}
                <div class="shrink-0">
                    @if($participation->org_action === 'invited')
                    <span class="badge badge-success">✓ Đã mời</span>
                    @elseif($participation->org_action === 'rejected' || $participation->status->value === 'declined')
                    <span class="badge badge-error">Từ chối</span>
                    @else
                    <button onclick="document.getElementById('invite_modal_{{ $participation->id }}').showModal()"
                            class="btn btn-primary btn-sm">
                        {{ $revealed ? 'Mời phỏng vấn' : '🔓 Mời (reveal)' }}
                    </button>

                    <dialog id="invite_modal_{{ $participation->id }}" class="modal">
                        <div class="modal-box">
                            <h3 class="font-bold text-lg mb-3">
                                Mời {{ $revealed ? $participation->user?->name : $participation->anonymousLabel() }}
                            </h3>
                            @if(!$revealed)
                            <div class="alert alert-info mb-3 text-sm">
                                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Sau khi mời, bạn sẽ thấy tên và email thật của ứng viên.
                            </div>
                            @endif
                            <form method="POST"
                                  action="{{ route('campaigns.admin.invite', [$campaign->uuid, $participation->uuid]) }}">
                                @csrf
                                <div class="space-y-3">
                                    <div>
                                        <label class="label label-text text-sm pb-1">Đánh giá (1–5 sao)</label>
                                        <input type="number" name="org_rating" min="1" max="5"
                                               class="input input-bordered input-sm w-24" placeholder="5">
                                    </div>
                                    <div>
                                        <label class="label label-text text-sm pb-1">Ghi chú cho ứng viên</label>
                                        <textarea name="org_note" rows="2"
                                                  class="textarea textarea-bordered w-full"
                                                  placeholder="VD: Chúng tôi ấn tượng với kết quả AI Literacy của bạn..."></textarea>
                                    </div>
                                </div>
                                <div class="modal-action">
                                    <button type="button" onclick="document.getElementById('invite_modal_{{ $participation->id }}').close()" class="btn btn-ghost btn-sm">Huỷ</button>
                                    <button type="submit" class="btn btn-success btn-sm">Gửi lời mời</button>
                                </div>
                            </form>
                        </div>
                        <form method="dialog" class="modal-backdrop"><button>close</button></form>
                    </dialog>
                    @endif
                </div>

            </div>

            <div class="text-xs text-base-content/30 mt-2 ml-9">
                Nộp bài: {{ $participation->completed_at?->format('d/m/Y H:i') ?? '—' }}
            </div>
        </div>
    </div>
    @endforeach
</div>

</div>{{-- /x-data --}}

@endif

@endsection
