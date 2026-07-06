/**
 * lead-index.js — Listing page controller (Tabulator + Kanban + Filters)
 *
 * Server config được truyền qua:
 *   <script id="lead-list-data" type="application/json">{ ... }</script>
 * trong blade — không có global var nào được khai báo ngoài module scope.
 *
 * window globals (cần thiết cho Tabulator row formatters):
 *   window.leadDeleteConfirm(url, name) — mở delete modal từ cell formatter
 *   window.leadTable                    — Tabulator instance (để reload từ nơi khác)
 */

// ── Config từ DOM ──────────────────────────────────────────────────────────

const CFG = JSON.parse(
    document.getElementById('lead-list-data')?.textContent ?? '{}'
);
const {
    apiListing,
    apiKanban,
    csrf,
    stages      = [],
    sources     = [],
    maskContact = false,
    canDelete   = false,
} = CFG;

// ── LocalStorage keys ──────────────────────────────────────────────────────

const LS_COLS = 'lead-list-hidden-cols';
const LS_VIEW = 'lead-list-view';

// ── Lookup data ────────────────────────────────────────────────────────────

const STATUSES = [
    { value: '1', text: 'Đang hoạt động' },
    { value: '2', text: 'Đã chốt'        },
    { value: '3', text: 'Đã lưu trữ'     },
    { value: '4', text: 'Tạm hoãn'       },
];

// ── Helpers ────────────────────────────────────────────────────────────────

