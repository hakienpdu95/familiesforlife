import { createTs } from '@shared/tom-select-factory.js';

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const EMPTY = '<span class="text-base-content/25 text-xs">—</span>';

const COLUMNS = [
    {
        title: '★', field: 'is_featured', width: 60, hozAlign: 'center', sorter: 'boolean',
        formatter(cell) {
            return cell.getValue()
                ? '<span class="text-warning text-lg leading-none" title="Nổi bật">★</span>'
                : '<span class="text-base-content/20">☆</span>';
        },
    },
    {
        title: 'Mã', field: 'province_code', width: 80, hozAlign: 'center', sorter: 'string',
        formatter(cell) {
            return '<span class="font-mono text-sm">' + esc(cell.getValue()) + '</span>';
        },
    },
    {
        title: 'Tỉnh/Thành phố', field: 'name', minWidth: 220, sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<span class="font-medium text-sm">' + esc(d.name) + '</span>';
            if (d.short_name && d.short_name !== d.name) html += '<div class="text-xs text-base-content/40">' + esc(d.short_name) + '</div>';
            return html;
        },
    },
    {
        title: 'Slug', field: 'slug', minWidth: 140, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (!d.slug) return EMPTY;
            const slug = '<span class="font-mono text-xs">' + esc(d.slug) + '</span>';
            return d.public_url
                ? '<a href="' + esc(d.public_url) + '" target="_blank" rel="noopener" class="link link-hover">' + slug + '</a>'
                : slug;
        },
    },
    {
        title: 'Loại', field: 'place_type', width: 140, sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            return '<span class="badge badge-sm ' + esc(d.place_type_badge) + '">' + esc(d.place_type_label) + '</span>';
        },
    },
    {
        title: 'Màu chủ đạo', field: 'theme_color', width: 130, headerSort: false,
        formatter(cell) {
            const v = cell.getValue();
            if (!v) return EMPTY;
            return '<span class="inline-flex items-center gap-1.5"><span class="inline-block w-4 h-4 rounded-full border border-base-300" style="background:' + esc(v) + '"></span><span class="font-mono text-xs">' + esc(v) + '</span></span>';
        },
    },
    {
        title: 'Vùng', field: 'region', minWidth: 160, headerSort: false,
        formatter(cell) {
            return esc(cell.getValue()) || EMPTY;
        },
    },
    {
        title: 'Trạng thái', field: 'is_active', width: 140, hozAlign: 'center', sorter: 'boolean',
        formatter(cell) {
            return cell.getValue()
                ? '<span class="badge badge-sm badge-success">Hoạt động</span>'
                : '<span class="badge badge-sm badge-ghost">Ngừng</span>';
        },
    },
    {
        title: '', field: 'id', width: 70, hozAlign: 'center', headerSort: false, frozen: true,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (!d.can_update) return '';
            return '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square" title="Sửa">'
                + '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>'
                + '</a>';
        },
    },
];

document.addEventListener('alpine:init', () => {
    Alpine.data('provinceListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        let tableInst    = null;
        let tsRegion     = null;
        let tsPlaceType  = null;
        let tsIsFeatured = null;
        let tsIsActive   = null;

        const emptyFilters = () => ({ search: '', region_id: '', place_type: '', is_active: '', is_featured: '' });
        const optText = (ts, v) => ts?.options?.[v]?.text ?? v;

        return {
            filters: emptyFilters(),

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.region_id || f.place_type || f.is_active !== '' || f.is_featured !== '');
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search)             chips.push({ key: 'search',      label: 'Tìm: ' + f.search });
                if (f.region_id)          chips.push({ key: 'region_id',   label: optText(tsRegion, f.region_id) });
                if (f.place_type)         chips.push({ key: 'place_type',  label: optText(tsPlaceType, f.place_type) });
                if (f.is_featured !== '') chips.push({ key: 'is_featured', label: optText(tsIsFeatured, f.is_featured) });
                if (f.is_active !== '')   chips.push({ key: 'is_active',   label: optText(tsIsActive, f.is_active) });
                return chips;
            },

            init() {
                this.loadState();
                this.$nextTick(() => { this._setup(); this._initTomSelects(); });
            },

            _initTomSelects() {
                const regionEl     = document.getElementById('ts-region');
                const placeTypeEl  = document.getElementById('ts-place_type');
                const isFeaturedEl = document.getElementById('ts-is_featured');
                const isActiveEl   = document.getElementById('ts-is_active');
                if (!regionEl || !placeTypeEl || !isFeaturedEl || !isActiveEl) return;

                tsRegion = createTs(regionEl, {
                    placeholder: 'Tất cả vùng',
                    onChange() { regionEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });

                tsPlaceType = createTs(placeTypeEl, {
                    placeholder: 'Tất cả loại',
                    onChange() { placeTypeEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });

                tsIsFeatured = createTs(isFeaturedEl, {
                    placeholder: 'Tất cả',
                    onChange() { isFeaturedEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });

                tsIsActive = createTs(isActiveEl, {
                    placeholder: 'Tất cả trạng thái',
                    onChange() { isActiveEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#province-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search)           p.search     = f.search;
                        if (f.region_id)        p.region_id  = f.region_id;
                        if (f.place_type)       p.place_type = f.place_type;
                        if (f.is_active !== '') p.is_active  = f.is_active;
                        if (f.is_featured !== '') p.is_featured = f.is_featured;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[province] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         50,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'province_code', dir: 'asc' }],

                    layout:           'fitColumns',
                    responsiveLayout: 'collapse',
                    movableColumns:   true,

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
                    placeholder: '<div class="py-16 text-center opacity-40"><p class="text-sm">Chưa có tỉnh thành nào</p></div>',
                });

                window.provinceTable = tableInst;
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))  this.filters.search     = p.get('q');
                if (p.has('rg')) this.filters.region_id  = p.get('rg');
                if (p.has('pt')) this.filters.place_type = p.get('pt');
                if (p.has('ac')) this.filters.is_active  = p.get('ac');
                if (p.has('ft')) this.filters.is_featured = p.get('ft');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search)           p.set('q',  f.search);
                if (f.region_id)        p.set('rg', f.region_id);
                if (f.place_type)       p.set('pt', f.place_type);
                if (f.is_active !== '') p.set('ac', f.is_active);
                if (f.is_featured !== '') p.set('ft', f.is_featured);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.saveState(); this.refresh(); },

            removeChip(key) {
                if (key === 'search')      this.filters.search = '';
                if (key === 'region_id')   { this.filters.region_id = ''; tsRegion?.setValue('', true); }
                if (key === 'place_type')  { this.filters.place_type = ''; tsPlaceType?.setValue('', true); }
                if (key === 'is_featured') { this.filters.is_featured = ''; tsIsFeatured?.setValue('', true); }
                if (key === 'is_active')   { this.filters.is_active = ''; tsIsActive?.setValue('', true); }
                this.saveState();
                this.refresh();
            },

            reset() {
                this.filters = emptyFilters();
                tsRegion?.setValue('', true);
                tsPlaceType?.setValue('', true);
                tsIsFeatured?.setValue('', true);
                tsIsActive?.setValue('', true);
                history.replaceState(null, '', location.pathname);
                this.refresh();
            },
        };
    });
});
