/**
 * pages/organization-index.js
 * Alpine component + Tabulator + TomSelect + Flatpickr cho index.blade.php tổ chức.
 *
 * Server data truyền vào qua x-data="orgListPage({{ Js::from([...]) }})".
 * Globals: window.Alpine, window.TomSelect, window.Tabulator,
 *          window.initDateRangePicker, meta[name=csrf-token]
 */

// ── Utilities ─────────────────────────────────────────────────────────────────

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function isoDate(d) {
    return d.getFullYear() + '-'
        + String(d.getMonth() + 1).padStart(2, '0') + '-'
        + String(d.getDate()).padStart(2, '0');
}

function displayDate(iso) {
    if (!iso) return '';
    const p = iso.split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
}

function presetRange(preset) {
    const now = new Date(), y = now.getFullYear(), m = now.getMonth(), d = now.getDate();
    if (preset === 'today')  return [new Date(y, m, d), new Date(y, m, d)];
    if (preset === 'week') {
        const dow = now.getDay() === 0 ? 6 : now.getDay() - 1;
        return [new Date(y, m, d - dow), new Date(y, m, d - dow + 6)];
    }
    if (preset === 'month') return [new Date(y, m, 1), new Date(y, m + 1, 0)];
    if (preset === 'year')  return [new Date(y, 0, 1), new Date(y, 11, 31)];
    return [null, null];
}

function tsSetPlaceholder(ts, text) {
    ts.settings.placeholder = text;
    if (ts.control_input) ts.control_input.setAttribute('placeholder', text);
}

// ── Tabulator columns factory ─────────────────────────────────────────────────

function buildColumns(canDelete) {
    return [
        {
            title: 'Tên tổ chức', field: 'name', minWidth: 220, sorter: 'string', frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<div>'
                    + '<a href="' + esc(d.show_url) + '" class="font-semibold text-sm hover:text-primary transition-colors">' + esc(d.name) + '</a>'
                    + '<p class="text-xs text-base-content/40 font-mono mt-0.5">' + esc(d.slug) + '</p>'
                    + '</div>';
            },
        },
        {
            title: 'Mã số thuế', field: 'tax_code', width: 140, headerSort: false,
            formatter(cell) {
                const v = cell.getValue();
                return v ? '<span class="font-mono text-xs">' + esc(v) + '</span>'
                         : '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Ngành nghề', field: 'industry', minWidth: 150, sorter: 'string',
            formatter(cell) {
                return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Email / SĐT', field: 'email', minWidth: 180, headerSort: false,
            formatter(cell) {
                const d = cell.getRow().getData();
                const parts = [];
                if (d.email) parts.push('<p class="text-sm">' + esc(d.email) + '</p>');
                if (d.phone) parts.push('<p class="text-xs text-base-content/50">' + esc(d.phone) + '</p>');
                return parts.length ? parts.join('') : '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Tỉnh/Thành', field: 'province_name', width: 160, sorter: 'string',
            formatter(cell) {
                return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Phường/Xã', field: 'ward_name', width: 160, headerSort: false,
            formatter(cell) {
                return esc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Thành viên', field: 'members_count', width: 110, hozAlign: 'center', sorter: 'number',
            formatter(cell) {
                return '<span class="badge badge-ghost badge-sm">' + esc(cell.getValue()) + ' người</span>';
            },
        },
        {
            title: 'Trạng thái', field: 'status_value', width: 140, hozAlign: 'center', sorter: 'string',
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="badge badge-sm badge-soft ' + esc(d.status_badge) + '">'
                    + esc(d.status_label) + '</span>';
            },
        },
        {
            title: 'Ngày tạo', field: 'created_at', width: 110, hozAlign: 'center', sorter: 'string',
        },
        {
            title: 'Thao tác', field: 'id', width: 110, hozAlign: 'center', headerSort: false, frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<div class="flex items-center justify-center gap-1">';

                html += '<a href="' + esc(d.show_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-info" title="Xem">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>'
                    + '</a>';

                html += '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                    + '</a>';

                if (canDelete && d.can_delete) {
                    html += '<button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa"'
                        + ' data-url="' + esc(d.delete_url) + '" data-name="' + esc(d.name) + '"'
                        + ' onclick="window.orgDeleteConfirm(this.dataset.url, this.dataset.name)">'
                        + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>'
                        + '</button>';
                }

                html += '</div>';
                return html;
            },
        },
    ];
}

// ── AJAX Delete ───────────────────────────────────────────────────────────────

let pendingDeleteUrl = null;

window.orgDeleteConfirm = function (url, name) {
    pendingDeleteUrl = url;
    const nameEl = document.getElementById('deleteItemName');
    if (nameEl) nameEl.textContent = '"' + name + '"';
    document.getElementById('deleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async function () {
        if (!pendingDeleteUrl) return;

        const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        this.disabled    = true;
        this.textContent = 'Đang xóa...';

        try {
            const res  = await fetch(pendingDeleteUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':     csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept':           'application/json',
                    'Content-Type':     'application/x-www-form-urlencoded',
                },
                body: '_method=DELETE',
            });

            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                document.getElementById('deleteModal')?.close();
                window.orgTable?.replaceData();
            } else {
                alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[org] delete failed', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled    = false;
            this.textContent = 'Xóa';
            pendingDeleteUrl = null;
        }
    });
});

