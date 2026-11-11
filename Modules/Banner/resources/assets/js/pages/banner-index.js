/**
 * pages/banner-index.js
 * Alpine component + Tabulator cho dashboard/banners/items —
 * remote pagination/sort/filter qua API, xoá qua AJAX + modal xác nhận.
 *
 * Server data truyền vào qua x-data="bannerListPage({{ Js::from([...]) }})".
 */

import { createTs } from '@shared/tom-select-factory.js';

import { t } from '../../../../../../resources/js/shared/i18n.js';
function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const COLUMNS = [
    {
        title: t('Ảnh'), field: 'image_url', width: 90, headerSort: false,
        formatter(cell) {
            const v = cell.getValue();
            return v ? '<img src="' + esc(v) + '" alt="" class="h-10 w-auto rounded border border-base-300 object-cover">' : '';
        },
    },
    {
        title: t('Vị trí'), field: 'placement_label', minWidth: 160, sorter: 'string',
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<span class="text-sm">' + esc(d.placement_label) + '</span>';
            if (d.title) html += '<p class="text-xs text-base-content/40">' + esc(d.title) + '</p>';
            return html;
        },
    },
    {
        title: 'Target', field: 'target_label', minWidth: 180, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (!d.target_label) return '';
            const cls = d.target_kind == null ? 'badge-ghost'
                : d.target_label.includes(t('đã xoá')) || d.target_label.includes(t('không rõ')) ? 'badge-warning'
                : 'badge-info';
            return '<span class="badge ' + cls + ' badge-sm">' + esc(d.target_label) + '</span>';
        },
    },
    {
        title: t('Lịch chạy'), field: 'start_date', width: 170, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (!d.start_date && !d.end_date) return ('<span class="text-base-content/30 text-xs">' + t('Không giới hạn') + '</span>');
            return '<span class="text-xs">' + esc(d.start_date || '—') + ' → ' + esc(d.end_date || '—') + '</span>';
        },
    },
    {
        title: 'Click', field: 'click_count', width: 90, hozAlign: 'center', sorter: 'number',
    },
    {
        title: t('Trạng thái'), field: 'is_active', width: 130, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (d.is_running) return ('<span class="badge badge-success badge-sm">' + t('Đang chạy') + '</span>');
            if (!d.is_active) return ('<span class="badge badge-neutral badge-sm">' + t('Đã tắt') + '</span>');
            return ('<span class="badge badge-ghost badge-sm">' + t('Ngoài lịch') + '</span>');
        },
    },
    {
        title: '', field: 'id', width: 110, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            let html = '<div class="flex items-center justify-center gap-1">';

            if (d.can_update) {
                html += '<a href="' + esc(d.edit_url) + ('" class="btn btn-ghost btn-xs" title="' + t('Sửa') + '">' + t('Sửa') + '</a>');
            }
            if (d.can_delete) {
                html += ('<button class="btn btn-ghost btn-xs text-error" title="' + t('Xoá') + '"')
                    + ' data-url="' + esc(d.destroy_url) + '"'
                    + (' onclick="window.bannerDeleteConfirm(this.dataset.url)">' + t('Xoá') + '</button>');
            }

            html += '</div>';
            return html;
        },
    },
];

// ── AJAX delete ──────────────────────────────────────────────────────────────

let pendingDeleteUrl = null;

window.bannerDeleteConfirm = function (url) {
    pendingDeleteUrl = url;
    document.getElementById('bannerDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('bannerConfirmDeleteBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async function () {
        if (!pendingDeleteUrl) return;

        const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        this.disabled    = true;
        this.textContent = t('Đang xoá...');

        try {
            const res = await fetch(pendingDeleteUrl, {
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
                document.getElementById('bannerDeleteModal')?.close();
                window.bannerTable?.replaceData();
            } else {
                alert(data.message || t('Xoá thất bại. Vui lòng thử lại.'));
            }
        } catch (e) {
            console.error('[banner] delete failed', e);
            alert(t('Lỗi kết nối. Vui lòng thử lại.'));
        } finally {
            this.disabled    = false;
            this.textContent = t('Xoá');
            pendingDeleteUrl = null;
        }
    });
});

// ── Alpine component ──────────────────────────────────────────────────────────

document.addEventListener('alpine:init', () => {
    Alpine.data('bannerListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        let tableInst    = null;
        let tsPlacement  = null;
        let tsTargetType = null;

        const optText = (ts, v) => ts?.options?.[v]?.text ?? v;

        return {
            filters: { placement: '', target_type: '' },

            get hasFilters() {
                return !!(this.filters.placement || this.filters.target_type);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.placement)   chips.push({ key: 'placement',   label: optText(tsPlacement, f.placement) });
                if (f.target_type) chips.push({ key: 'target_type', label: optText(tsTargetType, f.target_type) });
                return chips;
            },

            init() {
                const p = new URLSearchParams(location.search);
                if (p.has('pl')) this.filters.placement   = p.get('pl');
                if (p.has('tt')) this.filters.target_type = p.get('tt');
                this.$nextTick(() => { this._setup(); this._initTomSelects(); });
            },

            _initTomSelects() {
                const placementEl  = document.getElementById('ts-placement');
                const targetTypeEl = document.getElementById('ts-target_type');
                if (!placementEl || !targetTypeEl) return;

                tsPlacement = createTs(placementEl, {
                    placeholder: t('Tất cả vị trí'),
                    maxOptions: null,
                    onChange() { placementEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });

                tsTargetType = createTs(targetTypeEl, {
                    placeholder: t('Tất cả target'),
                    onChange() { targetTypeEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#banner-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.placement)   p.placement   = f.placement;
                        if (f.target_type) p.target_type = f.target_type;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[banner] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         20,
                    paginationSizeSelector: [10, 20, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'sort_order', dir: 'asc' }],

                    layout:           'fitColumns',
                    responsiveLayout: 'collapse',
                    movableColumns:   true,

                    locale: 'vi-VN',
                    langs: {
                        'vi-VN': {
                            pagination: {
                                page_size: t('Dòng/trang'), page_title: t('Trang'),
                                first: '«', last: '»', prev: '‹', next: '›',
                                first_title: t('Trang đầu'), last_title: t('Trang cuối'),
                                prev_title: t('Trang trước'), next_title: t('Trang sau'),
                                counter: { showing: '', of: t('trong'), rows: t('dòng'), pages: t('trang') },
                            },
                        },
                    },

                    columns: COLUMNS,
                    placeholder: '<div class="py-16 text-center opacity-40">'
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 6h16v12H4V6z"/></svg>'
                        + ('<p class="text-sm">' + t('Chưa có banner nào') + '</p></div>'),
                });

                window.bannerTable = tableInst;
            },

            onFilterChange() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.placement)   p.set('pl', f.placement);
                if (f.target_type) p.set('tt', f.target_type);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
                tableInst?.replaceData();
            },

            removeChip(key) {
                if (key === 'placement')   { this.filters.placement = ''; tsPlacement?.setValue('', true); }
                if (key === 'target_type') { this.filters.target_type = ''; tsTargetType?.setValue('', true); }
                this.onFilterChange();
            },

            reset() {
                this.filters = { placement: '', target_type: '' };
                tsPlacement?.setValue('', true);
                tsTargetType?.setValue('', true);
                history.replaceState(null, '', location.pathname);
                tableInst?.replaceData();
            },
        };
    });
});
