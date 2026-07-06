@extends('layouts.backend')
@section('title', 'Sơ đồ: ' . $orgChartConfig->name)


@section('content')
<div x-data="orgChartView({{ Js::from([
    'treeData'   => $treeData,
    'treeApiUrl' => route('backend.api.org-charts.tree', $orgChartConfig),
]) }})" x-init="init()">

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-base-content">{{ $orgChartConfig->name }}</h1>
                @if($orgChartConfig->is_default)
                <span class="badge badge-primary badge-sm">Mặc định</span>
                @endif
            </div>
            <p class="text-sm text-base-content/50 mt-0.5">
                {{ $orgChartConfig->view_type->label() }} · Nhóm theo {{ $orgChartConfig->group_by->label() }}
                @if($orgChartConfig->scopeBranch)
                · Chi nhánh: {{ $orgChartConfig->scopeBranch->name }}
                @endif
                · <span id="occ-total">{{ $treeData['total'] }}</span> nhân viên
            </p>
        </div>
        <div class="flex items-center gap-2">

            {{-- Switch config --}}
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-ghost btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                    </svg>
                    Đổi view
                </label>
                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box shadow-lg border border-base-200 w-64 z-50 p-2">
                    @foreach($configs as $cfg)
                    <li>
                        <a href="{{ route('backend.org-charts.show', $cfg) }}"
                           class="{{ $cfg->id === $orgChartConfig->id ? 'active' : '' }}">
                            @if($cfg->is_default)<span class="badge badge-primary badge-xs">★</span>@endif
                            {{ $cfg->name }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>

            <button onclick="occExpandAll()" class="btn btn-ghost btn-sm" title="Mở rộng tất cả">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
            </button>
            <button onclick="occCollapseAll()" class="btn btn-ghost btn-sm" title="Thu gọn tất cả">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5l5.25 5.25"/>
                </svg>
            </button>
            <button :class="loading ? 'loading' : ''" @click="reload()" class="btn btn-ghost btn-sm" title="Tải lại">
                <svg x-show="!loading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>

            @can('update', $orgChartConfig)
            <a href="{{ route('backend.org-charts.edit', $orgChartConfig) }}" class="btn btn-ghost btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Chỉnh sửa
            </a>
            @endcan

        </div>
    </div>

    {{-- ── Loading state ────────────────────────────────────────────────── --}}
    <div x-show="loading" class="flex items-center justify-center py-20">
        <span class="loading loading-spinner loading-lg text-primary"></span>
    </div>

    {{-- ── Error state ──────────────────────────────────────────────────── --}}
    <div x-show="error" x-cloak class="alert alert-error mb-4">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <span x-text="error"></span>
    </div>

    {{-- ── Chart container (rendered by JS) ──────────────────────────────── --}}
    <div x-show="!loading" class="org-chart-wrapper">
        <div class="org-chart-scroll">
            <div id="occ-tree-root" class="org-chart-root"></div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
/* ── Org Chart Layout ─────────────────────────────────────────────── */
.org-chart-wrapper {
    background: oklch(var(--b1));
    border: 1px solid oklch(var(--b2));
    border-radius: 1rem;
    overflow: hidden;
}
.org-chart-scroll {
    overflow-x: auto;
    overflow-y: auto;
    max-height: calc(100vh - 200px);
    padding: 2.5rem 3rem;
}
.org-chart-root {
    display: flex;
    flex-direction: row;
    gap: 2rem;
    align-items: flex-start;
    justify-content: flex-start;
    min-width: max-content;
}
/* ── Node wrap ────────────────────────────────────────────────────── */
.occ-node-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
}
/* ── Card ─────────────────────────────────────────────────────────── */
.occ-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: .625rem;
    padding: .625rem .875rem;
    background: oklch(var(--b1));
    border: 1.5px solid oklch(var(--b3));
    border-radius: .75rem;
    min-width: 180px;
    max-width: 230px;
    box-shadow: 0 1px 3px rgba(0,0,0,.07);
    transition: border-color .15s, box-shadow .15s;
    user-select: none;
}
.occ-card:hover {
    border-color: oklch(var(--p)/.5);
    box-shadow: 0 2px 8px oklch(var(--p)/.12);
}
.occ-card.is-root {
    border-color: oklch(var(--p)/.5);
    background: oklch(var(--p)/.05);
}
.occ-card.has-more {
    border-style: dashed;
    opacity: .7;
}
/* ── Avatar ───────────────────────────────────────────────────────── */
.occ-avatar {
    flex-shrink: 0;
    width: 2.25rem; height: 2.25rem;
    border-radius: 9999px;
    display: flex; align-items: center; justify-content: center;
    overflow: hidden;
    background: oklch(var(--p)/.15);
    color: oklch(var(--p));
    font-size: .875rem; font-weight: 700;
    border: 2px solid oklch(var(--p)/.25);
}
.occ-avatar img { width: 100%; height: 100%; object-fit: cover; }
/* ── Info ─────────────────────────────────────────────────────────── */
.occ-info { flex: 1; min-width: 0; }
.occ-name  { font-size: .8125rem; font-weight: 600; color: oklch(var(--bc)); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.occ-code  { font-size: .6875rem; font-family: monospace; color: oklch(var(--bc)/.4); margin-top: .0625rem; }
.occ-title { font-size: .6875rem; color: oklch(var(--p)); margin-top: .125rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.occ-dept  { font-size: .6875rem; color: oklch(var(--bc)/.5); margin-top: .0625rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.occ-branch{ font-size: .6875rem; color: oklch(var(--bc)/.35); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
/* ── Toggle button ────────────────────────────────────────────────── */
.occ-toggle {
    flex-shrink: 0;
    display: flex; align-items: center; gap: .25rem;
    padding: .25rem .375rem;
    border-radius: .375rem;
    background: oklch(var(--b2));
    color: oklch(var(--bc)/.6);
    font-size: .6875rem;
    border: none; cursor: pointer;
    transition: background .15s, color .15s;
    line-height: 1;
}
.occ-toggle:hover { background: oklch(var(--b3)); color: oklch(var(--bc)); }
.occ-toggle svg   { transition: transform .2s; }
.occ-toggle.open svg { transform: rotate(90deg); }
/* ── Has-more dot ─────────────────────────────────────────────────── */
.occ-more {
    position: absolute; bottom: -.5rem; left: 50%; transform: translateX(-50%);
    background: oklch(var(--b3)); color: oklch(var(--bc)/.5);
    font-size: .625rem; padding: .0625rem .375rem; border-radius: 9999px;
    white-space: nowrap;
}
/* ── Connector lines ──────────────────────────────────────────────── */
.occ-children-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 1.25rem;
    position: relative;
}
.occ-children-wrap::before {
    content: '';
    position: absolute; top: 0; left: 50%;
    width: 2px; height: 1.25rem;
    background: oklch(var(--b3));
}
.occ-children {
    display: flex;
    flex-direction: row;
    gap: 1.5rem;
    align-items: flex-start;
    position: relative;
    padding-top: 1.25rem;
}
.occ-children::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0;
    height: 2px; background: oklch(var(--b3));
}
.occ-node-wrap > .occ-children-wrap > .occ-children > .occ-node-wrap::before {
    content: '';
    position: absolute; top: -1.25rem; left: 50%;
    width: 2px; height: 1.25rem;
    background: oklch(var(--b3));
}
</style>
@endpush

@push('scripts')
    @vite(['Modules/OrgChart/resources/assets/js/org-chart.js'], 'build/backend')
@endpush
