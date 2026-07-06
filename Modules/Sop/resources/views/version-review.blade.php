@extends('layouts.backend')
@section('title', $sop->code . ' — Phiên bản v' . $version->version_number)


@section('content')
<div x-data="{
    approveLoading: false,
    rejectLoading: false,
    rejectComment: '',
    approveComment: '',
    showRejectForm: false,
    error: '',
    async approve() {
        if (!confirm('Xác nhận duyệt phiên bản v{{ $version->version_number }}?')) return;
        this.approveLoading = true; this.error = '';
        @if($pendingFlow)
        const res = await fetch('{{ route('backend.api.sop.approval.approve', $pendingFlow) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ comment: this.approveComment }),
        });
        const d = await res.json();
        if (!res.ok) { this.error = d.message; this.approveLoading = false; return; }
        window.location = '{{ route('backend.sop.show', $sop) }}?tab=versions';
        @endif
    },
    async reject() {
        if (!this.rejectComment.trim()) { this.error = 'Vui lòng nhập lý do từ chối.'; return; }
        this.rejectLoading = true; this.error = '';
        @if($pendingFlow)
        const res = await fetch('{{ route('backend.api.sop.approval.reject', $pendingFlow) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ comment: this.rejectComment }),
        });
        const d = await res.json();
        if (!res.ok) { this.error = d.message; this.rejectLoading = false; return; }
        window.location = '{{ route('backend.sop.show', $sop) }}?tab=versions';
        @endif
    }
}">

    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <div class="flex flex-wrap items-center gap-2 mb-1">
                <span class="font-mono text-sm font-bold text-primary">{{ $sop->code }}</span>
                <span class="badge badge-sm badge-neutral">v{{ $version->version_number }}</span>
                @php
                    $statusClass = match($version->status?->value) {
                        'approved'  => 'badge-success',
                        'submitted' => 'badge-warning',
                        'rejected'  => 'badge-error',
                        default     => 'badge-ghost',
                    };
                @endphp
                <span class="badge badge-sm {{ $statusClass }}">{{ $version->status?->label() }}</span>
            </div>
            <h1 class="text-xl font-bold text-base-content">{{ $sop->title }}</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Gửi bởi {{ $version->createdBy?->name ?? '—' }}
                · {{ $version->created_at?->format('d/m/Y H:i') }}
            </p>
        </div>
        <a href="{{ route('backend.sop.show', $sop) }}?tab=versions"
           class="btn btn-ghost btn-sm gap-1.5">
            ← Quay lại
        </a>
    </div>

    @if($version->change_summary)
    <div class="alert alert-info py-3 text-sm mb-5">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <p class="font-semibold mb-0.5">Mô tả thay đổi</p>
            <p>{{ $version->change_summary }}</p>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Flowchart snapshot --}}
        <div class="lg:col-span-2">
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-base-content/60 uppercase tracking-wide">
                            @if($version->status?->value === 'approved')
                                Flowchart (Snapshot đã approve)
                            @else
                                Flowchart hiện tại (live — để duyệt)
                            @endif
                        </h3>
                        @if($version->status?->value === 'approved')
                        <div class="flex gap-2 text-xs">
                            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm inline-block bg-[#639922]"></span> Thêm mới</span>
                            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm inline-block bg-[#EF9F27]"></span> Đã sửa</span>
                            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm inline-block bg-[#E24B4A]"></span> Đã xóa</span>
                        </div>
                        @endif
                    </div>

                    @if($version->status?->value === 'approved')
                    {{-- Snapshot flowchart (diff view) --}}
                    <div
                        x-data="sopFlowchart('{{ route('backend.api.sop.versions.flowchart-data', [$sop, $version]) }}')"
                        x-init="load()">
                        <div class="overflow-auto border rounded-lg bg-base-50" style="max-height: 420px">
                            <div x-show="loading" class="flex items-center justify-center h-32 text-base-content/30 text-sm">
                                <span class="loading loading-spinner loading-sm mr-2"></span> Đang tải…
                            </div>
                            <svg x-show="!loading"
                                 :width="canvas.width" :height="canvas.height"
                                 :viewBox="`0 0 ${canvas.width} ${canvas.height}`">
                                <defs>
                                    <marker id="sv-arr-gray" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="5" markerHeight="5" orient="auto-start-reverse">
                                        <path d="M2 1L8 5L2 9" fill="none" stroke="#B4B2A9" stroke-width="1.5" stroke-linecap="round"/>
                                    </marker>
                                    <marker id="sv-arr-green" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="5" markerHeight="5" orient="auto-start-reverse">
                                        <path d="M2 1L8 5L2 9" fill="none" stroke="#639922" stroke-width="1.5" stroke-linecap="round"/>
                                    </marker>
                                    <marker id="sv-arr-red" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="5" markerHeight="5" orient="auto-start-reverse">
                                        <path d="M2 1L8 5L2 9" fill="none" stroke="#E24B4A" stroke-width="1.5" stroke-linecap="round"/>
                                    </marker>
                                </defs>
                                <template x-for="conn in layout.connectors" :key="conn.id">
                                    <g>
                                        <path :d="conn.path" fill="none" :stroke="conn.color"
                                              stroke-width="1.2" :stroke-dasharray="conn.dashed ? '5 3' : 'none'"
                                              :marker-end="`url(#sv-arr-${conn.markerColor})`"/>
                                        <text x-show="conn.label" :x="conn.labelX" :y="conn.labelY"
                                              text-anchor="middle" font-size="10" :fill="conn.color"
                                              font-family="ui-sans-serif,sans-serif" x-text="conn.label"/>
                                    </g>
                                </template>
                                <template x-for="node in layout.nodes" :key="node.id">
                                    <g @click="selectStep(node)" class="cursor-pointer"
                                       :opacity="selected && selected.id !== node.id ? 0.55 : 1">
                                        <template x-if="node.shape === 'rect' || node.shape === 'rect_double' || node.shape === 'rounded_rect'">
                                            <g>
                                                <rect :x="node.x" :y="node.y" :width="node.w" :height="node.h"
                                                      :rx="node.shape === 'rounded_rect' ? 16 : 6"
                                                      :fill="node.fill" :stroke="node.change_color || node.color"
                                                      :stroke-width="node.change_color ? 2.5 : (selected?.id === node.id ? 2 : 0.9)"/>
                                                <template x-if="node.shape === 'rect_double'">
                                                    <rect :x="node.x+4" :y="node.y+4" :width="node.w-8" :height="node.h-8"
                                                          rx="3" fill="none" :stroke="node.color" stroke-width="0.6"/>
                                                </template>
                                            </g>
                                        </template>
                                        <template x-if="node.shape === 'diamond'">
                                            <path :d="`M${node.cx},${node.y} L${node.x+node.w},${node.cy} L${node.cx},${node.y+node.h} L${node.x},${node.cy} Z`"
                                                  :fill="node.fill" :stroke="node.change_color || node.color"
                                                  :stroke-width="node.change_color ? 2.5 : 0.9"/>
                                        </template>
                                        <template x-if="node.shape === 'oval' || node.shape === 'oval_double'">
                                            <ellipse :cx="node.cx" :cy="node.cy" :rx="node.w/2" :ry="node.h/2"
                                                     :fill="node.fill" :stroke="node.change_color || node.color"
                                                     :stroke-width="node.change_color ? 2.5 : 1"/>
                                        </template>
                                        <template x-if="node.shape === 'parallelogram'">
                                            <path :d="`M${node.x+12},${node.y} L${node.x+node.w},${node.y} L${node.x+node.w-12},${node.y+node.h} L${node.x},${node.y+node.h} Z`"
                                                  :fill="node.fill" :stroke="node.change_color || node.color"
                                                  :stroke-width="node.change_color ? 2.5 : 0.9"/>
                                        </template>
                                        <text :x="node.cx" :y="node.cy" text-anchor="middle" dominant-baseline="central"
                                              font-size="10" font-weight="500" :fill="node.color"
                                              font-family="ui-sans-serif,sans-serif" x-text="node.shortTitle"/>
                                    </g>
                                </template>
                            </svg>
                        </div>
                        <div x-show="selected" class="mt-3 p-3 rounded-lg border-l-2 bg-base-50 text-sm"
                             :style="`border-color: ${selected?.color}`">
                            <template x-if="selected">
                                <div>
                                    <p class="font-medium mb-1" x-text="`Bước ${selected.position}: ${selected.title}`"></p>
                                    <p class="text-xs text-base-content/50" x-text="selected.description"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                    @else
                    {{-- Live flowchart for review --}}
                    <div
                        x-data="sopFlowchart('{{ route('backend.sop.flowchart.data', $sop) }}')"
                        x-init="load()">
                        <div class="overflow-auto border rounded-lg bg-base-50" style="max-height: 420px">
                            <div x-show="loading" class="flex items-center justify-center h-32 text-base-content/30 text-sm">
                                <span class="loading loading-spinner loading-sm mr-2"></span> Đang tải…
                            </div>
                            <svg x-show="!loading" :width="canvas.width" :height="canvas.height"
                                 :viewBox="`0 0 ${canvas.width} ${canvas.height}`">
                                <defs>
                                    <marker id="rv-arr-gray" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="5" markerHeight="5" orient="auto-start-reverse">
                                        <path d="M2 1L8 5L2 9" fill="none" stroke="#B4B2A9" stroke-width="1.5" stroke-linecap="round"/>
                                    </marker>
                                </defs>
                                <template x-for="conn in layout.connectors" :key="conn.id">
                                    <path :d="conn.path" fill="none" :stroke="conn.color" stroke-width="1.2"
                                          :marker-end="`url(#rv-arr-gray)`"/>
                                </template>
                                <template x-for="node in layout.nodes" :key="node.id">
                                    <g>
                                        <rect :x="node.x" :y="node.y" :width="node.w" :height="node.h"
                                              rx="6" :fill="node.fill" :stroke="node.color" stroke-width="0.9"/>
                                        <text :x="node.cx" :y="node.cy" text-anchor="middle" dominant-baseline="central"
                                              font-size="10" font-weight="500" :fill="node.color"
                                              font-family="ui-sans-serif,sans-serif" x-text="node.shortTitle"/>
                                    </g>
                                </template>
                            </svg>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Approval panel --}}
        <div class="space-y-4">

            {{-- Approval Flow Status --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-4">
                    <h3 class="text-sm font-semibold text-base-content/60 uppercase tracking-wide mb-3">Luồng phê duyệt</h3>
                    <div class="space-y-3">
                        @foreach($version->approvalFlows->sortBy('step_order') as $flow)
                        @php
                            $fBadge = match($flow->action) {
                                'approved'  => ['class' => 'badge-success', 'label' => 'Đã duyệt'],
                                'rejected'  => ['class' => 'badge-error',   'label' => 'Từ chối'],
                                'forwarded' => ['class' => 'badge-info',    'label' => 'Chuyển tiếp'],
                                default     => ['class' => 'badge-warning', 'label' => 'Chờ duyệt'],
                            };
                        @endphp
                        <div class="flex items-start gap-3 text-sm">
                            <div class="mt-0.5 w-6 h-6 rounded-full flex items-center justify-center shrink-0 text-xs font-bold
                                        {{ $flow->action === 'approved' ? 'bg-success/10 text-success' : ($flow->action === 'rejected' ? 'bg-error/10 text-error' : 'bg-warning/10 text-warning') }}">
                                {{ $flow->step_order }}
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="badge badge-xs {{ $fBadge['class'] }}">{{ $fBadge['label'] }}</span>
                                    @if($flow->required_role)
                                    <span class="text-xs text-base-content/40">{{ $flow->required_role }}</span>
                                    @endif
                                </div>
                                @if($flow->approver)
                                <p class="text-xs text-base-content/50 mt-0.5">
                                    {{ $flow->approver->name }}
                                    @if($flow->acted_at)· {{ $flow->acted_at->format('d/m/Y H:i') }}@endif
                                </p>
                                @endif
                                @if($flow->comment)
                                <p class="text-xs bg-base-200 rounded p-1.5 mt-1 leading-relaxed">{{ $flow->comment }}</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Action form (only for pending, authorized approver) --}}
            @if($canApprove)
            <div class="card bg-base-100 border border-primary/30 shadow-sm">
                <div class="card-body p-4">
                    <h3 class="text-sm font-semibold text-base-content/60 uppercase tracking-wide mb-3">Hành động</h3>

                    <div x-show="error" class="text-error text-xs mb-3" x-text="error"></div>

                    <div class="mb-3">
                        <label class="text-xs font-medium text-base-content/60 mb-1 block">Ghi chú (tuỳ chọn)</label>
                        <textarea x-model="approveComment" rows="2"
                                  class="textarea textarea-bordered textarea-sm w-full"
                                  placeholder="Ghi chú khi duyệt…" maxlength="500"></textarea>
                    </div>
                    <button @click="approve()" :disabled="approveLoading"
                            class="btn btn-success btn-sm w-full gap-1.5 mb-2">
                        <span x-show="approveLoading" class="loading loading-spinner loading-xs"></span>
                        <svg x-show="!approveLoading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Duyệt phiên bản v{{ $version->version_number }}
                    </button>

                    <div x-show="!showRejectForm">
                        <button @click="showRejectForm = true" class="btn btn-ghost btn-sm w-full text-error">
                            Từ chối
                        </button>
                    </div>
                    <div x-show="showRejectForm" x-transition>
                        <div class="divider text-xs text-base-content/30 my-2">Lý do từ chối</div>
                        <textarea x-model="rejectComment" rows="3"
                                  class="textarea textarea-bordered textarea-sm w-full mb-2"
                                  placeholder="Vui lòng nêu rõ lý do từ chối…" maxlength="1000"></textarea>
                        <div class="flex gap-2">
                            <button @click="showRejectForm = false" class="btn btn-ghost btn-sm flex-1">Huỷ</button>
                            <button @click="reject()" :disabled="rejectLoading"
                                    class="btn btn-error btn-sm flex-1 gap-1.5">
                                <span x-show="rejectLoading" class="loading loading-spinner loading-xs"></span>
                                Xác nhận từ chối
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Snapshot stats --}}
            @if($version->status?->value === 'approved')
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-4">
                    <h3 class="text-sm font-semibold text-base-content/60 uppercase tracking-wide mb-3">Thống kê snapshot</h3>
                    <div class="grid grid-cols-2 gap-3 text-center">
                        <div class="bg-base-50 rounded-lg p-3">
                            <p class="text-2xl font-bold text-primary">{{ $version->total_steps }}</p>
                            <p class="text-xs text-base-content/50 mt-0.5">Bước</p>
                        </div>
                        <div class="bg-base-50 rounded-lg p-3">
                            <p class="text-2xl font-bold text-primary">{{ $version->total_duration_minutes ?: '—' }}</p>
                            <p class="text-xs text-base-content/50 mt-0.5">Phút</p>
                        </div>
                    </div>
                    @if($version->approved_at)
                    <p class="text-xs text-base-content/40 mt-3 text-center">
                        Duyệt: {{ $version->approved_at->format('d/m/Y H:i') }}
                    </p>
                    @endif
                </div>
            </div>
            @endif

        </div>
    </div>

</div>
@endsection

@push('scripts')
@vite(['Modules/Sop/resources/assets/sass/sop.scss', 'Modules/Sop/resources/assets/js/sop.js'], 'build/backend')
@endpush
