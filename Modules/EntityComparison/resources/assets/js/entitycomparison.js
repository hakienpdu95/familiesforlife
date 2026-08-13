/**
 * Tabulator cho các trang admin dashboard/entity-comparison/* — 1 file JS asset chung cho cả
 * module (đúng convention Ocop/Heritage: 1 file/module, không tách theo trang khi module còn
 * nhỏ). Gồm 2 kiểu bảng khác nhau:
 *   - criteria (dataTree, nhóm theo EntityType — đối tượng có nhiều tiêu chí) — đúng pattern
 *     Modules/Post/resources/assets/js/pages/category-index.js.
 *   - entity-types (remote pagination/sort, danh sách phẳng) — đúng pattern
 *     Modules/Post/resources/assets/js/pages/article-index.js.
 *
 * `index: 'row_id'` ở bảng criteria — QUAN TRỌNG: EntityType và Criterion là 2 bảng khác nhau, id
 * tự tăng của chúng chắc chắn trùng nhau (VD entity_types.id=1 và criteria.id=1 cùng tồn tại). Nếu
 * dùng field `id` thô làm khoá nhận diện hàng (mặc định của Tabulator), select/toggle sẽ nhầm hàng.
 */

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
}

/** Wiring chung cho modal xác nhận xoá kiểu dialog — dùng lại cho mọi resource trong module (criterion/entity-type/...). */
function setupDeleteConfirm({ globalFnName, modalId, itemNameId, confirmBtnId, tableGlobalName, successMessage, errorMessage }) {
    let pendingDeleteUrl = null;

    window[globalFnName] = (url, name) => {
        pendingDeleteUrl = url;
        const nameEl = document.getElementById(itemNameId);
        if (nameEl) nameEl.textContent = name;
        document.getElementById(modalId)?.showModal();
    };

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById(confirmBtnId)?.addEventListener('click', async () => {
            if (! pendingDeleteUrl) return;

            try {
                const res = await fetch(pendingDeleteUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: new URLSearchParams({ _method: 'DELETE' }),
                });

                if (res.ok) {
                    window.Toast?.success(successMessage);
                    window[tableGlobalName]?.replaceData();
                } else {
                    window.Toast?.error(errorMessage);
                }
            } catch (error) {
                console.error(`[entity-comparison] delete error (${globalFnName})`, error);
                window.Toast?.error(errorMessage);
            } finally {
                pendingDeleteUrl = null;
                document.getElementById(modalId)?.close();
            }
        });
    });
}

setupDeleteConfirm({
    globalFnName: 'criterionDeleteConfirm',
    modalId: 'criterionDeleteModal',
    itemNameId: 'criterionDeleteItemName',
    confirmBtnId: 'criterionConfirmDeleteBtn',
    tableGlobalName: 'criterionTable',
    successMessage: 'Đã xoá tiêu chí.',
    errorMessage: 'Không thể xoá tiêu chí.',
});

setupDeleteConfirm({
    globalFnName: 'entityTypeDeleteConfirm',
    modalId: 'entityTypeDeleteModal',
    itemNameId: 'entityTypeDeleteItemName',
    confirmBtnId: 'entityTypeConfirmDeleteBtn',
    tableGlobalName: 'entityTypeTable',
    successMessage: 'Đã xoá loại đối tượng.',
    errorMessage: 'Không thể xoá loại đối tượng.',
});

setupDeleteConfirm({
    globalFnName: 'entityDeleteConfirm',
    modalId: 'entityDeleteModal',
    itemNameId: 'entityDeleteItemName',
    confirmBtnId: 'entityConfirmDeleteBtn',
    tableGlobalName: 'entityTable',
    successMessage: 'Đã xoá đối tượng.',
    errorMessage: 'Không thể xoá đối tượng.',
});

// ── dashboard/entity-comparison/criteria — dataTree ─────────────────────────

