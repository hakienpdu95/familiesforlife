@extends('layouts.backend')
@section('title', 'Phân quyền theo phạm vi')


@section('content')
<div x-data="roleScopeListPage({{ Js::from([
    'apiUrl'      => route('backend.api.role-scopes'),
    'roles'       => $roles->map(fn($r) => ['value' => $r->id, 'text' => $r->name])->values()->all(),
    'scopeLevels' => $scopeLevels,
    'statuses'    => $statuses,
    'canDelete'   => auth()->user()->can('delete', \Modules\RoleScope\Models\UserRoleScope::class),
]) }})">

    {{-- ── Page header ──────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Phân quyền theo phạm vi</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Gán role cho user với phạm vi chi nhánh hoặc phòng ban cụ thể</p>
        </div>
        <div class="flex items-center gap-2">
            {{-- Column visibility --}}
            <div class="dropdown dropdown-end">
                <label tabindex="0" class="btn btn-ghost btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                    </svg>
                    Cột
                </label>
                <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box shadow-lg border border-base-200 w-52 z-50 p-2">
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

            @can('create', \Modules\RoleScope\Models\UserRoleScope::class)
            <a href="{{ route('backend.role-scopes.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Cấp quyền mới
            </a>
            @endcan
        </div>
    </div>

    {{-- ── Stat cards ────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Tổng phân quyền</div>
            <div class="stat-value text-2xl">{{ number_format($totalAll) }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Còn hiệu lực</div>
            <div class="stat-value text-2xl text-success">{{ number_format($totalActive) }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Đã hết hạn</div>
            <div class="stat-value text-2xl text-error">{{ number_format($totalExpired) }}</div>
        </div>
        <div class="stat bg-base-100 border border-base-200 rounded-xl py-3 px-4 shadow-sm">
            <div class="stat-title text-xs">Phạm vi toàn org</div>
            <div class="stat-value text-2xl text-info">{{ number_format($totalOrgScope) }}</div>
        </div>
    </div>

    {{-- ── Filter bar ────────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3 px-4 space-y-3">
            <div class="flex flex-wrap gap-3 items-end">

                <div class="form-control flex-1 min-w-52">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Tìm kiếm</span>
                        <span class="label-text-alt text-xs text-base-content/40">Tên user, email, tên role</span>
                    </label>
                    <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                        <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input id="filter-search" type="text"
                               x-model="filters.search"
                               @input.debounce.350ms="onFilterChange()"
                               placeholder="Nhập từ khóa..."
                               class="grow bg-transparent outline-none text-sm"/>
                    </div>
                </div>

                <div class="form-control min-w-40">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Role</span></label>
                    <select id="filter-role" class="select select-sm select-bordered"></select>
                </div>

                <div class="form-control min-w-40">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Phạm vi</span></label>
                    <select id="filter-scope-level" class="select select-sm select-bordered"></select>
                </div>

                <div class="form-control min-w-40">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Trạng thái</span></label>
                    <select id="filter-status" class="select select-sm select-bordered"></select>
                </div>

                <button @click="clearFilters()" class="btn btn-ghost btn-sm" :class="{ 'opacity-40 pointer-events-none': !hasFilters }">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Xóa lọc
                </button>
            </div>

            {{-- Active chips --}}
            <div x-show="activeChips.length > 0" class="flex flex-wrap gap-1.5">
                <template x-for="chip in activeChips" :key="chip.key">
                    <span class="badge badge-outline badge-sm gap-1 cursor-pointer hover:badge-error transition-colors"
                          @click="removeChip(chip.key)">
                        <span x-text="chip.label" class="max-w-40 truncate"></span>
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </span>
                </template>
            </div>
        </div>
    </div>

    {{-- ── Table ─────────────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div id="role-scope-table"></div>
    </div>

</div>
@endsection

@push('styles')
<style>
#role-scope-table .tabulator { border:none; border-radius:0; background:transparent; font-size:.8125rem; }
#role-scope-table .tabulator-header { background:oklch(var(--b2)); border-bottom:1px solid oklch(var(--b3)); color:oklch(var(--bc)/.65); font-weight:600; font-size:.75rem; text-transform:uppercase; letter-spacing:.04em; }
#role-scope-table .tabulator-col { background:transparent; border-right:1px solid oklch(var(--b3)); }
#role-scope-table .tabulator-row { background:oklch(var(--b1)); border-bottom:1px solid oklch(var(--b2)); }
#role-scope-table .tabulator-row:hover { background:oklch(var(--b2)/.6); }
#role-scope-table .tabulator-row .tabulator-cell { border-right:1px solid oklch(var(--b2)); color:oklch(var(--bc)); padding:.5rem .75rem; }
#role-scope-table .tabulator-footer { background:oklch(var(--b2)/.5); border-top:1px solid oklch(var(--b3)); }
#role-scope-table .tabulator-page { background:transparent; border:1px solid oklch(var(--b3)); color:oklch(var(--bc)); border-radius:.375rem; padding:.2rem .5rem; margin:0 1px; }
#role-scope-table .tabulator-page.active { background:oklch(var(--p)); color:oklch(var(--pc)); border-color:oklch(var(--p)); }
#role-scope-table .tabulator-page[disabled] { opacity:.35; }
#role-scope-table .tabulator-page-size { background:oklch(var(--b1)); border:1px solid oklch(var(--b3)); color:oklch(var(--bc)); border-radius:.375rem; padding:.2rem .4rem; }
#role-scope-table .tabulator-frozen.tabulator-frozen-right { box-shadow:-2px 0 4px oklch(var(--b3)/.5); }
#role-scope-table .tabulator-frozen.tabulator-frozen-left  { box-shadow: 2px 0 4px oklch(var(--b3)/.5); }
#role-scope-table .tabulator-tableholder::-webkit-scrollbar { width:6px; height:6px; }
#role-scope-table .tabulator-tableholder::-webkit-scrollbar-track { background:oklch(var(--b2)); }
#role-scope-table .tabulator-tableholder::-webkit-scrollbar-thumb { background:oklch(var(--b3)); border-radius:3px; }

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
    return String(v).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

document.addEventListener('alpine:init', function () {
    var tableInst      = null;
    var roleTsInst     = null;
    var scopeTsInst    = null;
    var statusTsInst   = null;
    var LS_COLS        = 'role-scope-list-hidden-cols';

    Alpine.data('roleScopeListPage', function (cfg) {
        var API_URL    = cfg.apiUrl;
        var ROLES      = cfg.roles;
        var SCOPE_LVLS = cfg.scopeLevels;
        var STATUSES   = cfg.statuses;
        var CAN_DELETE = cfg.canDelete;

        var COLUMNS = [
            {
                title: 'User', field: 'user_name', minWidth: 200, sorter: 'string', frozen: true,
                formatter: function (cell) {
                    var d = cell.getRow().getData();
                    return '<a href="' + esc(d.show_url) + '" class="font-medium hover:underline">' + esc(d.user_name) + '</a>'
                        + '<div class="text-xs text-base-content/50">' + esc(d.user_email) + '</div>';
                },
            },
            {
                title: 'Role', field: 'role_name', width: 150,
                formatter: function (cell) {
                    return '<span class="badge badge-outline badge-sm">' + esc(cell.getValue()) + '</span>';
                },
            },
            {
                title: 'Phạm vi', field: 'scope_level', width: 130, hozAlign: 'center',
                formatter: function (cell) {
                    var v = cell.getValue();
                    if (v === 'org')    return '<span class="badge badge-info badge-sm">Toàn org</span>';
                    if (v === 'branch') return '<span class="badge badge-accent badge-sm">Chi nhánh</span>';
                    return '<span class="badge badge-secondary badge-sm">Phòng ban</span>';
                },
            },
            {
                title: 'Chi tiết phạm vi', field: 'scope_branch_name', minWidth: 160,
                formatter: function (cell) {
                    var d = cell.getRow().getData();
                    if (d.scope_level === 'org') return '<span class="opacity-40">Toàn tổ chức</span>';
                    if (d.scope_level === 'branch') return esc(d.scope_branch_name || '—') + (d.scope_branch_code ? ' <span class="badge badge-ghost badge-xs font-mono">' + esc(d.scope_branch_code) + '</span>' : '');
                    return esc(d.scope_dept_name || '—') + (d.scope_dept_code ? ' <span class="badge badge-ghost badge-xs font-mono">' + esc(d.scope_dept_code) + '</span>' : '');
                },
            },
            {
                title: 'Trạng thái', field: 'is_expired', width: 130, hozAlign: 'center', headerSort: false,
                formatter: function (cell) {
                    var d = cell.getRow().getData();
                    if (d.is_expired) return '<span class="badge badge-error badge-sm">Đã hết hạn</span>';
                    if (d.expires_at) return '<span class="badge badge-warning badge-sm">Có hạn</span>';
                    return '<span class="badge badge-success badge-sm">Vĩnh viễn</span>';
                },
            },
            {
                title: 'Hết hạn', field: 'expires_at', width: 140,
                formatter: function (cell) {
                    return esc(cell.getValue()) || '<span class="opacity-30">—</span>';
                },
            },
            {
                title: 'Cấp bởi', field: 'granted_by_name', width: 130,
                formatter: function (cell) {
                    return esc(cell.getValue()) || '<span class="opacity-30">—</span>';
                },
            },
            {
                title: 'Cấp lúc', field: 'granted_at', width: 140,
                formatter: function (cell) {
                    return esc(cell.getValue()) || '<span class="opacity-30">—</span>';
                },
            },
            {
                title: 'Thao tác', field: 'id', width: 110, hozAlign: 'center', headerSort: false, frozen: true,
                formatter: function (cell) {
                    var d   = cell.getRow().getData();
                    var out = '<div class="flex gap-1 justify-center">'
                        + '<a href="' + esc(d.show_url) + '" class="btn btn-ghost btn-xs btn-square" title="Xem">'
                        +   '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>'
                        + '</a>'
                        + '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square" title="Sửa">'
                        +   '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                        + '</a>';
                    if (CAN_DELETE) {
                        out += '<button class="btn btn-ghost btn-xs btn-square text-error" title="Thu hồi"'
                            +   ' data-url="' + esc(d.delete_url) + '" data-name="' + esc(d.user_name + ' / ' + d.role_name) + '"'
                            +   ' onclick="window.confirmDelete(this.dataset.url, this.dataset.name)">'
                            +   '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>'
                            + '</button>';
                    }
                    out += '</div>';
                    return out;
                },
            },
        ];

        return {
            filters: { search: '', role_id: '', scope_level: '', status: '' },
            hiddenCols: [],

            get hasFilters() {
                var f = this.filters;
                return !!(f.search || f.role_id || f.scope_level || f.status);
            },

            get activeChips() {
                var chips = [], f = this.filters;
                if (f.search)      chips.push({ key: 'search',      label: 'Tìm: ' + f.search });
                if (f.role_id) {
                    var r = ROLES.find(function (x) { return String(x.value) === String(f.role_id); });
                    chips.push({ key: 'role_id', label: 'Role: ' + (r ? r.text : f.role_id) });
                }
                if (f.scope_level) {
                    var s = SCOPE_LVLS.find(function (x) { return x.value === f.scope_level; });
                    chips.push({ key: 'scope_level', label: 'Phạm vi: ' + (s ? s.text : f.scope_level) });
                }
                if (f.status) {
                    var st = STATUSES.find(function (x) { return x.value === f.status; });
                    chips.push({ key: 'status', label: st ? st.text : f.status });
                }
                return chips;
            },

            get toggleableCols() {
                return COLUMNS.filter(function (c) { return !c.frozen; }).map(function (c) {
                    return { field: c.field, title: c.title };
                });
            },

            init: function () {
                var self = this;
                self.loadState();
                try { self.hiddenCols = JSON.parse(localStorage.getItem(LS_COLS) || '[]'); } catch (e) {}
                document.addEventListener('DOMContentLoaded', function () { self._setup(); }, { once: true });
            },

            _setup: function () {
                var self = this;

                tableInst = new window.Tabulator('#role-scope-table', {
                    ajaxURL:    API_URL,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams: function () {
                        var p = {}, f = self.filters;
                        if (f.search)      p.search      = f.search;
                        if (f.role_id)     p.role_id     = f.role_id;
                        if (f.scope_level) p.scope_level = f.scope_level;
                        if (f.status)      p.status      = f.status;
                        return p;
                    },
                    ajaxResponse: function (_u, _p, res) { return res; },
                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:    'remote',
                    initialSort: [{ column: 'granted_at', dir: 'desc' }],
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

                self.hiddenCols.forEach(function (field) { tableInst.hideColumn(field); });

                roleTsInst = new window.TomSelect('#filter-role', {
                    dropdownParent: 'body', placeholder: 'Tất cả role...', maxOptions: null,
                    searchField: ['text'], plugins: ['clear_button'],
                    options: ROLES,
                    items: self.filters.role_id ? [String(self.filters.role_id)] : [],
                    onChange: function (val) { self.filters.role_id = val || ''; self.saveState(); self.refresh(); },
                    render: { no_results: function () { return '<div class="no-results p-3 text-sm opacity-50">Không tìm thấy</div>'; } },
                });

                scopeTsInst = new window.TomSelect('#filter-scope-level', {
                    dropdownParent: 'body', placeholder: 'Tất cả phạm vi...', maxOptions: null,
                    searchField: ['text'], plugins: ['clear_button'],
                    options: SCOPE_LVLS,
                    items: self.filters.scope_level ? [self.filters.scope_level] : [],
                    onChange: function (val) { self.filters.scope_level = val || ''; self.saveState(); self.refresh(); },
                    render: { no_results: function () { return '<div class="no-results p-3 text-sm opacity-50">Không tìm thấy</div>'; } },
                });

                statusTsInst = new window.TomSelect('#filter-status', {
                    dropdownParent: 'body', placeholder: 'Tất cả trạng thái...', maxOptions: null,
                    searchField: ['text'], plugins: ['clear_button'],
                    options: STATUSES,
                    items: self.filters.status ? [self.filters.status] : [],
                    onChange: function (val) { self.filters.status = val || ''; self.saveState(); self.refresh(); },
                    render: { no_results: function () { return '<div class="no-results p-3 text-sm opacity-50">Không tìm thấy</div>'; } },
                });
            },

            loadState: function () {
                var p = new URLSearchParams(location.search);
                if (p.has('q'))  this.filters.search      = p.get('q');
                if (p.has('r'))  this.filters.role_id      = p.get('r');
                if (p.has('sl')) this.filters.scope_level  = p.get('sl');
                if (p.has('st')) this.filters.status       = p.get('st');
            },

            saveState: function () {
                var p = new URLSearchParams(), f = this.filters;
                if (f.search)      p.set('q',  f.search);
                if (f.role_id)     p.set('r',  f.role_id);
                if (f.scope_level) p.set('sl', f.scope_level);
                if (f.status)      p.set('st', f.status);
                var qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh: function () { if (tableInst) tableInst.replaceData(); },

            onFilterChange: function () { this.saveState(); this.refresh(); },

            clearFilters: function () {
                this.filters = { search: '', role_id: '', scope_level: '', status: '' };
                if (document.getElementById('filter-search')) document.getElementById('filter-search').value = '';
                if (roleTsInst)   roleTsInst.clear(true);
                if (scopeTsInst)  scopeTsInst.clear(true);
                if (statusTsInst) statusTsInst.clear(true);
                this.saveState();
                this.refresh();
            },

            removeChip: function (key) {
                var self = this;
                if (key === 'search')      { self.filters.search = ''; if (document.getElementById('filter-search')) document.getElementById('filter-search').value = ''; }
                if (key === 'role_id')     { self.filters.role_id = '';     if (roleTsInst)   roleTsInst.clear(true); }
                if (key === 'scope_level') { self.filters.scope_level = ''; if (scopeTsInst)  scopeTsInst.clear(true); }
                if (key === 'status')      { self.filters.status = '';      if (statusTsInst) statusTsInst.clear(true); }
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
                try { localStorage.setItem(LS_COLS, JSON.stringify(this.hiddenCols)); } catch (e) {}
            },
        };
    });
});
</script>
@endpush
