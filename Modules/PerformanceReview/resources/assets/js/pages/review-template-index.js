/**
 * pages/review-template-index.js
 * Alpine + Tabulator + TomSelect + Flatpickr cho trang danh sách mẫu đánh giá.
 */

// ── Utilities ─────────────────────────────────────────────────────────────────

function rtEsc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function rtIsoDate(d) {
    return d.getFullYear() + '-'
        + String(d.getMonth() + 1).padStart(2, '0') + '-'
        + String(d.getDate()).padStart(2, '0');
}

function rtDisplayDate(iso) {
    if (!iso) return '';
    const p = iso.split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
}

function rtPresetRange(preset) {
    const now = new Date(), y = now.getFullYear(), m = now.getMonth(), d = now.getDate();
    if (preset === 'today') return [new Date(y, m, d), new Date(y, m, d)];
    if (preset === 'week') {
        const dow = now.getDay() === 0 ? 6 : now.getDay() - 1;
        return [new Date(y, m, d - dow), new Date(y, m, d - dow + 6)];
    }
    if (preset === 'month') return [new Date(y, m, 1), new Date(y, m + 1, 0)];
    if (preset === 'year')  return [new Date(y, 0, 1), new Date(y, 11, 31)];
    return [null, null];
}

// ── Tabulator columns ─────────────────────────────────────────────────────────

function buildRtColumns(canDelete) {
    return [
        {
            title: 'Tên mẫu', field: 'name', minWidth: 220, sorter: 'string', frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<a href="' + rtEsc(d.show_url) + '" class="font-semibold text-sm hover:text-primary transition-colors">'
                    + rtEsc(d.name) + '</a>';
                if (d.badges && d.badges.length) {
                    html += '<div class="flex flex-wrap gap-1 mt-1">';
                    d.badges.forEach(b => {
                        html += '<span class="badge badge-xs ' + rtEsc(b.class) + '">' + rtEsc(b.label) + '</span>';
                    });
                    html += '</div>';
                }
                return html;
            },
        },
        {
            title: 'Chu kỳ', field: 'period_label', width: 130, hozAlign: 'center', sorter: 'string',
            formatter(cell) {
                return '<span class="badge badge-sm badge-soft badge-info">' + rtEsc(cell.getValue()) + '</span>';
            },
        },
        {
            title: 'Thang điểm', field: 'rating_scale', width: 110, hozAlign: 'center', sorter: 'number',
            formatter(cell) {
                return '<span class="badge badge-sm badge-soft badge-primary">' + rtEsc(cell.getValue()) + ' điểm</span>';
            },
        },
        {
            title: 'Tiêu chí', field: 'criteria_count', width: 90, hozAlign: 'center', sorter: 'number',
            formatter(cell) {
                const v = cell.getValue();
                return v > 0
                    ? '<span class="badge badge-ghost badge-sm">' + v + '</span>'
                    : '<span class="text-base-content/25 text-xs">0</span>';
            },
        },
        {
            title: 'Ngày tạo', field: 'created_at', width: 110, hozAlign: 'center', sorter: 'string',
        },
        {
            title: 'Thao tác', field: 'id', width: 100, hozAlign: 'center', headerSort: false, frozen: true,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<div class="flex items-center justify-center gap-1">';

                html += '<a href="' + rtEsc(d.show_url) + '" '
                    + 'class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-info" title="Xem">'
                    + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">'
                    + '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>'
                    + '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>'
                    + '</svg></a>';

                if (canDelete && d.can_delete) {
                    html += '<button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa"'
                        + ' data-url="' + rtEsc(d.delete_url) + '" data-name="' + rtEsc(d.name) + '"'
                        + ' onclick="window.rtDeleteConfirm(this.dataset.url,this.dataset.name)">'
                        + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">'
                        + '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>'
                        + '</svg></button>';
                }

                html += '</div>';
                return html;
            },
        },
    ];
}

// ── AJAX Delete ───────────────────────────────────────────────────────────────

let rtPendingDeleteUrl = null;

window.rtDeleteConfirm = function (url, name) {
    rtPendingDeleteUrl = url;
    const nameEl = document.getElementById('rtDeleteName');
    if (nameEl) nameEl.textContent = '"' + name + '"';
    document.getElementById('rtDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('rtDeleteConfirmBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async function () {
        if (!rtPendingDeleteUrl) return;

        const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        this.disabled    = true;
        this.textContent = 'Đang xóa...';

        try {
            const res = await fetch(rtPendingDeleteUrl, {
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
                document.getElementById('rtDeleteModal')?.close();
                window.rtTable?.replaceData();
            } else {
                alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
            }
        } catch (e) {
            console.error('[rt] delete failed', e);
            alert('Lỗi kết nối. Vui lòng thử lại.');
        } finally {
            this.disabled    = false;
            this.textContent = 'Xóa';
            rtPendingDeleteUrl = null;
        }
    });
});

