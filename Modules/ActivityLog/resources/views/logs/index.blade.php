@extends('layouts.backend')
@section('title', 'Activity Log')


@section('content')
<div x-data="activityLogIndex">

    {{-- ── Page header ──────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Activity Log</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Lịch sử hoạt động hệ thống</p>
        </div>
        <div class="flex items-center gap-2">
            {{-- Summary badges --}}
            <template x-if="stats">
                <div class="flex items-center gap-2">
                    <span class="badge badge-ghost gap-1">
                        Hôm nay: <b x-text="stats.total_today"></b>
                    </span>
                    <span class="badge badge-warning gap-1" x-show="stats.warnings > 0">
                        Warning: <b x-text="stats.warnings"></b>
                    </span>
                    <span class="badge badge-error gap-1" x-show="stats.error_today > 0">
                        Error: <b x-text="stats.error_today"></b>
                    </span>
                    <span class="badge badge-error gap-1" x-show="stats.critical_today > 0"
                          style="background:#7f1d1d;color:#fff">
                        Critical: <b x-text="stats.critical_today"></b>
                    </span>
                </div>
            </template>
            <button @click="doExport()" class="btn btn-outline btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Xuất Excel
            </button>
        </div>
    </div>

    {{-- ── Filter bar ───────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3 px-4">
            <div class="flex flex-wrap gap-3 items-end">

                <div class="form-control min-w-32">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Từ ngày</span>
                    </label>
                    <input type="date" class="input input-sm input-bordered"
                           x-model="f.date_from" @change="reload()">
                </div>
                <div class="form-control min-w-32">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Đến ngày</span>
                    </label>
                    <input type="date" class="input input-sm input-bordered"
                           x-model="f.date_to" @change="reload()">
                </div>

                <div class="form-control self-end">
                    <button @click="reset()" class="btn btn-ghost btn-sm">Xóa lọc</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tabulator table ──────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-hidden rounded-2xl tabulator-daisy">
            <div id="actlog-table"></div>
        </div>
    </div>

</div>
@endsection

@push('styles')
    <x-tabulator-theme />
    @vite(['Modules/ActivityLog/resources/assets/sass/activity-log.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'Modules/ActivityLog/resources/assets/js/activity-log.js',
    ], 'build/backend')
@endpush