/** HTML-escape để dùng trong Tabulator formatters (innerHTML context). */
function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function isoDate(d) {
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

function formatMoney(val, currency) {
    const n = parseFloat(val);
    if (val == null || isNaN(n)) return '';
    return n.toLocaleString('vi-VN') + ' ' + (currency || 'VND');
}

const stageColor  = id => stages.find(s => s.id == id)?.color  ?? '#94a3b8';
const stageLabel  = id => stages.find(s => s.id == id)?.label  ?? '—';
const sourceLabel = id => sources.find(s => s.id == id)?.label ?? '—';

// ── Column definitions ─────────────────────────────────────────────────────

const COLUMNS = [
    {
        title: 'Cơ hội / Khách hàng', field: 'title', minWidth: 220, sorter: 'string', frozen: true,
        formatter(cell) {
            const d    = cell.getRow().getData();
            const name = maskContact
                ? '<span class="text-base-content/30 italic text-xs">Ẩn</span>'
                : esc(d.contact_name);
            const company = maskContact ? '' : (d.contact_company
                ? `<p class="text-xs text-base-content/40">${esc(d.contact_company)}</p>` : '');
            return `<div>
                <a href="${esc(d.show_url)}" class="font-semibold text-sm hover:text-primary transition-colors">${esc(d.title || d.contact_name)}</a>
                ${d.title && !maskContact ? `<p class="text-xs text-base-content/50 mt-0.5">${name}</p>` : ''}
                ${company}
            </div>`;
        },
    },
    {
        title: 'SĐT', field: 'contact_phone', width: 130, headerSort: false,
        formatter(cell) {
            if (maskContact) return '<span class="text-base-content/25 text-xs">***</span>';
            return cell.getValue()
                ? esc(cell.getValue())
                : '<span class="text-base-content/25 text-xs">—</span>';
        },
    },
    {
        title: 'Tình trạng', field: 'stage_id', width: 150, headerSort: false,
        formatter(cell) {
            const d     = cell.getRow().getData();
            const color = d.stage_color || stageColor(d.stage_id);
            const label = d.stage_label || stageLabel(d.stage_id);
            return `<div class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full shrink-0" style="background:${esc(color)}"></span>
                <span class="text-sm">${esc(label)}</span>
            </div>`;
        },
    },
    {
        title: 'Nguồn', field: 'source_label', width: 130, headerSort: false,
        formatter(cell) {
            const d     = cell.getRow().getData();
            const label = d.source_label || sourceLabel(d.source_id);
            return label ? esc(label) : '<span class="text-base-content/25 text-xs">—</span>';
        },
    },
    {
        title: 'Người phụ trách', field: 'assignee_name', width: 150, headerSort: false,
        formatter(cell) {
            const v = cell.getValue();
            return v
                ? `<div class="flex items-center gap-1.5">
                       <div class="w-5 h-5 rounded-full bg-primary/20 text-primary text-xs font-bold flex items-center justify-center shrink-0">
                           ${esc(v.charAt(0).toUpperCase())}
                       </div>
                       <span class="text-sm">${esc(v)}</span>
                   </div>`
                : '<span class="text-base-content/25 text-xs">Chưa phân công</span>';
        },
    },
    {
        title: 'Giá trị', field: 'expected_value', width: 130, sorter: 'number', hozAlign: 'right',
        formatter(cell) {
            const d = cell.getRow().getData();
            return d.expected_value
                ? `<span class="font-medium text-success text-sm">${esc(formatMoney(d.expected_value, d.currency))}</span>`
                : '<span class="text-base-content/25 text-xs">—</span>';
        },
    },
    {
        title: 'Điểm', field: 'lead_score', width: 80, sorter: 'number', hozAlign: 'center',
        formatter(cell) {
            const v = cell.getValue();
            if (v == null) return '<span class="text-base-content/25 text-xs">—</span>';
            const cls = v >= 70 ? 'badge-error' : (v >= 40 ? 'badge-warning' : 'badge-ghost');
            return `<span class="badge badge-sm ${cls}">${v}</span>`;
        },
    },
    {
        title: 'Trạng thái', field: 'status_value', width: 130, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            return d.status_badge
                ? `<span class="badge badge-sm badge-soft ${esc(d.status_badge)}">${esc(d.status_label)}</span>`
                : '';
        },
    },
    {
        title: 'Ngày chốt', field: 'expected_close_date', width: 110, hozAlign: 'center', sorter: 'string',
        formatter(cell) {
            const v = cell.getValue();
            if (!v) return '<span class="text-base-content/25 text-xs">—</span>';
            const [y, mo, d] = v.split('-');
            const today = isoDate(new Date());
            const cls   = v < today ? 'text-error font-medium' : 'text-base-content/70';
            return `<span class="text-xs ${cls}">${d}/${mo}/${y}</span>`;
        },
    },
    {
        title: 'Hoạt động', field: 'last_activity_at', width: 110, sorter: 'string', hozAlign: 'center',
        formatter(cell) {
            return cell.getValue()
                ? `<span class="text-xs text-base-content/60">${esc(cell.getValue())}</span>`
                : '<span class="text-base-content/25 text-xs">—</span>';
        },
    },
    {
        title: 'Thao tác', field: 'id', width: 100, hozAlign: 'center', headerSort: false, frozen: true,
        formatter(cell) {
            const d = cell.getRow().getData();

            const viewBtn = `<a href="${esc(d.show_url)}" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-info" title="Xem">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </a>`;

            const editBtn = `<a href="${esc(d.edit_url)}" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </a>`;

            const delBtn = canDelete
                ? `<button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa"
                       onclick="leadDeleteConfirm('${esc(d.delete_url)}', '${esc(d.title || d.contact_name)}')">
                       <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                   </button>`
                : '';

            return `<div class="flex items-center justify-center gap-1">${viewBtn}${editBtn}${delBtn}</div>`;
        },
    },
];

// ── Delete modal ────────────────────────────────────────────────────────────

let pendingDeleteUrl = null;

window.leadDeleteConfirm = (url, name) => {
    pendingDeleteUrl = url;
    const el = document.getElementById('deleteItemName');
    if (el) el.textContent = `"${name}"`;
    document.getElementById('deleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('confirmDeleteBtn')?.addEventListener('click', async function () {
        if (!pendingDeleteUrl) return;

        this.disabled    = true;
        this.textContent = 'Đang xóa...';

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

            if (res.ok) {
                document.getElementById('deleteModal')?.close();
                window.leadTable?.replaceData();
            } else {
                const data = await res.json().catch(() => ({}));
                alert(data.message || 'Xóa thất bại. Vui lòng thử lại.');
            }
        } catch {
            alert('Lỗi kết nối.');
        } finally {
            this.disabled    = false;
            this.textContent = 'Xóa';
            pendingDeleteUrl = null;
        }
    });
});

