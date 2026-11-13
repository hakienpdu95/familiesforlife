import { t } from '../../../../../../resources/js/shared/i18n.js';

const COLLAPSED_KEY = 'post-category-tree:collapsed';

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function foldChar(c) {
    return c.normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'D').toLowerCase();
}

function fold(text) {
    return [...String(text ?? '')].map(foldChar).join('');
}

function highlight(text, needle) {
    const raw = String(text ?? '');
    if (!needle) return esc(raw);
    const chars = [...raw];
    const folded = chars.map(foldChar);
    if (folded.some((c) => c.length !== 1)) return esc(raw);
    const start = folded.join('').indexOf(needle);
    if (start < 0) return esc(raw);
    const end = start + needle.length;
    return esc(chars.slice(0, start).join(''))
        + '<mark class="rounded bg-warning/40 px-0.5 text-inherit">' + esc(chars.slice(start, end).join('')) + '</mark>'
        + esc(chars.slice(end).join(''));
}

const ICON = {
    chevron: '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>',
    add: '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>',
    edit: '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>',
    more: '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><circle cx="5" cy="12" r="1.75"/><circle cx="12" cy="12" r="1.75"/><circle cx="19" cy="12" r="1.75"/></svg>',
};

function buildColumns(state) {
    return [
        {
            title: t('Danh mục'), field: 'name', minWidth: 240, headerSort: false, responsive: 0,
            formatter(cell) {
                const d = cell.getRow().getData();
                const root = d.depth === 0;
                const context = state.filtering && !state.matchedIds.has(d.id);
                const name = d.can_update
                    ? '<a href="' + esc(d.edit_url) + '" title="' + esc(d.slug) + '" class="truncate hover:text-primary hover:underline underline-offset-2">' + highlight(d.name, state.needle) + '</a>'
                    : '<span class="truncate" title="' + esc(d.slug) + '">' + highlight(d.name, state.needle) + '</span>';
                const kids = d.children_count
                    ? '<span class="shrink-0 text-xs text-base-content/40">' + esc(t(':count mục con').replace(':count', d.children_count)) + '</span>'
                    : '';
                const hidden = d.is_active ? '' : '<span class="badge badge-xs badge-ghost shrink-0">' + esc(t('Đang ẩn')) + '</span>';
                const translated = Object.entries(d.translations || {}).filter(([, v]) => v);
                const sub = translated.length
                    ? '<div class="truncate text-xs text-base-content/45">' + translated.map(([locale, v]) =>
                        '<span class="mr-1 text-[10px] font-semibold uppercase tracking-wide text-base-content/35">' + esc(locale) + '</span>' + highlight(v, state.needle)).join(' · ') + '</div>'
                    : '';

                return '<div class="category-name-cell inline-flex items-center gap-2.5 min-w-0' + (context ? ' opacity-45' : '') + (d.is_active ? '' : ' is-inactive') + '">'
                    + '<div class="min-w-0"><div class="flex items-center gap-2 min-w-0 ' + (root ? 'text-[15px] font-semibold' : 'text-sm font-medium') + '">' + name + kids + hidden + '</div>' + sub + '</div>'
                    + '</div>';
            },
        },
        {
            title: t('Bài viết'), field: 'subtree_articles', width: 110, hozAlign: 'right', headerHozAlign: 'right', headerSort: false, responsive: 1,
            formatter(cell) {
                const d = cell.getRow().getData();
                const total = d.subtree_articles;
                const tip = total > d.articles_count
                    ? ' title="' + esc(t(':own bài trực tiếp, :total bài tính cả danh mục con').replace(':own', d.articles_count).replace(':total', total)) + '"'
                    : '';
                return '<span class="text-xs tabular-nums ' + (total ? 'text-base-content/70' : 'text-base-content/30') + '"' + tip + '>'
                    + esc(t(':count bài').replace(':count', total)) + '</span>';
            },
        },
        {
            title: '', field: 'id', width: state.compact ? 76 : 108, hozAlign: 'right', headerSort: false, responsive: 0,
            formatter(cell) {
                const d = cell.getRow().getData();
                let html = '<div class="category-actions flex items-center justify-end gap-0.5">';
                if (d.create_child_url && !state.compact) {
                    html += '<a href="' + esc(d.create_child_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/60" title="' + esc(t('Thêm danh mục con')) + '">' + ICON.add + '</a>';
                }
                if (d.can_update) {
                    html += '<a href="' + esc(d.edit_url) + '" class="btn btn-ghost btn-xs btn-square text-base-content/60" title="' + esc(t('Sửa')) + '">' + ICON.edit + '</a>';
                }
                html += '<button type="button" class="btn btn-ghost btn-xs btn-square text-base-content/60" data-row-menu="' + d.id + '" title="' + esc(t('Thao tác khác')) + '">' + ICON.more + '</button>';
                return html + '</div>';
            },
        },
    ];
}

function menuItems(d, labels) {
    const items = [];
    if (d.public_url) items.push('<li><a href="' + esc(d.public_url) + '" target="_blank" rel="noopener">' + esc(t('Xem trên cổng thông tin')) + '</a></li>');
    if (d.create_child_url) items.push('<li><a href="' + esc(d.create_child_url) + '">' + esc(t('Thêm danh mục con')) + '</a></li>');
    if (d.can_update) {
        Object.entries(d.translations || {}).forEach(([locale, value]) => {
            const label = (value ? t('Sửa bản dịch :language') : t('Dịch sang :language')).replace(':language', labels[locale] || locale.toUpperCase());
            items.push('<li><a href="' + esc(d.edit_url) + '#translation-' + esc(locale) + '">' + esc(label) + '</a></li>');
        });
    }
    if (d.can_delete) {
        items.push(d.delete_blocker
            ? '<li class="menu-disabled mt-1 border-t border-base-200 pt-1"><span class="block"><span class="block">' + esc(t('Xoá danh mục')) + '</span><span class="block text-[11px]">'
                + esc(t('Không thể xoá: :reason').replace(':reason', d.delete_blocker)) + '</span></span></li>'
            : '<li class="mt-1 border-t border-base-200 pt-1"><button type="button" class="text-error" data-delete-url="' + esc(d.destroy_url) + '" data-delete-name="' + esc(d.name) + '">' + esc(t('Xoá danh mục')) + '</button></li>');
    }
    return items.join('');
}

let pendingDeleteUrl = null;

window.categoryDeleteConfirm = function (url, name) {
    pendingDeleteUrl = url;
    const nameEl = document.getElementById('categoryDeleteItemName');
    if (nameEl) nameEl.textContent = '"' + name + '"';
    document.getElementById('categoryDeleteModal')?.showModal();
};

document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('categoryConfirmDeleteBtn');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async function () {
        if (!pendingDeleteUrl) return;

        const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        this.disabled = true;
        this.textContent = t('Đang xoá...');

        try {
            const res = await fetch(pendingDeleteUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: '_method=DELETE',
            });
            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                window.location.reload();
                return;
            }
            alert(data.message || t('Xoá thất bại. Vui lòng thử lại.'));
        } catch (e) {
            console.error('[post-category] delete failed', e);
            alert(t('Lỗi kết nối. Vui lòng thử lại.'));
        } finally {
            this.disabled = false;
            this.textContent = t('Xoá');
            pendingDeleteUrl = null;
        }
    });
});

