import { createTs } from '@shared/tom-select-factory.js';

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

const EMPTY = '<span class="text-base-content/25 text-xs">—</span>';

const ICON_EDIT = 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z';

function iconSvg(path) {
    return '<svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' + path + '"/></svg>';
}

function withTooltip(label, inner) {
    return '<span class="tooltip tooltip-left" data-tip="' + esc(label) + '">' + inner + '</span>';
}

function actionButton(url, method, confirmText, cls, title, icon) {
    return withTooltip(title, '<button class="btn btn-ghost btn-sm btn-square ' + cls + '" aria-label="' + esc(title) + '"'
        + ' data-url="' + esc(url) + '" data-method="' + method + '" data-confirm="' + esc(confirmText) + '"'
        + ' onclick="window.platformUserAction(this.dataset)">' + iconSvg(icon) + '</button>');
}

const COLUMNS = [
    {
        formatter: 'rowSelection', titleFormatter: 'rowSelection', hozAlign: 'center', headerHozAlign: 'center',
        width: 44, headerSort: false, frozen: true, download: false,
    },
    {
        title: 'Tên', field: 'name', minWidth: 200, sorter: 'string', frozen: true,
        formatter(cell) {
            return '<span class="font-medium text-sm">' + esc(cell.getValue()) + '</span>';
        },
    },
    {
        title: 'Email', field: 'email', minWidth: 220, sorter: 'string',
        formatter(cell) {
            return '<span class="text-sm text-base-content/70">' + esc(cell.getValue()) + '</span>';
        },
    },
    {
        title: 'Vai trò', field: 'role_label', minWidth: 180, headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            return d.role_label ? '<span class="badge badge-soft badge-sm ' + esc(d.role_badge) + '">' + esc(d.role_label) + '</span>' : EMPTY;
        },
    },
    {
        title: 'Trạng thái', field: 'is_active', width: 150, hozAlign: 'center', headerSort: false,
        accessorDownload: (v) => (v ? 'Hoạt động' : 'Đã vô hiệu hoá'),
        formatter(cell) {
            return cell.getValue()
                ? '<span class="badge badge-success badge-sm">Hoạt động</span>'
                : '<span class="badge badge-ghost badge-sm">Đã vô hiệu hoá</span>';
        },
    },
    {
        title: 'Ngày tạo', field: 'created_at', width: 110, hozAlign: 'center', sorter: 'string',
    },
    {
        title: 'Thao tác', field: 'id', width: 170, hozAlign: 'center', headerHozAlign: 'center', headerSort: false, frozen: true, download: false,
        cssClass: 'platform-user-actions',
        formatter(cell) {
            const d = cell.getRow().getData();
            if (!d.can_update) return '<span class="text-xs text-base-content/30">Không thể sửa qua đây</span>';

            let html = '<div class="flex items-center justify-center gap-3">';

            html += withTooltip('Sửa', '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-sm btn-square text-primary" aria-label="Sửa">' + iconSvg(ICON_EDIT) + '</a>');

            if (d.can_reset_password) {
                html += actionButton(d.reset_password_url, 'PATCH',
                    'Bạn có chắc chắn muốn reset mật khẩu tài khoản này về mặc định?',
                    'text-warning', 'Reset mật khẩu',
                    'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z');
            }

            if (d.can_deactivate) {
                html += actionButton(d.destroy_url, 'DELETE', 'Vô hiệu hoá tài khoản này?',
                    'text-error', 'Vô hiệu hoá',
                    'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636');
            }

            if (d.can_activate) {
                html += actionButton(d.activate_url, 'PATCH', 'Kích hoạt lại tài khoản này?',
                    'text-success', 'Kích hoạt lại',
                    'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z');
            }

            html += '</div>';
            return html;
        },
    },
];

window.platformUserAction = function ({ url, method, confirm: text }) {
    if (!window.confirm(text)) return;
    const form = document.getElementById('platformUserActionForm');
    if (!form) return;
    form.action = url;
    form.querySelector('input[name=_method]').value = method;
    form.submit();
};

