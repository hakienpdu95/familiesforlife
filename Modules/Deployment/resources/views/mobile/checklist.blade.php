@extends('layouts.mobile')

@section('title', $target->targetOrganization?->name ?? 'Checklist')
@section('subtitle', 'Phase: ' . ($phaseLabels[$target->current_phase] ?? $target->current_phase))
@section('back_url', route('deployment.targets.show', ['vertical' => $vertical->code(), 'target' => $target->id]))

@section('content')

@if(session('success'))
<div class="alert alert-success text-sm mb-4">{{ session('success') }}</div>
@endif

{{-- GPS capture card --}}
<div class="card bg-base-100 border border-base-200 shadow-sm mb-5"
     x-data="{
        lat: '',
        lng: '',
        accuracy: null,
        loading: false,
        error: '',
        capture() {
            if (!navigator.geolocation) { this.error = 'Thiết bị không hỗ trợ GPS.'; return; }
            this.loading = true;
            this.error = '';
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    this.lat      = pos.coords.latitude.toFixed(6);
                    this.lng      = pos.coords.longitude.toFixed(6);
                    this.accuracy = Math.round(pos.coords.accuracy);
                    this.loading  = false;
                },
                (err) => {
                    this.error   = 'Không lấy được vị trí: ' + err.message;
                    this.loading = false;
                },
                { enableHighAccuracy: true, timeout: 15000 }
            );
        }
     }">
    <div class="card-body p-4">
        <h3 class="font-semibold text-sm mb-3">Ghi nhận vị trí GPS</h3>

        <div class="grid grid-cols-2 gap-2 mb-3">
            <div class="form-control">
                <label class="label label-text text-xs pb-1">Latitude</label>
                <input type="text" x-model="lat" name="gps_lat" readonly
                       class="input input-bordered input-sm bg-base-200 font-mono text-xs"
                       placeholder="—">
            </div>
            <div class="form-control">
                <label class="label label-text text-xs pb-1">Longitude</label>
                <input type="text" x-model="lng" name="gps_lng" readonly
                       class="input input-bordered input-sm bg-base-200 font-mono text-xs"
                       placeholder="—">
            </div>
        </div>

        <template x-if="accuracy">
            <p class="text-xs text-base-content/50 mb-2">Độ chính xác: <span x-text="accuracy"></span>m</p>
        </template>
        <template x-if="error">
            <p class="text-xs text-error mb-2" x-text="error"></p>
        </template>

        <button type="button"
                x-on:click="capture()"
                :disabled="loading"
                class="btn btn-primary w-full">
            <span x-show="!loading">📍 Ghi nhận GPS</span>
            <span x-show="loading" class="loading loading-spinner loading-sm"></span>
        </button>

        <template x-if="lat && lng">
            <p class="text-xs text-success text-center mt-2">
                ✓ Đã ghi nhận: <span x-text="lat"></span>, <span x-text="lng"></span>
            </p>
        </template>
    </div>
</div>

