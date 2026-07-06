/**
 * pages/job-posting-index.js
 * Alpine component + Tabulator + TomSelect + Flatpickr cho trang danh sách tin tuyển dụng.
 */

// ── Utilities ─────────────────────────────────────────────────────────────────

function jpEsc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function jpIsoDate(d) {
    return d.getFullYear() + '-'
        + String(d.getMonth() + 1).padStart(2, '0') + '-'
        + String(d.getDate()).padStart(2, '0');
}

function jpDisplayDate(iso) {
    if (!iso) return '';
    const p = iso.split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
}

function jpPresetRange(preset) {
    const now = new Date(), y = now.getFullYear(), m = now.getMonth(), d = now.getDate();
    if (preset === 'month')   return [new Date(y, m, 1), new Date(y, m + 1, 0)];
    if (preset === 'quarter') {
        const q = Math.floor(m / 3);
        return [new Date(y, q * 3, 1), new Date(y, q * 3 + 3, 0)];
    }
    if (preset === 'year') return [new Date(y, 0, 1), new Date(y, 11, 31)];
    return [null, null];
}

function jpSalaryDisplay(d) {
    if (!d.salary_is_visible) return '<span class="text-xs text-base-content/40">Thỏa thuận</span>';
    if (!d.salary_min && !d.salary_max) return '<span class="text-base-content/25 text-xs">—</span>';
    const fmt = v => v ? new Intl.NumberFormat('vi-VN', { notation: 'compact', maximumFractionDigits: 1 }).format(v) : '';
    const cur = jpEsc(d.salary_currency || 'VND');
    if (d.salary_min && d.salary_max) return jpEsc(fmt(d.salary_min)) + '–' + jpEsc(fmt(d.salary_max)) + ' ' + cur;
    return (d.salary_min ? jpEsc(fmt(d.salary_min)) : '') + (d.salary_max ? jpEsc(fmt(d.salary_max)) : '') + ' ' + cur;
}

// ── Column definitions ────────────────────────────────────────────────────────

