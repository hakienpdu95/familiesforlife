@extends('layouts.backend')

@section('title', 'Responses — ' . $survey->title)


@section('content')
<div x-data="responseListPage">

    {{-- ── Page header ─────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Responses</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Survey: <span class="font-semibold text-base-content/70">{{ $survey->title }}</span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            @can('survey.export')
            <a id="btn-export"
               href="{{ route('backend.surveys.responses.export', $survey) }}"
               class="btn btn-success btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export Excel
            </a>
            @endcan
            @if($survey->hasScoring())
            <a href="{{ route('backend.surveys.results.summary', $survey) }}" class="btn btn-info btn-soft btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 4h16v16H4z"/>
                </svg>
                Kết quả scoring
            </a>
            @endif
            <a href="{{ route('backend.surveys.stats.index', $survey) }}" class="btn btn-ghost btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Thống kê
            </a>
            <a href="{{ route('backend.surveys.edit', $survey) }}" class="btn btn-ghost btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                </svg>
                Builder
            </a>
        </div>
    </div>

    {{-- ── Flash messages ────────────────────────────────────────────────── --}}
    @if(session('success'))
    <div class="alert alert-success text-sm py-2 px-4 rounded-lg mb-4">{{ session('success') }}</div>
    @endif
    @if(session('info'))
    <div class="alert alert-info text-sm py-2 px-4 rounded-lg mb-4">{{ session('info') }}</div>
    @endif
    @if(session('error'))
    <div class="alert alert-error text-sm py-2 px-4 rounded-lg mb-4">{{ session('error') }}</div>
    @endif

    {{-- ── Stat cards (server-side) ─────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-4">
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Tổng responses</div>
            <div class="stat-value text-2xl">{{ number_format($totalAll) }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Hoàn chỉnh</div>
            <div class="stat-value text-2xl text-success">{{ number_format($totalComplete) }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Đang điền</div>
            <div class="stat-value text-2xl text-warning">{{ number_format($totalAll - $totalComplete) }}</div>
        </div>
    </div>

    {{-- ── Filter bar ───────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3 px-4 space-y-3">

            <div class="flex flex-wrap gap-3 items-end">

                {{-- Respondent ref search --}}
                <div class="form-control flex-1 min-w-52">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Respondent</span>
                        <span class="label-text-alt text-xs text-base-content/40">Tìm theo prefix</span>
                    </label>
                    <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                        <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input id="filter-ref" type="text"
                               x-model="filters.respondent_ref"
                               @input.debounce.350ms="onFilterChange()"
                               placeholder="Nhập email, phone..."
                               class="grow bg-transparent outline-none text-sm"/>
                        <button x-show="filters.respondent_ref" @click="clearRef()"
                                class="text-base-content/30 hover:text-base-content transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Status TomSelect --}}
                <div class="form-control w-48">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Trạng thái</span>
                    </label>
                    <select id="filter-status" class="select select-sm select-bordered w-full"></select>
                </div>

                {{-- Date range --}}
                <div class="form-control w-64">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Nộp lúc</span>
                    </label>
                    <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                        <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <input id="filter-date" type="text" readonly
                               placeholder="Chọn khoảng ngày..."
                               class="grow bg-transparent outline-none text-sm cursor-pointer"/>
                        <button x-show="filters.from" @click="clearDate()"
                                class="text-base-content/30 hover:text-base-content transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Reset --}}
                <div class="form-control ml-auto">
                    <label class="label py-0.5 invisible"><span class="label-text text-xs">.</span></label>
                    <button @click="reset()" x-show="hasFilters" x-transition
                            class="btn btn-ghost btn-sm gap-1.5 text-error">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Đặt lại
                    </button>
                </div>

            </div>

            {{-- Active chips --}}
            <div x-show="activeChips.length > 0" x-transition class="flex flex-wrap gap-2 pt-1 border-t border-base-200">
                <span class="text-xs text-base-content/40 self-center">Đang lọc:</span>
                <template x-for="chip in activeChips" :key="chip.key">
                    <span class="badge badge-sm gap-1 cursor-pointer hover:badge-error transition-colors"
                          @click="removeChip(chip.key)">
                        <span x-text="chip.label"></span>
                        <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </span>
                </template>
            </div>

        </div>
    </div>

    {{-- ── Tabulator table ───────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-hidden rounded-2xl tabulator-daisy">
            <div id="response-table"></div>
        </div>
    </div>

</div>

{{-- ── Delete confirm modal ──────────────────────────────────────────────── --}}
<dialog id="deleteModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xóa</h3>
        <p class="py-3 text-sm text-base-content/70">
            Bạn có chắc muốn xóa response
            <strong id="deleteResponseId" class="text-base-content font-mono"></strong>?
        </p>
        <p class="text-xs text-base-content/40">Dữ liệu có thể khôi phục từ database nếu cần.</p>
        <div class="modal-action mt-4">
            <button id="confirmDeleteBtn" class="btn btn-error btn-sm">Xóa</button>
            <button class="btn btn-ghost btn-sm" onclick="deleteModal.close()">Hủy</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endsection

@push('styles')
<x-tabulator-theme />
@endpush

@push('scripts')
@vite(['resources/js/modules/tabulator.js', 'resources/js/modules/tom-select.js', 'resources/js/modules/flatpickr.js'], 'build/backend')
<script>
// ── Security: HTML escape for innerHTML formatters ───────────────────────────
function esc(v) {
    if (v == null) return '';
    return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

var CSRF_TOKEN    = '{{ csrf_token() }}';
var API_URL       = '{{ route('backend.api.surveys.responses', $survey) }}';
var EXPORT_BASE   = '{{ route('backend.surveys.responses.export', $survey) }}';
var STATUSES      = @json($statuses);

var CAN_VIEW_RESPONSES = {{ auth()->user()->can('survey.view_responses') ? 'true' : 'false' }};
var CAN_DELETE         = {{ auth()->user()->can('survey.view_responses') ? 'true' : 'false' }};

// ── Helpers ──────────────────────────────────────────────────────────────────
function isoDate(d) {
    return d.getFullYear() + '-'
        + String(d.getMonth() + 1).padStart(2, '0') + '-'
        + String(d.getDate()).padStart(2, '0');
}
function displayDate(iso) {
    if (!iso) return '';
    var p = iso.split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
}

// ── Delete logic ─────────────────────────────────────────────────────────────
var pendingDeleteUrl = null;

window.responseDeleteConfirm = function (url, id) {
    pendingDeleteUrl = url;
    document.getElementById('deleteResponseId').textContent = '#' + id;
    document.getElementById('deleteModal').showModal();
};

document.getElementById('confirmDeleteBtn').addEventListener('click', async function () {
    if (!pendingDeleteUrl) return;
    var btn = this;
    btn.disabled    = true;
    btn.textContent = 'Đang xóa...';

    try {
        var res = await fetch(pendingDeleteUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN':       CSRF_TOKEN,
                'X-Requested-With':   'XMLHttpRequest',
                'Accept':             'application/json',
                'Content-Type':       'application/x-www-form-urlencoded',
            },
            body: '_method=DELETE',
        });

        if (res.ok) {
            document.getElementById('deleteModal').close();
            if (window._responseTableInst) window._responseTableInst.replaceData();
        }
    } catch (e) {
        console.error('Delete failed', e);
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Xóa';
        pendingDeleteUrl = null;
    }
});

// ── Column definitions ────────────────────────────────────────────────────────
var COLUMNS = [
    {
        title: '#', field: 'id', width: 75, sorter: 'number', hozAlign: 'center',
        formatter: function (cell) {
            return '<span class="font-mono text-xs text-base-content/40">' + esc(cell.getValue()) + '</span>';
        },
    },
    {
        title: 'Respondent', field: 'respondent_ref', minWidth: 200, sorter: 'string',
        formatter: function (cell) {
            var v = cell.getValue();
            return v
                ? '<span class="text-sm font-medium">' + esc(v) + '</span>'
                : '<span class="text-base-content/30 text-sm">—</span>';
        },
    },
    {
        title: 'IP', field: 'respondent_ip', width: 140, headerSort: false,
        formatter: function (cell) {
            var v = cell.getValue();
            return v
                ? '<span class="font-mono text-xs text-base-content/50">' + esc(v) + '</span>'
                : '<span class="text-base-content/20 text-xs">—</span>';
        },
    },
    {
        title: 'Trạng thái', field: 'status_value', width: 130, hozAlign: 'center', sorter: 'number',
        formatter: function (cell) {
            var d = cell.getRow().getData();
            return '<span class="badge badge-sm badge-soft ' + esc(d.status_badge) + '">' + esc(d.status_label) + '</span>';
        },
    },
    {
        title: 'Nộp lúc', field: 'submitted_at', width: 140, sorter: 'string',
    },
    {
        title: 'Hành động', field: 'show_url', width: 100, hozAlign: 'center',
        headerSort: false, frozen: true,
        formatter: function (cell) {
            var d   = cell.getRow().getData();
            var html = '<div class="flex items-center justify-center gap-1">';

            if (CAN_VIEW_RESPONSES) {
                html += '<a href="' + esc(d.show_url) + '"'
                    + ' class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-info" title="Xem chi tiết">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">'
                    + '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>'
                    + '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>'
                    + '</svg></a>';
            }

            if (CAN_DELETE) {
                html += '<button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa"'
                    + ' data-url="' + esc(d.delete_url) + '" data-id="' + esc(d.id) + '"'
                    + ' onclick="window.responseDeleteConfirm(this.dataset.url, this.dataset.id)">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">'
                    + '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"'
                    + ' d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>'
                    + '</svg></button>';
            }

            html += '</div>';
            return html;
        },
    },
];

// ── Alpine component ──────────────────────────────────────────────────────────
document.addEventListener('alpine:init', function () {
    var tableInst   = null;
    var statusTsInst = null;
    var dateFpInst  = null;
    var settingPreset = false;

    Alpine.data('responseListPage', function () {
        return {
            filters: { respondent_ref: '', status: '', from: '', to: '' },

            get hasFilters() {
                var f = this.filters;
                return !!(f.respondent_ref || f.status !== '' || f.from);
            },

            get activeChips() {
                var chips = [], f = this.filters;
                if (f.respondent_ref)
                    chips.push({ key: 'ref', label: 'Ref: ' + f.respondent_ref });
                if (f.status !== '' && f.status !== null && f.status !== undefined) {
                    var st = STATUSES.find(function (s) { return String(s.value) === String(f.status); });
                    chips.push({ key: 'status', label: st ? st.text : f.status });
                }
                if (f.from && f.to)
                    chips.push({ key: 'date', label: displayDate(f.from) + ' — ' + displayDate(f.to) });
                return chips;
            },

            init: function () {
                var self = this;
                this.loadState();
                // Defer to DOMContentLoaded since Tabulator and other libs need the DOM
                self.$nextTick(() => self._setup());
            },

            _setup: function () {
                var self = this;

                // ── Tabulator ────────────────────────────────────────────────
                tableInst = new window.Tabulator('#response-table', {
                    ajaxURL:    API_URL,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams: function () {
                        var p = {}, f = self.filters;
                        if (f.respondent_ref) p.respondent_ref = f.respondent_ref;
                        if (f.status !== '' && f.status !== null && f.status !== undefined)
                            p.status = f.status;
                        if (f.from) p.from = f.from;
                        if (f.to)   p.to   = f.to;
                        return p;
                    },
                    ajaxResponse: function (_u, _p, res) { return res; },

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         50,
                    paginationSizeSelector: [20, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'submitted_at', dir: 'desc' }],

                    layout:           'fitColumns',
                    responsiveLayout: 'collapse',
                    height:           '68vh',

                    locale: 'vi-VN',
                    langs: {
                        'vi-VN': {
                            pagination: {
                                page_size: 'Dòng/trang', page_title: 'Trang',
                                first: '«', last: '»', prev: '‹', next: '›',
                                first_title: 'Trang đầu', last_title: 'Trang cuối',
                                prev_title: 'Trang trước', next_title: 'Trang sau',
                                counter: { showing: '', of: 'trong', rows: 'dòng', pages: 'trang' },
                            },
                        },
                    },

                    columns: COLUMNS,

                    placeholder: '<div class="py-16 text-center opacity-40">'
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">'
                        + '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"'
                        + ' d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>'
                        + '</svg><p>Không có response nào</p></div>',
                });

                window._responseTableInst = tableInst;

                // ── Status TomSelect ─────────────────────────────────────────
                statusTsInst = new window.TomSelect('#filter-status', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả trạng thái...',
                    plugins:        ['clear_button'],
                    options:        STATUSES.map(function (s) { return { value: String(s.value), text: s.text }; }),
                    items:          (self.filters.status !== '' && self.filters.status !== null)
                                        ? [String(self.filters.status)] : [],
                    onChange: function (val) {
                        self.filters.status = (val !== '' && val !== null) ? val : '';
                        self.saveState();
                        self.refresh();
                    },
                    render: { no_results: function () { return '<div class="no-results p-3 text-sm opacity-50">Không tìm thấy</div>'; } },
                });

                // ── Flatpickr date range ─────────────────────────────────────
                dateFpInst = window.initDateRangePicker('#filter-date', {
                    disableMobile: true,
                    onChange: function (dates) {
                        if (settingPreset) return;
                        if (dates.length === 2) {
                            self.filters.from = isoDate(dates[0]);
                            self.filters.to   = isoDate(dates[1]);
                        } else {
                            self.filters.from = '';
                            self.filters.to   = '';
                        }
                        self.saveState();
                        self.refresh();
                    },
                });

                if (self.filters.from && self.filters.to) {
                    dateFpInst.setDate([self.filters.from, self.filters.to], false);
                }
            },

            // ── State persistence via URL ──────────────────────────────────────
            loadState: function () {
                var p = new URLSearchParams(location.search);
                if (p.has('ref'))  this.filters.respondent_ref = p.get('ref');
                if (p.has('st'))   this.filters.status         = p.get('st');
                if (p.has('from')) this.filters.from           = p.get('from');
                if (p.has('to'))   this.filters.to             = p.get('to');
            },

            saveState: function () {
                var p = new URLSearchParams(), f = this.filters;
                if (f.respondent_ref) p.set('ref',  f.respondent_ref);
                if (f.status !== '' && f.status !== null && f.status !== undefined)
                                      p.set('st',   String(f.status));
                if (f.from) p.set('from', f.from);
                if (f.to)   p.set('to',   f.to);
                var qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);

                // Keep export link in sync with active filters
                var params = new URLSearchParams();
                if (f.respondent_ref) params.set('respondent_ref', f.respondent_ref);
                if (f.from) params.set('from', f.from);
                if (f.to)   params.set('to',   f.to);
                var exportEl = document.getElementById('btn-export');
                if (exportEl) {
                    exportEl.href = EXPORT_BASE + (params.toString() ? '?' + params.toString() : '');
                }
            },

            // ── Actions ────────────────────────────────────────────────────────
            refresh: function () { if (tableInst) tableInst.replaceData(); },

            onFilterChange: function () { this.saveState(); this.refresh(); },

            clearRef: function () {
                this.filters.respondent_ref = '';
                this.saveState();
                this.refresh();
            },

            clearDate: function () {
                this.filters.from = '';
                this.filters.to   = '';
                if (dateFpInst) dateFpInst.clear(false);
                this.saveState();
                this.refresh();
            },

            removeChip: function (key) {
                if (key === 'ref')    { this.filters.respondent_ref = ''; }
                if (key === 'status') { this.filters.status = ''; if (statusTsInst) statusTsInst.clear(true); }
                if (key === 'date')   { this.clearDate(); return; }
                this.saveState();
                this.refresh();
            },

            reset: function () {
                this.filters = { respondent_ref: '', status: '', from: '', to: '' };
                if (statusTsInst) statusTsInst.clear(true);
                if (dateFpInst)   dateFpInst.clear(false);
                history.replaceState(null, '', location.pathname);
                var exportEl = document.getElementById('btn-export');
                if (exportEl) exportEl.href = EXPORT_BASE;
                this.refresh();
            },
        };
    });
});
</script>
@endpush
