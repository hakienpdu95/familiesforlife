@extends('layouts.backend')
@section('title', 'Lịch sử chạy — ' . $workflow->name)


@section('content')
<div x-data="wfExecListPage">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Lịch sử chạy</h1>
            <p class="text-sm text-base-content/50 mt-0.5">{{ $workflow->name }}</p>
        </div>
        <a href="{{ route('workflows.show', $workflow) }}" class="btn btn-ghost btn-sm">← Quay lại</a>
    </div>

    {{-- ── Filter bar ──────────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3 px-4">
            <div class="flex flex-wrap gap-3 items-end">
                <div class="form-control w-40">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Trạng thái</span></label>
                    <select class="select select-sm select-bordered" x-model="filters.status" @change="refresh()">
                        <option value="">Tất cả</option>
                        <option value="1">Pass</option>
                        <option value="2">Skip</option>
                        <option value="3">Fail</option>
                        <option value="4">Partial</option>
                        <option value="5">Scheduled</option>
                    </select>
                </div>
                <div class="form-control w-40">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Từ ngày</span></label>
                    <input type="date" class="input input-sm input-bordered" x-model="filters.date_from" @change="refresh()"/>
                </div>
                <div class="form-control w-40">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Đến ngày</span></label>
                    <input type="date" class="input input-sm input-bordered" x-model="filters.date_to" @change="refresh()"/>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Table ────────────────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-hidden rounded-2xl tabulator-daisy">
            <div id="exec-table"></div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<x-tabulator-theme />
@endpush

@push('scripts')
@vite(['resources/js/modules/tabulator.js'], 'build/backend')
<script>
var EXEC_API = '{{ route('backend.api.workflows.executions') }}?workflow_id={{ $workflow->id }}';

function esc(v) {
    if (v == null) return '';
    return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

var EXEC_COLUMNS = [
    {
        title: 'Run ID', field: 'run_id', width: 130, headerSort: false,
        formatter: function (cell) {
            var d = cell.getRow().getData();
            return '<a href="/dashboard/workflows/executions/' + d.id + '" class="font-mono text-xs hover:text-primary link">'
                + esc(String(d.run_id).slice(0, 8)) + '…</a>';
        },
    },
    {
        title: 'Trạng thái', field: 'status_badge', width: 130, hozAlign: 'center', headerSort: false,
        formatter: function (cell) {
            var d = cell.getRow().getData();
            return '<span class="badge badge-sm badge-soft ' + esc(d.status_badge) + '">' + esc(d.status_label) + '</span>';
        },
    },
    {
        title: 'Trigger', field: 'trigger_type', width: 180,
        formatter: function (cell) {
            return '<span class="font-mono text-xs">' + esc(cell.getValue()) + '</span>';
        },
    },
    {
        title: 'Steps (✓/✗)', field: 'steps_total', width: 120, hozAlign: 'center', headerSort: false,
        formatter: function (cell) {
            var d = cell.getRow().getData();
            return '<span class="text-xs">'
                + '<span class="text-success">' + (d.steps_success ?? 0) + '</span>'
                + ' / <span class="text-error">'  + (d.steps_failed ?? 0)  + '</span>'
                + ' / ' + (d.steps_total ?? 0)
                + '</span>';
        },
    },
    {
        title: 'Thời gian (ms)', field: 'duration_ms', width: 130, hozAlign: 'right', sorter: 'number',
        formatter: function (cell) {
            return '<span class="font-mono text-xs">' + esc(cell.getValue() ?? 0) + ' ms</span>';
        },
    },
    {
        title: 'Kích hoạt lúc', field: 'triggered_at', width: 160, hozAlign: 'center', sorter: 'string',
        formatter: function (cell) {
            var v = cell.getValue();
            return v ? '<span class="text-xs">' + esc(new Date(v).toLocaleString('vi-VN')) + '</span>' : '—';
        },
    },
    {
        title: '', field: 'id', width: 70, hozAlign: 'center', headerSort: false,
        formatter: function (cell) {
            var d = cell.getRow().getData();
            return '<a href="/dashboard/workflows/executions/' + d.id + '" class="btn btn-ghost btn-xs">Chi tiết</a>';
        },
    },
];

document.addEventListener('alpine:init', function () {
    Alpine.data('wfExecListPage', function () {
        return {
            filters: { status: '', date_from: '', date_to: '' },
            _table: null,

            init() {
                this.$nextTick(() => {
                    var self = this;
                    self._table = new window.Tabulator('#exec-table', {
                        ajaxURL:    EXEC_API,
                        ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                        ajaxParams: function () {
                            var p = {}, f = self.filters;
                            if (f.status)    p.status    = f.status;
                            if (f.date_from) p.date_from = f.date_from;
                            if (f.date_to)   p.date_to   = f.date_to;
                            return p;
                        },
                        ajaxResponse: function (_u, _p, res) { return res; },

                        pagination:        true,
                        paginationMode:    'remote',
                        paginationSize:    20,
                        paginationCounter: 'rows',
                        sortMode:          'remote',
                        initialSort:       [{ column: 'triggered_at', dir: 'desc' }],

                        layout: 'fitColumns',
                        height: '65vh',
                        locale: 'vi-VN',
                        langs: { 'vi-VN': { pagination: {
                            page_size: 'Dòng/trang', first: '«', last: '»', prev: '‹', next: '›',
                            counter: { showing: '', of: 'trong', rows: 'dòng', pages: 'trang' },
                        }}},

                        columns: EXEC_COLUMNS,
                        placeholder: '<div class="py-12 text-center opacity-40 text-sm">Chưa có lần chạy nào</div>',
                    });
                });
            },

            refresh() { this._table && this._table.replaceData(); },
        };
    });
});
</script>
@endpush
