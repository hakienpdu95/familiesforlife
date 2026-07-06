@extends('layouts.backend')
@section('title', $sop->code . ' — ' . $sop->title)


@section('content')
<div x-data="{
    tab: '{{ request('tab', 'overview') }}',
    submitModalOpen: false,
    submitSummary: '',
    submitLoading: false,
    submitError: '',
    async submitForReview() {
        this.submitLoading = true;
        this.submitError = '';
        try {
            const res = await fetch('{{ route('backend.api.sop.submit-review', $sop) }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({ change_summary: this.submitSummary }),
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Lỗi khi gửi duyệt');
            this.submitModalOpen = false;
            window.location.reload();
        } catch (e) {
            this.submitError = e.message;
        } finally {
            this.submitLoading = false;
        }
    }
}">

    {{-- ── Page header ─────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
        <div>
            <div class="flex flex-wrap items-center gap-2 mb-1">
                <span class="font-mono text-sm font-bold text-primary">{{ $sop->code }}</span>
                <span class="badge badge-sm {{ $sop->status?->badgeClass() }}">{{ $sop->status?->label() }}</span>
                <span class="badge badge-sm badge-outline">{{ $sop->type?->label() }}</span>
                @if($sop->version > 0)
                <span class="badge badge-sm badge-neutral">v{{ $sop->version }}</span>
                @endif
            </div>
            <h1 class="text-xl font-bold text-base-content">{{ $sop->title }}</h1>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('update', $sop)
            <a href="{{ route('backend.sop.edit', $sop) }}" class="btn btn-outline btn-sm gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Chỉnh sửa
            </a>
            @endcan
            @if(in_array($sop->status?->value, ['draft', 'rejected']) && auth()->user()->can('submitReview', $sop))
            <button type="button" @click="submitModalOpen = true"
                    class="btn btn-success btn-sm gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Gửi duyệt
            </button>
            @endif
            @if($sop->status?->value === 'pending_review' && auth()->user()->can('approve', $sop))
            <a href="{{ route('backend.sop.pending-approvals') }}"
               class="btn btn-warning btn-sm gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                Duyệt
            </a>
            @endif
            {{-- Export dropdown --}}
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-ghost btn-sm gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Xuất
                    <svg class="w-3 h-3 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </label>
                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box shadow-lg border border-base-200 w-44 z-50 p-1 mt-1">
                    <li>
                        <a href="{{ route('backend.sop.export.pdf', $sop) }}" target="_blank"
                           class="flex items-center gap-2 text-sm py-2">
                            <svg class="w-4 h-4 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            Xuất PDF
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('backend.sop.export.png', $sop) }}"
                           class="flex items-center gap-2 text-sm py-2"
                           onclick="this.textContent='Đang xử lý…'">
                            <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Xuất PNG
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('backend.sop.print', $sop) }}" target="_blank"
                           class="flex items-center gap-2 text-sm py-2">
                            <svg class="w-4 h-4 text-base-content/60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            In / In PDF
                        </a>
                    </li>
                </ul>
            </div>

            @can('delete', $sop)
            <form method="POST" action="{{ route('backend.sop.destroy', $sop) }}"
                  onsubmit="return confirm('Lưu trữ SOP {{ addslashes($sop->code) }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-ghost btn-sm text-error gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8l1 12a2 2 0 002 2h8a2 2 0 002-2l1-12"/>
                    </svg>
                    Lưu trữ
                </button>
            </form>
            @endcan
        </div>
    </div>

    {{-- ── Warning banners ─────────────────────────────────────────────────── --}}
    @php
        $prereqRelations  = $sop->sopRelations->where('relation_type', 'prerequisite');
        $replacedByRelations = $sop->sopRelations->where('relation_type', 'replaced_by');
    @endphp
    @if($prereqRelations->isNotEmpty())
    <div class="alert alert-warning mb-4 py-3 text-sm">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.07 16.5c-.77.833.192 2.5 1.732 2.5z"/>
        </svg>
        <div>
            <p class="font-semibold mb-1">Tiền điều kiện — cần hoàn thành trước khi thực hiện quy trình này:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($prereqRelations as $pre)
                <li>
                    <a href="{{ route('backend.sop.show', $pre->relatedSop?->uuid) }}"
                       class="font-mono font-semibold hover:underline">{{ $pre->relatedSop?->code }}</a>
                    — {{ $pre->relatedSop?->title }}
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif
    @if($replacedByRelations->isNotEmpty())
    <div class="alert alert-error mb-4 py-3 text-sm">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
        </svg>
        <div>
            <p class="font-semibold mb-1">Quy trình này đã được thay thế bởi:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($replacedByRelations as $rb)
                <li>
                    <a href="{{ route('backend.sop.show', $rb->relatedSop?->uuid) }}"
                       class="font-mono font-semibold hover:underline">{{ $rb->relatedSop?->code }}</a>
                    — {{ $rb->relatedSop?->title }}
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- ── Tab navigation ──────────────────────────────────────────────────── --}}
    <div class="border-b border-base-200 mb-5">
        <nav class="flex -mb-px overflow-x-auto" role="tablist">
            @php
            $tabs = [
                'overview'  => ['icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'Tổng quan'],
                'flowchart' => ['icon' => 'M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2', 'label' => 'Flowchart'],
                'versions'  => ['icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'label' => 'Phiên bản'],
                'relations' => ['icon' => 'M7 20l4-16m2 16l4-16M6 9h14M4 15h14', 'label' => 'Quan hệ SOP'],
            ];
            @endphp
            @foreach($tabs as $key => $tab)
            <button type="button" role="tab"
                    :aria-selected="tab === '{{ $key }}'"
                    @click="tab = '{{ $key }}'"
                    class="flex items-center gap-1.5 px-1 py-3.5 mr-6 text-sm font-medium border-b-2 transition-colors whitespace-nowrap shrink-0"
                    :class="tab === '{{ $key }}'
                        ? 'border-primary text-primary'
                        : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $tab['icon'] }}"/>
                </svg>
                {{ $tab['label'] }}
                @if($key === 'relations' && $sop->sopRelations->isNotEmpty())
                <span class="badge badge-xs badge-neutral">{{ $sop->sopRelations->count() }}</span>
                @endif
                @if($key === 'flowchart')
                <span class="badge badge-xs badge-outline">{{ $sop->activeSteps->count() }}</span>
                @endif
            </button>
            @endforeach
        </nav>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- Tab: Tổng quan --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'overview'" x-cloak>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            {{-- Meta --}}
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-5">
                    <h3 class="text-sm font-semibold text-base-content/60 uppercase tracking-wide mb-3">Thông tin quy trình</h3>
                    <dl class="space-y-3 text-sm">
                        @if($sop->description)
                        <div>
                            <dt class="text-xs text-base-content/50 mb-0.5">Mô tả</dt>
                            <dd class="text-base-content/80 leading-relaxed">{{ $sop->description }}</dd>
                        </div>
                        @endif
                        <div class="grid grid-cols-2 gap-3 pt-1">
                            <div>
                                <dt class="text-xs text-base-content/50 mb-0.5">Người phụ trách</dt>
                                <dd class="font-medium">{{ $sop->owner?->name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-base-content/50 mb-0.5">Loại</dt>
                                <dd>{{ $sop->type?->label() ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-base-content/50 mb-0.5">Phòng ban</dt>
                                <dd>{{ $sop->department?->name ?? <span class="opacity-40">—</span> }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-base-content/50 mb-0.5">Chi nhánh</dt>
                                <dd>{{ $sop->branch?->name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-base-content/50 mb-0.5">Ngày hiệu lực</dt>
                                <dd>{{ $sop->effective_date?->format('d/m/Y') ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-base-content/50 mb-0.5">Ngày hết hạn</dt>
                                <dd class="{{ $sop->expired_date && $sop->expired_date->isPast() ? 'text-error font-medium' : '' }}">
                                    {{ $sop->expired_date?->format('d/m/Y') ?? '—' }}
                                    @if($sop->expired_date && $sop->expired_date->isPast())
                                    <span class="text-xs">(đã hết hạn)</span>
                                    @endif
                                </dd>
                            </div>
                        </div>
                    </dl>
                </div>
            </div>

            {{-- Stats + history --}}
            <div class="space-y-4">
                <div class="card bg-base-100 border border-base-200 shadow-sm">
                    <div class="card-body p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-semibold text-base-content/60 uppercase tracking-wide">Thống kê</h3>
                            <a href="{{ route('backend.sop.raci', $sop) }}"
                               class="text-xs text-primary hover:underline font-medium">Ma trận RACI →</a>
                        </div>
                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div class="bg-base-50 rounded-lg p-3">
                                <p class="text-2xl font-bold text-primary">{{ $sop->activeSteps->count() }}</p>
                                <p class="text-xs text-base-content/50 mt-0.5">Bước</p>
                            </div>
                            <div class="bg-base-50 rounded-lg p-3">
                                @php $totalMin = $sop->activeSteps->sum('duration_minutes'); @endphp
                                <p class="text-2xl font-bold text-primary">{{ $totalMin ?: '—' }}</p>
                                <p class="text-xs text-base-content/50 mt-0.5">Phút</p>
                            </div>
                            <div class="bg-base-50 rounded-lg p-3">
                                <p class="text-2xl font-bold text-primary">v{{ $sop->version }}</p>
                                <p class="text-xs text-base-content/50 mt-0.5">Phiên bản</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-base-100 border border-base-200 shadow-sm">
                    <div class="card-body p-5">
                        <h3 class="text-sm font-semibold text-base-content/60 uppercase tracking-wide mb-3">Lịch sử</h3>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-base-content/50">Tạo bởi</dt>
                                <dd>{{ $sop->createdBy?->name ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-base-content/50">Ngày tạo</dt>
                                <dd>{{ $sop->created_at?->format('d/m/Y H:i') }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-base-content/50">Cập nhật bởi</dt>
                                <dd>{{ $sop->updatedBy?->name ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-base-content/50">Cập nhật lần cuối</dt>
                                <dd>{{ $sop->updated_at?->format('d/m/Y H:i') }}</dd>
                            </div>
                            @if($sop->approved_at)
                            <div class="flex justify-between">
                                <dt class="text-base-content/50">Duyệt lần cuối</dt>
                                <dd>{{ $sop->approved_at->format('d/m/Y H:i') }}</dd>
                            </div>
                            @endif
                        </dl>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- Tab: Flowchart --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'flowchart'" x-cloak>
    @php $canEdit = $sop->status?->value === 'draft' && Gate::allows('update', $sop); @endphp

    @if($canEdit)
    {{-- ── Editor mode (draft + can update) ──────────────────────────────── --}}
    <div
        x-data="sopEditor(
            '{{ route('backend.sop.flowchart.data', $sop) }}',
            {
                locked:           false,
                stepsUrl:         '{{ url('/backend/api/sop/'.$sop->uuid.'/steps') }}',
                connectorsUrl:    '{{ url('/backend/api/sop/'.$sop->uuid.'/connectors') }}',
                reorderUrl:       '{{ url('/backend/api/sop/'.$sop->uuid.'/steps/reorder') }}',
                approvedSopsUrl:  '{{ route('backend.api.sop.approved') }}',
                raciBaseUrl:      '{{ url('/backend/api/sop-steps') }}',
                usersSearchUrl:   '{{ route('backend.api.users.search') }}',
                rolesUrl:         '{{ route('backend.api.roles') }}',
            }
        )"
        x-init="init()"
    >
        {{-- Editor toolbar --}}
        <div class="flex flex-wrap items-center gap-2 mb-3">
            <button @click="showAddPanel = !showAddPanel; showEditDrawer = false"
                    :class="showAddPanel ? 'btn-primary' : 'btn-outline btn-primary'"
                    class="btn btn-sm gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm bước
            </button>

            <button @click="connectMode = !connectMode; connectSource = null"
                    :class="connectMode ? 'btn-secondary' : 'btn-outline btn-secondary'"
                    class="btn btn-sm gap-1.5"
                    :title="connectMode ? 'Nhấp vào 2 bước để tạo kết nối' : 'Bật chế độ kết nối'">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                <span x-text="connectMode ? 'Đang kết nối…' : 'Kết nối'"></span>
            </button>

            <button @click="toggleValidation()"
                    :class="showValidation && validationErrors.length ? 'btn-error' : 'btn-ghost'"
                    class="btn btn-sm gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Kiểm tra
                <span x-show="showValidation && validationErrors.length > 0"
                      class="badge badge-xs badge-error text-white" x-text="validationErrors.length"></span>
            </button>

            <div class="ml-auto flex items-center gap-2 text-xs text-base-content/40" x-show="!loading">
                <span x-text="`${meta.step_count} bước`"></span>
                <template x-if="meta.total_duration > 0">
                    <span x-text="`· ${meta.total_duration} phút`"></span>
                </template>
            </div>

            <div x-show="connectMode" class="text-xs text-secondary font-medium">
                <span x-show="!connectSource">Nhấp vào bước nguồn…</span>
                <span x-show="connectSource">Nhấp vào bước đích…
                    <button @click="cancelConnect()" class="ml-1 text-base-content/40 hover:text-base-content/70 underline">Huỷ</button>
                </span>
            </div>
        </div>

        {{-- Validation errors --}}
        <div x-show="showValidation && validationErrors.length > 0"
             class="alert alert-warning text-sm py-2 mb-3">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.07 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <ul class="list-disc list-inside">
                <template x-for="err in validationErrors" :key="err">
                    <li x-text="err"></li>
                </template>
            </ul>
        </div>

        {{-- Add Step Panel --}}
        <div x-show="showAddPanel"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="card bg-base-100 border border-primary/30 shadow-sm mb-3">
            <div class="card-body p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-semibold text-base-content/70">Thêm bước mới</h4>
                    <button @click="showAddPanel = false; resetNewStep()"
                            class="btn btn-ghost btn-xs px-1">✕</button>
                </div>

                {{-- Step type palette --}}
                <div class="flex flex-wrap gap-1.5 mb-3">
                    @php
                    $stepTypes = [
                        ['value' => 'start',        'label' => 'Bắt đầu',    'icon' => '●', 'color' => 'text-emerald-600'],
                        ['value' => 'end',          'label' => 'Kết thúc',   'icon' => '◉', 'color' => 'text-emerald-600'],
                        ['value' => 'action',       'label' => 'Hành động',  'icon' => '▬', 'color' => 'text-blue-500'],
                        ['value' => 'decision',     'label' => 'Quyết định', 'icon' => '◆', 'color' => 'text-amber-500'],
                        ['value' => 'sub_sop',      'label' => 'SOP con',    'icon' => '⊞', 'color' => 'text-emerald-500'],
                        ['value' => 'notification', 'label' => 'Thông báo',  'icon' => '▱', 'color' => 'text-violet-500'],
                        ['value' => 'wait',         'label' => 'Chờ',        'icon' => '▢', 'color' => 'text-gray-500'],
                    ];
                    @endphp
                    @foreach($stepTypes as $st)
                    <button type="button"
                            @click="newStep.step_type = '{{ $st['value'] }}'"
                            :class="newStep.step_type === '{{ $st['value'] }}' ? 'ring-2 ring-primary bg-primary/5' : 'hover:bg-base-200'"
                            class="flex flex-col items-center px-3 py-2 rounded-lg border border-base-200 text-xs gap-1 transition-all">
                        <span class="{{ $st['color'] }} text-base leading-none">{{ $st['icon'] }}</span>
                        <span class="text-base-content/70">{{ $st['label'] }}</span>
                    </button>
                    @endforeach
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-base-content/60 mb-1 block">Tên bước <span class="text-error">*</span></label>
                        <input type="text" x-model="newStep.title"
                               :class="addErrors.title ? 'input-error' : ''"
                               class="input input-sm input-bordered w-full"
                               placeholder="Nhập tên bước…"
                               @keydown.enter.prevent="saveNewStep()">
                        <p x-show="addErrors.title" class="text-xs text-error mt-1" x-text="addErrors.title"></p>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-base-content/60 mb-1 block">Thời gian (phút)</label>
                        <input type="number" x-model="newStep.duration_minutes"
                               class="input input-sm input-bordered w-full" min="1" max="32767" placeholder="Tuỳ chọn">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-xs font-medium text-base-content/60 mb-1 block">Mô tả</label>
                        <textarea x-model="newStep.description" rows="2"
                                  class="textarea textarea-sm textarea-bordered w-full resize-none"
                                  placeholder="Hướng dẫn thực hiện…"></textarea>
                    </div>

                    {{-- Sub-SOP ref (only for sub_sop type) --}}
                    <div class="md:col-span-2" x-show="newStep.step_type === 'sub_sop'">
                        <label class="text-xs font-medium text-base-content/60 mb-1 block">SOP con (đã approved)</label>
                        <div class="flex gap-2">
                            <input type="text" x-model="subSopQuery"
                                   @input.debounce.400ms="loadApprovedSops(subSopQuery)"
                                   @focus="loadApprovedSops(subSopQuery)"
                                   class="input input-sm input-bordered flex-1"
                                   placeholder="Tìm theo mã hoặc tên SOP…">
                        </div>
                        <div x-show="approvedSops.length > 0"
                             class="mt-1 border border-base-200 rounded bg-base-100 max-h-40 overflow-y-auto text-xs shadow">
                            <template x-for="s in approvedSops" :key="s.id">
                                <div @click="newStep.ref_sop_id = s.id; subSopQuery = s.label; approvedSops = []"
                                     class="px-3 py-2 hover:bg-base-200 cursor-pointer flex gap-2">
                                    <span class="font-mono font-semibold text-primary shrink-0" x-text="s.code"></span>
                                    <span class="text-base-content/60 truncate" x-text="s.title"></span>
                                </div>
                            </template>
                        </div>
                        <p x-show="newStep.ref_sop_id" class="text-xs text-success mt-1">
                            ✓ Đã chọn SOP con
                        </p>
                    </div>

                    <div class="md:col-span-2 flex items-center gap-2">
                        <input type="checkbox" x-model="newStep.is_mandatory"
                               id="new-step-mandatory" class="checkbox checkbox-xs">
                        <label for="new-step-mandatory" class="text-xs text-base-content/60 cursor-pointer">Bước bắt buộc</label>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-3">
                    <button type="button" @click="showAddPanel = false; resetNewStep()"
                            class="btn btn-ghost btn-sm">Huỷ</button>
                    <button type="button" @click="saveNewStep()"
                            :disabled="addSaving"
                            class="btn btn-primary btn-sm gap-1.5">
                        <span x-show="addSaving" class="loading loading-spinner loading-xs"></span>
                        Thêm bước
                    </button>
                </div>
            </div>
        </div>

        {{-- Loading / Error --}}
        <div x-show="loading" class="flex items-center justify-center py-16 text-base-content/30">
            <span class="loading loading-spinner loading-sm mr-2"></span>
            <span class="text-sm">Đang tải flowchart…</span>
        </div>
        <div x-show="error && !loading" class="alert alert-error text-sm py-3" x-text="error"></div>

        {{-- Main editor area: canvas + edit drawer --}}
        <div x-show="!loading && !error" class="flex gap-3 items-start">

            {{-- SVG Canvas --}}
            <div class="flex-1 min-w-0">

                {{-- Canvas toolbar --}}
                <div class="flex items-center gap-1 mb-2">
                    <label class="flex items-center gap-1.5 text-xs text-base-content/40 cursor-pointer select-none">
                        <input type="checkbox" x-model="showDuration" class="checkbox checkbox-xs">
                        Hiện thời gian
                    </label>
                    <div class="ml-auto flex items-center gap-1">
                        <button @click="toggleLayout()" class="btn btn-ghost btn-xs px-2 text-xs"
                                :title="layoutDir === 'horizontal' ? 'Chuyển sang dọc' : 'Chuyển sang ngang'"
                                x-text="layoutDir === 'horizontal' ? '⇅' : '⇄'"></button>
                        <div class="w-px h-4 bg-base-300 mx-0.5"></div>
                        <button @click="zoomOut()" class="btn btn-ghost btn-xs px-2 font-bold text-sm" title="Thu nhỏ (Ctrl+scroll)">−</button>
                        <span class="text-xs text-base-content/30 w-9 text-center tabular-nums" x-text="`${Math.round(zoom * 100)}%`"></span>
                        <button @click="zoomIn()"  class="btn btn-ghost btn-xs px-2 font-bold text-sm" title="Phóng to (Ctrl+scroll)">+</button>
                        <button @click="fitToScreen()" class="btn btn-ghost btn-xs px-2 text-xs" title="Vừa màn hình">⊡</button>
                    </div>
                </div>

                {{-- Empty canvas --}}
                <div x-show="steps.length === 0"
                     class="flex flex-col items-center py-16 text-base-content/30 border border-dashed border-base-300 rounded-lg">
                    <svg class="w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.8" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                    </svg>
                    <p class="text-sm font-medium mb-1">Quy trình chưa có bước nào</p>
                    <p class="text-xs mb-3">Nhấn "+ Thêm bước" ở trên để bắt đầu</p>
                    <button @click="showAddPanel = true" class="btn btn-primary btn-sm gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Thêm bước đầu tiên
                    </button>
                </div>

                {{-- SVG Canvas --}}
                <div x-show="steps.length > 0"
                     class="relative overflow-hidden border border-base-200 rounded-lg sop-flowchart-canvas"
                     style="height: 440px;"
                     :class="connectMode ? 'cursor-crosshair' : (isPanning ? 'cursor-grabbing' : (spaceDown ? 'cursor-grab' : ''))"
                     @mousedown="onSvgMouseDown($event)"
                     @mousemove="onSvgMouseMove($event)"
                     @mouseup="onSvgMouseUp()"
                     @mouseleave="onSvgMouseUp()"
                     @wheel="onSvgWheel($event)"
                     @touchstart="onTouchStart($event)"
                     @touchmove="onTouchMove($event)"
                     @touchend="onTouchEnd()">
                    <svg width="100%" height="100%"
                         :viewBox="viewBoxStr()"
                         x-ref="svg" class="block select-none">
                        <defs>
                            <marker id="ed-arr-gray" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="5" markerHeight="5" orient="auto-start-reverse">
                                <path d="M2 1L8 5L2 9" fill="none" stroke="#B4B2A9" stroke-width="1.5" stroke-linecap="round"/>
                            </marker>
                            <marker id="ed-arr-green" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="5" markerHeight="5" orient="auto-start-reverse">
                                <path d="M2 1L8 5L2 9" fill="none" stroke="#639922" stroke-width="1.5" stroke-linecap="round"/>
                            </marker>
                            <marker id="ed-arr-red" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="5" markerHeight="5" orient="auto-start-reverse">
                                <path d="M2 1L8 5L2 9" fill="none" stroke="#E24B4A" stroke-width="1.5" stroke-linecap="round"/>
                            </marker>
                            <marker id="ed-arr-purple" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="5" markerHeight="5" orient="auto-start-reverse">
                                <path d="M2 1L8 5L2 9" fill="none" stroke="#7F77DD" stroke-width="1.5" stroke-linecap="round"/>
                            </marker>
                            <marker id="ed-arr-orange" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="5" markerHeight="5" orient="auto-start-reverse">
                                <path d="M2 1L8 5L2 9" fill="none" stroke="#EF9F27" stroke-width="1.5" stroke-linecap="round"/>
                            </marker>
                        </defs>

                        {{-- Connectors --}}
                        <template x-for="conn in layout.connectors" :key="conn.uuid ?? conn.id">
                            <g>
                                <path :d="conn.path" fill="none" :stroke="conn.color"
                                      stroke-width="1.3"
                                      :stroke-dasharray="conn.dashed ? '5 3' : 'none'"
                                      :marker-end="`url(#ed-arr-${conn.markerColor})`"/>
                                {{-- Wide transparent hit area for click --}}
                                <path :d="conn.path" fill="none" stroke="transparent" stroke-width="14"
                                      class="cursor-pointer"
                                      @click.stop="selectedConn = selectedConn?.uuid === conn.uuid ? null : conn"/>
                                {{-- Label --}}
                                <text x-show="conn.label"
                                      :x="conn.labelX" :y="conn.labelY"
                                      text-anchor="middle" font-size="10"
                                      :fill="conn.color"
                                      font-family="ui-sans-serif,system-ui,sans-serif"
                                      x-text="conn.label"/>
                                {{-- Delete button (shown when connector is selected) --}}
                                <g x-show="selectedConn?.uuid === conn.uuid"
                                   @click.stop="deleteConnector(conn, $event)"
                                   class="cursor-pointer">
                                    <circle :cx="conn.labelX" :cy="conn.labelY - 14" r="9" fill="#E24B4A"/>
                                    <text :x="conn.labelX" :y="conn.labelY - 14"
                                          text-anchor="middle" dominant-baseline="central"
                                          font-size="13" fill="white" font-family="sans-serif">×</text>
                                </g>
                            </g>
                        </template>

                        {{-- Nodes --}}
                        <template x-for="node in layout.nodes" :key="node.id">
                            <g @click="selectStep(node)"
                               :class="connectMode ? 'cursor-crosshair' : 'cursor-pointer'"
                               :opacity="selected && !connectMode && selected.id !== node.id ? 0.5 : 1"
                               style="transition: opacity 0.12s">

                                {{-- Connect-mode source ring --}}
                                <circle x-show="connectMode && isConnectSource(node)"
                                        :cx="node.cx" :cy="node.cy"
                                        :r="Math.max(node.w, node.h) / 2 + 6"
                                        fill="none" stroke="#7F77DD" stroke-width="2" stroke-dasharray="4 2" opacity="0.8"/>

                                {{-- rect / rect_double / rounded_rect --}}
                                <template x-if="node.shape === 'rect' || node.shape === 'rect_double' || node.shape === 'rounded_rect'">
                                    <g>
                                        <rect :x="node.x" :y="node.y" :width="node.w" :height="node.h"
                                              :rx="node.shape === 'rounded_rect' ? 18 : 6"
                                              :fill="node.fill" :stroke="node.color"
                                              :stroke-width="selected?.id === node.id ? 2 : 0.9"/>
                                        <template x-if="node.shape === 'rect_double'">
                                            <rect :x="node.x + 4" :y="node.y + 4"
                                                  :width="node.w - 8" :height="node.h - 8"
                                                  rx="3" fill="none" :stroke="node.color" stroke-width="0.6"/>
                                        </template>
                                    </g>
                                </template>

                                {{-- diamond --}}
                                <template x-if="node.shape === 'diamond'">
                                    <path :d="`M${node.cx},${node.y} L${node.x+node.w},${node.cy} L${node.cx},${node.y+node.h} L${node.x},${node.cy} Z`"
                                          :fill="node.fill" :stroke="node.color"
                                          :stroke-width="selected?.id === node.id ? 2 : 0.9"/>
                                </template>

                                {{-- oval / oval_double --}}
                                <template x-if="node.shape === 'oval' || node.shape === 'oval_double'">
                                    <g>
                                        <ellipse :cx="node.cx" :cy="node.cy"
                                                 :rx="node.w / 2" :ry="node.h / 2"
                                                 :fill="node.fill" :stroke="node.color"
                                                 :stroke-width="selected?.id === node.id ? 2 : 1"/>
                                        <template x-if="node.shape === 'oval_double'">
                                            <ellipse :cx="node.cx" :cy="node.cy"
                                                     :rx="node.w / 2 - 4" :ry="node.h / 2 - 4"
                                                     fill="none" :stroke="node.color" stroke-width="0.6"/>
                                        </template>
                                    </g>
                                </template>

                                {{-- parallelogram --}}
                                <template x-if="node.shape === 'parallelogram'">
                                    <path :d="`M${node.x+12},${node.y} L${node.x+node.w},${node.y} L${node.x+node.w-12},${node.y+node.h} L${node.x},${node.y+node.h} Z`"
                                          :fill="node.fill" :stroke="node.color"
                                          :stroke-width="selected?.id === node.id ? 2 : 0.9"/>
                                </template>

                                {{-- Label --}}
                                <text :x="node.cx"
                                      :y="node.cy - (showDuration && node.duration_minutes ? 8 : 0)"
                                      text-anchor="middle" dominant-baseline="central"
                                      font-size="10" font-weight="500" :fill="node.color"
                                      font-family="ui-sans-serif,system-ui,sans-serif"
                                      x-text="node.shortTitle"/>
                                <text x-show="showDuration && node.duration_minutes"
                                      :x="node.cx" :y="node.cy + 9"
                                      text-anchor="middle" dominant-baseline="central"
                                      font-size="9" :fill="node.color" opacity="0.65"
                                      font-family="ui-sans-serif,system-ui,sans-serif"
                                      x-text="`${node.duration_minutes}ph`"/>

                                {{-- Delete button (top-right corner) --}}
                                <g x-show="!connectMode"
                                   @click.stop="deleteStep(node, $event)"
                                   class="cursor-pointer"
                                   :transform="`translate(${node.x + node.w - 8}, ${node.y - 8})`">
                                    <circle r="8" fill="#E24B4A" opacity="0.85"/>
                                    <text text-anchor="middle" dominant-baseline="central"
                                          font-size="11" fill="white" font-family="sans-serif">×</text>
                                </g>
                            </g>
                        </template>
                    </svg>

                    {{-- Minimap overlay (hiển thị khi > 10 bước) --}}
                    <div x-show="steps.length > 10 && layout.nodes.length > 0"
                         class="absolute bottom-2 right-2 rounded shadow border border-base-300 bg-base-100/90"
                         style="pointer-events: none; line-height: 0;">
                        <svg width="120" height="80"
                             :viewBox="`0 0 ${canvas.width} ${canvas.height}`"
                             class="block rounded">
                            <template x-for="n in layout.nodes" :key="n.id">
                                <rect :x="n.x" :y="n.y" :width="n.w" :height="n.h"
                                      :fill="n.fill" :stroke="n.color" stroke-width="3" rx="4"/>
                            </template>
                            {{-- Viewport indicator --}}
                            <rect x-show="zoom !== 1 || panX !== 0 || panY !== 0"
                                  :x="panX" :y="panY"
                                  :width="canvas.width / zoom"
                                  :height="canvas.height / zoom"
                                  fill="rgba(55,138,221,0.12)" stroke="#378ADD" stroke-width="4"/>
                        </svg>
                    </div>

                    {{-- Keyboard hint (mờ, bottom-left) --}}
                    <div class="absolute bottom-2 left-2 text-xs text-base-content/20 select-none pointer-events-none leading-tight">
                        <span>Space+kéo: pan · Ctrl+scroll: zoom · Del: xóa</span>
                    </div>
                </div>

                {{-- Connector Picker (shown when two nodes clicked in connect mode) --}}
                <div x-show="showConnectorPicker"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="mt-2 card bg-base-100 border border-base-200 shadow-md">
                    <div class="card-body p-4">
                        <h5 class="text-sm font-semibold mb-3">Chọn loại kết nối</h5>
                        <div class="grid grid-cols-3 gap-2 mb-3">
                            @php
                            $connTypes = [
                                ['value' => 'sequence',  'label' => 'Tuần tự',    'color' => '#B4B2A9'],
                                ['value' => 'yes_branch','label' => 'Nhánh Có',   'color' => '#639922'],
                                ['value' => 'no_branch', 'label' => 'Nhánh Không','color' => '#E24B4A'],
                                ['value' => 'trigger',   'label' => 'Kích hoạt',  'color' => '#7F77DD'],
                                ['value' => 'return',    'label' => 'Quay về',    'color' => '#EF9F27'],
                                ['value' => 'exception', 'label' => 'Ngoại lệ',  'color' => '#E24B4A'],
                            ];
                            @endphp
                            @foreach($connTypes as $ct)
                            <button type="button"
                                    @click="pendingConn.connector_type = '{{ $ct['value'] }}'"
                                    :class="pendingConn.connector_type === '{{ $ct['value'] }}' ? 'ring-2 ring-primary bg-primary/5' : 'hover:bg-base-200'"
                                    class="flex flex-col items-center gap-1 p-2 rounded-lg border border-base-200 text-xs transition-all">
                                <span class="w-8 h-1 rounded" style="background: {{ $ct['color'] }}"></span>
                                <span class="text-base-content/70">{{ $ct['label'] }}</span>
                            </button>
                            @endforeach
                        </div>
                        <div class="mb-3">
                            <label class="text-xs font-medium text-base-content/60 mb-1 block">Nhãn (tuỳ chọn)</label>
                            <input type="text" x-model="pendingConn.label"
                                   class="input input-sm input-bordered w-full"
                                   placeholder="Có / Không / Lỗi…"
                                   maxlength="60">
                        </div>
                        <div class="flex justify-end gap-2">
                            <button @click="cancelConnect()" class="btn btn-ghost btn-sm">Huỷ</button>
                            <button @click="saveConnector()" :disabled="connSaving"
                                    class="btn btn-primary btn-sm gap-1.5">
                                <span x-show="connSaving" class="loading loading-spinner loading-xs"></span>
                                Tạo kết nối
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Step reorder bar --}}
                <div x-show="steps.length > 1" class="mt-3">
                    <p class="text-xs text-base-content/40 mb-1.5">Kéo thả để sắp xếp thứ tự bước:</p>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="(step, idx) in steps" :key="step.id">
                            <div draggable="true"
                                 @dragstart="onDragStart($event, idx)"
                                 @dragover="onDragOver($event, idx)"
                                 @dragleave="onDragLeave()"
                                 @drop="onDrop($event, idx)"
                                 :class="{
                                     'opacity-40': dragIdx === idx,
                                     'ring-2 ring-primary ring-offset-1': dragoverIdx === idx && dragIdx !== idx,
                                 }"
                                 class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg border border-base-200 bg-base-100 cursor-grab active:cursor-grabbing text-xs select-none hover:bg-base-200 transition-all">
                                <span class="text-base-content/30">⠿</span>
                                <span class="text-base-content/40 font-mono shrink-0" x-text="step.position"></span>
                                <span class="font-medium text-base-content/80 max-w-24 truncate" x-text="step.title"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Edit Drawer (right side) --}}
            <div x-show="showEditDrawer"
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-x-4"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 class="w-80 shrink-0">
                <div class="card bg-base-100 border border-base-200 shadow-sm">
                    <div class="card-body p-4">
                        {{-- Drawer header --}}
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-semibold text-base-content/70">Chỉnh sửa bước</h4>
                            <button @click="showEditDrawer = false; selected = null"
                                    class="btn btn-ghost btn-xs px-1">✕</button>
                        </div>

                        {{-- Drawer sub-tabs --}}
                        <div class="flex border-b border-base-200 mb-3 -mx-1">
                            @foreach([['info','Thông tin'],['raci','RACI'],['files','File']] as [$k,$l])
                            <button type="button"
                                    @click="drawerTab = '{{ $k }}'"
                                    :class="drawerTab === '{{ $k }}'
                                        ? 'border-b-2 border-primary text-primary'
                                        : 'text-base-content/40 hover:text-base-content/70'"
                                    class="text-xs px-3 py-1.5 font-medium transition-colors">
                                {{ $l }}
                                @if($k === 'raci')
                                <span x-show="stepRaci.length > 0" class="ml-0.5 text-primary font-bold" x-text="`(${stepRaci.length})`"></span>
                                @endif
                                @if($k === 'files')
                                <span x-show="stepAttachments.length > 0" class="ml-0.5 text-primary font-bold" x-text="`(${stepAttachments.length})`"></span>
                                @endif
                            </button>
                            @endforeach
                        </div>

                        {{-- Tab: Thông tin --}}
                        <div x-show="drawerTab === 'info'" class="space-y-3">
                            <div>
                                <label class="text-xs font-medium text-base-content/60 mb-1 block">Loại bước</label>
                                <select x-model="editForm.step_type" class="select select-sm select-bordered w-full"
                                        @change="if (editForm.step_type === 'sub_sop') loadApprovedSops('')">
                                    @foreach($stepTypes as $st)
                                    <option value="{{ $st['value'] }}">{{ $st['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-medium text-base-content/60 mb-1 block">Tên bước <span class="text-error">*</span></label>
                                <input type="text" x-model="editForm.title"
                                       :class="editErrors.title ? 'input-error' : ''"
                                       class="input input-sm input-bordered w-full"
                                       @keydown.enter.prevent="saveEdit()">
                                <p x-show="editErrors.title" class="text-xs text-error mt-1" x-text="editErrors.title"></p>
                            </div>

                            <div>
                                <label class="text-xs font-medium text-base-content/60 mb-1 block">Mô tả</label>
                                <textarea x-model="editForm.description" rows="2"
                                          class="textarea textarea-sm textarea-bordered w-full resize-none"
                                          placeholder="Hướng dẫn thực hiện…"></textarea>
                            </div>

                            <div>
                                <label class="text-xs font-medium text-base-content/60 mb-1 block">Kết quả đầu ra</label>
                                <textarea x-model="editForm.expected_output" rows="2"
                                          class="textarea textarea-sm textarea-bordered w-full resize-none"
                                          placeholder="Sản phẩm/kết quả kỳ vọng…"></textarea>
                            </div>

                            <div>
                                <label class="text-xs font-medium text-base-content/60 mb-1 block">Cảnh báo</label>
                                <textarea x-model="editForm.warning_note" rows="2"
                                          class="textarea textarea-sm textarea-bordered w-full resize-none"
                                          placeholder="Lưu ý quan trọng…"></textarea>
                            </div>

                            <div>
                                <label class="text-xs font-medium text-base-content/60 mb-1 block">Thời gian (phút)</label>
                                <input type="number" x-model="editForm.duration_minutes"
                                       class="input input-sm input-bordered w-full" min="1" max="32767">
                            </div>

                            {{-- Sub-SOP ref --}}
                            <div x-show="editForm.step_type === 'sub_sop'">
                                <label class="text-xs font-medium text-base-content/60 mb-1 block">SOP con</label>
                                <input type="text" x-model="subSopQuery"
                                       @input.debounce.400ms="loadApprovedSops(subSopQuery)"
                                       @focus="loadApprovedSops(subSopQuery)"
                                       class="input input-sm input-bordered w-full"
                                       placeholder="Tìm SOP đã approved…">
                                <div x-show="approvedSops.length > 0"
                                     class="mt-1 border border-base-200 rounded bg-base-100 max-h-32 overflow-y-auto text-xs shadow">
                                    <template x-for="s in approvedSops" :key="s.id">
                                        <div @click="editForm.ref_sop_id = s.id; subSopQuery = s.label; approvedSops = []"
                                             class="px-2 py-1.5 hover:bg-base-200 cursor-pointer flex gap-2">
                                            <span class="font-mono font-semibold text-primary shrink-0" x-text="s.code"></span>
                                            <span class="text-base-content/60 truncate" x-text="s.title"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <input type="checkbox" x-model="editForm.is_mandatory"
                                       id="edit-step-mandatory" class="checkbox checkbox-xs">
                                <label for="edit-step-mandatory" class="text-xs text-base-content/60 cursor-pointer">Bước bắt buộc</label>
                            </div>

                            <div class="flex justify-end gap-2 pt-2 border-t border-base-200">
                                <button @click="showEditDrawer = false; selected = null"
                                        class="btn btn-ghost btn-sm">Huỷ</button>
                                <button @click="saveEdit()" :disabled="editSaving"
                                        class="btn btn-primary btn-sm gap-1.5">
                                    <span x-show="editSaving" class="loading loading-spinner loading-xs"></span>
                                    Lưu
                                </button>
                            </div>
                        </div>

                        {{-- Tab: RACI --}}
                        <div x-show="drawerTab === 'raci'">

                            {{-- RACI add form --}}
                            <div class="mb-3 p-2.5 bg-base-50 rounded-lg border border-base-200">
                                <p class="text-xs font-medium text-base-content/60 mb-2">Thêm phân công</p>
                                <div class="grid grid-cols-2 gap-1.5 mb-2">
                                    <div>
                                        <label class="text-xs text-base-content/50 mb-0.5 block">Loại</label>
                                        <select x-model="raciForm.assignee_type"
                                                @change="raciSearchQ = ''; raciSearchResults = []"
                                                class="select select-xs select-bordered w-full">
                                            <option value="user">Người dùng</option>
                                            <option value="role">Vai trò</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="text-xs text-base-content/50 mb-0.5 block">RACI</label>
                                        <select x-model="raciForm.raci_type" class="select select-xs select-bordered w-full">
                                            <option value="R">R — Thực hiện</option>
                                            <option value="A">A — Chịu trách nhiệm</option>
                                            <option value="C">C — Tư vấn</option>
                                            <option value="I">I — Thông báo</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="relative">
                                    <input type="text" x-model="raciSearchQ"
                                           @input.debounce.300ms="searchRaciAssignees()"
                                           class="input input-xs input-bordered w-full"
                                           :placeholder="raciForm.assignee_type === 'user' ? 'Tìm người dùng…' : 'Tìm vai trò…'">
                                    <div x-show="raciSearchResults.length > 0"
                                         class="absolute z-20 left-0 right-0 top-full mt-1 border border-base-200 rounded bg-base-100 max-h-32 overflow-y-auto shadow text-xs">
                                        <template x-for="r in raciSearchResults" :key="r.id">
                                            <div @click="addRaciAssignment(r)"
                                                 class="px-2.5 py-1.5 hover:bg-base-200 cursor-pointer flex items-center justify-between">
                                                <span class="font-medium" x-text="r.name"></span>
                                                <span class="text-base-content/40 text-xs" x-text="r.meta"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                                <div x-show="raciSaving" class="text-xs text-base-content/40 mt-1 text-center">
                                    <span class="loading loading-spinner loading-xs mr-1"></span>Đang lưu…
                                </div>
                            </div>

                            {{-- RACI matrix 4 columns --}}
                            <div x-show="stepRaci.length === 0" class="text-xs text-base-content/30 text-center py-4">
                                Chưa có phân công RACI nào
                            </div>
                            <div x-show="stepRaci.length > 0" class="grid grid-cols-4 gap-1 text-xs">
                                <template x-for="type in ['R','A','C','I']" :key="type">
                                    <div>
                                        <div class="text-center font-bold text-xs py-0.5 mb-1.5 rounded"
                                             :class="{
                                                'bg-blue-50 text-blue-700': type === 'R',
                                                'bg-amber-50 text-amber-700': type === 'A',
                                                'bg-teal-50 text-teal-700': type === 'C',
                                                'bg-gray-100 text-gray-600': type === 'I',
                                             }"
                                             x-text="type"></div>
                                        <template x-for="r in stepRaci.filter(x => x.raci_type === type)" :key="r.uuid">
                                            <div class="flex items-center gap-0.5 mb-1 group">
                                                <span class="flex-1 truncate leading-tight" x-text="r.assignee_name" :title="r.assignee_name"></span>
                                                <button @click="removeRaciAssignment(r)"
                                                        class="opacity-0 group-hover:opacity-100 text-error/50 hover:text-error shrink-0 transition-opacity leading-none text-base">×</button>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Tab: File đính kèm --}}
                        <div x-show="drawerTab === 'files'">

                            {{-- File list --}}
                            <div x-show="stepAttachments.length === 0" class="text-xs text-base-content/30 text-center py-3">
                                Chưa có file đính kèm nào
                            </div>
                            <div class="space-y-1.5 mb-3">
                                <template x-for="att in stepAttachments" :key="att.uuid">
                                    <div class="flex items-center gap-2 px-2 py-1.5 rounded border border-base-200 hover:bg-base-50 text-xs group">
                                        <svg class="w-3.5 h-3.5 text-base-content/30 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                        </svg>
                                        <a :href="att.file_url" target="_blank"
                                           class="flex-1 truncate text-primary hover:underline font-medium"
                                           x-text="att.file_name" :title="att.file_name"></a>
                                        <span class="text-base-content/30 shrink-0" x-text="att.file_size_label"></span>
                                        <button @click="deleteAttachmentItem(att)"
                                                class="opacity-0 group-hover:opacity-100 text-error/50 hover:text-error shrink-0 transition-opacity text-base leading-none">×</button>
                                    </div>
                                </template>
                            </div>

                            {{-- Upload zone --}}
                            <label class="flex flex-col items-center justify-center py-4 border-2 border-dashed border-base-300 rounded-lg cursor-pointer hover:border-primary/50 hover:bg-primary/5 transition-all"
                                   @dragover.prevent="$el.classList.add('border-primary','bg-primary/5')"
                                   @dragleave="$el.classList.remove('border-primary','bg-primary/5')"
                                   @drop.prevent="$el.classList.remove('border-primary','bg-primary/5'); handleDropUpload($event)">
                                <input type="file" class="hidden"
                                       @change="handleFileUpload($event)"
                                       multiple
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.png,.jpg,.jpeg,.gif,.webp,.txt,.zip">
                                <svg class="w-6 h-6 mb-1.5 text-base-content/25" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <span class="text-xs text-base-content/40">Kéo thả hoặc nhấp để tải lên</span>
                                <span class="text-xs text-base-content/25 mt-0.5">PDF, Word, Excel, ảnh, ZIP · tối đa 20MB</span>
                            </label>

                            <div x-show="uploading" class="mt-2 text-xs text-base-content/50 flex items-center justify-center gap-1.5">
                                <span class="loading loading-spinner loading-xs"></span>
                                Đang tải lên…
                            </div>
                            <div x-show="uploadError" class="mt-1 text-xs text-error" x-text="uploadError"></div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>

    @else
    {{-- ── View mode (approved / no permission) ───────────────────────────── --}}
    @if($sop->activeSteps->isEmpty())
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body py-16 flex flex-col items-center text-base-content/30">
            <svg class="w-16 h-16 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.8" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
            </svg>
            <p class="text-sm font-medium mb-1">Quy trình chưa có bước nào</p>
            <p class="text-xs">SOP đang ở trạng thái {{ $sop->status?->label() }}</p>
        </div>
    </div>
    @else
    <div
        x-data="sopFlowchart('{{ route('backend.sop.flowchart.data', $sop) }}')"
        x-init="init()"
    >
        {{-- Toolbar --}}
        <div class="flex flex-wrap items-center gap-3 mb-3">
            <div class="flex items-center gap-1.5 text-xs text-base-content/50" x-show="!loading && !error">
                <span x-text="`${meta.step_count} bước`"></span>
                <template x-if="meta.total_duration > 0">
                    <span x-text="`· ${meta.total_duration} phút`"></span>
                </template>
            </div>
            <div class="flex items-center gap-1 ml-auto">
                <label class="flex items-center gap-1.5 text-xs text-base-content/50 cursor-pointer select-none">
                    <input type="checkbox" x-model="showDuration" class="checkbox checkbox-xs">
                    Hiện thời gian
                </label>
                <button @click="toggleLayout()" class="btn btn-ghost btn-xs px-2 text-xs"
                        :title="layoutDir === 'horizontal' ? 'Chuyển sang dọc' : 'Chuyển sang ngang'"
                        x-text="layoutDir === 'horizontal' ? '⇅' : '⇄'"></button>
                <div class="w-px h-4 bg-base-300 mx-0.5"></div>
                <button @click="zoomOut()" class="btn btn-ghost btn-xs px-2 font-bold text-base" title="Thu nhỏ (Ctrl+scroll)">−</button>
                <span class="text-xs text-base-content/30 w-9 text-center tabular-nums" x-text="`${Math.round(zoom * 100)}%`"></span>
                <button @click="zoomIn()"  class="btn btn-ghost btn-xs px-2 font-bold text-base" title="Phóng to (Ctrl+scroll)">+</button>
                <button @click="fitToScreen()" class="btn btn-ghost btn-xs px-2 text-xs" title="Vừa màn hình">⊡</button>
            </div>
        </div>

        <div x-show="loading" class="flex items-center justify-center py-16 text-base-content/30">
            <span class="loading loading-spinner loading-sm mr-2"></span>
            <span class="text-sm">Đang tải flowchart…</span>
        </div>
        <div x-show="error && !loading" class="alert alert-error text-sm py-3" x-text="error"></div>

        <div x-show="!loading && !error"
             class="relative overflow-hidden border border-base-200 rounded-lg sop-flowchart-canvas"
             style="height: 460px;"
             :class="isPanning ? 'cursor-grabbing' : (spaceDown ? 'cursor-grab' : '')"
             @mousedown="onSvgMouseDown($event)"
             @mousemove="onSvgMouseMove($event)"
             @mouseup="onSvgMouseUp()"
             @mouseleave="onSvgMouseUp()"
             @wheel="onSvgWheel($event)"
             @touchstart="onTouchStart($event)"
             @touchmove="onTouchMove($event)"
             @touchend="onTouchEnd()">
            <svg width="100%" height="100%"
                 :viewBox="viewBoxStr()"
                 x-ref="svg" class="block select-none">
                <defs>
                    <marker id="sop-arr-gray" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="5" markerHeight="5" orient="auto-start-reverse">
                        <path d="M2 1L8 5L2 9" fill="none" stroke="#B4B2A9" stroke-width="1.5" stroke-linecap="round"/>
                    </marker>
                    <marker id="sop-arr-green" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="5" markerHeight="5" orient="auto-start-reverse">
                        <path d="M2 1L8 5L2 9" fill="none" stroke="#639922" stroke-width="1.5" stroke-linecap="round"/>
                    </marker>
                    <marker id="sop-arr-red" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="5" markerHeight="5" orient="auto-start-reverse">
                        <path d="M2 1L8 5L2 9" fill="none" stroke="#E24B4A" stroke-width="1.5" stroke-linecap="round"/>
                    </marker>
                    <marker id="sop-arr-purple" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="5" markerHeight="5" orient="auto-start-reverse">
                        <path d="M2 1L8 5L2 9" fill="none" stroke="#7F77DD" stroke-width="1.5" stroke-linecap="round"/>
                    </marker>
                    <marker id="sop-arr-orange" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="5" markerHeight="5" orient="auto-start-reverse">
                        <path d="M2 1L8 5L2 9" fill="none" stroke="#EF9F27" stroke-width="1.5" stroke-linecap="round"/>
                    </marker>
                </defs>

                <template x-for="conn in layout.connectors" :key="conn.id">
                    <g>
                        <path :d="conn.path" fill="none" :stroke="conn.color"
                              stroke-width="1.3" :stroke-dasharray="conn.dashed ? '5 3' : 'none'"
                              :marker-end="`url(#sop-arr-${conn.markerColor})`"/>
                        <text x-show="conn.label" :x="conn.labelX" :y="conn.labelY"
                              text-anchor="middle" font-size="10" :fill="conn.color"
                              font-family="ui-sans-serif,system-ui,sans-serif" x-text="conn.label"/>
                    </g>
                </template>

                <template x-for="node in layout.nodes" :key="node.id">
                    <g class="cursor-pointer" @click="selectStep(node)"
                       :opacity="selected && selected.id !== node.id ? 0.5 : 1"
                       style="transition: opacity 0.12s">
                        <template x-if="node.shape === 'rect' || node.shape === 'rect_double' || node.shape === 'rounded_rect'">
                            <g>
                                <rect :x="node.x" :y="node.y" :width="node.w" :height="node.h"
                                      :rx="node.shape === 'rounded_rect' ? 18 : 6"
                                      :fill="node.fill" :stroke="node.color"
                                      :stroke-width="selected?.id === node.id ? 2 : 0.9"/>
                                <template x-if="node.shape === 'rect_double'">
                                    <rect :x="node.x + 4" :y="node.y + 4" :width="node.w - 8" :height="node.h - 8"
                                          rx="3" fill="none" :stroke="node.color" stroke-width="0.6"/>
                                </template>
                            </g>
                        </template>
                        <template x-if="node.shape === 'diamond'">
                            <path :d="`M${node.cx},${node.y} L${node.x+node.w},${node.cy} L${node.cx},${node.y+node.h} L${node.x},${node.cy} Z`"
                                  :fill="node.fill" :stroke="node.color" :stroke-width="selected?.id === node.id ? 2 : 0.9"/>
                        </template>
                        <template x-if="node.shape === 'oval' || node.shape === 'oval_double'">
                            <g>
                                <ellipse :cx="node.cx" :cy="node.cy" :rx="node.w / 2" :ry="node.h / 2"
                                         :fill="node.fill" :stroke="node.color" :stroke-width="selected?.id === node.id ? 2 : 1"/>
                                <template x-if="node.shape === 'oval_double'">
                                    <ellipse :cx="node.cx" :cy="node.cy" :rx="node.w / 2 - 4" :ry="node.h / 2 - 4"
                                             fill="none" :stroke="node.color" stroke-width="0.6"/>
                                </template>
                            </g>
                        </template>
                        <template x-if="node.shape === 'parallelogram'">
                            <path :d="`M${node.x+12},${node.y} L${node.x+node.w},${node.y} L${node.x+node.w-12},${node.y+node.h} L${node.x},${node.y+node.h} Z`"
                                  :fill="node.fill" :stroke="node.color" :stroke-width="selected?.id === node.id ? 2 : 0.9"/>
                        </template>
                        <text :x="node.cx" :y="node.cy - (showDuration && node.duration_minutes ? 8 : 0)"
                              text-anchor="middle" dominant-baseline="central"
                              font-size="10" font-weight="500" :fill="node.color"
                              font-family="ui-sans-serif,system-ui,sans-serif" x-text="node.shortTitle"/>
                        <text x-show="showDuration && node.duration_minutes"
                              :x="node.cx" :y="node.cy + 9"
                              text-anchor="middle" dominant-baseline="central"
                              font-size="9" :fill="node.color" opacity="0.65"
                              font-family="ui-sans-serif,system-ui,sans-serif"
                              x-text="`${node.duration_minutes}ph`"/>
                    </g>
                </template>
            </svg>

            {{-- Minimap overlay (hiển thị khi > 10 bước) --}}
            <div x-show="steps.length > 10 && layout.nodes.length > 0"
                 class="absolute bottom-2 right-2 rounded shadow border border-base-300 bg-base-100/90"
                 style="pointer-events: none; line-height: 0;">
                <svg width="120" height="80"
                     :viewBox="`0 0 ${canvas.width} ${canvas.height}`"
                     class="block rounded">
                    <template x-for="n in layout.nodes" :key="n.id">
                        <rect :x="n.x" :y="n.y" :width="n.w" :height="n.h"
                              :fill="n.fill" :stroke="n.color" stroke-width="3" rx="4"/>
                    </template>
                    <rect x-show="zoom !== 1 || panX !== 0 || panY !== 0"
                          :x="panX" :y="panY"
                          :width="canvas.width / zoom"
                          :height="canvas.height / zoom"
                          fill="rgba(55,138,221,0.12)" stroke="#378ADD" stroke-width="4"/>
                </svg>
            </div>

            {{-- Keyboard hint --}}
            <div class="absolute bottom-2 left-2 text-xs text-base-content/20 select-none pointer-events-none leading-tight">
                <span>Space+kéo: pan · Ctrl+scroll: zoom · Esc: bỏ chọn</span>
            </div>
        </div>

        {{-- Step detail panel --}}
        <div x-show="selected && !loading"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="mt-3 p-4 rounded-lg border-l-4 bg-base-50 border border-base-200"
             :style="`border-left-color: ${selected?.color}`">
            <template x-if="selected">
                <div>
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <span class="font-semibold text-sm" x-text="`Bước ${selected.position}: ${selected.title}`"></span>
                            <span class="ml-2 text-xs text-base-content/40" x-text="selected.step_type"></span>
                        </div>
                        <button @click="selected = null"
                                class="text-base-content/30 hover:text-base-content/60 text-xl leading-none shrink-0 ml-3">&times;</button>
                    </div>
                    <p x-show="selected.description" class="text-xs text-base-content/60 leading-relaxed mb-2" x-text="selected.description"></p>
                    <div x-show="selected.expected_output" class="text-xs text-base-content/50 mb-2" x-text="`→ ${selected.expected_output}`"></div>
                    <div x-show="selected.warning_note" class="text-xs text-amber-700 bg-amber-50 border border-amber-200 p-2 rounded mb-2" x-text="`⚠ ${selected.warning_note}`"></div>
                    <div x-show="selected.raci && selected.raci.length > 0" class="flex flex-wrap gap-1 mb-2">
                        <template x-for="r in selected.raci" :key="r.raci_type + r.assignee_name">
                            <span class="text-xs px-2 py-0.5 rounded font-medium"
                                  :class="{'bg-blue-50 text-blue-700': r.raci_type === 'R', 'bg-amber-50 text-amber-700': r.raci_type === 'A', 'bg-teal-50 text-teal-700': r.raci_type === 'C', 'bg-gray-100 text-gray-600': r.raci_type === 'I'}"
                                  x-text="`${r.raci_type}: ${r.assignee_name}`"/>
                        </template>
                    </div>
                    <div x-show="selected.attachment_count > 0" class="text-xs text-base-content/40 mb-2" x-text="`📎 ${selected.attachment_count} file đính kèm`"></div>
                    <div x-show="selected.step_type === 'sub_sop' && selected.ref_sop_code" class="mt-1">
                        <a :href="`/dashboard/sop/${selected.ref_sop_code}`" class="text-xs text-primary hover:underline"
                           x-text="`↗ Xem SOP con: ${selected.ref_sop_code} — ${selected.ref_sop_title}`"></a>
                    </div>
                    <div x-show="!selected.is_mandatory" class="mt-1">
                        <span class="badge badge-xs badge-ghost">Tùy chọn</span>
                    </div>
                </div>
            </template>
        </div>
    </div>
    @endif
    @endif
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- Tab: Phiên bản — Visual Timeline --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'versions'" x-cloak>
    @php
        $versions = $sop->versions()
            ->with(['createdBy:id,name', 'approvedBy:id,name', 'approvalFlows.approver:id,name'])
            ->orderByDesc('version_number')
            ->get();
    @endphp
    @if($versions->isEmpty())
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body py-14 flex flex-col items-center text-base-content/30">
                <svg class="w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-medium mb-1">Chưa có phiên bản nào</p>
                <p class="text-xs">Gửi duyệt để tạo phiên bản đầu tiên</p>
            </div>
        </div>
    @else
    {{-- Visual timeline --}}
    <div class="relative">
        {{-- Vertical spine --}}
        <div class="absolute left-[19px] top-6 bottom-0 w-0.5 bg-base-200" style="z-index:0"></div>

        <div class="space-y-0">
            @foreach($versions as $ver)
            @php
                $statusVal = $ver->status?->value ?? (string)$ver->status;
                $dotColor  = match($statusVal) {
                    'approved'  => 'bg-success border-success',
                    'submitted' => 'bg-warning border-warning',
                    'rejected'  => 'bg-error border-error',
                    default     => 'bg-base-300 border-base-300',
                };
                $badgeClass = match($statusVal) {
                    'approved'  => 'badge-success',
                    'submitted' => 'badge-warning',
                    'rejected'  => 'badge-error',
                    default     => 'badge-ghost',
                };
                $pendingFlow = $ver->approvalFlows->first(fn($f) => $f->action === null);
                $isLast = $loop->last;
            @endphp
            <div class="relative flex gap-4 pb-6">
                {{-- Dot --}}
                <div class="relative z-10 flex-shrink-0 w-10 flex justify-center pt-1">
                    <span class="w-4 h-4 rounded-full border-2 {{ $dotColor }} flex items-center justify-center shadow-sm">
                        @if($statusVal === 'approved')
                        <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        @elseif($statusVal === 'rejected')
                        <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                        @endif
                    </span>
                </div>

                {{-- Content card --}}
                <div class="flex-1 min-w-0">
                    <div class="card bg-base-100 border border-base-200 shadow-sm hover:shadow-md transition-shadow">
                        <div class="card-body p-4">
                            {{-- Header row --}}
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-mono font-bold text-primary text-base">v{{ $ver->version_number }}</span>
                                    <span class="badge badge-sm {{ $badgeClass }}">{{ $ver->status?->label() }}</span>
                                    @if($ver->total_steps)
                                    <span class="text-xs text-base-content/40 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                        </svg>
                                        {{ $ver->total_steps }} bước
                                    </span>
                                    @endif
                                    @if($ver->total_duration_minutes)
                                    <span class="text-xs text-base-content/40 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        {{ $ver->total_duration_minutes }} phút
                                    </span>
                                    @endif
                                </div>
                                <div class="flex gap-2 shrink-0">
                                    @if($statusVal === 'approved')
                                    <a href="{{ route('backend.sop.versions.review', [$sop, $ver]) }}"
                                       class="btn btn-ghost btn-xs gap-1">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        Xem snapshot
                                    </a>
                                    @can('update', $sop)
                                    <button type="button"
                                            onclick="if(confirm('Rollback về v{{ $ver->version_number }}? SOP sẽ về trạng thái draft.')) {
                                                fetch('{{ route('backend.api.sop.rollback', [$sop, $ver]) }}', {
                                                    method:'POST',
                                                    headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content}
                                                }).then(r=>r.json()).then(d=>{ alert(d.message); location.reload(); });
                                            }"
                                            class="btn btn-ghost btn-xs text-warning gap-1">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        Rollback
                                    </button>
                                    @endcan
                                    @elseif($statusVal === 'submitted' && $pendingFlow && auth()->user()->can('approve', $sop))
                                    <a href="{{ route('backend.sop.versions.review', [$sop, $ver]) }}"
                                       class="btn btn-warning btn-xs gap-1">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Duyệt ngay
                                    </a>
                                    @endif
                                </div>
                            </div>

                            {{-- Change summary --}}
                            @if($ver->change_summary)
                            <p class="text-sm text-base-content/60 mt-2 leading-relaxed border-l-2 border-base-300 pl-3">
                                {{ $ver->change_summary }}
                            </p>
                            @endif

                            {{-- Meta row --}}
                            <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-xs text-base-content/40">
                                <span>Tạo bởi <span class="text-base-content/60">{{ $ver->createdBy?->name ?? '—' }}</span></span>
                                <span>{{ $ver->created_at?->format('d/m/Y H:i') }}</span>
                                @if($ver->approved_at)
                                <span>·
                                    Duyệt bởi <span class="text-base-content/60">{{ $ver->approvedBy?->name ?? '—' }}</span>
                                    lúc {{ $ver->approved_at->format('d/m/Y H:i') }}
                                </span>
                                @endif
                            </div>

                            {{-- Approval flow steps --}}
                            @if($ver->approvalFlows->isNotEmpty())
                            <div class="mt-3 pt-3 border-t border-base-200">
                                <p class="text-xs font-medium text-base-content/50 mb-2">Luồng phê duyệt</p>
                                <div class="flex flex-wrap items-center gap-2">
                                    @foreach($ver->approvalFlows->sortBy('step_order') as $flow)
                                    @php
                                        $fc = match($flow->action) {
                                            'approved'  => 'badge-success',
                                            'rejected'  => 'badge-error',
                                            'forwarded' => 'badge-info',
                                            default     => 'badge-ghost',
                                        };
                                    @endphp
                                    @if(!$loop->first)
                                    <svg class="w-3 h-3 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    @endif
                                    <span class="badge badge-sm {{ $fc }} gap-1">
                                        <span class="font-mono text-xs opacity-70">{{ $flow->step_order }}</span>
                                        @if($flow->action)
                                            {{ $flow->approver?->name ?? '—' }} —
                                            {{ match($flow->action) { 'approved' => 'Đã duyệt', 'rejected' => 'Từ chối', 'forwarded' => 'Chuyển tiếp' } }}
                                        @else
                                            @if($flow->required_role) {{ $flow->required_role }} — @endif Chờ duyệt
                                        @endif
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- Tab: Quan hệ SOP --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'relations'" x-cloak>
    @php
    $initialRelations = $sop->sopRelations->map(fn ($r) => [
        'uuid'          => $r->uuid,
        'relation_type' => $r->relation_type,
        'note'          => $r->note,
        'related_sop'   => $r->relatedSop ? [
            'uuid'   => $r->relatedSop->uuid,
            'code'   => $r->relatedSop->code,
            'title'  => $r->relatedSop->title,
            'status' => $r->relatedSop->status?->value,
        ] : null,
    ])->values();
    $canManageRel = Gate::allows('update', $sop);
    @endphp
    <div x-data="{
        relations: @json($initialRelations),
        relationsUrl: '{{ url('/backend/api/sop/' . $sop->uuid . '/relations') }}',
        sopSearchUrl: '{{ route('backend.api.sop.search') }}',
        sopExcludeId: {{ $sop->id }},
        canManage: {{ $canManageRel ? 'true' : 'false' }},

        adding: false,
        saving: false,
        addError: '',
        relForm: { related_sop_id: null, relation_type: 'related', note: '' },
        sopQuery: '',
        sopResults: [],
        selectedSop: null,
        loadingSops: false,

        get prerequisites() { return this.relations.filter(r => r.relation_type === 'prerequisite') },
        get replacedBy()    { return this.relations.filter(r => r.relation_type === 'replaced_by') },

        get grouped() {
            const defs = [
                { type: 'prerequisite', label: 'Tiền điều kiện', badgeClass: 'badge-warning' },
                { type: 'related',      label: 'Liên quan',      badgeClass: 'badge-info' },
                { type: 'replaces',     label: 'Thay thế',       badgeClass: 'badge-success' },
                { type: 'replaced_by',  label: 'Bị thay thế bởi', badgeClass: 'badge-error' },
            ];
            return defs.map(d => ({ ...d, items: this.relations.filter(r => r.relation_type === d.type) }))
                       .filter(g => g.items.length > 0);
        },

        async searchSops(q) {
            if (!q.trim()) { this.sopResults = []; return; }
            this.loadingSops = true;
            try {
                const res = await fetch(this.sopSearchUrl + '?q=' + encodeURIComponent(q) + '&exclude=' + this.sopExcludeId);
                this.sopResults = await res.json();
            } catch(e) { this.sopResults = []; }
            this.loadingSops = false;
        },

        selectSop(s) {
            this.selectedSop = s;
            this.relForm.related_sop_id = s.id;
            this.sopQuery = s.label;
            this.sopResults = [];
        },

        clearSop() {
            this.selectedSop = null;
            this.relForm.related_sop_id = null;
            this.sopQuery = '';
            this.sopResults = [];
        },

        cancelAdd() {
            this.adding = false;
            this.addError = '';
            this.relForm = { related_sop_id: null, relation_type: 'related', note: '' };
            this.clearSop();
        },

        async addRelation() {
            if (!this.relForm.related_sop_id) { this.addError = 'Vui lòng chọn SOP liên quan.'; return; }
            this.saving = true;
            this.addError = '';
            try {
                const res = await fetch(this.relationsUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                    },
                    body: JSON.stringify(this.relForm),
                });
                if (res.ok) {
                    const data = await res.json();
                    this.relations.push(data);
                    this.cancelAdd();
                } else {
                    const err = await res.json();
                    this.addError = err.message || 'Có lỗi xảy ra.';
                }
            } catch(e) {
                this.addError = 'Không thể kết nối máy chủ.';
            }
            this.saving = false;
        },

        async deleteRelation(rel) {
            if (!confirm('Xoá quan hệ này?')) return;
            try {
                const res = await fetch(this.relationsUrl + '/' + rel.uuid, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '' },
                });
                if (res.ok) {
                    this.relations = this.relations.filter(x => x.uuid !== rel.uuid);
                }
            } catch(e) {}
        },
    }">

        {{-- ── Prerequisite banner (reactive) ─────────────────────────────────── --}}
        <template x-if="prerequisites.length > 0">
            <div class="alert alert-warning mb-4 py-3 text-sm">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.07 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <div>
                    <p class="font-semibold">Tiền điều kiện — cần hoàn thành trước khi thực hiện quy trình này</p>
                    <ul class="list-disc list-inside mt-1 space-y-0.5">
                        <template x-for="r in prerequisites" :key="r.uuid">
                            <li>
                                <a :href="`/dashboard/sop/${r.related_sop?.uuid}`"
                                   class="font-mono font-semibold hover:underline"
                                   x-text="r.related_sop?.code"></a>
                                <span class="ml-1 text-warning-content/70" x-text="`— ${r.related_sop?.title}`"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </template>

        {{-- ── Replaced-by banner (reactive) ──────────────────────────────────── --}}
        <template x-if="replacedBy.length > 0">
            <div class="alert alert-error mb-4 py-3 text-sm">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
                <div>
                    <p class="font-semibold">Quy trình này đã được thay thế bởi:</p>
                    <ul class="list-disc list-inside mt-1 space-y-0.5">
                        <template x-for="r in replacedBy" :key="r.uuid">
                            <li>
                                <a :href="`/dashboard/sop/${r.related_sop?.uuid}`"
                                   class="font-mono font-semibold hover:underline"
                                   x-text="r.related_sop?.code"></a>
                                <span x-text="`— ${r.related_sop?.title}`"></span>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </template>

        {{-- ── Main card ───────────────────────────────────────────────────────── --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-5">

                {{-- Header --}}
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-semibold text-base-content/70">
                        Quan hệ SOP
                        <span x-show="relations.length > 0"
                              class="ml-1 badge badge-sm badge-neutral"
                              x-text="relations.length"></span>
                    </h3>
                    <button x-show="canManage && !adding"
                            @click="adding = true"
                            class="btn btn-sm btn-outline btn-primary gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Thêm quan hệ
                    </button>
                </div>

                {{-- Add form --}}
                <div x-show="adding"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="mb-4 p-4 rounded-lg border border-primary/30 bg-primary/5">
                    <h4 class="text-sm font-semibold text-base-content/70 mb-3">Thêm quan hệ mới</h4>

                    {{-- Relation type --}}
                    <div class="mb-3">
                        <label class="text-xs font-medium text-base-content/60 mb-1.5 block">Loại quan hệ</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            @php
                            $relTypes = [
                                ['value' => 'prerequisite', 'label' => 'Tiền điều kiện', 'desc' => 'Cần hoàn thành trước'],
                                ['value' => 'related',      'label' => 'Liên quan',       'desc' => 'Liên quan chủ đề'],
                                ['value' => 'replaces',     'label' => 'Thay thế',        'desc' => 'SOP này thay thế SOP kia'],
                                ['value' => 'replaced_by',  'label' => 'Bị thay thế',    'desc' => 'SOP kia thay thế SOP này'],
                            ];
                            @endphp
                            @foreach($relTypes as $rt)
                            <button type="button"
                                    @click="relForm.relation_type = '{{ $rt['value'] }}'"
                                    :class="relForm.relation_type === '{{ $rt['value'] }}'
                                        ? 'ring-2 ring-primary bg-primary/10 border-primary/30'
                                        : 'hover:bg-base-200 border-base-200'"
                                    class="flex flex-col items-start gap-0.5 p-2.5 rounded-lg border text-left transition-all">
                                <span class="text-xs font-semibold text-base-content/80">{{ $rt['label'] }}</span>
                                <span class="text-xs text-base-content/40">{{ $rt['desc'] }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- SOP search --}}
                    <div class="mb-3">
                        <label class="text-xs font-medium text-base-content/60 mb-1 block">SOP liên quan <span class="text-error">*</span></label>
                        <div class="relative">
                            <input type="text" x-model="sopQuery"
                                   @input.debounce.350ms="searchSops(sopQuery)"
                                   @focus="searchSops(sopQuery)"
                                   :class="addError && !relForm.related_sop_id ? 'input-error' : ''"
                                   class="input input-sm input-bordered w-full pr-7"
                                   placeholder="Tìm theo mã hoặc tên SOP…">
                            <button x-show="selectedSop"
                                    @click="clearSop()"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-base-content/30 hover:text-base-content/60 text-lg leading-none">×</button>
                        </div>
                        <div x-show="sopResults.length > 0 || loadingSops"
                             class="mt-1 border border-base-200 rounded-lg bg-base-100 shadow-sm max-h-40 overflow-y-auto text-xs">
                            <div x-show="loadingSops" class="flex items-center gap-2 px-3 py-2 text-base-content/40">
                                <span class="loading loading-spinner loading-xs"></span>
                                Đang tìm kiếm…
                            </div>
                            <template x-for="s in sopResults" :key="s.id">
                                <div @click="selectSop(s)"
                                     class="px-3 py-2 hover:bg-base-200 cursor-pointer flex items-center gap-2">
                                    <span class="font-mono font-semibold text-primary shrink-0" x-text="s.code"></span>
                                    <span class="text-base-content/60 truncate flex-1" x-text="s.title"></span>
                                    <span class="badge badge-xs shrink-0"
                                          :class="{
                                            'badge-success': s.status === 'approved',
                                            'badge-warning': s.status === 'draft',
                                            'badge-ghost': !['approved','draft'].includes(s.status),
                                          }"
                                          x-text="s.status"></span>
                                </div>
                            </template>
                        </div>
                        <p x-show="selectedSop" class="text-xs text-success mt-1 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span x-text="`Đã chọn: ${selectedSop?.label}`"></span>
                        </p>
                    </div>

                    {{-- Note --}}
                    <div class="mb-3">
                        <label class="text-xs font-medium text-base-content/60 mb-1 block">Ghi chú (tuỳ chọn)</label>
                        <input type="text" x-model="relForm.note"
                               class="input input-sm input-bordered w-full"
                               placeholder="Mô tả ngắn về mối quan hệ…"
                               maxlength="500">
                    </div>

                    <div x-show="addError" class="text-xs text-error mb-2" x-text="addError"></div>

                    <div class="flex justify-end gap-2">
                        <button type="button" @click="cancelAdd()" class="btn btn-ghost btn-sm">Huỷ</button>
                        <button type="button" @click="addRelation()" :disabled="saving"
                                class="btn btn-primary btn-sm gap-1.5">
                            <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                            Thêm
                        </button>
                    </div>
                </div>

                {{-- Empty state --}}
                <div x-show="relations.length === 0 && !adding"
                     class="flex flex-col items-center py-10 text-base-content/30">
                    <svg class="w-10 h-10 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.8" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/>
                    </svg>
                    <p class="text-sm">Chưa có quan hệ SOP nào</p>
                    <template x-if="canManage">
                        <button @click="adding = true"
                                class="mt-3 btn btn-sm btn-outline btn-primary gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Thêm quan hệ đầu tiên
                        </button>
                    </template>
                </div>

                {{-- Grouped list --}}
                <div x-show="relations.length > 0" class="space-y-4">
                    <template x-for="group in grouped" :key="group.type">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="badge badge-sm" :class="group.badgeClass" x-text="group.label"></span>
                                <span class="text-xs text-base-content/30" x-text="`${group.items.length} quan hệ`"></span>
                            </div>
                            <div class="space-y-1.5">
                                <template x-for="rel in group.items" :key="rel.uuid">
                                    <div class="flex items-center gap-3 p-3 rounded-lg border border-base-200 hover:bg-base-50 text-sm group">
                                        <a :href="`/dashboard/sop/${rel.related_sop?.uuid}`"
                                           class="font-mono font-semibold text-primary hover:underline shrink-0"
                                           x-text="rel.related_sop?.code"></a>
                                        <span class="text-base-content/70 truncate flex-1"
                                              x-text="rel.related_sop?.title"></span>
                                        <span x-show="rel.related_sop?.status"
                                              class="badge badge-xs shrink-0"
                                              :class="{
                                                'badge-success': rel.related_sop?.status === 'approved',
                                                'badge-warning': rel.related_sop?.status === 'draft',
                                                'badge-ghost': !['approved','draft'].includes(rel.related_sop?.status),
                                              }"
                                              x-text="rel.related_sop?.status"></span>
                                        <span x-show="rel.note"
                                              class="text-xs text-base-content/40 italic shrink-0 max-w-32 truncate"
                                              x-text="rel.note" :title="rel.note"></span>
                                        <button x-show="canManage"
                                                @click="deleteRelation(rel)"
                                                class="opacity-0 group-hover:opacity-100 btn btn-ghost btn-xs text-error/60 hover:text-error transition-opacity shrink-0 px-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

            </div>
        </div>
    </div>
    </div>

    {{-- ── Submit for Review Modal ────────────────────────────────────────── --}}
    <div x-show="submitModalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
         @keydown.escape.window="submitModalOpen = false">
        <div @click.outside="submitModalOpen = false"
             class="bg-base-100 rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
            <h3 class="font-semibold text-base mb-1">Gửi duyệt phiên bản mới</h3>
            <p class="text-sm text-base-content/50 mb-4">
                Mô tả thay đổi so với phiên bản trước (không bắt buộc).
            </p>
            <textarea x-model="submitSummary" rows="3"
                      class="textarea textarea-bordered w-full text-sm mb-4"
                      placeholder="Vd: Bổ sung bước kiểm tra chất lượng, sửa điều kiện quyết định tại bước 3…"
                      maxlength="1000"></textarea>
            <div x-show="submitError" class="text-error text-sm mb-3" x-text="submitError"></div>
            <div class="flex justify-end gap-2">
                <button @click="submitModalOpen = false" class="btn btn-ghost btn-sm">Huỷ</button>
                <button @click="submitForReview()" :disabled="submitLoading"
                        class="btn btn-success btn-sm gap-1.5">
                    <span x-show="submitLoading" class="loading loading-spinner loading-xs"></span>
                    Xác nhận gửi duyệt
                </button>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
     class="fixed bottom-5 right-5 z-50 alert alert-success shadow-lg max-w-sm py-3 text-sm">
    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
    </svg>
    <span>{{ session('success') }}</span>
</div>
@endif
@endsection

@push('scripts')
@vite(['Modules/Sop/resources/assets/sass/sop.scss', 'Modules/Sop/resources/assets/js/sop.js'], 'build/backend')
@endpush
