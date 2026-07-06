@extends('layouts.backend')
@section('title', 'Quy trình SOP')


@section('content')
<div x-data="sopListPage" x-init="init()">

    {{-- ── Page header ─────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Quy trình SOP</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Quản lý quy trình vận hành chuẩn của tổ chức</p>
        </div>
        <div class="flex items-center gap-2">
            {{-- Analytics link --}}
            <a href="{{ route('backend.sop.analytics') }}" class="btn btn-ghost btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Analytics
            </a>

            {{-- Column toggle --}}
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-ghost btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                    </svg>
                    Cột
                </label>
                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box shadow-lg border border-base-200 w-56 z-50 p-2">
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

            @can('create', \Modules\Sop\Models\SopProcess::class)
            <a href="{{ route('backend.sop.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tạo SOP mới
            </a>
            @endcan
        </div>
    </div>

    {{-- ── Filter bar ──────────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="flex flex-wrap gap-3 items-end">

                {{-- Search --}}
                <div class="flex-1 min-w-[200px]">
                    <label class="label label-text text-xs font-medium pb-1">Tìm kiếm</label>
                    <input type="text"
                           class="input input-bordered input-sm w-full"
                           placeholder="Mã SOP, tên quy trình..."
                           x-model="filters.search"
                           @input.debounce.400ms="saveState(); refresh()"/>
                </div>

                {{-- Status --}}
                <div class="min-w-[160px]">
                    <label class="label label-text text-xs font-medium pb-1">Trạng thái</label>
                    <select id="filter-status" class="select select-bordered select-sm w-full"></select>
                </div>

                {{-- Type --}}
                <div class="min-w-[160px]">
                    <label class="label label-text text-xs font-medium pb-1">Loại SOP</label>
                    <select id="filter-type" class="select select-bordered select-sm w-full"></select>
                </div>

                {{-- Department --}}
                <div class="min-w-[180px]">
                    <label class="label label-text text-xs font-medium pb-1">Phòng ban</label>
                    <select id="filter-department" class="select select-bordered select-sm w-full"></select>
                </div>

                {{-- Actions --}}
                <div class="flex gap-2">
                    <button @click="clearFilters()" class="btn btn-ghost btn-sm" x-show="hasFilters">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Xóa lọc
                    </button>
                </div>

            </div>

            {{-- Active filter chips --}}
            <div class="flex flex-wrap gap-2 mt-3" x-show="activeChips.length > 0">
                <template x-for="chip in activeChips" :key="chip.key">
                    <div class="badge badge-outline gap-1 cursor-pointer" @click="removeChip(chip.key)">
                        <span x-text="chip.label" class="text-xs"></span>
                        <svg class="w-3 h-3 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- ── Table ───────────────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body p-0">
            <div id="sop-table"></div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
/* ── Tabulator ─────────────────────────────────────────────────────────── */
#sop-table .tabulator { border:none; border-radius:0; background:transparent; font-size:.8125rem; }
#sop-table .tabulator-header { background:oklch(var(--b2)); border-bottom:1px solid oklch(var(--b3)); color:oklch(var(--bc)/.65); font-weight:600; font-size:.75rem; text-transform:uppercase; letter-spacing:.04em; }
#sop-table .tabulator-col { background:transparent; border-right:1px solid oklch(var(--b3)); }
#sop-table .tabulator-row { background:oklch(var(--b1)); border-bottom:1px solid oklch(var(--b2)); }
#sop-table .tabulator-row:hover { background:oklch(var(--b2)/.6); }
#sop-table .tabulator-row .tabulator-cell { border-right:1px solid oklch(var(--b2)); color:oklch(var(--bc)); padding:.5rem .75rem; }
#sop-table .tabulator-footer { background:oklch(var(--b2)/.5); border-top:1px solid oklch(var(--b3)); }
#sop-table .tabulator-page { background:transparent; border:1px solid oklch(var(--b3)); color:oklch(var(--bc)); border-radius:.375rem; padding:.2rem .5rem; margin:0 1px; }
#sop-table .tabulator-page.active { background:oklch(var(--p)); color:oklch(var(--pc)); border-color:oklch(var(--p)); }
#sop-table .tabulator-page[disabled] { opacity:.35; }
#sop-table .tabulator-page-size { background:oklch(var(--b1)); border:1px solid oklch(var(--b3)); color:oklch(var(--bc)); border-radius:.375rem; padding:.2rem .4rem; }
#sop-table .tabulator-frozen.tabulator-frozen-right { box-shadow:-2px 0 4px oklch(var(--b3)/.5); }
#sop-table .tabulator-frozen.tabulator-frozen-left  { box-shadow: 2px 0 4px oklch(var(--b3)/.5); }
#sop-table .tabulator-tableholder::-webkit-scrollbar { width:6px; height:6px; }
#sop-table .tabulator-tableholder::-webkit-scrollbar-track { background:oklch(var(--b2)); }
#sop-table .tabulator-tableholder::-webkit-scrollbar-thumb { background:oklch(var(--b3)); border-radius:3px; }

/* ── TomSelect ─────────────────────────────────────────────────────────── */
.ts-wrapper.single .ts-control { background:oklch(var(--b1)); border-color:oklch(var(--b3)); color:oklch(var(--bc)); border-radius:.375rem; min-height:2rem; padding:.25rem .5rem; font-size:.875rem; }
.ts-wrapper.single.focus .ts-control { border-color:oklch(var(--p)); outline:none; box-shadow:0 0 0 2px oklch(var(--p)/.2); }
.ts-dropdown { background:oklch(var(--b1)); border:1px solid oklch(var(--b3)); border-radius:.5rem; box-shadow:0 4px 16px rgba(0,0,0,.15); z-index:9999; }
.ts-dropdown .ts-option { color:oklch(var(--bc)); padding:.4rem .75rem; font-size:.875rem; }
.ts-dropdown .ts-option:hover, .ts-dropdown .ts-option.active { background:oklch(var(--b2)); }
.ts-dropdown .ts-option.selected { background:oklch(var(--p)/.15); color:oklch(var(--p)); }
.ts-wrapper .clear-button { color:oklch(var(--bc)/.4); }
.ts-wrapper .clear-button:hover { color:oklch(var(--bc)); }
.ts-control input { color:oklch(var(--bc)) !important; }
</style>
@endpush

@push('scripts')
@vite(['resources/js/modules/tabulator.js', 'resources/js/modules/tom-select.js'], 'build/backend')
<script>
function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

var SOP_STATUSES   = @json($statuses);
var SOP_TYPES      = @json($types);
var SOP_DEPTS      = @json($departments);
var SOP_API_URL    = '{{ route('backend.api.sop') }}';
var SOP_LS_COLS    = 'sop-list-hidden-cols';

var COLUMNS = [
    {
        title: 'Mã SOP', field: 'code', minWidth: 120, sorter: 'string', frozen: true,
        formatter: function (cell) {
            var d = cell.getRow().getData();
            return '<a href="' + esc(d.show_url) + '" class="font-mono font-semibold text-primary hover:underline">'
                + esc(d.code) + '</a>';
        },
    },
    {
        title: 'Tên quy trình', field: 'title', minWidth: 240, sorter: 'string',
        formatter: function (cell) {
            var d = cell.getRow().getData();
            return '<a href="' + esc(d.show_url) + '" class="hover:underline">' + esc(d.title) + '</a>';
        },
    },
    {
        title: 'Trạng thái', field: 'status', width: 130, hozAlign: 'center',
        formatter: function (cell) {
            var d = cell.getRow().getData();
            return '<span class="badge badge-sm ' + esc(d.status_badge) + '">' + esc(d.status_label) + '</span>';
        },
    },
    {
        title: 'Loại', field: 'type', width: 120, hozAlign: 'center',
        formatter: function (cell) {
            var v = cell.getRow().getData().type_label;
            return v ? '<span class="badge badge-outline badge-sm">' + esc(v) + '</span>'
                     : '<span class="opacity-30">—</span>';
        },
    },
    {
        title: 'Phiên bản', field: 'version', width: 90, hozAlign: 'center',
        formatter: function (cell) {
            var v = cell.getValue();
            return v > 0 ? 'v' + v : '<span class="opacity-30">—</span>';
        },
    },
    {
        title: 'Người phụ trách', field: 'owner_name', minWidth: 150,
        formatter: function (cell) {
            return esc(cell.getValue()) || '<span class="opacity-30">—</span>';
        },
    },
    {
        title: 'Phòng ban', field: 'department_name', minWidth: 150,
        formatter: function (cell) {
            return esc(cell.getValue()) || '<span class="opacity-30">—</span>';
        },
    },
    {
        title: 'Hiệu lực', field: 'effective_date', width: 110,
        formatter: function (cell) {
            return esc(cell.getValue()) || '<span class="opacity-30">—</span>';
        },
    },
    {
        title: 'Hết hạn', field: 'expired_date', width: 110,
        formatter: function (cell) {
            return esc(cell.getValue()) || '<span class="opacity-30">—</span>';
        },
    },
    {
        title: 'Ngày tạo', field: 'created_at', width: 110,
        formatter: function (cell) { return esc(cell.getValue()); },
    },
    {
        title: 'Thao tác', field: 'uuid', width: 90, hozAlign: 'center',
        headerSort: false, frozen: true,
        formatter: function (cell) {
            var d = cell.getRow().getData();
            return '<div class="flex gap-1 justify-center">'
                + '<a href="' + esc(d.show_url) + '" class="btn btn-ghost btn-xs btn-square" title="Xem">'
                + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>'
                + '</a>'
                + '<button class="btn btn-ghost btn-xs btn-square text-error" title="Xóa"'
                + ' data-url="' + esc(d.delete_url) + '" data-name="' + esc(d.code + ' – ' + d.title) + '"'
                + ' onclick="window.confirmDelete(this.dataset.url, this.dataset.name)">×</button>'
                + '</div>';
        },
    },
];

document.addEventListener('alpine:init', function () {
    var tableInst      = null;
    var statusTsInst   = null;
    var typeTsInst     = null;
    var deptTsInst     = null;

    Alpine.data('sopListPage', function () {
        return {
            filters: { search: '', status: '', type: '', department_id: '' },
            hiddenCols: [],

            get hasFilters() {
                var f = this.filters;
                return f.search || f.status || f.type || f.department_id;
            },

            get activeChips() {
                var chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.status) {
                    var st = SOP_STATUSES.find(function (s) { return s.value === f.status; });
                    chips.push({ key: 'status', label: 'Trạng thái: ' + (st ? st.text : f.status) });
                }
                if (f.type) {
                    var tp = SOP_TYPES.find(function (t) { return t.value === f.type; });
                    chips.push({ key: 'type', label: 'Loại: ' + (tp ? tp.text : f.type) });
                }
                if (f.department_id) {
                    var dept = SOP_DEPTS.find(function (d) { return d.value == f.department_id; });
                    chips.push({ key: 'department_id', label: 'Phòng ban: ' + (dept ? dept.text : f.department_id) });
                }
                return chips;
            },

            get toggleableCols() {
                return COLUMNS.filter(function (c) { return !c.frozen && c.field !== 'uuid'; })
                    .map(function (c) { return { field: c.field, title: c.title }; });
            },

            init: function () {
                var self = this;
                self.loadState();
                try { self.hiddenCols = JSON.parse(localStorage.getItem(SOP_LS_COLS) || '[]'); } catch (e) {}
                document.addEventListener('DOMContentLoaded', function () { self._setup(); }, { once: true });
            },

            _setup: function () {
                var self = this;

                tableInst = new window.Tabulator('#sop-table', {
                    ajaxURL:    SOP_API_URL,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams: function () {
                        var p = {}, f = self.filters;
                        if (f.search)       p.search        = f.search;
                        if (f.status)       p.status        = f.status;
                        if (f.type)         p.type          = f.type;
                        if (f.department_id) p.department_id = f.department_id;
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
                                counter: { showing: '', of: 'trong', rows: 'dòng', pages: 'trang' },
                            },
                        },
                    },
                    columns: COLUMNS,
                });

                // Restore hidden cols
                self.hiddenCols.forEach(function (field) {
                    if (tableInst) tableInst.hideColumn(field);
                });

                // TomSelect — Status
                statusTsInst = new window.TomSelect('#filter-status', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả trạng thái...',
                    maxOptions:     null,
                    plugins:        ['clear_button'],
                    options:        SOP_STATUSES.map(function (s) { return { value: s.value, text: s.text }; }),
                    items:          self.filters.status ? [self.filters.status] : [],
                    onChange: function (val) {
                        self.filters.status = val || '';
                        self.saveState();
                        self.refresh();
                    },
                    render: { no_results: function () { return '<div class="p-3 text-sm opacity-50">Không tìm thấy</div>'; } },
                });

                // TomSelect — Type
                typeTsInst = new window.TomSelect('#filter-type', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả loại...',
                    maxOptions:     null,
                    plugins:        ['clear_button'],
                    options:        SOP_TYPES.map(function (t) { return { value: t.value, text: t.text }; }),
                    items:          self.filters.type ? [self.filters.type] : [],
                    onChange: function (val) {
                        self.filters.type = val || '';
                        self.saveState();
                        self.refresh();
                    },
                    render: { no_results: function () { return '<div class="p-3 text-sm opacity-50">Không tìm thấy</div>'; } },
                });

                // TomSelect — Department
                deptTsInst = new window.TomSelect('#filter-department', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả phòng ban...',
                    maxOptions:     null,
                    plugins:        ['clear_button'],
                    options:        SOP_DEPTS.map(function (d) { return { value: d.value, text: d.text }; }),
                    items:          self.filters.department_id ? [String(self.filters.department_id)] : [],
                    onChange: function (val) {
                        self.filters.department_id = val || '';
                        self.saveState();
                        self.refresh();
                    },
                    render: { no_results: function () { return '<div class="p-3 text-sm opacity-50">Không tìm thấy</div>'; } },
                });
            },

            loadState: function () {
                var p = new URLSearchParams(location.search);
                if (p.has('q'))    this.filters.search        = p.get('q');
                if (p.has('st'))   this.filters.status        = p.get('st');
                if (p.has('tp'))   this.filters.type          = p.get('tp');
                if (p.has('dept')) this.filters.department_id = p.get('dept');
            },

            saveState: function () {
                var p = new URLSearchParams(), f = this.filters;
                if (f.search)        p.set('q',    f.search);
                if (f.status)        p.set('st',   f.status);
                if (f.type)          p.set('tp',   f.type);
                if (f.department_id) p.set('dept', f.department_id);
                var qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh: function () { if (tableInst) tableInst.replaceData(); },

            clearFilters: function () {
                this.filters = { search: '', status: '', type: '', department_id: '' };
                if (statusTsInst) statusTsInst.clear(true);
                if (typeTsInst)   typeTsInst.clear(true);
                if (deptTsInst)   deptTsInst.clear(true);
                this.saveState();
                this.refresh();
            },

            removeChip: function (key) {
                var self = this;
                if (key === 'search')        { self.filters.search = ''; }
                if (key === 'status')        { self.filters.status = '';        if (statusTsInst) statusTsInst.clear(true); }
                if (key === 'type')          { self.filters.type = '';          if (typeTsInst)   typeTsInst.clear(true); }
                if (key === 'department_id') { self.filters.department_id = ''; if (deptTsInst)   deptTsInst.clear(true); }
                this.saveState();
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
                try { localStorage.setItem(SOP_LS_COLS, JSON.stringify(this.hiddenCols)); } catch (e) {}
            },
        };
    });
});
</script>
@endpush