const CRITERION_COLUMNS = [
    {
        title: 'Tên',
        field: 'name',
        widthGrow: 3,
        formatter: (cell) => {
            const d = cell.getData();
            if (d.row_type === 'entity_type') {
                const activeBadge = d.is_active ? '' : ' <span class="badge badge-ghost badge-xs">tắt</span>';
                return `<span class="font-bold">${escapeHtml(d.name)}</span> `
                    + `<span class="badge badge-ghost badge-xs">${d.criteria_count} tiêu chí</span>${activeBadge}`;
            }
            const requiredBadge = d.is_required ? ' <span class="badge badge-warning badge-xs">bắt buộc</span>' : '';
            return `<span>${escapeHtml(d.name)}</span>${requiredBadge}`;
        },
    },
    {
        title: 'Kiểu',
        field: 'type_label',
        widthGrow: 1,
        formatter: (cell) => (cell.getData().row_type === 'criterion' ? escapeHtml(cell.getValue() || '') : ''),
    },
    {
        title: 'Đơn vị',
        field: 'unit',
        widthGrow: 1,
        formatter: (cell) => (cell.getData().row_type === 'criterion' ? escapeHtml(cell.getValue() || '—') : ''),
    },
    {
        title: 'Lọc',
        field: 'is_filterable',
        hozAlign: 'center',
        width: 70,
        formatter: (cell) => (cell.getData().row_type === 'criterion' ? (cell.getValue() ? '✓' : '—') : ''),
    },
    {
        title: 'So sánh',
        field: 'is_comparable',
        hozAlign: 'center',
        width: 80,
        formatter: (cell) => (cell.getData().row_type === 'criterion' ? (cell.getValue() ? '✓' : '—') : ''),
    },
    {
        title: '',
        field: 'actions',
        hozAlign: 'right',
        widthGrow: 1,
        formatter: (cell) => {
            const d = cell.getData();

            if (d.row_type === 'entity_type') {
                return `<a href="${d.criteria_url}" class="btn btn-ghost btn-xs">Quản lý tiêu chí</a>`;
            }

            let html = '';
            if (d.can_update) {
                html += `<a href="${d.edit_url}" class="btn btn-ghost btn-xs">Sửa</a> `;
            }
            if (d.can_delete) {
                html += `<button onclick="criterionDeleteConfirm('${d.destroy_url}', '${escapeHtml(d.name)}')" `
                    + `class="btn btn-ghost btn-xs text-error">Xoá</button>`;
            }
            return html;
        },
    },
];

document.addEventListener('alpine:init', () => {
    Alpine.data('criterionListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;
        let tableInst = null;

        return {
            filters: { search: '' },

            init() {
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#criterion-table', {
                    ajaxURL: apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {};
                        if (self.filters.search) p.search = self.filters.search;
                        return p;
                    },
                    ajaxResponse: (_url, _params, response) => response.data,
                    ajaxError: (error) => console.error('[entity-comparison-criterion] API error', error),

                    index: 'row_id',
                    dataTree: true,
                    dataTreeChildField: 'children',
                    dataTreeStartExpanded: true,
                    dataTreeChildIndent: 20,

                    pagination: false,
                    layout: 'fitColumns',
                    responsiveLayout: 'collapse',

                    columns: CRITERION_COLUMNS,
                    placeholder: '<div class="py-16 text-center opacity-40">Chưa có tiêu chí nào.</div>',
                });

                window.criterionTable = tableInst;
            },

            onFilterChange() {
                tableInst?.replaceData();
            },

            clearSearch() {
                this.filters.search = '';
                this.onFilterChange();
            },

            get hasFilters() {
                return !! this.filters.search;
            },

            reset() {
                this.clearSearch();
            },
        };
    });
});

// ── dashboard/entity-comparison/entity-types — remote pagination/sort ──────
// Đúng pattern Modules/Post/resources/assets/js/pages/article-index.js.

const ENTITY_TYPE_COLUMNS = [
    {
        title: 'Loại đối tượng',
        field: 'name',
        minWidth: 220,
        sorter: 'string',
        formatter: (cell) => {
            const d = cell.getData();
            const cover = d.cover_url
                ? `<img src="${escapeHtml(d.cover_url)}" alt="" class="w-8 h-8 rounded object-cover shrink-0">`
                : '<div class="w-8 h-8 rounded bg-base-200 shrink-0"></div>';
            const activeBadge = d.is_active ? '' : ' <span class="badge badge-ghost badge-xs">tắt</span>';
            return `<div class="flex items-center gap-2">${cover}`
                + `<span class="font-medium text-sm">${escapeHtml(d.name)}</span>${activeBadge}</div>`;
        },
    },
    {
        title: 'Slug',
        field: 'slug',
        width: 160,
        headerSort: false,
        formatter: (cell) => `<span class="text-xs text-base-content/50">${escapeHtml(cell.getValue())}</span>`,
    },
    {
        title: 'Đối tượng',
        field: 'entities_count',
        width: 100,
        hozAlign: 'center',
        sorter: 'number',
    },
    {
        title: 'Tiêu chí',
        field: 'criteria_count',
        width: 100,
        hozAlign: 'center',
        headerSort: false,
    },
    {
        title: 'Thứ tự',
        field: 'sort_order',
        width: 90,
        hozAlign: 'center',
        sorter: 'number',
    },
    {
        title: '',
        field: 'actions',
        hozAlign: 'right',
        widthGrow: 1,
        headerSort: false,
        formatter: (cell) => {
            const d = cell.getData();
            let html = `<a href="${d.criteria_url}" class="btn btn-ghost btn-xs">Tiêu chí</a> `;
            if (d.can_update) {
                html += `<a href="${d.edit_url}" class="btn btn-ghost btn-xs">Sửa</a> `;
            }
            if (d.can_delete) {
                html += `<button onclick="entityTypeDeleteConfirm('${d.destroy_url}', '${escapeHtml(d.name)}')" `
                    + `class="btn btn-ghost btn-xs text-error">Xoá</button>`;
            }
            return html;
        },
    },
];

