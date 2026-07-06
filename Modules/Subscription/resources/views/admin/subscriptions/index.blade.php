@extends('layouts.backend')
@section('title', 'Quản lý Subscriptions')

@push('styles')
    @vite(['Modules/Subscription/resources/assets/sass/subscription.scss'], 'build/backend')
@endpush

@section('content')

{{-- Alpine wrapper: modal state + table --}}
<div x-data="{
    modalOpen: false,
    activeTab: 'assign',
    org: null,

    get hasSubscription() { return this.org?.status && this.org.status !== 'none'; },

    get assignUrl() {
        return this.org
            ? '{{ url('dashboard/subscription/admin/subscriptions') }}/' + this.org.id + '/assign'
            : '#';
    },
    get extendUrl() {
        return this.org
            ? '{{ url('dashboard/subscription/admin/subscriptions') }}/' + this.org.id + '/extend'
            : '#';
    },
    get overrideUrl() {
        return this.org
            ? '{{ url('dashboard/subscription/admin/subscriptions') }}/' + this.org.id + '/override'
            : '#';
    },

    get statusBadgeClass() {
        const m = { active: 'badge-success', trial: 'badge-info', expired: 'badge-error', canceled: 'badge-warning', none: 'badge-ghost' };
        return 'badge badge-sm ' + (m[this.org?.status] ?? 'badge-ghost');
    },
    get statusLabel() {
        const m = { active: 'Active', trial: 'Trial', expired: 'Expired', canceled: 'Canceled', none: 'Chưa có plan' };
        return m[this.org?.status] ?? '—';
    },

    openModal(data) {
        this.org = data;
        this.activeTab = 'assign';
        this.modalOpen = true;
        this.$nextTick(() => {
            window.dispatchEvent(new CustomEvent('subscription:modal-open', { detail: data }));
        });
    }
}" @keydown.escape.window="modalOpen = false" data-subscription-index>

{{-- Flash messages --}}
@foreach(['success', 'error', 'warning'] as $type)
    @if(session($type))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
         x-transition:leave="transition duration-500" x-transition:leave-end="opacity-0"
         class="alert alert-{{ $type }} py-3 px-4 mb-4 flex items-center gap-3 text-sm">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            @if($type === 'success')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            @elseif($type === 'error')
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            @else
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            @endif
        </svg>
        <span class="flex-1">{{ session($type) }}</span>
        <button @click="show = false" class="btn btn-ghost btn-xs">✕</button>
    </div>
    @endif
@endforeach

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-4 flex items-start gap-3 text-sm">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