// ── Alpine component ────────────────────────────────────────────────────────

// Module-level instances — shared giữa các lần Alpine khởi tạo component
// (thực tế chỉ có 1 instance trên index page)
let _tableInst     = null;
let _stageTsInst   = null;
let _sourceTsInst  = null;
let _statusTsInst  = null;
let _closingFpInst = null;

/**
 * Factory function — định nghĩa ở module scope để:
 *  1. `window.leadListPage` có thể set ngay khi module load (Alpine fallback)
 *  2. `Alpine.data` dùng cùng factory trong `alpine:init`
 */
function _leadListPageFactory() {
    return {

        view:          localStorage.getItem(LS_VIEW) || 'list',
        filters: {
            search: '', stage_id: '', source_id: '',
            status: '', closing_after: '', closing_before: '',
        },
        closingPreset: '',
        hiddenCols:    [],
        kanbanBoard:   [],
        kanbanLoading: false,

        get toggleableCols() {
            return COLUMNS
                .filter(c => c.field !== 'id' && c.field !== 'title')
                .map(c => ({ field: c.field, title: c.title }));
        },

        get hasFilters() {
            const f = this.filters;
            return !!(f.search || f.stage_id || f.source_id || f.status || f.closing_after);
        },

        get activeChips() {
            const chips = [], f = this.filters;
            if (f.search)    chips.push({ key: 'search', label: `Tìm: ${f.search}` });
            if (f.stage_id) {
                const s = stages.find(s => String(s.id) === String(f.stage_id));
                chips.push({ key: 'stage', label: `TT: ${s ? s.label : f.stage_id}` });
            }
            if (f.source_id) {
                const s = sources.find(s => String(s.id) === String(f.source_id));
                chips.push({ key: 'source', label: `Nguồn: ${s ? s.label : f.source_id}` });
            }
            if (f.status) {
                const st = STATUSES.find(s => s.value === f.status);
                chips.push({ key: 'status', label: st ? st.text : f.status });
            }
            if (f.closing_after && f.closing_before)
                chips.push({ key: 'closing', label: `Chốt: ${f.closing_after} — ${f.closing_before}` });
            return chips;
        },

        init() {
            this._loadState();
            try { this.hiddenCols = JSON.parse(localStorage.getItem(LS_COLS) || '[]'); } catch {}
            this.$nextTick(() => this._setup());
        },

        _setup() {
            const self = this;

            // ── Tabulator ──────────────────────────────────────────────
            _tableInst = new window.Tabulator('#lead-table', {
                ajaxURL:    apiListing,
                ajaxConfig: {
                    headers: {
                        'X-CSRF-TOKEN':     csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept':           'application/json',
                    },
                },
                ajaxParams() {
                    const p = {}, f = self.filters;
                    if (f.search)         p.search         = f.search;
                    if (f.stage_id)       p.stage_id       = f.stage_id;
                    if (f.source_id)      p.source_id      = f.source_id;
                    if (f.status)         p.status         = f.status;
                    if (f.closing_after)  p.closing_after  = f.closing_after;
                    if (f.closing_before) p.closing_before = f.closing_before;
                    return p;
                },
                ajaxResponse(_u, _p, res) { return res; },

                pagination:             true,
                paginationMode:         'remote',
                paginationSize:         25,
                paginationSizeSelector: [10, 25, 50, 100],
                paginationCounter:      'rows',
                sortMode:               'remote',
                initialSort:            [{ column: 'updated_at', dir: 'desc' }],
                layout:                 'fitColumns',
                responsiveLayout:       'collapse',
                movableColumns:         true,
                height:                 '68vh',
                locale:                 'vi-VN',
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
                columns:     COLUMNS,
                placeholder: '<div class="py-16 text-center opacity-40"><svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg><p class="text-sm">Không tìm thấy cơ hội nào</p></div>',
            });

            window.leadTable = _tableInst;
            self.hiddenCols.forEach(f => _tableInst.hideColumn(f));

            // ── Stage TomSelect ────────────────────────────────────────
            _stageTsInst = new window.TomSelect('#filter-stage', {
                dropdownParent: 'body',
                placeholder:    'Tất cả tình trạng...',
                plugins:        ['clear_button'],
                options:        stages.map(s => ({ value: String(s.id), text: s.label })),
                items:          self.filters.stage_id ? [String(self.filters.stage_id)] : [],
                render:         { no_results: () => '<div class="no-results p-3 text-sm opacity-50">Không tìm thấy</div>' },
                onChange(val) { self.filters.stage_id = val || ''; self._saveState(); self.refresh(); },
            });

            // ── Source TomSelect ───────────────────────────────────────
            _sourceTsInst = new window.TomSelect('#filter-source', {
                dropdownParent: 'body',
                placeholder:    'Tất cả nguồn...',
                plugins:        ['clear_button'],
                options:        sources.map(s => ({ value: String(s.id), text: s.label })),
                items:          self.filters.source_id ? [String(self.filters.source_id)] : [],
                render:         { no_results: () => '<div class="no-results p-3 text-sm opacity-50">Không tìm thấy</div>' },
                onChange(val) { self.filters.source_id = val || ''; self._saveState(); self.refresh(); },
            });

            // ── Status TomSelect ───────────────────────────────────────
            _statusTsInst = new window.TomSelect('#filter-status', {
                dropdownParent: 'body',
                placeholder:    'Tất cả trạng thái...',
                plugins:        ['clear_button'],
                options:        STATUSES,
                items:          self.filters.status ? [self.filters.status] : [],
                onChange(val) { self.filters.status = val || ''; self._saveState(); self.refresh(); },
            });

            // ── Flatpickr closing date ─────────────────────────────────
            _closingFpInst = window.initDateRangePicker('#filter-closing', {
                disableMobile: true,
                onChange(dates) {
                    self.closingPreset = '';
                    if (dates.length === 2) {
                        self.filters.closing_after  = isoDate(dates[0]);
                        self.filters.closing_before = isoDate(dates[1]);
                    } else {
                        self.filters.closing_after  = '';
                        self.filters.closing_before = '';
                    }
                    self._saveState(); self.refresh();
                },
            });

            if (self.filters.closing_after && self.filters.closing_before)
                _closingFpInst.setDate([self.filters.closing_after, self.filters.closing_before], false);
        },

        // ── State persistence ──────────────────────────────────────────
        _loadState() {
            const p = new URLSearchParams(location.search);
            if (p.has('q'))  this.filters.search         = p.get('q');
            if (p.has('sg')) this.filters.stage_id       = p.get('sg');
            if (p.has('sr')) this.filters.source_id      = p.get('sr');
            if (p.has('st')) this.filters.status         = p.get('st');
            if (p.has('ca')) this.filters.closing_after  = p.get('ca');
            if (p.has('cb')) this.filters.closing_before = p.get('cb');
            if (p.has('cp')) this.closingPreset          = p.get('cp');
        },

        _saveState() {
            const p = new URLSearchParams(), f = this.filters;
            if (f.search)           p.set('q',  f.search);
            if (f.stage_id)         p.set('sg', f.stage_id);
            if (f.source_id)        p.set('sr', f.source_id);
            if (f.status)           p.set('st', f.status);
            if (f.closing_after)    p.set('ca', f.closing_after);
            if (f.closing_before)   p.set('cb', f.closing_before);
            if (this.closingPreset) p.set('cp', this.closingPreset);
            const qs = p.toString();
            history.replaceState(null, '', qs ? `?${qs}` : location.pathname);
        },

        // ── Core actions ───────────────────────────────────────────────
        refresh()        { _tableInst?.replaceData(); },
        onFilterChange() { this._saveState(); this.refresh(); },
        clearSearch()    { this.filters.search = ''; this._saveState(); this.refresh(); },

        clearClosing() {
            this.closingPreset = '';
            this.filters.closing_after  = '';
            this.filters.closing_before = '';
            _closingFpInst?.clear(false);
            this._saveState(); this.refresh();
        },

        setClosingPreset(preset) {
            const now = new Date(), y = now.getFullYear(), m = now.getMonth(), d = now.getDate();
            let from, to;
            if      (preset === 'week')    { from = isoDate(now); to = isoDate(new Date(y, m, d + 7)); }
            else if (preset === 'month')   { from = isoDate(new Date(y, m, 1)); to = isoDate(new Date(y, m + 1, 0)); }
            else if (preset === 'overdue') { from = '2020-01-01'; to = isoDate(new Date(y, m, d - 1)); }
            else return;

            this.closingPreset          = preset;
            this.filters.closing_after  = from;
            this.filters.closing_before = to;
            _closingFpInst?.setDate([from, to], false);
            this._saveState(); this.refresh();
        },

        async switchToKanban() {
            localStorage.setItem(LS_VIEW, 'kanban');
            this.view = 'kanban';
            if (!this.kanbanBoard.length) await this.loadKanban();
        },

        async loadKanban() {
            this.kanbanLoading = true;
            try {
                const res  = await fetch(apiKanban, {
                    headers: {
                        'X-CSRF-TOKEN':     csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept':           'application/json',
                    },
                });
                const json = await res.json();
                this.kanbanBoard = json.data ?? [];
            } catch (e) {
                console.error('[lead] kanban load failed', e);
            } finally {
                this.kanbanLoading = false;
            }
        },

        removeChip(key) {
            if (key === 'search')  this.filters.search    = '';
            if (key === 'stage')   { this.filters.stage_id  = ''; _stageTsInst?.clear(true); }
            if (key === 'source')  { this.filters.source_id = ''; _sourceTsInst?.clear(true); }
            if (key === 'status')  { this.filters.status    = ''; _statusTsInst?.clear(true); }
            if (key === 'closing') { this.clearClosing(); return; }
            this._saveState(); this.refresh();
        },

        reset() {
            this.filters       = { search: '', stage_id: '', source_id: '', status: '', closing_after: '', closing_before: '' };
            this.closingPreset = '';
            _stageTsInst?.clear(true);
            _sourceTsInst?.clear(true);
            _statusTsInst?.clear(true);
            _closingFpInst?.clear(false);
            history.replaceState(null, '', location.pathname);
            this.refresh();
        },

        toggleCol(field) {
            if (this.hiddenCols.includes(field)) {
                this.hiddenCols = this.hiddenCols.filter(f => f !== field);
                _tableInst?.showColumn(field);
            } else {
                this.hiddenCols.push(field);
                _tableInst?.hideColumn(field);
            }
            try { localStorage.setItem(LS_COLS, JSON.stringify(this.hiddenCols)); } catch {}
        },

        // ── Kanban helpers ─────────────────────────────────────────────
        formatCurrency: (val, currency) => formatMoney(val, currency),
        formatDate(iso) {
            if (!iso) return '';
            const [, mo, d] = iso.split('-');
            return `${d}/${mo}`;
        },

    };
}

// ── Đăng ký Alpine component ────────────────────────────────────────────────
//
// Alpine v3 tìm `x-data="leadListPage"` theo 2 cách:
//   1. Alpine.data registry (đăng ký qua alpine:init)
//   2. window.leadListPage (global fallback)
//
// Gán window.leadListPage NGAY KHI MODULE LOAD để đảm bảo Alpine
// luôn tìm thấy component dù alpine:init chưa fire (race condition
// khi Vite bundle load muộn hơn Alpine.start()).

window.leadListPage = _leadListPageFactory;

document.addEventListener('alpine:init', () => {
    Alpine.data('leadListPage', _leadListPageFactory);
});
