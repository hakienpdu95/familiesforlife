@extends('layouts.backend')
@section('title', 'Danh sách Khảo sát')


@section('content')
<div x-data="surveyListPage">

    {{-- ── Page header ─────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Khảo sát</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Tạo và quản lý các khảo sát thu thập phản hồi</p>
        </div>
        <div class="flex items-center gap-2">
            {{-- Column visibility dropdown --}}
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-ghost btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                    </svg>
                    Cột
                </label>
                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box shadow-lg border border-base-200 w-48 z-50 p-2">
                    <template x-for="col in toggleableCols" :key="col.field">
                        <li>
                            <label class="flex items-center gap-2 cursor-pointer py-1.5 px-2 rounded-lg hover:bg-base-200">
                                <input type="checkbox" class="checkbox checkbox-xs"
                                       :checked="!hiddenCols.includes(col.field)"
                                       @change="toggleCol(col.field)"/>
                                <span x-text="col.title" class="text-sm"></span>
                            </label>
                        </li>
                    </template>
                </ul>
            </div>
            @can('survey.create')
            <a href="{{ route('backend.surveys.create') }}" class="btn btn-primary btn-sm gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tạo khảo sát
            </a>
            @endcan
        </div>
    </div>

    {{-- ── Filter bar ───────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3 px-4 space-y-3">

            {{-- Row 1: text search + status --}}
            <div class="flex flex-wrap gap-3 items-end">

                {{-- Global search --}}
                <div class="form-control flex-1 min-w-52">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Tìm kiếm</span>
                        <span class="label-text-alt text-xs text-base-content/40">Tiêu đề, Slug</span>
                    </label>
                    <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                        <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input id="filter-search" type="text"
                               x-model="filters.search"
                               @input.debounce.350ms="onSearchInput()"
                               placeholder="Nhập từ khóa..."
                               class="grow bg-transparent outline-none text-sm"/>
                        <button x-show="filters.search" @click="clearSearch()"
                                class="text-base-content/30 hover:text-base-content transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Status TomSelect --}}
                <div class="form-control w-52">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Trạng thái</span>
                    </label>
                    <select id="filter-status" class="select select-sm select-bordered w-full"></select>
                </div>

            </div>

            {{-- Row 2: date range + presets + reset --}}
            <div class="flex flex-wrap gap-3 items-end">

                {{-- Date range picker --}}
                <div class="form-control w-64">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Ngày tạo</span>
                    </label>
                    <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                        <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <input id="filter-date" type="text" readonly
                               placeholder="Chọn khoảng ngày..."
                               class="grow bg-transparent outline-none text-sm cursor-pointer"/>
                        <button x-show="filters.date_from" @click="clearDate()"
                                class="text-base-content/30 hover:text-base-content transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Date presets --}}
                <div class="form-control">
                    <label class="label py-0.5 invisible"><span class="label-text text-xs">.</span></label>
                    <div class="flex gap-1">
                        <button @click="setDatePreset('today')"
                                :class="activeDatePreset === 'today' ? 'btn-primary' : 'btn-ghost'"
                                class="btn btn-xs">Hôm nay</button>
                        <button @click="setDatePreset('week')"
                                :class="activeDatePreset === 'week' ? 'btn-primary' : 'btn-ghost'"
                                class="btn btn-xs">Tuần này</button>
                        <button @click="setDatePreset('month')"
                                :class="activeDatePreset === 'month' ? 'btn-primary' : 'btn-ghost'"
                                class="btn btn-xs">Tháng này</button>
                        <button @click="setDatePreset('year')"
                                :class="activeDatePreset === 'year' ? 'btn-primary' : 'btn-ghost'"
                                class="btn btn-xs">Năm nay</button>
                    </div>
                </div>

                {{-- Reset all --}}
                <div class="form-control ml-auto">
                    <label class="label py-0.5 invisible"><span class="label-text text-xs">.</span></label>
                    <button @click="reset()" x-show="hasFilters" x-transition
                            class="btn btn-ghost btn-sm gap-1.5 text-error">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Đặt lại
                    </button>
                </div>

            </div>

            {{-- Active filter chips --}}
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

    {{-- ── Tabulator table ──────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-hidden rounded-2xl tabulator-daisy">
            <div id="survey-table"></div>
        </div>
    </div>

</div>

{{-- Delete confirm modal --}}
<dialog id="deleteModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xóa</h3>
        <p class="py-3 text-sm text-base-content/70">
            Bạn có chắc muốn xóa khảo sát <strong id="deleteItemName" class="text-base-content"></strong>?
        </p>
        <p class="text-xs text-error/70">Toàn bộ sections, fields và dữ liệu liên quan sẽ bị xóa theo.</p>
        <div class="modal-action mt-4">
            <form method="POST" id="deleteForm">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-error btn-sm">Xóa</button>
            </form>
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
// ── Security: HTML escape for use in innerHTML formatters ────────────────────
function esc(v) {
    if (v == null) return '';
    return String(v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

window.surveyDeleteConfirm = function (url, name) {
    document.getElementById('deleteForm').action = url;
    document.getElementById('deleteItemName').textContent = name;
    deleteModal.showModal();
};

document.addEventListener('alpine:init', function () {
    // ── Closure-scoped lib instances (non-reactive) ──────────────────────
    var tableInst    = null;
    var statusTsInst = null;
    var dateFpInst   = null;
    var settingPreset = false;

    var API_URL  = '{{ route('backend.api.surveys') }}';
    var STATUSES = @json($statuses);
    var LS_COLS  = 'survey-list-hidden-cols';

    // Permissions injected from server — avoids API round-trip for auth checks
    var CAN_UPDATE          = {{ auth()->user()->can('survey.update')         ? 'true' : 'false' }};
    var CAN_DELETE          = {{ auth()->user()->can('survey.delete')         ? 'true' : 'false' }};
    var CAN_VIEW_RESPONSES  = {{ auth()->user()->can('survey.view_responses') ? 'true' : 'false' }};

    // ── Helpers ──────────────────────────────────────────────────────────
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

    function presetRange(preset) {
        var now = new Date();
        var y = now.getFullYear(), m = now.getMonth(), d = now.getDate();
        if (preset === 'today')  return [new Date(y, m, d), new Date(y, m, d)];
        if (preset === 'week') {
            var dow = now.getDay() === 0 ? 6 : now.getDay() - 1;
            return [new Date(y, m, d - dow), new Date(y, m, d - dow + 6)];
        }
        if (preset === 'month') return [new Date(y, m, 1), new Date(y, m + 1, 0)];
        if (preset === 'year')  return [new Date(y, 0, 1), new Date(y, 11, 31)];
        return [null, null];
    }

    // ── Column definitions ────────────────────────────────────────────────
    var COLUMNS = [
        {
            title: 'Tiêu đề / Slug', field: 'title', minWidth: 240, sorter: 'string', frozen: true,
            formatter: function (cell) {
                var d = cell.getRow().getData();
                return '<div>'
                    + '<a href="' + esc(d.edit_url) + '" class="font-semibold text-sm hover:text-primary">' + esc(d.title) + '</a>'
                    + '<p class="text-xs text-base-content/40 font-mono mt-0.5">' + esc(d.slug) + '</p>'
                    + '</div>';
            },
        },
        {
            title: 'Version', field: 'version', width: 90, hozAlign: 'center', sorter: 'number',
            formatter: function (cell) {
                return '<span class="badge badge-ghost badge-sm">v' + esc(cell.getValue()) + '</span>';
            },
        },
        {
            title: 'Trạng thái', field: 'status', width: 130, hozAlign: 'center', sorter: 'number',
            formatter: function (cell) {
                var d = cell.getRow().getData();
                return '<span class="badge badge-sm ' + esc(d.status_badge) + '">' + esc(d.status_label) + '</span>';
            },
        },
        {
            title: 'Responses', field: 'responses_count', width: 120, hozAlign: 'center', sorter: 'number',
            formatter: function (cell) {
                var d = cell.getRow().getData();
                var count = esc(cell.getValue());
                if (CAN_VIEW_RESPONSES) {
                    return '<a href="' + esc(d.responses_url) + '" class="font-medium hover:text-primary hover:underline underline-offset-2 transition-colors">' + count + '</a>';
                }
                return '<span class="font-medium">' + count + '</span>';
            },
        },
        {
            title: 'Ngày tạo', field: 'created_at', width: 115, hozAlign: 'center', sorter: 'string',
        },
        {
            title: 'Thao tác', field: 'id', width: 140, hozAlign: 'center', headerSort: false, frozen: true,
            formatter: function (cell) {
                var d = cell.getRow().getData();
                var html = '<div class="flex items-center justify-center gap-1">';

                html += '<a href="' + esc(d.take_url) + '" target="_blank" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-secondary" title="Xem template khảo sát">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>'
                    + '</a>';

                if (CAN_VIEW_RESPONSES) {
                    html += '<a href="' + esc(d.responses_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-info" title="Responses">'
                        + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>'
                        + '</a>';
                    html += '<a href="' + esc(d.stats_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-primary" title="Thống kê">'
                        + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>'
                        + '</a>';
                }

                if (CAN_UPDATE) {
                    html += '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square" title="Chỉnh sửa / Builder">'
                        + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                        + '</a>';
                }

                if (CAN_DELETE && d.can_delete) {
                    html += '<button class="btn btn-ghost btn-xs btn-square text-error/40 hover:text-error" title="Xóa"'
                        + ' data-url="' + esc(d.delete_url) + '" data-name="' + esc(d.title) + '"'
                        + ' onclick="window.surveyDeleteConfirm(this.dataset.url, this.dataset.name)">'
                        + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>'
                        + '</button>';
                }

                html += '</div>';
                return html;
            },
        },
    ];

    // ── Alpine component ──────────────────────────────────────────────────
    Alpine.data('surveyListPage', function () {
        return {
            filters: { search: '', status: '', date_from: '', date_to: '' },
            activeDatePreset: '',
            hiddenCols: [],

            get toggleableCols() {
                return COLUMNS.filter(function (c) { return c.field !== 'id' && c.field !== 'title'; })
                              .map(function (c) { return { field: c.field, title: c.title }; });
            },

            get hasFilters() {
                var f = this.filters;
                return !!(f.search || f.status || f.date_from);
            },

            get activeChips() {
                var chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.status !== '' && f.status !== null && f.status !== undefined) {
                    var st = STATUSES.find(function (s) { return String(s.value) === String(f.status); });
                    chips.push({ key: 'status', label: st ? st.text : f.status });
                }
                if (f.date_from && f.date_to)
                    chips.push({ key: 'date', label: displayDate(f.date_from) + ' — ' + displayDate(f.date_to) });
                return chips;
            },

            init: function () {
                var self = this;
                self.loadState();
                try { self.hiddenCols = JSON.parse(localStorage.getItem(LS_COLS) || '[]'); } catch (e) {}
                self.$nextTick(() => self._setup());
            },

            _setup: function () {
                var self = this;

                // ── Tabulator ────────────────────────────────────────────
                tableInst = new window.Tabulator('#survey-table', {
                    ajaxURL:    API_URL,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams: function () {
                        var p = {}, f = self.filters;
                        if (f.search)    p.search    = f.search;
                        if (f.status !== '' && f.status !== null && f.status !== undefined)
                                         p.status    = f.status;
                        if (f.date_from) p.date_from = f.date_from;
                        if (f.date_to)   p.date_to   = f.date_to;
                        return p;
                    },
                    ajaxResponse: function (_u, _p, res) { return res; },

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'created_at', dir: 'desc' }],

                    layout:           'fitColumns',
                    responsiveLayout: 'collapse',
                    movableColumns:   true,
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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>'
                        + '<p>Không có khảo sát nào</p></div>',
                });

                // Restore hidden columns from localStorage
                self.hiddenCols.forEach(function (field) {
                    tableInst.hideColumn(field);
                });

                // ── Status TomSelect ─────────────────────────────────────
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

                // ── Flatpickr date range ─────────────────────────────────
                dateFpInst = window.initDateRangePicker('#filter-date', {
                    disableMobile: true,
                    onChange: function (dates) {
                        if (settingPreset) return;
                        self.activeDatePreset = '';
                        if (dates.length === 2) {
                            self.filters.date_from = isoDate(dates[0]);
                            self.filters.date_to   = isoDate(dates[1]);
                        } else {
                            self.filters.date_from = '';
                            self.filters.date_to   = '';
                        }
                        self.saveState();
                        self.refresh();
                    },
                });

                if (self.filters.date_from && self.filters.date_to) {
                    dateFpInst.setDate([self.filters.date_from, self.filters.date_to], false);
                }
            },

            // ── State persistence via URL ─────────────────────────────────
            loadState: function () {
                var p = new URLSearchParams(location.search);
                if (p.has('q'))    this.filters.search   = p.get('q');
                if (p.has('st'))   this.filters.status   = p.get('st');
                if (p.has('from')) this.filters.date_from = p.get('from');
                if (p.has('to'))   this.filters.date_to   = p.get('to');
                if (p.has('dpre')) this.activeDatePreset  = p.get('dpre');
            },

            saveState: function () {
                var p = new URLSearchParams(), f = this.filters;
                if (f.search)    p.set('q',    f.search);
                if (f.status !== '' && f.status !== null && f.status !== undefined)
                                 p.set('st',   String(f.status));
                if (f.date_from) p.set('from', f.date_from);
                if (f.date_to)   p.set('to',   f.date_to);
                if (this.activeDatePreset) p.set('dpre', this.activeDatePreset);
                var qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            // ── Core actions ──────────────────────────────────────────────
            refresh: function () { if (tableInst) tableInst.replaceData(); },

            onSearchInput: function () { this.saveState(); this.refresh(); },

            clearSearch: function () { this.filters.search = ''; this.saveState(); this.refresh(); },

            setDatePreset: function (preset) {
                var range = presetRange(preset);
                if (!range[0]) return;
                settingPreset = true;
                this.activeDatePreset  = preset;
                this.filters.date_from = isoDate(range[0]);
                this.filters.date_to   = isoDate(range[1]);
                dateFpInst.setDate([range[0], range[1]], false);
                settingPreset = false;
                this.saveState();
                this.refresh();
            },

            clearDate: function () {
                this.activeDatePreset  = '';
                this.filters.date_from = '';
                this.filters.date_to   = '';
                if (dateFpInst) dateFpInst.clear(false);
                this.saveState();
                this.refresh();
            },

            removeChip: function (key) {
                if (key === 'search') { this.filters.search = ''; }
                if (key === 'status') { this.filters.status = ''; if (statusTsInst) statusTsInst.clear(true); }
                if (key === 'date')   { this.clearDate(); return; }
                this.saveState();
                this.refresh();
            },

            reset: function () {
                this.filters = { search: '', status: '', date_from: '', date_to: '' };
                this.activeDatePreset = '';
                if (statusTsInst) statusTsInst.clear(true);
                if (dateFpInst)   dateFpInst.clear(false);
                history.replaceState(null, '', location.pathname);
                this.refresh();
            },

            toggleCol: function (field) {
                if (this.hiddenCols.includes(field)) {
                    this.hiddenCols = this.hiddenCols.filter(function (f) { return f !== field; });
                    if (tableInst) tableInst.showColumn(field);
                } else {
                    this.hiddenCols.push(field);
                    if (tableInst) tableInst.hideColumn(field);
                }
                try { localStorage.setItem(LS_COLS, JSON.stringify(this.hiddenCols)); } catch (e) {}
            },
        };
    });
});
</script>
@endpush