{{-- Page header --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Subscriptions</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Quản lý gói dịch vụ của từng tổ chức</p>
    </div>
    <a href="{{ route('subscription.admin.plans.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
        </svg>
        Quản lý Plans
    </a>
</div>

{{-- Filter bar --}}
<form method="GET" class="card bg-base-100 shadow-sm border border-base-200 mb-4" data-filter-form>
    <div class="card-body py-3 px-4">
        <div class="flex flex-wrap gap-3 items-end">

            <div class="form-control flex-1 min-w-52">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium text-xs">Tìm kiếm tổ chức</span>
                </label>
                <input type="text" name="search" value="{{ $search }}"
                       class="input input-bordered input-sm w-full"
                       placeholder="Tên tổ chức...">
            </div>

            <div class="form-control w-48">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium text-xs">Plan</span>
                </label>
                <select id="ts-filter-plan" name="plan_id"
                        class="select select-bordered select-sm w-full ts-init"
                        data-ts-placeholder="Tất cả plans">
                    <option value="">Tất cả plans</option>
                    @foreach ($plans as $p)
                        <option value="{{ $p->id }}" {{ $planId == $p->id ? 'selected' : '' }}>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-control w-44">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium text-xs">Trạng thái</span>
                </label>
                <select id="ts-filter-status" name="status"
                        class="select select-bordered select-sm w-full ts-init"
                        data-ts-placeholder="Tất cả">
                    <option value="">Tất cả</option>
                    <option value="active"  {{ $status === 'active'  ? 'selected' : '' }}>Active</option>
                    <option value="trial"   {{ $status === 'trial'   ? 'selected' : '' }}>Trial</option>
                    <option value="expired" {{ $status === 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="no_plan" {{ $status === 'no_plan' ? 'selected' : '' }}>Chưa có plan</option>
                </select>
            </div>

            <div class="flex gap-2 self-end">
                <button type="submit" class="btn btn-primary btn-sm">Lọc</button>
                <a href="{{ route('subscription.admin.subscriptions.index') }}"
                   class="btn btn-ghost btn-sm">Xóa lọc</a>
            </div>
        </div>
    </div>
</form>

{{-- Table --}}
<div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="table table-sm w-full">
            <thead class="bg-base-200/50">
                <tr class="text-xs text-base-content/60">
                    <th>Tổ chức</th>
                    <th>Plan hiện tại</th>
                    <th>Trạng thái</th>
                    <th>Hết hạn</th>
                    <th class="text-right w-28">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orgs as $org)
                @php
                    $sub      = $org->planSubscriptions->first();
                    $daysLeft = $sub?->ends_at ? (int) now()->diffInDays($sub->ends_at, false) : null;

                    if (!$sub)                   $statusKey = 'none';
                    elseif ($sub->onTrial())      $statusKey = 'trial';
                    elseif ($sub->active())       $statusKey = 'active';
                    elseif ($sub->canceled())     $statusKey = 'canceled';
                    else                          $statusKey = 'expired';

                    $orgData = [
                        'id'              => $org->id,
                        'name'            => $org->name,
                        'currentPlanId'   => $sub?->plan_id,
                        'currentPlanName' => $sub?->plan?->name ?? '',
                        'status'          => $statusKey,
                        'startsAt'        => $sub?->starts_at?->format('Y-m-d') ?? '',
                        'endsAt'          => $sub?->ends_at?->format('Y-m-d') ?? '',
                        'daysLeft'        => $daysLeft,
                    ];
                @endphp
                <tr class="hover:bg-base-200/30">
                    <td>
                        <div class="font-medium text-sm">{{ $org->name }}</div>
                        <div class="text-xs text-base-content/40">{{ $org->slug }}</div>
                    </td>
                    <td>
                        @if ($sub?->plan)
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="badge badge-outline badge-sm">{{ $sub->plan->name }}</span>
                                @if ($sub->plan->tier)
                                    <span class="text-xs text-base-content/40">{{ Str::ucfirst($sub->plan->tier) }}</span>
                                @endif
                            </div>
                        @else
                            <span class="text-xs text-base-content/30 italic">Chưa có plan</span>
                        @endif
                    </td>
                    <td>
                        @switch($statusKey)
                            @case('active')
                                <span class="badge badge-success badge-xs">Active</span>
                                @break
                            @case('trial')
                                <span class="badge badge-info badge-xs">Trial</span>
                                @break
                            @case('canceled')
                                <span class="badge badge-warning badge-xs">Canceled</span>
                                @break
                            @case('expired')
                                <span class="badge badge-error badge-xs">Expired</span>
                                @break
                            @default
                                <span class="badge badge-ghost badge-xs">—</span>
                        @endswitch
                    </td>
                    <td>
                        @if ($sub?->ends_at)
                            @php
                                $isExpiring = $daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 7;
                                $isExpired  = $daysLeft !== null && $daysLeft < 0;
                            @endphp
                            <div class="text-xs {{ $isExpiring ? 'text-warning font-semibold' : ($isExpired ? 'text-error' : 'text-base-content/60') }}">
                                {{ $sub->ends_at->format('d/m/Y') }}
                            </div>
                            <div class="text-xs text-base-content/40 mt-0.5">
                                @if ($daysLeft === null) —
                                @elseif ($daysLeft < 0)   Hết hạn {{ abs($daysLeft) }} ngày trước
                                @elseif ($daysLeft === 0)  Hết hạn hôm nay
                                @elseif ($daysLeft <= 7)   Còn {{ $daysLeft }} ngày ⚠
                                @else                      Còn {{ $daysLeft }} ngày
                                @endif
                            </div>
                        @else
                            <span class="text-xs text-base-content/30">—</span>
                        @endif
                    </td>
                    <td class="text-right">
                        <button type="button"
                                class="btn btn-sm btn-ghost gap-1.5"
                                @click="openModal({{ Js::from($orgData) }})">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Quản lý
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-12 text-base-content/40 text-sm">
                        <svg class="w-10 h-10 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Không tìm thấy tổ chức nào.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($orgs->hasPages())
    <div class="px-5 py-3 border-t border-base-200">
        {{ $orgs->links() }}
    </div>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- MODAL: Quản lý Subscription                                           --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}
<div x-show="modalOpen" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     data-modal>

    {{-- Backdrop --}}
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="modalOpen = false"></div>

    {{-- Modal panel --}}
    <div class="relative w-full max-w-lg bg-base-100 rounded-2xl shadow-2xl overflow-hidden"
         @click.stop>

        {{-- Modal header --}}
        <div class="flex items-start justify-between gap-3 px-6 pt-5 pb-4 border-b border-base-200">
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h3 class="font-bold text-base text-base-content truncate" x-text="org?.name ?? '—'"></h3>
                    <span :class="statusBadgeClass" x-text="statusLabel"></span>
                </div>
                <p class="text-xs text-base-content/40 mt-0.5">
                    <span x-show="org?.currentPlanName">
                        Plan: <span class="font-medium text-base-content/70" x-text="org?.currentPlanName"></span>
                    </span>
                    <span x-show="!org?.currentPlanName" class="italic">Chưa có plan</span>
                    <span x-show="org?.endsAt" class="ml-2">
                        · Hết hạn: <span x-text="org?.endsAt ? new Date(org.endsAt).toLocaleDateString('vi-VN') : ''"></span>
                    </span>
                </p>
            </div>
            <button @click="modalOpen = false"
                    class="btn btn-ghost btn-sm btn-square shrink-0 -mt-0.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Tab navigation --}}
        <div class="border-b border-base-200 px-6">
            <nav class="flex -mb-px" role="tablist">

                <button type="button" role="tab" :aria-selected="activeTab === 'assign'"
                        @click="activeTab = 'assign'"
                        class="flex items-center gap-1.5 px-1 py-3.5 mr-5 text-sm font-medium border-b-2 transition-colors"
                        :class="activeTab === 'assign'
                            ? 'border-primary text-primary'
                            : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    Gán plan
                </button>

                <button type="button" role="tab" :aria-selected="activeTab === 'extend'"
                        @click="activeTab = 'extend'"
                        class="flex items-center gap-1.5 px-1 py-3.5 mr-5 text-sm font-medium border-b-2 transition-colors"
                        :class="activeTab === 'extend'
                            ? 'border-primary text-primary'
                            : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Gia hạn
                </button>

                <button type="button" role="tab" :aria-selected="activeTab === 'override'"
                        @click="activeTab = 'override'"
                        class="flex items-center gap-1.5 px-1 py-3.5 text-sm font-medium border-b-2 transition-colors"
                        :class="activeTab === 'override'
                            ? 'border-primary text-primary'
                            : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                    </svg>
                    Override
                </button>

            </nav>
        </div>

        {{-- ── TAB: Gán plan ───────────────────────────────────────────── --}}
        <div x-show="activeTab === 'assign'" class="p-6 space-y-4" data-modal-assign>
            <p class="text-xs text-base-content/50">
                Chọn plan mới để gán. Nếu tổ chức đang có plan active, plan cũ sẽ bị hủy và plan mới có hiệu lực ngay.
            </p>

            <form :action="assignUrl" method="POST" novalidate data-assign-form>
                @csrf
                <div class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Plan <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-assign-plan" name="plan_id"
                                class="select select-bordered select-sm w-full"
                                data-ts-placeholder="— Chọn plan —"
                                data-req="Vui lòng chọn plan">
                            <option value="">— Chọn plan —</option>
                            @foreach ($plans as $p)
                                <option value="{{ $p->id }}"
                                        data-tier="{{ $p->tier }}"
                                        data-price="{{ number_format($p->price, 0, ',', '.') }}">
                                    {{ $p->name }}
                                    @if($p->price > 0)
                                        — {{ number_format($p->price, 0, ',', '.') }} VND/tháng
                                    @else
                                        — Miễn phí
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-base-content/40">Chỉ hiển thị plans đang kích hoạt</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ngày bắt đầu</span>
                                <span class="label-text-alt text-xs text-base-content/40">Mặc định: hôm nay</span>
                            </label>
                            <input type="text" name="start_date" id="fp-assign-start"
                                   class="input input-bordered input-sm w-full fp-init"
                                   placeholder="DD/MM/YYYY">
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ngày kết thúc</span>
                                <span class="label-text-alt text-xs text-base-content/40">Để trống = tự tính</span>
                            </label>
                            <input type="text" name="end_date" id="fp-assign-end"
                                   class="input input-bordered input-sm w-full fp-init"
                                   placeholder="DD/MM/YYYY">
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Ghi chú</span>
                            <span class="label-text-alt text-xs text-base-content/40">Tùy chọn</span>
                        </label>
                        <input type="text" name="reason"
                               class="input input-bordered input-sm w-full"
                               placeholder="VD: Nâng cấp theo yêu cầu tháng 6">
                    </div>

                    <div class="flex gap-2 pt-1">
                        <button type="button" @click="modalOpen = false"
                                class="btn btn-ghost btn-sm flex-1">Hủy</button>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Gán plan
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- ── TAB: Gia hạn ────────────────────────────────────────────── --}}
        <div x-show="activeTab === 'extend'" class="p-6" data-modal-extend>

            {{-- Chưa có subscription --}}
            <div x-show="!hasSubscription" class="py-8 text-center">
                <svg class="w-10 h-10 mx-auto mb-3 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <p class="text-sm text-base-content/50">Tổ chức này chưa có subscription.</p>
                <p class="text-xs text-base-content/30 mt-1">Vui lòng gán plan trước ở tab <strong>Gán plan</strong>.</p>
                <button type="button" @click="activeTab = 'assign'"
                        class="btn btn-primary btn-sm mt-4 gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    Sang tab Gán plan
                </button>
            </div>

            {{-- Có subscription --}}
            <div x-show="hasSubscription">
                <p class="text-xs text-base-content/50 mb-4">
                    Điều chỉnh ngày hết hạn mà không thay đổi plan. Dùng để gia hạn thủ công hoặc chỉnh sửa ngày kết thúc.
                </p>

                <form :action="extendUrl" method="POST" novalidate data-extend-form class="space-y-4">
                    @csrf

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Ngày hết hạn mới <span class="text-error">*</span></span>
                            <span class="label-text-alt text-xs text-base-content/40">
                                Hiện tại: <span x-text="org?.endsAt ? new Date(org.endsAt).toLocaleDateString('vi-VN') : '—'"></span>
                            </span>
                        </label>
                        <input type="text" name="ends_at" id="fp-extend-ends"
                               class="input input-bordered input-sm w-full fp-init"
                               placeholder="DD/MM/YYYY"
                               data-req="Vui lòng chọn ngày hết hạn">
                        <p class="mt-1 text-xs text-base-content/40">Ngày subscription thực sự hết hiệu lực</p>
                    </div>

                    {{-- Quick extend shortcuts --}}
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium text-xs">Gia hạn nhanh</span>
                        </label>
                        <div class="flex flex-wrap gap-1.5"
                             x-data="{
                                 setExtend(days) {
                                     const base = this.org?.endsAt && new Date(this.org.endsAt) > new Date()
                                         ? new Date(this.org.endsAt)
                                         : new Date();
                                     base.setDate(base.getDate() + days);
                                     const el = document.getElementById('fp-extend-ends');
                                     if (el?._flatpickr) el._flatpickr.setDate(base, true);
                                 }
                             }">
                            <button type="button" class="btn btn-xs btn-outline" @click="setExtend(30)">+30 ngày</button>
                            <button type="button" class="btn btn-xs btn-outline" @click="setExtend(90)">+3 tháng</button>
                            <button type="button" class="btn btn-xs btn-outline" @click="setExtend(180)">+6 tháng</button>
                            <button type="button" class="btn btn-xs btn-outline" @click="setExtend(365)">+1 năm</button>
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Ghi chú</span>
                            <span class="label-text-alt text-xs text-base-content/40">Tùy chọn</span>
                        </label>
                        <input type="text" name="reason"
                               class="input input-bordered input-sm w-full"
                               placeholder="VD: Gia hạn theo hóa đơn tháng 7">
                    </div>

                    <div class="flex gap-2 pt-1">
                        <button type="button" @click="modalOpen = false"
                                class="btn btn-ghost btn-sm flex-1">Hủy</button>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Lưu gia hạn
                        </button>
                    </div>
                </form>
            </div>

        </div>

        {{-- ── TAB: Override tính năng ─────────────────────────────────── --}}
        <div x-show="activeTab === 'override'" class="p-6 space-y-4" data-modal-override>
            <p class="text-xs text-base-content/50">
                Ghi đè một tính năng cụ thể vượt ngoài giới hạn plan. Override có thể đặt hạn thời gian để tự hết hiệu lực.
            </p>

            <form :action="overrideUrl" method="POST" novalidate data-override-form class="space-y-4">
                @csrf

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Tính năng <span class="text-error">*</span></span>
                    </label>
                    <select id="ts-override-feature" name="feature_slug"
                            class="select select-bordered select-sm w-full"
                            data-ts-placeholder="— Chọn feature slug —"
                            data-req="Vui lòng chọn tính năng">
                        <option value="">— Chọn feature slug —</option>
                        @foreach ($featureNames as $slug => $fname)
                            <option value="{{ $slug }}">{{ $fname }} — {{ $slug }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Giá trị override <span class="text-error">*</span></span>
                        <span class="label-text-alt text-xs text-base-content/40">Bool: 1/0 · Limit: số · Quota: số</span>
                    </label>
                    <input type="text" name="value"
                           class="input input-bordered input-sm w-full font-mono"
                           placeholder="VD: 1, 0, 500, 100"
                           data-req="Vui lòng nhập giá trị">
                    <p class="mt-1 text-xs text-base-content/40">
                        module/flag: <code class="bg-base-200 px-1 rounded">1</code> = bật,
                        <code class="bg-base-200 px-1 rounded">0</code> = tắt ·
                        limit/quota: số nguyên (0 = vô hạn)
                    </p>
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Hết hạn override</span>
                        <span class="label-text-alt text-xs text-base-content/40">Để trống = vĩnh viễn</span>
                    </label>
                    <input type="text" name="expires_at" id="fp-override-expires"
                           class="input input-bordered input-sm w-full fp-init"
                           placeholder="DD/MM/YYYY">
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Lý do override</span>
                        <span class="label-text-alt text-xs text-base-content/40">Tùy chọn</span>
                    </label>
                    <input type="text" name="override_reason"
                           class="input input-bordered input-sm w-full"
                           placeholder="VD: Khuyến mãi Q3, thỏa thuận riêng">
                </div>

                <div class="flex gap-2 pt-1">
                    <button type="button" @click="modalOpen = false"
                            class="btn btn-ghost btn-sm flex-1">Hủy</button>
                    <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Lưu override
                    </button>
                </div>
            </form>
        </div>

    </div>{{-- /modal panel --}}
</div>{{-- /modal backdrop --}}

</div>{{-- /x-data wrapper --}}

@endsection

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/flatpickr.js',
        'Modules/Subscription/resources/assets/js/subscription.js',
    ], 'build/backend')
@endpush
