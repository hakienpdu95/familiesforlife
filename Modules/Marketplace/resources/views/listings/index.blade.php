@extends('layouts.backend')

@section('title', 'Marketplace Center — Tin đăng')


@section('content')
<div x-data="mktListingPage" class="px-6 py-4 space-y-4">

    {{-- ── Header ──────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold">Marketplace — Tin đăng</h1>
            <p class="text-sm opacity-60 mt-0.5">Quản lý tất cả tin tuyển dụng / dự án trên Marketplace</p>
        </div>
        <div class="flex gap-2">
            @can('marketplace.manage')
            <a href="{{ route('backend.marketplace.org-approvals.index') }}"
               class="btn btn-outline btn-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Duyệt tổ chức
            </a>
            @endcan
            @can('marketplace.create')
            <a href="{{ route('backend.marketplace.listings.create') }}"
               class="btn btn-primary btn-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tạo tin mới
            </a>
            @endcan
        </div>
    </div>

    {{-- ── Filter bar ───────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body p-4">
            <div class="flex flex-wrap gap-3 items-end">

                {{-- Search --}}
                <div class="flex-1 min-w-48">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Tìm kiếm</span></label>
                    <input type="text" id="filter-search"
                           x-model="filters.search"
                           @input.debounce.400ms="saveState(); refresh()"
                           placeholder="Tiêu đề, địa điểm..."
                           class="input input-bordered input-sm w-full">
                </div>

                {{-- Status --}}
                <div class="w-44">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Trạng thái</span></label>
                    <select id="filter-status" class="hidden"></select>
                </div>

                {{-- Listing type --}}
                <div class="w-36">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Loại tin</span></label>
                    <select id="filter-listing-type" class="hidden"></select>
                </div>

                {{-- Work type --}}
                <div class="w-36">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Hình thức</span></label>
                    <select id="filter-work-type" class="hidden"></select>
                </div>

                {{-- Date range --}}
                <div class="w-52">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Ngày tạo</span></label>
                    <input type="text" id="filter-date" placeholder="Chọn khoảng ngày..."
                           class="input input-bordered input-sm w-full" readonly>
                </div>

                {{-- Clear --}}
                <div class="pt-1">
                    <button @click="clearFilters()" x-show="hasFilters"
                            class="btn btn-ghost btn-sm gap-1 text-error">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Xóa bộ lọc
                    </button>
                </div>
            </div>

            {{-- Active filter chips --}}
            <div class="flex flex-wrap gap-1.5 mt-2" x-show="activeChips.length > 0">
                <template x-for="chip in activeChips" :key="chip.key">
                    <span class="badge badge-outline badge-sm gap-1 pr-0.5">
                        <span x-text="chip.label"></span>
                        <button @click="removeChip(chip.key)" class="hover:text-error">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                </template>
            </div>
        </div>
    </div>

    {{-- ── Table ────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm overflow-hidden">
        <div id="mkt-listing-table"></div>
    </div>

</div>
@endsection

@push('styles')
<style>
/* ── Tabulator ─────────────────────────────────────────────────────────── */
#mkt-listing-table .tabulator { border:none; border-radius:0; background:transparent; font-size:.8125rem; }
#mkt-listing-table .tabulator-header { background:oklch(var(--b2)); border-bottom:1px solid oklch(var(--b3)); color:oklch(var(--bc)/.65); font-weight:600; font-size:.75rem; text-transform:uppercase; letter-spacing:.04em; }
#mkt-listing-table .tabulator-col { background:transparent; border-right:1px solid oklch(var(--b3)); }
#mkt-listing-table .tabulator-row { background:oklch(var(--b1)); border-bottom:1px solid oklch(var(--b2)); }
#mkt-listing-table .tabulator-row:hover { background:oklch(var(--b2)/.6); }
#mkt-listing-table .tabulator-row .tabulator-cell { border-right:1px solid oklch(var(--b2)); color:oklch(var(--bc)); padding:.5rem .75rem; }
#mkt-listing-table .tabulator-footer { background:oklch(var(--b2)/.5); border-top:1px solid oklch(var(--b3)); }
#mkt-listing-table .tabulator-page { background:transparent; border:1px solid oklch(var(--b3)); color:oklch(var(--bc)); border-radius:.375rem; padding:.2rem .5rem; margin:0 1px; }
#mkt-listing-table .tabulator-page.active { background:oklch(var(--p)); color:oklch(var(--pc)); border-color:oklch(var(--p)); }
#mkt-listing-table .tabulator-page[disabled] { opacity:.35; }
#mkt-listing-table .tabulator-page-size { background:oklch(var(--b1)); border:1px solid oklch(var(--b3)); color:oklch(var(--bc)); border-radius:.375rem; padding:.2rem .4rem; }
#mkt-listing-table .tabulator-frozen.tabulator-frozen-right { box-shadow:-2px 0 4px oklch(var(--b3)/.5); }
#mkt-listing-table .tabulator-frozen.tabulator-frozen-left  { box-shadow: 2px 0 4px oklch(var(--b3)/.5); }
#mkt-listing-table .tabulator-tableholder::-webkit-scrollbar { width:6px; height:6px; }
#mkt-listing-table .tabulator-tableholder::-webkit-scrollbar-track { background:oklch(var(--b2)); }
#mkt-listing-table .tabulator-tableholder::-webkit-scrollbar-thumb { background:oklch(var(--b3)); border-radius:3px; }

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
@vite(['resources/js/modules/tabulator.js', 'resources/js/modules/tom-select.js', 'resources/js/modules/flatpickr.js'], 'build/backend')
<script>
function esc(v) {
    if (v == null) return '';
    return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

var COLUMNS = [
    {
        title: 'Tiêu đề', field: 'title', minWidth: 260, frozen: true, sorter: 'string',
        formatter: function(cell) {
            var d = cell.getRow().getData();
            var syncBadge = d.jp_sync_status === 'out_of_sync'
                ? '<span class="badge badge-warning badge-xs ml-1" title="Lỗi thời với JP">!</span>' : '';
            return '<a href="'+esc(d.show_url)+'" class="font-medium hover:text-primary">'+esc(d.title)+'</a>'+syncBadge;
        },
    },
    {
        title: 'Trạng thái', field: 'status', width: 130, hozAlign: 'center',
        formatter: function(cell) {
            var d = cell.getRow().getData();
            return '<span class="badge badge-sm '+esc(d.status_badge)+'">'+esc(d.status_label)+'</span>';
        },
    },
    {
        title: 'Loại', field: 'listing_type', width: 110, hozAlign: 'center',
        formatter: function(cell) {
            var v = cell.getValue();
            var colors = {job:'badge-info', project:'badge-accent', resource:'badge-secondary'};
            var labels = {job:'Việc làm', project:'Dự án', resource:'Freelancer'};
            return '<span class="badge badge-outline badge-sm '+(colors[v]||'')+'">'+esc(labels[v]||v)+'</span>';
        },
    },
    {
        title: 'Hình thức', field: 'work_type', width: 120,
        formatter: function(cell) { return esc(cell.getValue()) || '<span class="opacity-30">—</span>'; },
    },
    {
        title: 'Địa điểm', field: 'location', minWidth: 130,
        formatter: function(cell) { return esc(cell.getValue()) || '<span class="opacity-30">—</span>'; },
    },
    {
        title: 'Ứng viên', field: 'application_count', width: 90, hozAlign: 'right',
        formatter: function(cell) {
            var v = cell.getValue();
            return v > 0 ? '<span class="font-semibold text-primary">'+v+'</span>' : '<span class="opacity-30">0</span>';
        },
    },
    {
        title: 'Người đăng', field: 'posted_by_name', minWidth: 130,
        formatter: function(cell) { return esc(cell.getValue()) || '<span class="opacity-30">—</span>'; },
    },
    {
        title: 'Hết hạn', field: 'expire_at', width: 110,
        formatter: function(cell) { return esc(cell.getValue()) || '<span class="opacity-30">—</span>'; },
    },
    {
        title: 'Ngày tạo', field: 'created_at', width: 110,
        formatter: function(cell) { return esc(cell.getValue()); },
    },
    {
        title: 'Thao tác', field: 'id', width: 110, hozAlign: 'center',
        headerSort: false, frozen: true,
        formatter: function(cell) {
            var d = cell.getRow().getData();
            var canEdit = @json(auth()->user()?->hasPermissionTo('marketplace.edit') ?? false);
            var btns = '<div class="flex gap-1 justify-center">';
            btns += '<a href="'+esc(d.show_url)+'" class="btn btn-ghost btn-xs btn-square" title="Xem"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>';
            if (canEdit && d.status !== 'closed') {
                btns += '<a href="'+esc(d.edit_url)+'" class="btn btn-ghost btn-xs btn-square" title="Sửa"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>';
                btns += '<button class="btn btn-ghost btn-xs btn-square text-warning" data-url="'+esc(d.close_url)+'" data-title="'+esc(d.title)+'" onclick="window.confirmClose(this.dataset.url,this.dataset.title)" title="Đóng"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg></button>';
            }
            btns += '</div>';
            return btns;
        },
    },
];

document.addEventListener('alpine:init', function() {
    var tableInst    = null;
    var statusTs     = null;
    var typeTs       = null;
    var workTs       = null;
    var dateFpInst   = null;
    var settingPreset = false;

    var API_URL   = '{{ route('backend.api.marketplace.listings') }}';
    var STATUSES  = @json($statuses);
    var TYPES     = @json($listingTypes);
    var WORKTYPES = @json($workTypes);
    var LS_COLS   = 'mkt-listing-hidden-cols';

    Alpine.data('mktListingPage', function() {
        return {
            filters: { search: '', status: '', listing_type: '', work_type: '', date_from: '', date_to: '' },
            hiddenCols: [],

            get hasFilters() {
                var f = this.filters;
                return f.search || f.status || f.listing_type || f.work_type || f.date_from || f.date_to;
            },

            get activeChips() {
                var chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.status) {
                    var s = STATUSES.find(function(x) { return x.value === f.status; });
                    chips.push({ key: 'status', label: s ? s.text : f.status });
                }
                if (f.listing_type) {
                    var t = TYPES.find(function(x) { return x.value === f.listing_type; });
                    chips.push({ key: 'listing_type', label: t ? t.text : f.listing_type });
                }
                if (f.work_type) {
                    var w = WORKTYPES.find(function(x) { return x.value === f.work_type; });
                    chips.push({ key: 'work_type', label: w ? w.text : f.work_type });
                }
                if (f.date_from || f.date_to) {
                    chips.push({ key: 'date', label: (f.date_from||'') + ' – ' + (f.date_to||'') });
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

                tableInst = new window.Tabulator('#mkt-listing-table', {
                    ajaxURL:    API_URL,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams: function() {
                        var p = {}, f = self.filters;
                        if (f.search)       p.search       = f.search;
                        if (f.status)       p.status       = f.status;
                        if (f.listing_type) p.listing_type = f.listing_type;
                        if (f.work_type)    p.work_type    = f.work_type;
                        if (f.date_from)    p.date_from    = f.date_from;
                        if (f.date_to)      p.date_to      = f.date_to;
                        return p;
                    },
                    ajaxResponse: function(_u, _p, res) { return res; },
                    pagination: true, paginationMode: 'remote',
                    paginationSize: 25, paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter: 'rows',
                    sortMode: 'remote',
                    initialSort: [{ column: 'created_at', dir: 'desc' }],
                    layout: 'fitColumns', responsiveLayout: 'collapse',
                    movableColumns: true, height: '68vh',
                    locale: 'vi-VN',
                    langs: { 'vi-VN': { pagination: {
                        page_size: 'Dòng/trang', page_title: 'Trang',
                        first: '«', last: '»', prev: '‹', next: '›',
                        counter: { showing: '', of: 'trong', rows: 'dòng', pages: 'trang' },
                    }}},
                    columns: COLUMNS,
                });

                // TomSelect filters
                statusTs = new window.TomSelect('#filter-status', {
                    dropdownParent: 'body', placeholder: 'Tất cả trạng thái',
                    plugins: ['clear_button'], maxOptions: null,
                    options: STATUSES,
                    items: self.filters.status ? [self.filters.status] : [],
                    onChange: function(v) { self.filters.status = v || ''; self.saveState(); self.refresh(); },
                    render: { no_results: function() { return '<div class="p-3 text-sm opacity-50">Không tìm thấy</div>'; }},
                });

                typeTs = new window.TomSelect('#filter-listing-type', {
                    dropdownParent: 'body', placeholder: 'Tất cả loại tin',
                    plugins: ['clear_button'], maxOptions: null,
                    options: TYPES,
                    items: self.filters.listing_type ? [self.filters.listing_type] : [],
                    onChange: function(v) { self.filters.listing_type = v || ''; self.saveState(); self.refresh(); },
                    render: { no_results: function() { return '<div class="p-3 text-sm opacity-50">Không tìm thấy</div>'; }},
                });

                workTs = new window.TomSelect('#filter-work-type', {
                    dropdownParent: 'body', placeholder: 'Tất cả',
                    plugins: ['clear_button'], maxOptions: null,
                    options: WORKTYPES,
                    items: self.filters.work_type ? [self.filters.work_type] : [],
                    onChange: function(v) { self.filters.work_type = v || ''; self.saveState(); self.refresh(); },
                    render: { no_results: function() { return '<div class="p-3 text-sm opacity-50">Không tìm thấy</div>'; }},
                });

                dateFpInst = window.initDateRangePicker('#filter-date', {
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
                        self.saveState();
                        self.refresh();
                    },
                });

                // Restore hidden cols
                self.hiddenCols.forEach(function(f) { tableInst.hideColumn(f); });
            },

            refresh: function() { if (tableInst) tableInst.replaceData(); },

            clearFilters: function() {
                this.filters = { search: '', status: '', listing_type: '', work_type: '', date_from: '', date_to: '' };
                if (statusTs) statusTs.clear(true);
                if (typeTs)   typeTs.clear(true);
                if (workTs)   workTs.clear(true);
                if (dateFpInst) dateFpInst.clear();
                this.saveState();
                this.refresh();
            },

            removeChip: function(key) {
                var self = this;
                var clear = {
                    search:       function() { self.filters.search = ''; },
                    status:       function() { self.filters.status = '';       if (statusTs) statusTs.clear(true); },
                    listing_type: function() { self.filters.listing_type = ''; if (typeTs)   typeTs.clear(true); },
                    work_type:    function() { self.filters.work_type = '';    if (workTs)   workTs.clear(true); },
                    date:         function() { self.filters.date_from = ''; self.filters.date_to = ''; if (dateFpInst) dateFpInst.clear(); },
                };
                if (clear[key]) clear[key]();
                this.saveState();
                this.refresh();
            },

            loadState: function() {
                var p = new URLSearchParams(location.search);
                if (p.has('q'))    this.filters.search       = p.get('q');
                if (p.has('st'))   this.filters.status       = p.get('st');
                if (p.has('lt'))   this.filters.listing_type = p.get('lt');
                if (p.has('wt'))   this.filters.work_type    = p.get('wt');
                if (p.has('from')) this.filters.date_from    = p.get('from');
                if (p.has('to'))   this.filters.date_to      = p.get('to');
            },

            saveState: function() {
                var p = new URLSearchParams(), f = this.filters;
                if (f.search)       p.set('q',    f.search);
                if (f.status)       p.set('st',   f.status);
                if (f.listing_type) p.set('lt',   f.listing_type);
                if (f.work_type)    p.set('wt',   f.work_type);
                if (f.date_from)    p.set('from', f.date_from);
                if (f.date_to)      p.set('to',   f.date_to);
                var qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },
        };
    });
});

function isoDate(d) {
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
}

window.confirmClose = function(url, title) {
    if (!confirm('Đóng tin "' + title + '"? Hành động này không thể hoàn tác.')) return;
    fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
    }).then(function(r) {
        if (r.ok) { window.Toast?.success('Đã đóng tin đăng.'); if (window.Tabulator) document.dispatchEvent(new Event('mkt:refresh')); }
        else window.Toast?.error('Có lỗi xảy ra.');
    });
};
document.addEventListener('mkt:refresh', function() {
    var t = document.querySelector('#mkt-listing-table')?._tabulator;
    if (t) t.replaceData();
});
</script>
@endpush
