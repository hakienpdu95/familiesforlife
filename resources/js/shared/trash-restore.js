import { t } from './i18n.js';

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

export const DELETED_AT_COLUMN = {
    title: t('Ngày xoá'), field: 'deleted_at', width: 140, hozAlign: 'center', sorter: 'string', visible: false,
};

export function restoreButtonHtml(row, label) {
    if (!row.restore_url) return '';
    return ('<button type="button" class="btn btn-success btn-xs gap-1" title="' + t('Khôi phục') + '"')
        + ' data-restore-url="' + esc(row.restore_url) + '" data-restore-label="' + esc(label) + '"'
        + ' data-restore-is-public="' + (row.is_public ? '1' : '0') + '">'
        + '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a5 5 0 015 5v2M3 10l4-4m-4 4l4 4"/></svg>'
        + (t('Khôi phục') + '</button>');
}

export function bindRestoreModal(getTable) {
    let pendingUrl = null;

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-restore-url]');
        const modal = document.getElementById('restoreModal');
        if (!btn || !modal) return;

        const isPublic = btn.dataset.restoreIsPublic === '1';
        pendingUrl = btn.dataset.restoreUrl;
        modal.querySelector('[data-restore-title]').textContent = '"' + btn.dataset.restoreLabel + '"';
        modal.querySelector('[data-restore-public]').hidden = !isPublic;
        modal.querySelector('[data-restore-private]').hidden = isPublic;
        modal.showModal();
    });

    document.addEventListener('click', async (e) => {
        const confirmBtn = e.target.closest('[data-restore-confirm]');
        if (!confirmBtn || !pendingUrl) return;

        const csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
        confirmBtn.disabled = true;
        confirmBtn.textContent = t('Đang khôi phục...');

        try {
            const res = await fetch(pendingUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            const data = await res.json().catch(() => ({}));

            if (res.ok) {
                document.getElementById('restoreModal')?.close();
                getTable()?.replaceData();
                window.Toast?.success?.(data.message || t('Đã khôi phục.'));
            } else {
                alert(data.message || t('Khôi phục thất bại. Vui lòng thử lại.'));
            }
        } catch (err) {
            console.error('[restore] failed', err);
            alert(t('Lỗi kết nối. Vui lòng thử lại.'));
        } finally {
            confirmBtn.disabled = false;
            confirmBtn.textContent = t('Khôi phục');
            pendingUrl = null;
        }
    });
}

export function trashState() {
    return {
        trashedCount: 0,

        setTrashed(value) {
            if (this.filters.trashed === value) return;
            this.filters.trashed = value;
            this.toggleTrashColumns();
            this.saveState();
            this.table()?.setSort([{ column: value ? 'deleted_at' : 'created_at', dir: 'desc' }]);
        },

        toggleTrashColumns() {
            const t = this.table();
            if (!t) return;
            this.filters.trashed ? t.showColumn('deleted_at') : t.hideColumn('deleted_at');
        },
    };
}
