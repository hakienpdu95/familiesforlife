/**
 * activity-log.js — Activity Log listing page controller
 *
 * Vite module: loaded via @vite() → deferred, runs before DOMContentLoaded.
 * Alpine.data registration fires via alpine:init → guaranteed before Alpine.start().
 *
 * Requires globals (core bundle): window.Alpine, window.Tabulator
 */

// ── Level config ────────────────────────────────────────────────────────────

const LEVEL_CFG = Object.freeze({
    1: { cls: 'badge-ghost',   label: 'Debug'    },
    2: { cls: 'badge-info',    label: 'Info'     },
    3: { cls: 'badge-warning', label: 'Warning'  },
    4: { cls: 'badge-error',   label: 'Error'    },
    5: { cls: 'badge-error',   label: 'Critical', extra: 'font-bold' },
});

// ── Formatter helpers (module scope — compiled once) ────────────────────────

function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function levelBadge(level) {
    const c = LEVEL_CFG[level] ?? { cls: 'badge-ghost', label: String(level), extra: '' };
    return `<span class="badge badge-sm ${c.cls} ${c.extra ?? ''}">${esc(c.label)}</span>`;
}

function actorHtml(row) {
    const name  = row.display_actor ?? '—';
    const type  = row.actor_type    ?? 'anonymous';
    const ip    = row.actor_ip;
    const init  = name.charAt(0) || '?';

    const avatarBg = type === 'user'
        ? 'background:oklch(var(--p));color:oklch(var(--pc))'
        : 'background:oklch(var(--b3));color:oklch(var(--bc))';

    const ipTag = ip
        ? `<span class="actlog-props" title="${esc(ip)}">${esc(ip)}</span>`
        : '';

    return `<div class="actlog-actor" title="${esc(name)}">
        <div class="actlog-actor-avatar" style="${avatarBg}">${esc(init)}</div>
        <div style="min-width:0">
            <div class="actlog-actor-name text-sm">${esc(name)}</div>
            ${ipTag}
        </div>
    </div>`;
}

function subjectHtml(row) {
    const label = row.display_subject;
    if (!label) return '<span class="opacity-30">—</span>';
    return `<div class="actlog-subject">
        <span class="text-sm" title="${esc(label)}">${esc(label)}</span>
    </div>`;
}

function descHtml(row) {
    const desc  = row.description;
    const props = row.props_preview;
    let html = '';
    if (desc)  html += `<div class="text-sm">${esc(desc)}</div>`;
    if (props) html += `<div class="actlog-props" title="${esc(props)}">${esc(props)}</div>`;
    return html || '<span class="opacity-30">—</span>';
}

// ── Tabulator column definitions ────────────────────────────────────────────

const COLUMNS = [
    {
        title: 'Thời gian', field: 'created_at', width: 148, sorter: 'datetime',
        formatter(cell) {
            const v = cell.getValue();
            if (!v) return '—';
            const d    = new Date(v);
            const date = d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' });
            const time = d.toLocaleTimeString('vi-VN', { hour12: false });
            return `<div class="text-sm">${date}</div><div class="actlog-props">${time}</div>`;
        },
    },
    {
        title: 'Cấp độ', field: 'level', width: 90, hozAlign: 'center', sorter: 'number',
        formatter(cell) { return levelBadge(cell.getValue()); },
    },
    {
        title: 'Module / Action', field: 'display_module', minWidth: 160,
        formatter(cell) {
            const row = cell.getData();
            return `<div class="text-sm font-medium">${esc(row.display_action ?? '—')}</div>`
                 + `<div class="actlog-props">${esc(row.display_module ?? '—')}</div>`;
        },
    },
    {
        title: 'Actor', field: 'display_actor', minWidth: 150,
        formatter(cell) { return actorHtml(cell.getData()); },
    },
    {
        title: 'Đối tượng', field: 'display_subject', minWidth: 150,
        formatter(cell) { return subjectHtml(cell.getData()); },
    },
    {
        title: 'Mô tả / Chi tiết', field: 'description', minWidth: 200,
        formatter(cell) { return descHtml(cell.getData()); },
    },
    {
        title: '', width: 58, hozAlign: 'center', headerSort: false,
        formatter() { return '<span class="btn btn-xs btn-ghost">Xem</span>'; },
        cellClick(_e, cell) {
            window.location = `/dashboard/activity-logs/${cell.getData().id}`;
        },
    },
];

// ── Alpine component ────────────────────────────────────────────────────────

document.addEventListener('alpine:init', () => {
    Alpine.data('activityLogIndex', () => ({
        stats: null,
        table: null,
        f: { date_from: '', date_to: '' },

        async init() {
            await this.loadStats();
            this.$nextTick(() => this._initTable());
        },

        async loadStats() {
            try {
                const r    = await fetch('/backend/api/activity-logs/stats?days=1');
                const data = await r.json();
                const byLevel = (v) => data.by_level?.find(l => l.level == v)?.count ?? 0;
                this.stats = {
                    total_today:    data.by_level?.reduce((s, l) => s + parseInt(l.count), 0) ?? 0,
                    warnings:       byLevel(3),
                    error_today:    data.error_today    ?? 0,
                    critical_today: data.critical_today ?? 0,
                };
            } catch (_) { /* silent — stats are non-critical */ }
        },

        reload() {
            if (this.table && !this.table.destroyed) this.table.replaceData();
        },

        reset() {
            this.f = { date_from: '', date_to: '' };
            this.reload();
        },

        async doExport() {
            try {
                const r = await fetch('/dashboard/activity-logs/export', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content ?? '',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        date_from: this.f.date_from || null,
                        date_to:   this.f.date_to   || null,
                    }),
                });
                if (!r.ok) return;
                const { key } = await r.json();
                const poll = setInterval(async () => {
                    const check = await fetch(`/dashboard/activity-logs/export/download/${key}`, { method: 'HEAD' });
                    if (check.ok) {
                        clearInterval(poll);
                        window.location = `/dashboard/activity-logs/export/download/${key}`;
                    }
                }, 2000);
                setTimeout(() => clearInterval(poll), 300_000);
            } catch (_) { /* silent */ }
        },

        _initTable() {
            if (this.table && !this.table.destroyed) return;
            const self = this;
            this.table = new window.Tabulator('#actlog-table', {
                ajaxURL:    '/backend/api/activity-logs',
                ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                ajaxParams() {
                    const p = {}, f = self.f;
                    if (f.date_from) p.date_from = f.date_from;
                    if (f.date_to)   p.date_to   = f.date_to;
                    return p;
                },
                ajaxResponse(_u, _p, res) { return res; },

                pagination:             true,
                paginationMode:         'remote',
                paginationSize:         20,
                paginationSizeSelector: [10, 20, 50, 100],
                paginationCounter:      'rows',
                sortMode:               'remote',
                initialSort:            [{ column: 'created_at', dir: 'desc' }],
                layout:                 'fitColumns',
                responsiveLayout:       'hide',

                rowFormatter(row) {
                    const l = row.getData().level;
                    if      (l >= 5) row.getElement().classList.add('actlog-row-critical');
                    else if (l >= 4) row.getElement().classList.add('actlog-row-error');
                },

                columns: COLUMNS,
            });
        },
    }));
});
