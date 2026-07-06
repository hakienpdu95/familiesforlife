@extends('layouts.backend')
@section('title', 'Tiến độ công việc')

@section('breadcrumb')
<nav class="breadcrumb-nav">
    <a href="{{ route('report.index') }}" class="breadcrumb-item">Báo cáo</a>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-item">Dự án</span>
    <span class="breadcrumb-sep">/</span>
    <span class="breadcrumb-item">Tiến độ</span>
</nav>
@endsection

@section('content')
<div
    x-data="reportProjectTasks"
    class="space-y-5"
>

    {{-- ── Page header ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Tiến độ công việc</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Theo dõi tiến độ task theo dự án, mức độ ưu tiên và velocity tuần</p>
        </div>
        <button @click="load()" :disabled="loading"
                class="btn btn-ghost btn-sm gap-1.5">
            <svg x-show="!loading" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Làm mới
        </button>
    </div>

    {{-- ── Filter bar ───────────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body py-3 px-4">
            <div class="flex flex-wrap gap-3 items-end">

                {{-- Project TomSelect remote --}}
                <div class="form-control min-w-64">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Dự án</span>
                    </label>
                    <select id="rpt-tk-project"
                            class="select select-sm select-bordered w-full bg-base-100"
                            placeholder="Tất cả dự án...">
                    </select>
                </div>

                {{-- Priority select --}}
                <div class="form-control w-44">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Mức độ ưu tiên</span>
                    </label>
                    <select id="rpt-tk-priority"
                            x-model="filters.priority"
                            class="select select-sm select-bordered w-full bg-base-100">
                        <option value="">Tất cả</option>
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                    </select>
                </div>

                {{-- Apply / Reset --}}
                <div class="flex gap-2 pb-0.5">
                    <button @click="load()" :disabled="loading"
                            class="btn btn-primary btn-sm gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                        </svg>
                        Xem báo cáo
                    </button>
                    <button @click="resetFilters()"
                            class="btn btn-ghost btn-sm gap-1.5 text-error">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Đặt lại
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Summary cards (6) ────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">

        {{-- Total Tasks --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-base-content/60">Tổng Tasks</span>
                <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-base-content leading-none"
                 x-text="summary.total_tasks !== undefined ? summary.total_tasks.toLocaleString('vi-VN') : '—'"></div>
            <div class="text-xs text-base-content/40 mt-1">tất cả công việc</div>
        </div>

        {{-- Done --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-base-content/60">Hoàn thành</span>
                <div class="w-8 h-8 rounded-lg bg-success/10 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-success leading-none"
                 x-text="summary.done !== undefined ? summary.done.toLocaleString('vi-VN') : '—'"></div>
            <div class="text-xs text-base-content/40 mt-1">done</div>
        </div>

        {{-- In Progress --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-base-content/60">Đang làm</span>
                <div class="w-8 h-8 rounded-lg bg-info/10 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-info leading-none"
                 x-text="summary.in_progress !== undefined ? summary.in_progress.toLocaleString('vi-VN') : '—'"></div>
            <div class="text-xs text-base-content/40 mt-1">in_progress</div>
        </div>

        {{-- Todo --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-base-content/60">Chờ làm</span>
                <div class="w-8 h-8 rounded-lg bg-base-300/50 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-base-content leading-none"
                 x-text="summary.todo !== undefined ? summary.todo.toLocaleString('vi-VN') : '—'"></div>
            <div class="text-xs text-base-content/40 mt-1">todo</div>
        </div>

        {{-- Overdue --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-base-content/60">Quá hạn</span>
                <div class="w-8 h-8 rounded-lg bg-error/10 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-error leading-none"
                 x-text="summary.overdue !== undefined ? summary.overdue.toLocaleString('vi-VN') : '—'"></div>
            <div class="text-xs text-base-content/40 mt-1">chưa xong</div>
        </div>

        {{-- Completion % --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-medium text-base-content/60">Hoàn thành</span>
                <div class="w-8 h-8 rounded-lg bg-warning/10 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-bold text-warning leading-none"
                 x-text="summary.completion_pct !== undefined ? summary.completion_pct.toFixed(1) + '%' : '—'"></div>
            <div class="text-xs text-base-content/40 mt-1">tỷ lệ %</div>
        </div>

    </div>

    {{-- ── Time tracking stats ───────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">

        {{-- Estimated Hours --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-base-content/50 mb-0.5">Giờ ước tính</p>
                <p class="text-2xl font-bold text-base-content leading-none"
                   x-text="timeTracking.estimated_hours !== undefined ? timeTracking.estimated_hours.toLocaleString('vi-VN') + 'h' : '—'"></p>
                <p class="text-xs text-base-content/40 mt-0.5">estimated hours</p>
            </div>
        </div>

        {{-- Logged Hours --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-success/10 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-base-content/50 mb-0.5">Giờ đã log</p>
                <p class="text-2xl font-bold text-success leading-none"
                   x-text="timeTracking.logged_hours !== undefined ? timeTracking.logged_hours.toLocaleString('vi-VN') + 'h' : '—'"></p>
                <p class="text-xs text-base-content/40 mt-0.5">logged hours</p>
            </div>
        </div>

        {{-- Variance % --}}
        <div class="bg-base-100 border border-base-200 rounded-2xl p-4 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0"
                 :class="(timeTracking.variance_pct ?? 0) > 0 ? 'bg-error/10' : 'bg-success/10'">
                <svg class="w-6 h-6"
                     :class="(timeTracking.variance_pct ?? 0) > 0 ? 'text-error' : 'text-success'"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-xs text-base-content/50 mb-0.5">Variance</p>
                <p class="text-2xl font-bold leading-none"
                   :class="(timeTracking.variance_pct ?? 0) > 0 ? 'text-error' : 'text-success'"
                   x-text="timeTracking.variance_pct !== undefined
                       ? ((timeTracking.variance_pct > 0 ? '+' : '') + timeTracking.variance_pct.toFixed(1) + '%')
                       : '—'"></p>
                <p class="text-xs text-base-content/40 mt-0.5">logged vs estimated</p>
            </div>
        </div>

    </div>

    {{-- ── Charts row ────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">

        {{-- Priority bar chart --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4 pb-3">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <h2 class="font-semibold text-sm text-base-content">Tasks theo mức độ ưu tiên</h2>
                    <span class="badge badge-xs badge-ghost">Bar chart</span>
                </div>
                <div x-show="loading" class="flex items-center justify-center h-64">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                </div>
                <div x-show="!loading" id="chart-tk-priority" class="w-full" style="height: 260px;"></div>
            </div>
        </div>

        {{-- Weekly velocity line chart --}}
        <div class="card bg-base-100 border border-base-200 shadow-sm">
            <div class="card-body p-4 pb-3">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <h2 class="font-semibold text-sm text-base-content">Velocity hoàn thành theo tuần</h2>
                    <span class="badge badge-xs badge-ghost">Line chart</span>
                </div>
                <div x-show="loading" class="flex items-center justify-center h-64">
                    <span class="loading loading-spinner loading-md text-primary"></span>
                </div>
                <div x-show="!loading" id="chart-tk-velocity" class="w-full" style="height: 260px;"></div>
            </div>
        </div>

    </div>

    {{-- ── Overdue tasks table ────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-sm text-base-content">Công việc quá hạn</h2>
                <span class="badge badge-sm badge-error"
                      x-text="overdueTasks.length + ' task'"></span>
            </div>

            <div x-show="loading" class="flex items-center justify-center py-12">
                <span class="loading loading-spinner loading-md text-primary"></span>
            </div>

            <div x-show="!loading && overdueTasks.length === 0"
                 class="flex flex-col items-center justify-center py-12 text-base-content/40">
                <svg class="w-12 h-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-medium">Tuyệt vời! Không có task quá hạn</p>
            </div>

            <div x-show="!loading && overdueTasks.length > 0" class="overflow-x-auto rounded-lg border border-base-200">
                <table class="table table-sm table-zebra w-full">
                    <thead class="bg-base-200/60">
                        <tr>
                            <th class="text-xs font-semibold text-base-content/70">#</th>
                            <th class="text-xs font-semibold text-base-content/70">Tiêu đề</th>
                            <th class="text-xs font-semibold text-base-content/70">Dự án</th>
                            <th class="text-xs font-semibold text-base-content/70">Người thực hiện</th>
                            <th class="text-xs font-semibold text-base-content/70 text-center">Ngày hết hạn</th>
                            <th class="text-xs font-semibold text-base-content/70 text-center">Số ngày trễ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(task, idx) in overdueTasks" :key="task.id ?? idx">
                            <tr class="hover:bg-base-200/40 transition-colors">
                                <td class="text-xs text-base-content/40 w-8" x-text="idx + 1"></td>
                                <td class="max-w-xs">
                                    <span class="font-medium text-sm text-base-content line-clamp-2"
                                          x-text="task.title || '—'"></span>
                                    <template x-if="task.priority">
                                        <span class="badge badge-xs mt-0.5"
                                              :class="{
                                                  'badge-error':   task.priority === 'critical',
                                                  'badge-warning': task.priority === 'high',
                                                  'badge-info':    task.priority === 'medium',
                                                  'badge-ghost':   task.priority === 'low',
                                              }"
                                              x-text="task.priority"></span>
                                    </template>
                                </td>
                                <td>
                                    <span class="text-sm text-base-content/80" x-text="task.project_name || '—'"></span>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="avatar placeholder shrink-0">
                                            <div class="w-7 h-7 rounded-full bg-primary/10 text-primary text-xs font-bold flex items-center justify-center">
                                                <span x-text="(task.assignee_name || '?').charAt(0).toUpperCase()"></span>
                                            </div>
                                        </div>
                                        <span class="text-sm text-base-content/80" x-text="task.assignee_name || 'Chưa gán'"></span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="text-sm font-mono text-base-content/70" x-text="task.due_date || '—'"></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-sm badge-error font-mono font-semibold"
                                          x-text="task.days_overdue !== undefined ? task.days_overdue + ' ngày' : '—'"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot x-show="overdueTasks.length > 0" class="bg-base-200/40 font-semibold">
                        <tr>
                            <td colspan="5" class="text-xs text-base-content/60">Tổng quá hạn</td>
                            <td class="text-center">
                                <span class="badge badge-sm badge-error font-semibold"
                                      x-text="overdueTasks.length + ' task'"></span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
@vite([
    'resources/js/modules/echarts.js',
    'resources/js/modules/tom-select.js',
    'Modules/Report/resources/assets/js/report.js',
], 'build/backend')

<script>
window.API_URL             = "{{ route('api.report.project.tasks') }}";
window.PROJECT_OPTIONS_URL = "{{ url('/api/v1/projects/options') }}";
</script>
@endpush