{{-- Checklist by phase --}}
@foreach($checklist as $phase => $items)
<div class="mb-5">
    <div class="flex items-center gap-2 mb-2">
        <h3 class="font-semibold text-sm">{{ $phaseLabels[$phase] ?? $phase }}</h3>
        @php
            $done  = $items->where('is_done', true)->count();
            $total = $items->count();
            $pct   = $total > 0 ? round($done / $total * 100) : 0;
        @endphp
        <span class="badge badge-sm {{ $pct == 100 ? 'badge-success' : 'badge-ghost' }}">
            {{ $done }}/{{ $total }}
        </span>
        @if($phase === $target->current_phase)
        <span class="badge badge-primary badge-xs">Hiện tại</span>
        @endif
    </div>

    <div class="w-full bg-base-200 rounded-full h-1 mb-3 overflow-hidden">
        <div class="h-1 rounded-full bg-primary" style="width: {{ $pct }}%"></div>
    </div>

    <div class="space-y-2">
        @foreach($items as $item)
        @php
            $rowClasses = $item->is_done ? 'bg-success/10 border-success/30' : 'bg-base-100 border-base-200';
            $dotClasses = $item->is_done ? 'bg-success border-success text-white' : 'border-base-300';
        @endphp
        <div x-data="{ noteOpen: false, assignOpen: false }">
            @can('toggleChecklist', $target)
            <form method="POST"
                  action="{{ route('deployment.checklist.toggle', ['vertical' => $vertical->code(), 'item' => $item->id]) }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center gap-3 px-4 py-3 rounded-t-xl border transition-colors active:bg-base-200 {{ $rowClasses }}">

                    <span class="shrink-0 w-6 h-6 rounded-full border-2 flex items-center justify-center {{ $dotClasses }}">
                        @if($item->is_done)
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        @endif
                    </span>

                    <span class="flex-1 text-left text-sm {{ $item->is_done ? 'line-through text-base-content/50' : '' }}">
                        {{ $item->item_label }}
                        @if($item->is_required)
                        <span class="text-error text-xs ml-1">*</span>
                        @endif
                    </span>

                    @if($item->is_done && $item->done_at)
                    <span class="text-xs text-base-content/40 shrink-0">
                        {{ \Carbon\Carbon::parse($item->done_at)->format('H:i') }}
                    </span>
                    @endif
                </button>
            </form>
            @else
            <div class="w-full flex items-center gap-3 px-4 py-3 rounded-t-xl border opacity-60 {{ $rowClasses }}">
                <span class="shrink-0 w-6 h-6 rounded-full border-2 flex items-center justify-center {{ $dotClasses }}">
                    @if($item->is_done)
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    @endif
                </span>
                <span class="flex-1 text-left text-sm {{ $item->is_done ? 'line-through text-base-content/50' : '' }}">
                    {{ $item->item_label }}
                    @if($item->is_required)
                    <span class="text-error text-xs ml-1">*</span>
                    @endif
                </span>
                @if($item->is_done && $item->done_at)
                <span class="text-xs text-base-content/40 shrink-0">
                    {{ \Carbon\Carbon::parse($item->done_at)->format('H:i') }}
                </span>
                @endif
            </div>
            @endcan

            {{-- Thanh giao việc — ai được PM chỉ định phụ trách mục này --}}
            <button type="button" x-on:click="assignOpen = !assignOpen"
                    class="w-full flex items-center justify-center gap-1.5 py-1.5 text-xs text-base-content/50 border border-t-0 {{ $rowClasses }}">
                👤 {{ $item->assignedEmployee?->full_name ?? 'Chưa giao việc' }}
            </button>
            @can('update', $target)
            <div x-show="assignOpen" x-cloak class="px-3 py-2 bg-base-200/40">
                <form method="POST"
                      action="{{ route('deployment.checklist.assign', ['vertical' => $vertical->code(), 'item' => $item->id]) }}"
                      class="flex gap-2">
                    @csrf
                    @method('PATCH')
                    <select name="assigned_employee_id" class="select select-bordered select-sm flex-1">
                        <option value="">— Chưa giao —</option>
                        @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" @selected($item->assigned_employee_id == $emp->id)>
                            {{ $emp->full_name }} ({{ $emp->employee_code }})
                        </option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm shrink-0">Lưu</button>
                </form>
            </div>
            @endcan

            {{-- Thanh ghi chú — gõ tay nhật ký riêng cho mục này, xem lại lịch sử --}}
            <button type="button" x-on:click="noteOpen = !noteOpen"
                    class="w-full flex items-center justify-center gap-1.5 py-1.5 text-xs text-base-content/50 border border-t-0 rounded-b-xl {{ $rowClasses }}">
                🗒 Ghi chú
                @if($item->progressLogs->count() > 0)
                <span class="badge badge-ghost badge-xs">{{ $item->progressLogs->count() }}</span>
                @endif
            </button>

            <div x-show="noteOpen" x-cloak class="px-3 py-2 mb-2 bg-base-200/40 rounded-b-xl">
                @if($item->progressLogs->isNotEmpty())
                <ul class="space-y-1 mb-2 max-h-40 overflow-y-auto">
                    @foreach($item->progressLogs as $log)
                    <li class="text-xs">
                        <span class="text-base-content/40">{{ $log->logged_at?->format('d/m H:i') }}</span>
                        <span class="text-base-content/60 font-medium">{{ $log->loggedBy?->name }}:</span>
                        {{ $log->remark }}
                    </li>
                    @endforeach
                </ul>
                @else
                <p class="text-xs text-base-content/40 mb-2">Chưa có ghi chú nào.</p>
                @endif

                @can('toggleChecklist', $target)
                <form method="POST"
                      action="{{ route('deployment.checklist.note', ['vertical' => $vertical->code(), 'item' => $item->id]) }}"
                      class="flex gap-2">
                    @csrf
                    <input type="text" name="note" required maxlength="2000"
                           placeholder="Thêm ghi chú..."
                           class="input input-bordered input-sm flex-1">
                    <button type="submit" class="btn btn-primary btn-sm shrink-0">Lưu</button>
                </form>
                @endcan
            </div>
        </div>
        @endforeach
    </div>
</div>
@endforeach

@endsection
