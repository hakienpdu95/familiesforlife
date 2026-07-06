{{-- resources/views/notifications/index.blade.php --}}
@extends('layouts.backend')

@section('title', 'Trung tâm thông báo')

@section('content')

{{-- Page header --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div>
        <h1 class="text-2xl font-bold text-base-content leading-tight">Thông báo</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            @if($unreadCount > 0)
                Bạn có <strong class="text-base-content/80">{{ $unreadCount }}</strong> thông báo chưa đọc
            @else
                Tất cả thông báo đã được đọc
            @endif
        </p>
    </div>
    <div class="flex items-center gap-2">
        @if($unreadCount > 0)
        <form method="POST" action="{{ route('backend.notifications.read-all') }}">
            @csrf
            <button type="submit" class="btn btn-outline btn-sm gap-1.5">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Đọc tất cả
            </button>
        </form>
        @endif
        <a href="{{ route('backend.notifications.preferences') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Cài đặt
        </a>
    </div>
</div>

{{-- Browser Push toggle --}}
@if(config('webpush.vapid.public_key'))
<div x-data="pushToggle()"
     class="flex items-center justify-between gap-3 bg-base-100 border border-base-200 rounded-xl px-4 py-3 mb-4 shadow-sm">
    <div class="flex items-center gap-3 min-w-0">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" class="w-5 h-5 text-base-content/40 shrink-0">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-base-content">Thông báo trình duyệt</p>
            <p class="text-xs text-base-content/50 truncate"
               x-text="error || (subscribed ? 'Đang bật — nhận thông báo kể cả khi không mở tab.' : 'Tắt — bật để nhận thông báo ngay cả khi không mở tab.')"></p>
        </div>
    </div>
    <button @click="toggle()"
            :disabled="!supported || permission === 'denied' || loading"
            class="btn btn-sm shrink-0"
            :class="subscribed ? 'btn-ghost' : 'btn-primary'">
        <span x-show="loading" class="notif-spinner" style="width:14px;height:14px;border-width:2px"></span>
        <span x-show="!loading" x-text="subscribed ? 'Tắt' : 'Bật'"></span>
    </button>
</div>
@endif

{{-- Flash messages --}}
@if(session('success'))
<div class="alert alert-success mb-4 text-sm">{{ session('success') }}</div>
@endif

{{-- Filter tabs --}}
<div class="nc-tabs">
    <a href="{{ route('backend.notifications.index') }}"
       class="nc-tab {{ !request('filter') ? 'nc-tab--active' : '' }}">
        Tất cả
        <span class="nc-tab-badge">{{ $notifications->total() }}</span>
    </a>
    <a href="{{ route('backend.notifications.index', ['filter' => 'unread']) }}"
       class="nc-tab {{ request('filter') === 'unread' ? 'nc-tab--active' : '' }}">
        Chưa đọc
        @if($unreadCount > 0)
        <span class="nc-tab-badge nc-tab-badge--unread">{{ $unreadCount }}</span>
        @endif
    </a>
</div>

{{-- Notification list --}}
<div class="card bg-base-100 border border-base-200 shadow-sm overflow-hidden">
    @forelse($notifications as $n)
    @php
        $data     = $n->data;
        $type     = $data['type']     ?? 'unknown';
        $title    = $data['title']    ?? $data['message'] ?? '(Thông báo)';
        $body     = $data['body']     ?? $data['message'] ?? '';
        $url      = $data['url']      ?? '';
        $icon     = $data['icon']     ?? 'bell';
        $severity = $data['severity'] ?? 'info';
        $isRead   = $n->read_at !== null;
        $uuid     = $n->uuid ?? $n->id;
    @endphp
    <div class="nc-item {{ $isRead ? '' : 'nc-item--unread' }}">

        {{-- Icon --}}
        <div class="notif-icon notif-icon--{{ $icon }}">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                @switch($icon)
                    @case('check') @case('success')
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        @break
                    @case('warning')
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        @break
                    @case('error')
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        @break
                    @case('sop')
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        @break
                    @case('kc')
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        @break
                    @case('task')
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        @break
                    @case('user')
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        @break
                    @case('info')
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        @break
                    @default
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                @endswitch
            </svg>
        </div>

        {{-- Content --}}
        <div class="nc-body">
            <div class="nc-top">
                <p class="nc-title {{ $isRead ? 'nc-title--read' : '' }}">{{ $title }}</p>
                @if(!$isRead)
                <span class="notif-dot" style="flex-shrink:0;margin-top:6px;"></span>
                @endif
            </div>
            @if($body)
            <p class="nc-desc">{{ $body }}</p>
            @endif
            <div class="nc-meta">
                <span>{{ $n->created_at->diffForHumans() }}</span>
                <span class="opacity-30">·</span>
                <span class="nc-type">{{ $type }}</span>
            </div>
        </div>

        {{-- Actions --}}
        <div class="nc-actions">
            @if($url)
            <a href="{{ $url }}" class="notif-act-link" title="Xem chi tiết">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
            @endif
            @if(!$isRead)
            <form method="POST" action="{{ route('backend.notifications.mark-read', $uuid) }}" style="display:inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="notif-act-btn" title="Đánh dấu đã đọc">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                </button>
            </form>
            @endif
            <form method="POST" action="{{ route('backend.notifications.destroy', $uuid) }}" style="display:inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="notif-act-btn nc-delete-btn" title="Xoá"
                        onclick="return confirm('Xoá thông báo này?')">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
    @empty
    <div class="nc-empty">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2" class="w-12 h-12 opacity-20">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <p class="text-base-content/40 text-sm">Không có thông báo nào</p>
    </div>
    @endforelse
</div>

{{-- Pagination --}}
@if($notifications->hasPages())
<div class="mt-4">
    {{ $notifications->links() }}
</div>
@endif

@endsection

@push('styles')
<style>
/* ── Filter tabs ──────────────────────────────────────────────────── */
.nc-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 16px;
    border-bottom: 1px solid oklch(var(--b2));
}
.nc-tab {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    font-size: 14px;
    font-weight: 500;
    color: oklch(var(--bc) / .5);
    border-bottom: 2px solid transparent;
    text-decoration: none;
    margin-bottom: -1px;
    transition: color .15s, border-color .15s;
}
.nc-tab:hover { color: oklch(var(--bc)); }
.nc-tab--active { color: oklch(var(--p)); border-bottom-color: oklch(var(--p)); }

.nc-tab-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    padding: 0 5px;
    height: 18px;
    font-size: 11px;
    font-weight: 600;
    background: oklch(var(--b2));
    color: oklch(var(--bc) / .6);
    border-radius: 9999px;
}
.nc-tab-badge--unread {
    background: oklch(var(--p));
    color: oklch(var(--pc));
}