// ── Alpine component ──────────────────────────────────────────────────────────

const LS_COLS = 'org-list-hidden-cols';

document.addEventListener('alpine:init', () => {

    Alpine.data('orgListPage', (serverData = {}) => {
        const {
            apiUrl    = '',
            wardsApi  = '',
            provinces = [],
            statuses  = [],
            canDelete = false,
        } = serverData;

        const COLUMNS = buildColumns(canDelete);

        // Lib instances (non-reactive)
        let tableInst    = null;
        let provTsInst   = null;
        let wardTsInst   = null;
        let statusTsInst = null;
        let dateFpInst   = null;
        const wardsCache = {};
        let settingPreset = false;

        return {
            filters: {
                search: '', province_code: '', ward_code: '',
                status: '', date_from: '', date_to: '',
            },
            wards:            [],
            activeDatePreset: '',
            hiddenCols:       [],

            get toggleableCols() {
                return COLUMNS
                    .filter(c => c.field !== 'id' && c.field !== 'name')
                    .map(c => ({ field: c.field, title: c.title }));
            },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.province_code || f.ward_code || f.status || f.date_from);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.province_code) {
                    const prov = provinces.find(p => p.province_code === f.province_code);
                    chips.push({ key: 'province', label: prov ? prov.name : f.province_code });
                }
                if (f.ward_code) {
                    const ward = this.wards.find(w => w.ward_code === f.ward_code);
                    chips.push({ key: 'ward', label: ward ? ward.name : f.ward_code });
                }
                if (f.status) {
                    const st = statuses.find(s => s.value === f.status);
                    chips.push({ key: 'status', label: st ? st.text : f.status });
                }
                if (f.date_from && f.date_to)
                    chips.push({ key: 'date', label: displayDate(f.date_from) + ' — ' + displayDate(f.date_to) });
                return chips;
            },

            // ── Lifecycle ──────────────────────────────────────────────────
            init() {
                this.loadState();
                try { this.hiddenCols = JSON.parse(localStorage.getItem(LS_COLS) || '[]'); } catch (_) {}
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                // ── Tabulator ──────────────────────────────────────────────
                tableInst = new window.Tabulator('#org-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)        p.search        = f.search;
                        if (f.province_code) p.province_code = f.province_code;
                        if (f.ward_code)     p.ward_code     = f.ward_code;
                        if (f.status)        p.status        = f.status;
                        if (f.date_from)     p.date_from     = f.date_from;
                        if (f.date_to)       p.date_to       = f.date_to;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[org] API error', error),

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>'
                        + '<p class="text-sm">Không có tổ chức nào</p></div>',
                });

                window.orgTable = tableInst;
                self.hiddenCols.forEach(field => tableInst.hideColumn(field));

                // ── Province TomSelect ─────────────────────────────────────
                provTsInst = new window.TomSelect('#filter-province', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả tỉnh/thành...',
                    maxOptions:     null,
                    searchField:    ['text'],
                    plugins:        ['clear_button'],
                    options:        provinces.map(p => ({ value: p.province_code, text: p.name })),
                    items:          self.filters.province_code ? [self.filters.province_code] : [],
                    onChange(val) {
                        self.filters.province_code = val || '';
                        self.onProvinceChange(val || '');
                    },
                    render: { no_results: () => '<div class="no-results p-3 text-sm opacity-50">Không tìm thấy</div>' },
                });

                // ── Ward TomSelect ─────────────────────────────────────────
                wardTsInst = new window.TomSelect('#filter-ward', {
                    dropdownParent: 'body',
                    placeholder:    self.filters.province_code ? 'Tất cả phường/xã...' : 'Chọn tỉnh trước...',
                    maxOptions:     null,
                    searchField:    ['text'],
                    plugins:        ['clear_button'],
                    onChange(val) {
                        self.filters.ward_code = val || '';
                        self.saveState();
                        self.refresh();
                    },
                    render: { no_results: () => '<div class="no-results p-3 text-sm opacity-50">Không tìm thấy</div>' },
                });
                if (!self.filters.province_code) wardTsInst.disable();

                // ── Status TomSelect ───────────────────────────────────────
                statusTsInst = new window.TomSelect('#filter-status', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả trạng thái...',
                    plugins:        ['clear_button'],
                    options:        statuses,
                    items:          self.filters.status ? [self.filters.status] : [],
                    onChange(val) {
                        self.filters.status = val || '';
                        self.saveState();
                        self.refresh();
                    },
                });

                // ── Flatpickr date range ───────────────────────────────────
                dateFpInst = window.initDateRangePicker('#filter-date', {
                    disableMobile: true,
                    onChange(dates) {
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

                if (self.filters.province_code) {
                    self._loadWards(self.filters.province_code, self.filters.ward_code, true);
                }
            },

            // ── URL state persistence ──────────────────────────────────────
            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))    this.filters.search        = p.get('q');
                if (p.has('prov')) this.filters.province_code = p.get('prov');
                if (p.has('ward')) this.filters.ward_code     = p.get('ward');
                if (p.has('st'))   this.filters.status        = p.get('st');
                if (p.has('from')) this.filters.date_from     = p.get('from');
                if (p.has('to'))   this.filters.date_to       = p.get('to');
                if (p.has('dpre')) this.activeDatePreset      = p.get('dpre');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)        p.set('q',    f.search);
                if (f.province_code) p.set('prov', f.province_code);
                if (f.ward_code)     p.set('ward', f.ward_code);
                if (f.status)        p.set('st',   f.status);
                if (f.date_from)     p.set('from', f.date_from);
                if (f.date_to)       p.set('to',   f.date_to);
                if (this.activeDatePreset) p.set('dpre', this.activeDatePreset);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            // ── Actions ───────────────────────────────────────────────────
            refresh()         { tableInst?.replaceData(); },
            onFilterChange()  { this.saveState(); this.refresh(); },
            clearSearch()     { this.filters.search = ''; this.saveState(); this.refresh(); },

            setDatePreset(preset) {
                const range = presetRange(preset);
                if (!range[0]) return;
                settingPreset         = true;
                this.activeDatePreset = preset;
                this.filters.date_from = isoDate(range[0]);
                this.filters.date_to   = isoDate(range[1]);
                dateFpInst?.setDate([range[0], range[1]], false);
                settingPreset = false;
                this.saveState();
                this.refresh();
            },

            clearDate() {
                this.activeDatePreset  = '';
                this.filters.date_from = '';
                this.filters.date_to   = '';
                dateFpInst?.clear(false);
                this.saveState();
                this.refresh();
            },

            onProvinceChange(code) {
                this.filters.ward_code = '';
                if (wardTsInst) { wardTsInst.clear(true); wardTsInst.clearOptions(); wardTsInst.disable(); }
                this.wards = [];
                this.saveState();
                this.refresh();
                if (code) this._loadWards(code, null, false);
            },

            _loadWards(code, pendingWard, silent) {
                if (wardsCache[code]) {
                    this._applyWards(wardsCache[code], pendingWard, silent);
                    return;
                }
                tsSetPlaceholder(wardTsInst, 'Đang tải...');
                fetch(wardsApi + '/' + encodeURIComponent(code) + '/wards', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                })
                    .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                    .then(data => { wardsCache[code] = data; this._applyWards(data, pendingWard, silent); })
                    .catch(() => tsSetPlaceholder(wardTsInst, 'Lỗi tải dữ liệu'));
            },

            _applyWards(data, pendingWard, silent) {
                this.wards = data;
                if (!wardTsInst) return;
                data.forEach(w => wardTsInst.addOption({ value: w.ward_code, text: w.name }));
                tsSetPlaceholder(wardTsInst, 'Tất cả phường/xã...');
                wardTsInst.enable();
                if (pendingWard) {
                    wardTsInst.setValue(pendingWard, true);
                    this.filters.ward_code = pendingWard;
                }
                if (!silent) this.refresh();
            },

            removeChip(key) {
                if (key === 'search')   this.filters.search = '';
                if (key === 'province') {
                    this.filters.province_code = '';
                    provTsInst?.clear(true);
                    this.filters.ward_code = '';
                    if (wardTsInst) { wardTsInst.clear(true); wardTsInst.clearOptions(); wardTsInst.disable(); }
                    this.wards = [];
                }
                if (key === 'ward')   { this.filters.ward_code = ''; wardTsInst?.clear(true); }
                if (key === 'status') { this.filters.status = '';    statusTsInst?.clear(true); }
                if (key === 'date')   { this.clearDate(); return; }
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', province_code: '', ward_code: '', status: '', date_from: '', date_to: '' };
                this.activeDatePreset = '';
                this.wards = [];
                provTsInst?.clear(true);
                if (wardTsInst) { wardTsInst.clear(true); wardTsInst.clearOptions(); wardTsInst.disable(); }
                statusTsInst?.clear(true);
                dateFpInst?.clear(false);
                history.replaceState(null, '', location.pathname);
                this.refresh();
            },

            toggleCol(field) {
                if (this.hiddenCols.includes(field)) {
                    this.hiddenCols = this.hiddenCols.filter(f => f !== field);
                    tableInst?.showColumn(field);
                } else {
                    this.hiddenCols.push(field);
                    tableInst?.hideColumn(field);
                }
                try { localStorage.setItem(LS_COLS, JSON.stringify(this.hiddenCols)); } catch (_) {}
            },
        };
    });
});
