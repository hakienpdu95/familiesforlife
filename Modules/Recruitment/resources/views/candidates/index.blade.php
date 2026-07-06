@extends('layouts.backend')

@section('title', 'Ứng viên — Recruitment')


@section('content')
<div x-data="rcCandidateList" class="p-6 space-y-4">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold">Danh sách ứng viên</h1>
            <p class="text-sm opacity-60 mt-0.5">Quản lý hồ sơ ứng viên trong hệ thống ATS</p>
        </div>
        @can('create', \Modules\Recruitment\Models\RcCandidate::class)
        <a href="{{ route('backend.recruitment.candidates.create') }}" class="btn btn-primary btn-sm gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Thêm ứng viên
        </a>
        @endcan
    </div>

    {{-- Filter bar --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4">
            <div class="flex flex-wrap gap-3 items-end">

                <div class="flex-1 min-w-48">
                    <label class="label label-text text-xs font-medium pb-1">Tìm kiếm</label>
                    <input type="text" id="rc-cand-search"
                           x-model="filters.search"
                           @input.debounce.400ms="saveState(); refresh()"
                           placeholder="Tên, email, điện thoại, chức danh..."
                           class="input input-bordered input-sm w-full">
                </div>

                <div class="w-44">
                    <label class="label label-text text-xs font-medium pb-1">Trạng thái</label>
                    <select id="rc-cand-status" class="hidden"></select>
                </div>

                <div class="w-44">
                    <label class="label label-text text-xs font-medium pb-1">Nguồn</label>
                    <select id="rc-cand-source" class="hidden"></select>
                </div>

                <div class="w-52">
                    <label class="label label-text text-xs font-medium pb-1">Ngày tạo</label>
                    <input type="text" id="rc-cand-date" placeholder="Chọn khoảng thời gian..." class="input input-bordered input-sm w-full cursor-pointer" readonly>
                </div>

                <button @click="clearAll()" class="btn btn-ghost btn-sm" x-show="hasFilters">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Xóa lọc
                </button>
            </div>

            {{-- Active chips --}}
            <template x-if="activeChips.length > 0">
                <div class="flex flex-wrap gap-2 mt-2 pt-2 border-t border-base-200">
                    <template x-for="chip in activeChips" :key="chip.key">
                        <span class="badge badge-outline gap-1 text-xs">
                            <span x-text="chip.label"></span>
                            <button @click="removeChip(chip.key)" class="hover:text-error">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </span>
                    </template>
                </div>
            </template>
        </div>
    </div>

    {{-- Table --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div id="rc-candidate-table"></div>
    </div>

</div>
@endsection

@push('styles')
<style>
#rc-candidate-table .tabulator { border:none; border-radius:0; background:transparent; font-size:.8125rem; }
#rc-candidate-table .tabulator-header { background:oklch(var(--b2)); border-bottom:1px solid oklch(var(--b3)); color:oklch(var(--bc)/.65); font-weight:600; font-size:.75rem; text-transform:uppercase; letter-spacing:.04em; }
#rc-candidate-table .tabulator-col { background:transparent; border-right:1px solid oklch(var(--b3)); }
#rc-candidate-table .tabulator-row { background:oklch(var(--b1)); border-bottom:1px solid oklch(var(--b2)); }
#rc-candidate-table .tabulator-row:hover { background:oklch(var(--b2)/.6); }
#rc-candidate-table .tabulator-row .tabulator-cell { border-right:1px solid oklch(var(--b2)); color:oklch(var(--bc)); padding:.5rem .75rem; }
#rc-candidate-table .tabulator-footer { background:oklch(var(--b2)/.5); border-top:1px solid oklch(var(--b3)); }
#rc-candidate-table .tabulator-page { background:transparent; border:1px solid oklch(var(--b3)); color:oklch(var(--bc)); border-radius:.375rem; padding:.2rem .5rem; margin:0 1px; }
#rc-candidate-table .tabulator-page.active { background:oklch(var(--p)); color:oklch(var(--pc)); border-color:oklch(var(--p)); }
#rc-candidate-table .tabulator-page[disabled] { opacity:.35; }
#rc-candidate-table .tabulator-page-size { background:oklch(var(--b1)); border:1px solid oklch(var(--b3)); color:oklch(var(--bc)); border-radius:.375rem; padding:.2rem .4rem; }
.ts-wrapper.single .ts-control { background:oklch(var(--b1)); border-color:oklch(var(--b3)); color:oklch(var(--bc)); border-radius:.375rem; min-height:2rem; padding:.25rem .5rem; font-size:.875rem; }
.ts-wrapper.single.focus .ts-control { border-color:oklch(var(--p)); outline:none; }
.ts-dropdown { background:oklch(var(--b1)); border:1px solid oklch(var(--b3)); border-radius:.5rem; box-shadow:0 4px 16px rgba(0,0,0,.15); z-index:9999; }
.ts-dropdown .ts-option { color:oklch(var(--bc)); padding:.4rem .75rem; font-size:.875rem; }
.ts-dropdown .ts-option:hover,.ts-dropdown .ts-option.active { background:oklch(var(--b2)); }
.ts-dropdown .ts-option.selected { background:oklch(var(--p)/.15); color:oklch(var(--p)); }
.ts-wrapper .clear-button { color:oklch(var(--bc)/.4); }
.ts-control input { color:oklch(var(--bc)) !important; }
</style>
@endpush

@push('scripts')
@vite(['resources/js/modules/tabulator.js', 'resources/js/modules/tom-select.js', 'resources/js/modules/flatpickr.js'], 'build/backend')
<script>
function esc(v) {
    if (v == null) return '';
    return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function isoDate(d) { return d ? d.toISOString().slice(0,10) : ''; }

var API_URL  = '{{ route('backend.api.recruitment.candidates') }}';
var STATUSES = @json($statuses);
var SOURCES  = @json($sources);
var LS_COLS  = 'rc-candidate-hidden-cols';

var COLUMNS = [
    {
        title: 'Ứng viên', field: 'full_name', minWidth: 220, sorter: 'string', frozen: true,
        formatter: function(cell) {
            var d = cell.getRow().getData();
            return '<a href="' + esc(d.show_url) + '" class="font-medium hover:text-primary">' + esc(d.full_name) + '</a>'
                + '<div class="text-xs opacity-50">' + esc(d.email) + '</div>';
        },
    },
    { title: 'Chức danh hiện tại', field: 'current_title', minWidth: 160, formatter: function(cell) { return esc(cell.getValue()) || '<span class="opacity-30">—</span>'; } },
    { title: 'Công ty', field: 'current_company', minWidth: 150, formatter: function(cell) { return esc(cell.getValue()) || '<span class="opacity-30">—</span>'; } },
    { title: 'Kinh nghiệm', field: 'years_experience', width: 110, hozAlign: 'center', formatter: function(cell) { var v = cell.getValue(); return v != null ? esc(v) + ' năm' : '<span class="opacity-30">—</span>'; } },
    {
        title: 'Nguồn', field: 'source', width: 120,
        formatter: function(cell) { return esc(cell.getRow().getData().source_label) || esc(cell.getValue()); },
    },
    {
        title: 'Trạng thái', field: 'status', width: 130, hozAlign: 'center',
        formatter: function(cell) {
            var s = cell.getValue(), label = esc(cell.getRow().getData().status_label);
            if (s === 'active')      return '<span class="badge badge-success badge-sm">' + label + '</span>';
            if (s === 'hired')       return '<span class="badge badge-info badge-sm">' + label + '</span>';
            if (s === 'blacklisted') return '<span class="badge badge-error badge-sm">' + label + '</span>';
            return '<span class="badge badge-ghost badge-sm">' + label + '</span>';
        },
    },
    { title: 'Số đơn', field: 'applications_count', width: 90, hozAlign: 'center', sorter: 'number' },
    { title: 'Ngày thêm', field: 'created_at', width: 120, hozAlign: 'center' },
    {
        title: 'Thao tác', field: 'id', width: 90, hozAlign: 'center', headerSort: false, frozen: true,
        formatter: function(cell) {
            var d = cell.getRow().getData();
            return '<div class="flex gap-1 justify-center">'
                + '<a href="' + esc(d.show_url) + '" class="btn btn-ghost btn-xs btn-square" title="Xem">'
                +   '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>'
                + '</a>'
                + '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square" title="Sửa">'
                +   '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                + '</a>'
                + '</div>';
        },
    },
];

document.addEventListener('alpine:init', function() {
    var tableInst   = null;
    var statusTs    = null;
    var sourceTs    = null;
    var dateFpInst  = null;
    var settingPreset = false;

    Alpine.data('rcCandidateList', function() {
        return {
            filters: { search: '', status: '', source: '', date_from: '', date_to: '' },
            hiddenCols: [],

            get hasFilters() {
                var f = this.filters;
                return f.search || f.status || f.source || f.date_from || f.date_to;
            },

            get activeChips() {
                var chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.status) {
                    var st = STATUSES.find(function(s) { return s.value === f.status; });
                    chips.push({ key: 'status', label: st ? st.text : f.status });
                }
                if (f.source) {
                    var sc = SOURCES.find(function(s) { return s.value === f.source; });
                    chips.push({ key: 'source', label: sc ? sc.text : f.source });
                }
                if (f.date_from || f.date_to) {
                    chips.push({ key: 'date', label: (f.date_from || '...') + ' → ' + (f.date_to || '...') });
                }
                return chips;
            },

            init: function() {
                var self = this;
                self.loadState();
                try { self.hiddenCols = JSON.parse(localStorage.getItem(LS_COLS) || '[]'); } catch(e) {}
                document.addEventListener('DOMContentLoaded', function() { self._setup(); }, { once: true });
            },

            _setup: function() {
                var self = this;
                tableInst = new window.Tabulator('#rc-candidate-table', {
                    ajaxURL: API_URL,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams: function() {
                        var p = {}, f = self.filters;
                        if (f.search)   p.search   = f.search;
                        if (f.status)   p.status   = f.status;
                        if (f.source)   p.source   = f.source;
                        if (f.date_from) p.date_from = f.date_from;
                        if (f.date_to)   p.date_to   = f.date_to;
                        return p;
                    },
                    ajaxResponse: function(_u, _p, res) { return res; },
                    pagination: true, paginationMode: 'remote', paginationSize: 25,
                    paginationSizeSelector: [10,25,50,100], paginationCounter: 'rows',
                    sortMode: 'remote', initialSort: [{ column: 'created_at', dir: 'desc' }],
                    layout: 'fitColumns', responsiveLayout: 'collapse', movableColumns: true,
                    height: '68vh',
                    locale: 'vi-VN',
                    langs: { 'vi-VN': { pagination: { page_size: 'Dòng/trang', first: '«', last: '»', prev: '‹', next: '›', counter: { showing:'', of:'trong', rows:'dòng', pages:'trang' } } } },
                    columns: COLUMNS,
                });

                self.hiddenCols.forEach(function(f) { tableInst.hideColumn(f); });

                statusTs = new window.TomSelect('#rc-cand-status', {
                    dropdownParent: 'body', placeholder: 'Tất cả...', maxOptions: null,
                    searchField: ['text'], plugins: ['clear_button'],
                    options: STATUSES.map(function(s) { return { value: s.value, text: s.text }; }),
                    items: self.filters.status ? [self.filters.status] : [],
                    onChange: function(val) { self.filters.status = val || ''; self.saveState(); self.refresh(); },
                    render: { no_results: function() { return '<div class="p-3 text-sm opacity-50">Không tìm thấy</div>'; } },
                });

                sourceTs = new window.TomSelect('#rc-cand-source', {
                    dropdownParent: 'body', placeholder: 'Tất cả...', maxOptions: null,
                    searchField: ['text'], plugins: ['clear_button'],
                    options: SOURCES.map(function(s) { return { value: s.value, text: s.text }; }),
                    items: self.filters.source ? [self.filters.source] : [],
                    onChange: function(val) { self.filters.source = val || ''; self.saveState(); self.refresh(); },
                    render: { no_results: function() { return '<div class="p-3 text-sm opacity-50">Không tìm thấy</div>'; } },
                });

                if (window.initDateRangePicker) {
                    dateFpInst = window.initDateRangePicker('#rc-cand-date', {
                        disableMobile: true,
                        onChange: function(dates) {
                            if (settingPreset) return;
                            if (dates.length === 2) {
                                self.filters.date_from = isoDate(dates[0]);
                                self.filters.date_to   = isoDate(dates[1]);
                            } else {
                                self.filters.date_from = '';
                                self.filters.date_to   = '';
                            }
                            self.saveState(); self.refresh();
                        },
                    });
                    if (self.filters.date_from && self.filters.date_to && dateFpInst) {
                        settingPreset = true;
                        dateFpInst.setDate([self.filters.date_from, self.filters.date_to], false);
                        settingPreset = false;
                    }
                }
            },

            loadState: function() {
                var p = new URLSearchParams(location.search);
                if (p.has('q'))    this.filters.search    = p.get('q');
                if (p.has('st'))   this.filters.status    = p.get('st');
                if (p.has('sc'))   this.filters.source    = p.get('sc');
                if (p.has('from')) this.filters.date_from = p.get('from');
                if (p.has('to'))   this.filters.date_to   = p.get('to');
            },

            saveState: function() {
                var p = new URLSearchParams(), f = this.filters;
                if (f.search)    p.set('q',    f.search);
                if (f.status)    p.set('st',   f.status);
                if (f.source)    p.set('sc',   f.source);
                if (f.date_from) p.set('from', f.date_from);
                if (f.date_to)   p.set('to',   f.date_to);
                var qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh: function() { if (tableInst) tableInst.replaceData(); },

            removeChip: function(key) {
                var self = this;
                if (key === 'search') { self.filters.search = ''; }
                else if (key === 'status') { self.filters.status = ''; if (statusTs) statusTs.clear(true); }
                else if (key === 'source') { self.filters.source = ''; if (sourceTs) sourceTs.clear(true); }
                else if (key === 'date') {
                    self.filters.date_from = ''; self.filters.date_to = '';
                    if (dateFpInst) dateFpInst.clear(false);
                    self.saveState(); self.refresh(); return;
                }
                self.saveState(); self.refresh();
            },

            clearAll: function() {
                this.filters = { search: '', status: '', source: '', date_from: '', date_to: '' };
                if (statusTs) statusTs.clear(true);
                if (sourceTs) sourceTs.clear(true);
                if (dateFpInst) dateFpInst.clear(false);
                this.saveState(); this.refresh();
            },
        };
    });
});
</script>
@endpush