document.addEventListener('alpine:init', () => {
    Alpine.data('platformUserListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;

        let tableInst = null;
        let tsRole    = null;
        let tsStatus  = null;

        const optText = (ts, v) => ts?.options?.[v]?.text ?? v;

        return {
            filters: { search: '', role: '', status: '' },
            selectedCount: 0,

            get hasFilters() {
                const f = this.filters;
                return !!(f.search || f.role || f.status);
            },

            get activeChips() {
                const chips = [], f = this.filters;
                if (f.search) chips.push({ key: 'search', label: 'Tìm: ' + f.search });
                if (f.role)   chips.push({ key: 'role',   label: optText(tsRole, f.role) });
                if (f.status) chips.push({ key: 'status', label: optText(tsStatus, f.status) });
                return chips;
            },

            init() {
                this.loadState();
                this.$nextTick(() => { this._setup(); this._initTomSelects(); });
            },

            _initTomSelects() {
                const roleEl   = document.getElementById('ts-platform-user-role');
                const statusEl = document.getElementById('ts-platform-user-status');
                if (!roleEl || !statusEl) return;

                tsRole = createTs(roleEl, {
                    placeholder: 'Tất cả vai trò',
                    onChange() { roleEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });

                tsStatus = createTs(statusEl, {
                    placeholder: 'Tất cả trạng thái',
                    onChange() { statusEl.dispatchEvent(new Event('change', { bubbles: true })); },
                });
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#platform-user-table', {
                    ajaxURL:    apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search) p.search = f.search;
                        if (f.role)   p.role   = f.role;
                        if (f.status) p.status = f.status;
                        return p;
                    },
                    ajaxResponse: (_u, _p, res) => res,
                    ajaxError: (error) => console.error('[platform-users] API error', error),

                    pagination:             true,
                    paginationMode:         'remote',
                    paginationSize:         25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter:      'rows',
                    sortMode:               'remote',
                    initialSort:            [{ column: 'name', dir: 'asc' }],

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

                    selectableRows: 'highlight',

                    columns: COLUMNS,
                    placeholder: '<div class="py-16 text-center opacity-40">'
                        + '<svg class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>'
                        + '<p class="text-sm">Chưa có nhân sự Platform nào</p></div>',
                });

                tableInst.on('rowSelectionChanged', (rows) => { self.selectedCount = rows.length; });
                tableInst.on('dataProcessed', () => { self.selectedCount = 0; });

                window.platformUserTable = tableInst;
            },

            loadState() {
                const p = new URLSearchParams(location.search);
                if (p.has('q'))      this.filters.search = p.get('q');
                if (p.has('role'))   this.filters.role   = p.get('role');
                if (p.has('status')) this.filters.status = p.get('status');
            },

            saveState() {
                const p = new URLSearchParams(), f = this.filters;
                if (f.search) p.set('q', f.search);
                if (f.role)   p.set('role', f.role);
                if (f.status) p.set('status', f.status);
                const qs = p.toString();
                history.replaceState(null, '', qs ? '?' + qs : location.pathname);
            },

            clearSelection() { tableInst?.deselectRow(); },

            exportSelected() {
                tableInst?.download('csv', 'nhan-su-platform.csv', { bom: true }, 'selected');
            },

            bulkDeactivate() {
                const rows = tableInst?.getSelectedData() ?? [];
                if (!rows.length || !window.confirm('Vô hiệu hoá ' + rows.length + ' tài khoản đã chọn? Tài khoản không đủ điều kiện (Super Admin, chính bạn, đã vô hiệu hoá) sẽ được bỏ qua.')) return;

                const form = document.getElementById('platformUserBulkForm');
                if (!form) return;
                form.querySelectorAll('input[name="ids[]"]').forEach((el) => el.remove());
                rows.forEach((r) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = r.id;
                    form.appendChild(input);
                });
                form.submit();
            },

            refresh()        { tableInst?.replaceData(); },
            onFilterChange() { this.saveState(); this.refresh(); },
            clearSearch()    { this.filters.search = ''; this.onFilterChange(); },

            removeChip(key) {
                if (key === 'search') this.filters.search = '';
                if (key === 'role')   { this.filters.role = ''; tsRole?.setValue('', true); }
                if (key === 'status') { this.filters.status = ''; tsStatus?.setValue('', true); }
                this.onFilterChange();
            },

            reset() {
                this.filters = { search: '', role: '', status: '' };
                tsRole?.setValue('', true);
                tsStatus?.setValue('', true);
                history.replaceState(null, '', location.pathname);
                this.refresh();
            },
        };
    });
});
