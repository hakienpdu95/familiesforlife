{{-- resources/views/notifications/preferences.blade.php --}}
@extends('layouts.backend')

@section('title', 'Cài đặt thông báo')

@section('content')

{{-- Header --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content leading-tight">Cài đặt thông báo</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Tuỳ chỉnh kênh nhận thông báo cho từng loại sự kiện</p>
    </div>
    <a href="{{ route('backend.notifications.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại thông báo
    </a>
</div>

{{-- Channel legend --}}
<div class="flex flex-wrap items-center gap-4 mb-5 text-sm text-base-content/60">
    <div class="flex items-center gap-1.5">
        <span class="w-3 h-3 rounded-full bg-primary inline-block"></span>
        <span>Trong app</span>
    </div>
    <div class="flex items-center gap-1.5">
        <span class="w-3 h-3 rounded-full bg-secondary inline-block"></span>
        <span>Email</span>
    </div>
    <div class="flex items-center gap-1.5">
        <span class="w-3 h-3 rounded-full {{ $pushAvailable ? 'bg-accent' : 'bg-base-300' }} inline-block"></span>
        <span>Push{{ !$pushAvailable ? ' (chưa cấu hình)' : '' }}</span>
    </div>
</div>

{{-- Groups --}}
@foreach($groups as $groupLabel => $types)
<div class="card bg-base-100 border border-base-200 shadow-sm overflow-hidden mb-4">
    {{-- Group header --}}
    <div class="px-4 py-2.5 bg-base-200/50 border-b border-base-200">
        <span class="text-xs font-semibold uppercase tracking-wider text-base-content/50">{{ $groupLabel }}</span>
    </div>

    {{-- Column headers --}}
    <div class="pref-row pref-header">
        <span class="pref-label text-xs font-medium text-base-content/40 uppercase tracking-wide">Loại thông báo</span>
        <span class="pref-ch text-xs font-medium text-base-content/40 text-center uppercase tracking-wide">Trong app</span>
        <span class="pref-ch text-xs font-medium text-base-content/40 text-center uppercase tracking-wide">Email</span>
        <span class="pref-ch text-xs font-medium text-base-content/40 text-center uppercase tracking-wide">Push</span>
        <span class="pref-status"></span>
    </div>

    @foreach($types as $eventType => $label)
    @php
        $pref = $saved[$eventType] ?? [];
        $db   = $pref['channel_db']   ?? true;
        $mail = $pref['channel_mail'] ?? true;   // default on when no preference saved
        $push = $pref['channel_push'] ?? false;
    @endphp
    <div class="pref-row"
         x-data="{
            db:   @js($db),
            mail: @js($mail),
            push: @js($push),
            saving: false,
            ok: false,
            async save() {
                this.saving = true;
                try {
                    await fetch('/api/notifications/preferences/{{ $eventType }}', {
                        method: 'PATCH',
                        headers: {
                            'Content-Type':  'application/json',
                            'X-CSRF-TOKEN':  document.querySelector('meta[name=csrf-token]')?.content ?? '',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({ channel_db: this.db, channel_mail: this.mail, channel_push: this.push }),
                    });
                    this.ok = true;
                    setTimeout(() => this.ok = false, 1800);
                } catch {}
                this.saving = false;
            }
         }">

        <span class="pref-label text-sm text-base-content">{{ $label }}</span>

        {{-- In-app toggle --}}
        <label class="pref-ch flex justify-center">
            <input type="checkbox" class="toggle toggle-primary toggle-sm"
                   x-model="db" @change="save()">
        </label>

        {{-- Email toggle --}}
        <label class="pref-ch flex justify-center">
            <input type="checkbox" class="toggle toggle-secondary toggle-sm"
                   x-model="mail" @change="save()">
        </label>

        {{-- Push toggle --}}
        <label class="pref-ch flex justify-center">
            <input type="checkbox" class="toggle toggle-accent toggle-sm"
                   x-model="push" @change="save()"
                   :disabled="{{ $pushAvailable ? 'false' : 'true' }}"
                   title="{{ $pushAvailable ? '' : 'Chưa cấu hình VAPID keys' }}">
        </label>

        {{-- Status --}}
        <span class="pref-status flex justify-center items-center w-6">
            <span x-show="saving" class="notif-spinner" style="width:12px;height:12px;border-width:2px;"></span>
            <svg x-show="ok && !saving" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                 stroke-width="2.5" class="w-3.5 h-3.5 text-success">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </span>
    </div>
    @endforeach
</div>
@endforeach

<p class="text-xs text-base-content/35 mt-2 text-center">
    Thay đổi được lưu tự động. Cài đặt áp dụng riêng cho từng tài khoản.
</p>

@endsection

@push('styles')
<style>
.pref-row {
    display: grid;
    grid-template-columns: 1fr 80px 80px 80px 32px;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-bottom: 1px solid oklch(var(--b2));
    transition: background .1s;
}
.pref-row:last-child { border-bottom: none; }
.pref-row:not(.pref-header):hover { background: oklch(var(--b2) / .4); }
.pref-header { padding: 6px 16px; background: transparent; }
.pref-label { min-width: 0; }
.pref-ch { flex-shrink: 0; }

/* Reuse spinner from notification center */
.notif-spinner {
    display: inline-block;
    border-radius: 9999px;
    border: 2px solid oklch(var(--bc) / .15);
    border-top-color: oklch(var(--p));
    animation: spin .6s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
@endpush