/* ── List items ───────────────────────────────────────────────────── */
.nc-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 14px 18px;
    border-bottom: 1px solid oklch(var(--b2));
    transition: background .12s;
}
.nc-item:last-child { border-bottom: none; }
.nc-item:hover { background: oklch(var(--b2) / .5); }
.nc-item--unread { background: oklch(var(--p) / .04); }
.nc-item--unread:hover { background: oklch(var(--p) / .08); }

.nc-body {
    flex: 1;
    min-width: 0;
}
.nc-top {
    display: flex;
    align-items: flex-start;
    gap: 8px;
}
.nc-title {
    font-size: 14px;
    font-weight: 600;
    color: oklch(var(--bc));
    margin: 0 0 2px;
    flex: 1;
    line-height: 1.4;
}
.nc-title--read {
    font-weight: 400;
    color: oklch(var(--bc) / .45);
}
.nc-desc {
    font-size: 13px;
    color: oklch(var(--bc) / .55);
    margin: 0 0 4px;
    line-height: 1.5;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}
.nc-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: oklch(var(--bc) / .35);
}
.nc-type {
    background: oklch(var(--b2));
    padding: 1px 6px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 11px;
}

.nc-actions {
    display: flex;
    align-items: center;
    gap: 4px;
    flex-shrink: 0;
}
.nc-delete-btn { color: #ef4444; }
.nc-delete-btn:hover { background: #fef2f2; color: #dc2626; }

.nc-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 48px 20px;
}
</style>
@endpush
