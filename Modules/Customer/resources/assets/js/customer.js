/**
 * Modules/Customer/resources/assets/js/customer.js
 * Entry point cho module Customer
 *
 * Blade:
 *   @vite(['Modules/Customer/resources/assets/js/customer.js'], 'build/backend')
 */

import './pages/customer-show.js';
import './pages/customer-form.js';

// ── Config từ DOM ──────────────────────────────────────────────────────────

const CFG = JSON.parse(
    document.getElementById('customer-list-data')?.textContent ?? '{}'
);
const {
    apiListing,
    csrf,
    types         = [],
    stages        = [],
    sources       = [],
    canDelete     = false,
    orgLocked     = true,
    organizations = [],
} = CFG;

if (apiListing) {
    initListPage();
}

// ── List page ──────────────────────────────────────────────────────────────

function initListPage() {

    const LS_COLS = 'customer-list-hidden-cols';

    function esc(v) {
        if (v == null) return '';
        return String(v)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function isoDate(d) {
        return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    }

    const stageLabel  = v => stages.find(s => s.value == v)?.label  ?? '—';
    const stageBadge  = v => stages.find(s => s.value == v)?.badge  ?? 'badge-ghost';
    const sourceLabel = id => sources.find(s => s.id == id)?.label ?? '—';

    // ── Columns ─────────────────────────────────────────────────────────────

    const ALL_COLS = [
        {
            title: 'Khách hàng', field: 'display_name', minWidth: 220, sorter: 'string', frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                const badge = `<span class="badge badge-xs badge-soft ${esc(d.type_badge)}">${esc(d.type_label)}</span>`;
                const sub = d.company_name && d.type_value == 1
                    ? `<p class="text-xs text-base-content/40">${esc(d.company_name)}</p>` : '';
                return `<div>
                    <div class="flex items-center gap-1.5">
                        <a href="${esc(d.show_url)}" class="font-semibold text-sm hover:text-primary transition-colors">${esc(d.display_name)}</a>
                        ${badge}
                    </div>
                    ${sub}
                </div>`;
            },
        },
        ...(orgLocked ? [] : [{
            title: 'Tổ chức', field: 'organization_name', width: 160, headerSort: false,
            formatter(cell) {
                return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        }]),
        {
            title: 'SĐT / Email', field: 'primary_phone', width: 180, headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                const phone = d.primary_phone ? esc(d.primary_phone) : '';
                const email = d.primary_email
                    ? `<p class="text-xs text-base-content/40">${esc(d.primary_email)}</p>` : '';
                return phone
                    ? `<div>${phone}${email}</div>`
                    : `<span class="text-base-content/25 text-xs">—</span>`;
            },
        },
        {
            title: 'Tình trạng', field: 'stage_value', width: 150, headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                return `<span class="badge badge-sm badge-soft ${esc(d.stage_badge)}">${esc(d.stage_label)}</span>`;
            },
        },
        {
            title: 'Nguồn', field: 'source_label', width: 130, headerSort: false,
            formatter(cell) {
                const v = cell.getValue() || sourceLabel(cell.getRow().getData().source_id);
                return v ? esc(v) : '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Phụ trách', field: 'assignee_name', width: 140, headerSort: false,
            formatter(cell) {
                const v = cell.getValue();
                if (!v) return '<span class="text-base-content/25 text-xs">—</span>';
                const init = v.charAt(0).toUpperCase();
                return `<div class="flex items-center gap-1.5">
                    <div class="w-5 h-5 rounded-full bg-primary/20 text-primary text-xs font-bold flex items-center justify-center shrink-0">${esc(init)}</div>
                    <span class="text-sm truncate">${esc(v)}</span>
                </div>`;
            },
        },
        {
            title: 'Hoạt động cuối', field: 'last_activity_at', width: 155, sorter: 'datetime',
            formatter(cell) {
                const v = cell.getValue();
                if (!v) return '<span class="text-base-content/25 text-xs">—</span>';
                return `<span class="text-xs">${esc(v)}</span>`;
            },
        },
        {
            title: 'Tạo lúc', field: 'created_at', width: 130, sorter: 'datetime',
            formatter(cell) {
                const v = cell.getValue();
                return v ? `<span class="text-xs">${esc(v)}</span>`
                         : '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: '', field: '_actions', width: 80, headerSort: false, frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                const del = canDelete && d.delete_url
                    ? `<button class="btn btn-ghost btn-xs text-error" onclick="window.customerDeleteConfirm('${esc(d.delete_url)}','${esc(d.display_name)}')" title="Xóa">
                           <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                       </button>`
                    : '';
                return `<div class="flex items-center gap-0.5 justify-end">
                    <a href="${esc(d.edit_url)}" class="btn btn-ghost btn-xs" title="Sửa">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                    ${del}
                </div>`;
            },
        },
    ];

    const TOGGLEABLE_FIELDS = ['organization_name', 'primary_phone', 'source_label', 'assignee_name', 'last_activity_at', 'created_at'];

    // ── Alpine component ─────────────────────────────────────────────────────

    document.addEventListener('alpine:init', () => {
        Alpine.data('customerListPage', () => ({
            filters: {
                search:         '',
                type:           '',
                stage:          '',
                source_id:      '',
                organization_id: '',
                date_from:      '',
                date_to:        '',
            },
            hiddenCols: JSON.parse(localStorage.getItem(LS_COLS) ?? '[]'),
            get toggleableCols() {
                return ALL_COLS.filter(c => TOGGLEABLE_FIELDS.includes(c.field));
            },
            get hasFilters() {
                return Object.values(this.filters).some(v => v !== '');
            },
            get activeChips() {
                const chips = [];
                if (this.filters.search)         chips.push({ key: 'search',          label: `Tìm: ${this.filters.search}` });
                if (this.filters.organization_id) chips.push({ key: 'organization_id', label: organizations.find(o => o.value == this.filters.organization_id)?.text ?? this.filters.organization_id });
                if (this.filters.type)           chips.push({ key: 'type',            label: types.find(t => t.value == this.filters.type)?.label ?? this.filters.type });
                if (this.filters.stage)          chips.push({ key: 'stage',           label: stageLabel(this.filters.stage) });
                if (this.filters.source_id)      chips.push({ key: 'source_id',       label: sourceLabel(this.filters.source_id) });
                if (this.filters.date_from)      chips.push({ key: 'date_from',       label: `Từ ${this.filters.date_from}` });
                return chips;
            },
            onFilterChange() {
                window.customerTable?.setData(apiListing, this._params());
            },
            _params() {
                const p = {};
                if (this.filters.search)          p.search          = this.filters.search;
                if (this.filters.organization_id) p.organization_id = this.filters.organization_id;
                if (this.filters.type)            p.type            = this.filters.type;
                if (this.filters.stage)           p.stage           = this.filters.stage;
                if (this.filters.source_id)       p.source_id       = this.filters.source_id;
                if (this.filters.date_from)       p.date_from       = this.filters.date_from;
                if (this.filters.date_to)         p.date_to         = this.filters.date_to;
                return p;
            },
            clearSearch()  { this.filters.search = '';    this.onFilterChange(); },
            clearDate()    { this.filters.date_from = this.filters.date_to = ''; this.onFilterChange(); },
            removeChip(key){ this.filters[key] = '';      this.onFilterChange(); },
            reset()        { Object.keys(this.filters).forEach(k => this.filters[k] = ''); this.onFilterChange(); },
            toggleCol(field) {
                const idx = this.hiddenCols.indexOf(field);
                if (idx >= 0) this.hiddenCols.splice(idx, 1);
                else          this.hiddenCols.push(field);
                localStorage.setItem(LS_COLS, JSON.stringify(this.hiddenCols));
                this._syncColVisibility();
            },
            _syncColVisibility() {
                ALL_COLS.forEach(col => {
                    if (!TOGGLEABLE_FIELDS.includes(col.field)) return;
                    if (this.hiddenCols.includes(col.field)) window.customerTable?.hideColumn(col.field);
                    else                                     window.customerTable?.showColumn(col.field);
                });
            },
            init() {
                this._initTomSelects();
                this._initDatePicker();
                this._initTabulator();
            },
            _initTomSelects() {
                // key: tên trong `filters` — mặc định suy từ id ('filter-xxx' → 'xxx'),
                // truyền rõ khi khác (vd 'filter-source' → filters.source_id).
                const mkSelect = (id, opts, placeholder, key) => {
                    const el = document.getElementById(id);
                    if (!el || !window.TomSelect) return;
                    const filterKey = key || id.replace('filter-', '');
                    new window.TomSelect(el, {
                        options:     opts,
                        valueField:  'value',
                        labelField:  'text',
                        searchField: ['text'],
                        placeholder,
                        allowEmptyOption: true,
                        onChange: v => { this.filters[filterKey] = v; this.onFilterChange(); },
                    });
                };

                if (!orgLocked) {
                    mkSelect('filter-organization', [{ value: '', text: 'Tất cả tổ chức' }, ...organizations], '— Tổ chức —', 'organization_id');
                }
                mkSelect('filter-type',  [{ value: '', text: 'Tất cả loại' }, ...types.map(t => ({ value: String(t.value), text: t.label }))],  '— Loại —');
                mkSelect('filter-stage', [{ value: '', text: 'Tất cả tình trạng' }, ...stages.map(s => ({ value: String(s.value), text: s.label }))], '— Tình trạng —');
                mkSelect('filter-source',[{ value: '', text: 'Tất cả nguồn' }, ...sources.map(s => ({ value: String(s.id), text: s.label }))],  '— Nguồn —', 'source_id');
            },
            _initDatePicker() {
                const el = document.getElementById('filter-date');
                if (!el || !window.flatpickr) return;
                window.flatpickr(el, {
                    mode: 'range',
                    dateFormat: 'Y-m-d',
                    locale: { rangeSeparator: ' → ' },
                    onChange: dates => {
                        this.filters.date_from = dates[0] ? isoDate(dates[0]) : '';
                        this.filters.date_to   = dates[1] ? isoDate(dates[1]) : '';
                        if (dates.length === 2 || !dates.length) this.onFilterChange();
                    },
                });
            },
            _initTabulator() {
                const hiddenCols = this.hiddenCols;
                const cols = ALL_COLS.map(c => ({
                    ...c,
                    visible: !TOGGLEABLE_FIELDS.includes(c.field) || !hiddenCols.includes(c.field),
                }));

                window.customerTable = new Tabulator('#customer-table', {
                    ajaxURL:         apiListing,
                    ajaxContentType: 'json',
                    pagination:      true,
                    paginationMode:  'remote',
                    sortMode:        'remote',
                    paginationSize:  25,
                    paginationSizeSelector: [15, 25, 50, 100],
                    layout:          'fitDataFill',
                    responsiveLayout: false,
                    columns:         cols,
                    ajaxResponse(url, params, response) {
                        return { data: response.data, last_page: response.last_page };
                    },
                    locale: 'vi-VN',
                    langs: {
                        'vi-VN': {
                            pagination: {
                                page_size: 'Số hàng:',
                                first: '«', last: '»', prev: '‹', next: '›',
                                all: 'Tất cả',
                                counter: { showing: 'Hiển thị', of: '/', rows: 'bản ghi', pages: 'trang' },
                            },
                        },
                    },
                    renderComplete: () => this._syncColVisibility(),
                });
            },
        }));
    });

    // ── Delete confirm ───────────────────────────────────────────────────────

    window.customerDeleteConfirm = function(url, name) {
        const modal = document.getElementById('deleteModal');
        const nameEl = document.getElementById('deleteItemName');
        const btn = document.getElementById('confirmDeleteBtn');
        if (!modal || !nameEl || !btn) return;
        nameEl.textContent = name;
        btn.onclick = () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = url;
            form.innerHTML = `<input name="_token" value="${csrf}"><input name="_method" value="DELETE">`;
            document.body.appendChild(form);
            form.submit();
        };
        modal.showModal();
    };
}