document.addEventListener('alpine:init', () => {
    Alpine.data('categoryTreePage', (labels = {}) => {
        let table = null;
        const collapsed = (() => {
            try { return new Set(JSON.parse(localStorage.getItem(COLLAPSED_KEY) || '[]')); } catch { return new Set(); }
        })();
        const saveCollapsed = () => localStorage.setItem(COLLAPSED_KEY, JSON.stringify([...collapsed]));
        const state = {
            needle: '',
            filtering: false,
            matchedIds: new Set(),
            compact: window.matchMedia('(max-width: 639px)').matches,
        };
        let rows = [];
        let visibleIds = new Set();
        const byId = new Map();

        const walk = (nodes, fn, parent = null) => nodes.forEach((n) => {
            fn(n, parent);
            if (n.children) walk(n.children, fn, n);
        });
        const eachRow = (list, fn) => list.forEach((row) => {
            fn(row);
            eachRow(row.getTreeChildren(), fn);
        });

        return {
            search: '',
            status: '',
            matched: 0,
            filtering: false,

            init() {
                rows = JSON.parse(document.getElementById('category-tree-data')?.textContent || '[]');
                walk(rows, (n, parent) => byId.set(n.id, { node: n, parent }));

                const p = new URLSearchParams(location.search);
                this.search = p.get('q') || '';
                this.status = p.get('status') || '';

                this.$nextTick(() => this.setup());

                document.addEventListener('keydown', (e) => {
                    if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) {
                        e.preventDefault();
                        this.$refs.search?.focus();
                    }
                });
            },

            setup() {
                table = new window.Tabulator('#category-table', {
                    data: rows,
                    index: 'id',
                    dataTree: true,
                    dataTreeChildField: 'children',
                    dataTreeChildIndent: 24,
                    dataTreeStartExpanded: (row) => !collapsed.has(row.getData().id),
                    dataTreeCollapseElement: '<span class="category-tree-toggle is-open">' + ICON.chevron + '</span>',
                    dataTreeExpandElement: '<span class="category-tree-toggle">' + ICON.chevron + '</span>',
                    dataTreeFilter: true,
                    layout: 'fitColumns',
                    responsiveLayout: 'hide',
                    headerVisible: true,
                    rowFormatter(row) {
                        const d = row.getData();
                        row.getElement().classList.toggle('category-row-root', d.depth === 0);
                        row.getElement().classList.toggle('category-row-inactive', !d.is_active);
                    },
                    columns: buildColumns(state),
                    placeholder: '<div class="py-14 text-center text-sm opacity-50">' + esc(t('Không có danh mục nào khớp bộ lọc')) + '</div>',
                });

                table.on('dataTreeRowCollapsed', (row) => {
                    if (state.filtering) return;
                    collapsed.add(row.getData().id);
                    saveCollapsed();
                });
                table.on('dataTreeRowExpanded', (row) => {
                    if (state.filtering) return;
                    collapsed.delete(row.getData().id);
                    saveCollapsed();
                });
                table.on('tableBuilt', () => this.apply(false));

                this.setupMenu();
                window.categoryTable = table;
            },

            setupMenu() {
                const menu = document.getElementById('categoryRowMenu');
                if (!menu) return;
                const close = () => menu.classList.add('hidden');

                document.addEventListener('click', (e) => {
                    const trigger = e.target.closest('[data-row-menu]');
                    if (trigger) {
                        e.preventDefault();
                        const entry = byId.get(Number(trigger.dataset.rowMenu));
                        if (!entry) return;
                        menu.innerHTML = menuItems(entry.node, labels);
                        menu.classList.remove('hidden');
                        const rect = trigger.getBoundingClientRect();
                        const top = rect.bottom + 4 + menu.offsetHeight > window.innerHeight ? rect.top - menu.offsetHeight - 4 : rect.bottom + 4;
                        menu.style.top = Math.max(8, top) + 'px';
                        menu.style.left = Math.max(8, rect.right - menu.offsetWidth) + 'px';
                        return;
                    }
                    const del = e.target.closest('[data-delete-url]');
                    if (del) {
                        close();
                        window.categoryDeleteConfirm(del.dataset.deleteUrl, del.dataset.deleteName);
                        return;
                    }
                    if (!menu.contains(e.target)) close();
                });
                document.addEventListener('keydown', (e) => e.key === 'Escape' && close());
                window.addEventListener('scroll', close, { passive: true });
                window.addEventListener('resize', close);
            },

            matches(n) {
                if (state.needle && !n.search_text.includes(state.needle)) return false;
                if (this.status === 'inactive') return !n.is_active;
                if (this.status === 'untranslated') return !n.is_translated;
                return true;
            },

            apply(syncUrl = true) {
                state.needle = fold(this.search.trim());
                state.filtering = !!(state.needle || this.status);
                state.matchedIds = new Set();
                visibleIds = new Set();

                if (state.filtering) {
                    walk(rows, (n) => {
                        if (!this.matches(n)) return;
                        state.matchedIds.add(n.id);
                        for (let e = byId.get(n.id); e; e = e.parent ? byId.get(e.parent.id) : null) visibleIds.add(e.node.id);
                    });
                }

                this.filtering = state.filtering;
                this.matched = state.matchedIds.size;

                if (syncUrl) {
                    const p = new URLSearchParams();
                    if (this.search.trim()) p.set('q', this.search.trim());
                    if (this.status) p.set('status', this.status);
                    history.replaceState(null, '', p.toString() ? '?' + p : location.pathname);
                }

                if (!table) return;
                if (state.filtering) {
                    table.setFilter((data) => visibleIds.has(data.id));
                    eachRow(table.getRows(), (row) => row.getTreeChildren().length && row.treeExpand());
                } else {
                    table.clearFilter();
                    eachRow(table.getRows(), (row) => {
                        if (!row.getTreeChildren().length) return;
                        collapsed.has(row.getData().id) ? row.treeCollapse() : row.treeExpand();
                    });
                }
                table.getRows().forEach((row) => eachRow([row], (r) => r.reformat()));
            },

            setStatus(value) {
                this.status = value;
                this.apply();
            },

            clearFilters() {
                this.search = '';
                this.status = '';
                this.apply();
            },

            setAll(open) {
                if (!table) return;
                eachRow(table.getRows(), (row) => {
                    if (!row.getTreeChildren().length) return;
                    open ? row.treeExpand() : row.treeCollapse();
                });
            },
        };
    });
});