function buildJobPostColumns(canDelete) {
    return [
        {
            title: 'Tin tuyển dụng', field: 'title', minWidth: 260, sorter: 'string', frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                const statusBadge = '<span class="badge badge-xs badge-soft ' + jpEsc(d.status_badge) + ' ml-1">' + jpEsc(d.status_label) + '</span>';
                return '<div>'
                    + '<div class="flex items-center gap-1 flex-wrap">'
                    + '<a href="' + jpEsc(d.show_url) + '" class="font-semibold text-sm hover:text-primary transition-colors">' + jpEsc(d.title) + '</a>'
                    + statusBadge
                    + '</div>'
                    + '<p class="text-xs font-mono text-base-content/40">' + jpEsc(d.code) + '</p>'
                    + '</div>';
            },
        },
        {
            title: 'Loại hình', field: 'employment_type', width: 130, hozAlign: 'center',
            formatter(cell) {
                const d = cell.getRow().getData();
                return d.employment_type_label
                    ? '<span class="badge badge-xs badge-ghost">' + jpEsc(d.employment_type_label) + '</span>'
                    : '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Hình thức', field: 'work_arrangement', width: 110, hozAlign: 'center',
            formatter(cell) {
                const d = cell.getRow().getData();
                return d.work_arrangement_label
                    ? '<span class="badge badge-xs badge-outline">' + jpEsc(d.work_arrangement_label) + '</span>'
                    : '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Cấp độ', field: 'experience_level', width: 120, hozAlign: 'center',
            formatter(cell) {
                const d = cell.getRow().getData();
                return d.experience_level_label
                    ? '<span class="text-xs">' + jpEsc(d.experience_level_label) + '</span>'
                    : '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Phòng ban', field: 'department_name', minWidth: 140,
            formatter(cell) {
                return jpEsc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Người phụ trách', field: 'owner_name', minWidth: 140,
            formatter(cell) {
                return jpEsc(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>';
            },
        },
        {
            title: 'Lương', field: 'salary_min', width: 160,
            formatter(cell) {
                return jpSalaryDisplay(cell.getRow().getData());
            },
        },
        {
            title: 'HC/Ứng tuyển', field: 'application_count', width: 120, hozAlign: 'center', sorter: 'number',
            formatter(cell) {
                const d = cell.getRow().getData();
                return '<span class="text-xs">'
                    + '<span class="font-medium">' + (d.hired_count || 0) + '</span>'
                    + '<span class="text-base-content/40">/' + (d.headcount || 1) + '</span>'
                    + ' · <span class="text-info">' + (d.application_count || 0) + ' đơn</span>'
                    + '</span>';
            },
        },
        {
            title: 'MKT', field: 'mkt_sync_status', width: 90, hozAlign: 'center',
            formatter(cell) {
                const d = cell.getRow().getData();
                if (!d.publish_to_marketplace) return '<span class="text-base-content/20 text-xs">—</span>';
                if (d.mkt_sync_status === 'synced')
                    return '<span class="badge badge-xs badge-success">Đồng bộ</span>';
                if (d.mkt_sync_status === 'out_of_sync')
                    return '<span class="badge badge-xs badge-warning">Lỗi thời</span>';
                return '<span class="badge badge-xs badge-ghost">Chờ sync</span>';
            },
        },
        {
            title: 'Publish / Hết hạn', field: 'published_at', width: 140, hozAlign: 'center',
            formatter(cell) {
                const d = cell.getRow().getData();
                if (!d.published_at) return '<span class="text-base-content/25 text-xs">Chưa publish</span>';
                const expire = d.expire_at ? ' <span class="text-xs text-error/60">→ ' + jpEsc(d.expire_at) + '</span>' : '';
                return '<span class="text-xs">' + jpEsc(d.published_at) + expire + '</span>';
            },
        },
        {
            title: 'Ngày tạo', field: 'created_at', width: 100, hozAlign: 'center',
            formatter(cell) {
                return '<span class="text-xs">' + jpEsc(cell.getValue()) + '</span>';
            },
        },
        {
            title: 'Thao tác', field: 'id', width: 100, hozAlign: 'center', headerSort: false, frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<div class="flex items-center justify-center gap-1">';
                html += '<a href="' + jpEsc(d.show_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-info" title="Xem">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>'
                    + '</a>';
                html += '<a href="' + jpEsc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                    + '</a>';
                if (canDelete) {
                    html += '<button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa"'
                        + ' data-url="' + jpEsc(d.delete_url) + '" data-name="' + jpEsc(d.title) + '"'
                        + ' onclick="window.jpDeleteConfirm(this.dataset.url,this.dataset.name)">'
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

let jpPendingDeleteUrl = null;

window.jpDeleteConfirm = function (url, name) {
    jpPendingDeleteUrl = url;
    const nameEl = document.getElementById('jpDeleteName');
    if (nameEl) nameEl.textContent = '"' + name + '"';
    document.getElementById('jpDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('jpDeleteConfirmBtn');
    if (!btn) return;

    btn.addEventListener('click', async function () {
        if (!jpPendingDeleteUrl) return;
        const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        this.disabled    = true;
        this.textContent = 'Đang xóa...';

        try {
            const res = await fetch(jpPendingDeleteUrl, {
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' },
                body:    '_method=DELETE',
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok) {
                document.getElementById('jpDeleteModal')?.close();
                window.jpTable?.replaceData();
            } else {
                alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled    = false;
            this.textContent = 'Xóa';
            jpPendingDeleteUrl = null;
        }
    });
});

// ── Alpine component ──────────────────────────────────────────────────────────

const JP_LS_COLS = 'job-post-list-hidden-cols';

document.addEventListener('alpine:init', () => {
    Alpine.data('jobPostListPage', (serverData = {}) => {
        const {
            apiUrl          = '',
            statuses        = [],
            employmentTypes = [],
            workArrangements= [],
            experienceLevels= [],
            industries      = [],
            departments     = [],
            canDelete       = false,
        } = serverData;

        const COLUMNS = buildJobPostColumns(canDelete);

        let tableInst      = null;
        let statusTsInst   = null;
        let empTypeTsInst  = null;
        let workArrTsInst  = null;
        let expLvlTsInst   = null;
        let industryTsInst = null;
        let deptTsInst     = null;
        let dateFpInst     = null;
        let settingPreset  = false;

        return {
            filters: {
                search: '', status: '', employment_type: '',
                work_arrangement: '', experience_level: '', industry: '',
                department_id: '', date_from: '', date_to: '',
            },
            activeDatePreset: '',
            hiddenCols:       [],

            get toggleableCols() {
                return COLUMNS
                    .filter(c => c.field !== 'id' && c.field !== 'title')
                    .map(c => ({ field: c.field, title: c.title }));
            },

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.status || f.employment_type || f.work_arrangement
                    || f.experience_level || f.industry || f.department_id || f.date_from);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search)           chips.push({ key: 'search',          label: 'Tìm: ' + f.search });
                if (f.status) {         const s = statuses.find(x => x.value === f.status);
                                        chips.push({ key: 'status',          label: s ? s.text : f.status }); }
                if (f.employment_type) { const e = employmentTypes.find(x => x.value === f.employment_type);
                                        chips.push({ key: 'employment_type', label: e ? e.text : f.employment_type }); }
                if (f.work_arrangement){ const w = workArrangements.find(x => x.value === f.work_arrangement);
                                        chips.push({ key: 'work_arrangement',label: w ? w.text : f.work_arrangement }); }
                if (f.experience_level){ const l = experienceLevels.find(x => x.value === f.experience_level);
                                        chips.push({ key: 'experience_level',label: l ? l.text : f.experience_level }); }
                if (f.industry) {       const i = industries.find(x => x.value === f.industry);
                                        chips.push({ key: 'industry',        label: 'Ngành: ' + (i ? i.text : f.industry) }); }
                if (f.department_id) {  const d = departments.find(x => String(x.value) === String(f.department_id));
                                        chips.push({ key: 'department',      label: 'PB: ' + (d ? d.text : f.department_id) }); }
                if (f.date_from && f.date_to)
                    chips.push({ key: 'date', label: jpDisplayDate(f.date_from) + ' — ' + jpDisplayDate(f.date_to) });
                return chips;
            },

            init() {
                this.loadState();
                try { this.hiddenCols = JSON.parse(localStorage.getItem(JP_LS_COLS) || '[]'); } catch (_) {}
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#jp-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)           p.search           = f.search;
                        if (f.status)           p.status           = f.status;
                        if (f.employment_type)  p.employment_type  = f.employment_type;
                        if (f.work_arrangement) p.work_arrangement = f.work_arrangement;
                        if (f.experience_level) p.experience_level = f.experience_level;
                        if (f.industry)         p.industry         = f.industry;
                        if (f.department_id)    p.department_id    = f.department_id;
                        if (f.date_from)        p.date_from        = f.date_from;
                        if (f.date_to)          p.date_to          = f.date_to;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,

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
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>'
                        + '<p class="text-sm">Không có tin tuyển dụng nào</p></div>',
                });

                window.jpTable = tableInst;
                self.hiddenCols.forEach(field => tableInst.hideColumn(field));

                const tsOpts = (opts, placeholder, filterKey) => ({
                    dropdownParent: 'body',
                    placeholder,
                    plugins:        ['clear_button'],
                    options:        opts,
                    items:          self.filters[filterKey] ? [self.filters[filterKey]] : [],
                    onChange(val) {
                        self.filters[filterKey] = val || '';
                        self.saveState();
                        self.refresh();
                    },
                });

                statusTsInst   = new window.TomSelect('#filter-status',           tsOpts(statuses,         'Tất cả trạng thái...', 'status'));
                empTypeTsInst  = new window.TomSelect('#filter-employment-type',   tsOpts(employmentTypes,  'Tất cả loại hình...',  'employment_type'));
                workArrTsInst  = new window.TomSelect('#filter-work-arrangement',  tsOpts(workArrangements, 'Tất cả hình thức...',  'work_arrangement'));
                expLvlTsInst   = new window.TomSelect('#filter-experience-level',  tsOpts(experienceLevels, 'Tất cả cấp độ...',     'experience_level'));
                industryTsInst = new window.TomSelect('#filter-industry',          tsOpts(industries,       'Tất cả ngành...',       'industry'));

                deptTsInst = new window.TomSelect('#filter-department', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả phòng ban...',
                    maxOptions:     null,
                    searchField:    ['text'],
                    plugins:        ['clear_button'],
                    options:        departments,
                    valueField:     'value',
                    labelField:     'text',
                    items:          self.filters.department_id ? [self.filters.department_id] : [],
                    onChange(val) {
                        self.filters.department_id = val || '';
                        self.saveState();
                        self.refresh();
                    },
                    render: { no_results: () => '<div class="no-results p-3 text-sm opacity-50">Không tìm thấy</div>' },
                });

                dateFpInst = window.initDateRangePicker('#filter-date', {
                    disableMobile: true,
                    onChange(dates) {
                        if (settingPreset) return;
                        self.activeDatePreset = '';
                        if (dates.length === 2) {
                            self.filters.date_from = jpIsoDate(dates[0]);
                            self.filters.date_to   = jpIsoDate(dates[1]);
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

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))   this.filters.search           = p.get('q');
                if (p.has('st'))  this.filters.status           = p.get('st');
                if (p.has('et'))  this.filters.employment_type  = p.get('et');
                if (p.has('wa'))  this.filters.work_arrangement = p.get('wa');
                if (p.has('el'))  this.filters.experience_level = p.get('el');
                if (p.has('ind')) this.filters.industry         = p.get('ind');
                if (p.has('dp'))  this.filters.department_id    = p.get('dp');
                if (p.has('from'))this.filters.date_from        = p.get('from');
                if (p.has('to'))  this.filters.date_to          = p.get('to');
                if (p.has('dpre'))this.activeDatePreset         = p.get('dpre');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)           p.set('q',    f.search);
                if (f.status)           p.set('st',   f.status);
                if (f.employment_type)  p.set('et',   f.employment_type);
                if (f.work_arrangement) p.set('wa',   f.work_arrangement);
                if (f.experience_level) p.set('el',   f.experience_level);
                if (f.industry)         p.set('ind',  f.industry);
                if (f.department_id)    p.set('dp',   f.department_id);
                if (f.date_from)        p.set('from', f.date_from);
                if (f.date_to)          p.set('to',   f.date_to);
                if (this.activeDatePreset) p.set('dpre', this.activeDatePreset);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            setDatePreset(preset) {
                const range = jpPresetRange(preset);
                if (!range[0]) return;
                settingPreset          = true;
                this.activeDatePreset  = preset;
                this.filters.date_from = jpIsoDate(range[0]);
                this.filters.date_to   = jpIsoDate(range[1]);
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

            removeChip(key) {
                if (key === 'search')          this.filters.search = '';
                if (key === 'status')        { this.filters.status = '';            statusTsInst?.clear(true); }
                if (key === 'employment_type'){ this.filters.employment_type = '';  empTypeTsInst?.clear(true); }
                if (key === 'work_arrangement'){this.filters.work_arrangement = ''; workArrTsInst?.clear(true); }
                if (key === 'experience_level'){this.filters.experience_level = ''; expLvlTsInst?.clear(true); }
                if (key === 'industry')      { this.filters.industry = '';          industryTsInst?.clear(true); }
                if (key === 'department')    { this.filters.department_id = '';     deptTsInst?.clear(true); }
                if (key === 'date')          { this.clearDate(); return; }
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', status: '', employment_type: '', work_arrangement: '', experience_level: '', industry: '', department_id: '', date_from: '', date_to: '' };
                this.activeDatePreset = '';
                [statusTsInst, empTypeTsInst, workArrTsInst, expLvlTsInst, industryTsInst, deptTsInst]
                    .forEach(ts => ts?.clear(true));
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
                try { localStorage.setItem(JP_LS_COLS, JSON.stringify(this.hiddenCols)); } catch (_) {}
            },
        };
    });
});