document.addEventListener('alpine:init', () => {
    Alpine.data('entityTypeListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;
        let tableInst = null;

        return {
            filters: { search: '' },

            get hasFilters() {
                return !! this.filters.search;
            },

            init() {
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#entity-type-table', {
                    ajaxURL: apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {};
                        if (self.filters.search) p.search = self.filters.search;
                        return p;
                    },
                    ajaxResponse: (_url, _params, response) => response,
                    ajaxError: (error) => console.error('[entity-comparison-entity-type] API error', error),

                    pagination: true,
                    paginationMode: 'remote',
                    paginationSize: 25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter: 'rows',
                    sortMode: 'remote',
                    initialSort: [{ column: 'sort_order', dir: 'asc' }],

                    layout: 'fitColumns',
                    responsiveLayout: 'collapse',

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

                    columns: ENTITY_TYPE_COLUMNS,
                    placeholder: '<div class="py-16 text-center opacity-40">Chưa có loại đối tượng nào.</div>',
                });

                window.entityTypeTable = tableInst;
            },

            onFilterChange() {
                tableInst?.replaceData();
            },

            clearSearch() {
                this.filters.search = '';
                this.onFilterChange();
            },

            reset() {
                this.clearSearch();
            },
        };
    });
});

// ── dashboard/entity-comparison/entities — remote pagination/sort ──────────
// Đúng pattern Modules/Post/resources/assets/js/pages/article-index.js.

const ENTITY_COLUMNS = [
    {
        title: 'Tên',
        field: 'name',
        minWidth: 220,
        sorter: 'string',
        formatter: (cell) => {
            const d = cell.getData();
            const cover = d.cover_url
                ? `<img src="${escapeHtml(d.cover_url)}" alt="" class="w-8 h-8 rounded object-cover shrink-0">`
                : '<div class="w-8 h-8 rounded bg-base-200 shrink-0"></div>';
            const activeBadge = d.is_active ? '' : ' <span class="badge badge-ghost badge-xs">tắt</span>';
            return `<div class="flex items-center gap-2">${cover}`
                + `<span class="font-medium text-sm">${escapeHtml(d.name)}</span>${activeBadge}</div>`;
        },
    },
    {
        title: 'Loại đối tượng',
        field: 'entity_type_name',
        minWidth: 160,
        headerSort: false,
        formatter: (cell) => escapeHtml(cell.getValue()) || '<span class="text-base-content/25 text-xs">—</span>',
    },
    {
        title: 'Thứ tự',
        field: 'sort_order',
        width: 90,
        hozAlign: 'center',
        sorter: 'number',
    },
    {
        title: '',
        field: 'actions',
        hozAlign: 'right',
        widthGrow: 1,
        headerSort: false,
        formatter: (cell) => {
            const d = cell.getData();
            let html = '';
            if (d.can_update) {
                html += `<a href="${d.edit_url}" class="btn btn-ghost btn-xs">Sửa</a> `;
            }
            if (d.can_delete) {
                html += `<button onclick="entityDeleteConfirm('${d.destroy_url}', '${escapeHtml(d.name)}')" `
                    + `class="btn btn-ghost btn-xs text-error">Xoá</button>`;
            }
            return html;
        },
    },
];

document.addEventListener('alpine:init', () => {
    Alpine.data('entityListPage', (serverData = {}) => {
        const { apiUrl = '' } = serverData;
        let tableInst = null;

        return {
            filters: { search: '', entity_type_id: '' },

            get hasFilters() {
                return !! (this.filters.search || this.filters.entity_type_id);
            },

            init() {
                this.$nextTick(() => this._setup());
            },

            _setup() {
                const self = this;

                tableInst = new window.Tabulator('#entity-table', {
                    ajaxURL: apiUrl,
                    ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                    ajaxParams() {
                        const p = {}, f = self.filters;
                        if (f.search) p.search = f.search;
                        if (f.entity_type_id) p.entity_type_id = f.entity_type_id;
                        return p;
                    },
                    ajaxResponse: (_url, _params, response) => response,
                    ajaxError: (error) => console.error('[entity-comparison-entity] API error', error),

                    pagination: true,
                    paginationMode: 'remote',
                    paginationSize: 25,
                    paginationSizeSelector: [10, 25, 50, 100],
                    paginationCounter: 'rows',
                    sortMode: 'remote',
                    initialSort: [{ column: 'name', dir: 'asc' }],

                    layout: 'fitColumns',
                    responsiveLayout: 'collapse',

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

                    columns: ENTITY_COLUMNS,
                    placeholder: '<div class="py-16 text-center opacity-40">Chưa có đối tượng nào.</div>',
                });

                window.entityTable = tableInst;
            },

            onFilterChange() {
                tableInst?.replaceData();
            },

            clearSearch() {
                this.filters.search = '';
                this.onFilterChange();
            },

            reset() {
                this.filters = { search: '', entity_type_id: '' };
                this.onFilterChange();
            },
        };
    });
});