// ── Alpine component ──────────────────────────────────────────────────────────

document.addEventListener('alpine:init', () => {

    Alpine.data('rtListPage', (serverData = {}) => {
        const {
            apiUrl      = '',
            periodTypes = [],
            canDelete   = false,
        } = serverData;

        const COLUMNS = buildRtColumns(canDelete);

        let tableInst      = null;
        let periodTsInst   = null;
        let activeTsInst   = null;
        let dateFpInst     = null;
        let settingPreset  = false;

        return {
            filters: {
                search: '', period_type: '', is_active: '',
                date_from: '', date_to: '',
            },
            activeDatePreset: '',

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.period_type || f.is_active !== '' || f.date_from);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.period_type) {
                    const pt = periodTypes.find(x => x.value === f.period_type);
                    chips.push({ key: 'period_type', label: pt ? pt.text : f.period_type });
                }
                if (f.is_active !== '') {
                    chips.push({ key: 'is_active', label: f.is_active === '1' ? 'Đang hoạt động' : 'Đã ẩn' });
                }
                if (f.date_from && f.date_to) {
                    chips.push({ key: 'date', label: rtDisplayDate(f.date_from) + ' — ' + rtDisplayDate(f.date_to) });
                }
                return chips;
            },

            init() {
                this.loadState();
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#rt-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)        p.search      = f.search;
                        if (f.period_type)   p.period_type = f.period_type;
                        if (f.is_active !== '') p.is_active = f.is_active;
                        if (f.date_from)     p.date_from   = f.date_from;
                        if (f.date_to)       p.date_to     = f.date_to;
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
                    height:           '65vh',

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
                        + '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>'
                        + '</svg><p class="text-sm">Chưa có mẫu đánh giá nào</p></div>',
                });

                window.rtTable = tableInst;

                // Period TomSelect
                periodTsInst = new window.TomSelect('#filter-period', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả chu kỳ...',
                    plugins:        ['clear_button'],
                    options:        periodTypes,
                    items:          self.filters.period_type ? [self.filters.period_type] : [],
                    onChange(val) {
                        self.filters.period_type = val || '';
                        self.saveState();
                        self.refresh();
                    },
                });

                // Active status TomSelect
                activeTsInst = new window.TomSelect('#filter-active', {
                    dropdownParent: 'body',
                    placeholder:    'Tất cả trạng thái...',
                    plugins:        ['clear_button'],
                    options:        [
                        { value: '1', text: 'Đang hoạt động' },
                        { value: '0', text: 'Đã ẩn' },
                    ],
                    items: self.filters.is_active !== '' ? [self.filters.is_active] : [],
                    onChange(val) {
                        self.filters.is_active = val ?? '';
                        self.saveState();
                        self.refresh();
                    },
                });

                // Flatpickr date range
                dateFpInst = window.initDateRangePicker('#filter-date', {
                    disableMobile: true,
                    onChange(dates) {
                        if (settingPreset) return;
                        self.activeDatePreset = '';
                        if (dates.length === 2) {
                            self.filters.date_from = rtIsoDate(dates[0]);
                            self.filters.date_to   = rtIsoDate(dates[1]);
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
                if (p.has('q'))    this.filters.search      = p.get('q');
                if (p.has('pt'))   this.filters.period_type = p.get('pt');
                if (p.has('act'))  this.filters.is_active   = p.get('act');
                if (p.has('from')) this.filters.date_from   = p.get('from');
                if (p.has('to'))   this.filters.date_to     = p.get('to');
                if (p.has('dpre')) this.activeDatePreset    = p.get('dpre');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)        p.set('q',    f.search);
                if (f.period_type)   p.set('pt',   f.period_type);
                if (f.is_active !== '') p.set('act', f.is_active);
                if (f.date_from)     p.set('from', f.date_from);
                if (f.date_to)       p.set('to',   f.date_to);
                if (this.activeDatePreset) p.set('dpre', this.activeDatePreset);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            setDatePreset(preset) {
                const range = rtPresetRange(preset);
                if (!range[0]) return;
                settingPreset          = true;
                this.activeDatePreset  = preset;
                this.filters.date_from = rtIsoDate(range[0]);
                this.filters.date_to   = rtIsoDate(range[1]);
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
                if (key === 'search')      this.filters.search = '';
                if (key === 'period_type') { this.filters.period_type = ''; periodTsInst?.clear(true); }
                if (key === 'is_active')   { this.filters.is_active = '';   activeTsInst?.clear(true); }
                if (key === 'date')        { this.clearDate(); return; }
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = { search: '', period_type: '', is_active: '', date_from: '', date_to: '' };
                this.activeDatePreset = '';
                periodTsInst?.clear(true);
                activeTsInst?.clear(true);
                dateFpInst?.clear(false);
                history.replaceState(null, '', location.pathname);
                this.refresh();
            },
        };
    });
});
