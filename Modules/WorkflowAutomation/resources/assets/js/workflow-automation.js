/**
 * workflow-automation.js — v2
 *
 * Two Alpine components:
 *  · wfListPage    — index listing (Tabulator)
 *  · wfBuilderPage — wizard create/edit (5 steps)
 *
 * Globals: window.Alpine, window.Toast, window.Tabulator
 */

import { makeWizardController } from '@shared/wizard-controller.js';

// ── Priority map ──────────────────────────────────────────────────────────
const PRIORITY_MAP = {
    1:  'Rất cao',
    3:  'Cao',
    5:  'Bình thường',
    7:  'Thấp',
    10: 'Rất thấp',
};

// ── Action types that should be treated as step_type = 2 (user task/pause) ─
const USER_TASK_ACTION_TYPES = new Set(['user_task.create']);

// ── HTML escape for Tabulator formatters ──────────────────────────────────
function esc(v) {
    if (v == null) return '';
    return String(v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ── Index data island ──────────────────────────────────────────────────────
const LIST_CFG = JSON.parse(
    document.getElementById('wf-list-data')?.textContent ?? 'null'
);

// ── Module-scoped index state ──────────────────────────────────────────────
let wfTableInst     = null;
let pendingDeleteId = null;

// ── Row-action globals (called from Tabulator HTML) ───────────────────────
window.wfToggle = async function (id, btn) {
    btn.disabled = true;
    try {
        const res = await fetch(`/dashboard/workflows/${id}/toggle`, {
            method:  'PATCH',
            headers: { 'X-CSRF-TOKEN': LIST_CFG?.csrf ?? '', Accept: 'application/json' },
        });
        if (res.ok && wfTableInst) wfTableInst.replaceData();
    } catch (e) { console.error(e); }
    finally { btn.disabled = false; }
};

window.wfDeleteConfirm = function (id, name) {
    pendingDeleteId = id;
    const nameEl = document.getElementById('wfDeleteName');
    if (nameEl) nameEl.textContent = `"${name}"`;
    document.getElementById('wfDeleteModal')?.showModal();
};

// ── Index: delete confirm (DOM ready) ─────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const confirmBtn = document.getElementById('wfConfirmDelete');
    if (!confirmBtn) return;

    confirmBtn.addEventListener('click', async function () {
        if (!pendingDeleteId) return;
        const btn = this;
        btn.disabled    = true;
        btn.textContent = 'Đang xóa...';
        try {
            const res = await fetch(`/dashboard/workflows/${pendingDeleteId}`, {
                method:  'POST',
                headers: {
                    'X-CSRF-TOKEN':     LIST_CFG?.csrf ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept:             'application/json',
                    'Content-Type':     'application/x-www-form-urlencoded',
                },
                body: '_method=DELETE',
            });
            if (res.ok) {
                document.getElementById('wfDeleteModal')?.close();
                wfTableInst?.replaceData();
            } else {
                const d = await res.json().catch(() => ({}));
                window.Toast?.error(d.message || 'Xóa thất bại.');
            }
        } catch { window.Toast?.error('Lỗi kết nối.'); }
        finally {
            btn.disabled    = false;
            btn.textContent = 'Xóa';
            pendingDeleteId = null;
        }
    });
});

// ── Tabulator columns (index) ──────────────────────────────────────────────
const WF_COLUMNS = [
    {
        title: 'Tên workflow', field: 'name', minWidth: 220, sorter: 'string',
        formatter(cell) {
            const d     = cell.getRow().getData();
            const badge = d.is_active
                ? '<span class="badge badge-xs badge-success ml-1.5">Bật</span>'
                : '<span class="badge badge-xs badge-ghost ml-1.5">Tắt</span>';
            return `<div>
                <a href="/dashboard/workflows/${d.id}" class="font-semibold text-sm hover:text-primary">${esc(d.name)}</a>
                ${badge}
                <p class="text-xs text-base-content/40 mt-0.5">${esc(d.trigger_type)}</p>
            </div>`;
        },
    },
    {
        title: 'Lần chạy', field: 'run_count', width: 110, hozAlign: 'center', sorter: 'number',
        formatter(cell) {
            return `<span class="badge badge-ghost badge-sm">${esc(cell.getValue() || 0)}</span>`;
        },
    },
    {
        title: 'Trạng thái gần nhất', field: 'last_run_badge', width: 170, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d = cell.getRow().getData();
            if (!d.last_run_badge) return '<span class="text-base-content/25 text-xs">Chưa chạy</span>';
            return `<span class="badge badge-sm ${esc(d.last_run_badge)}">${esc(d.last_run_label)}</span>`;
        },
    },
    {
        title: 'Lần chạy gần nhất', field: 'last_run_at', width: 160, hozAlign: 'center', sorter: 'string',
        formatter(cell) {
            const v = cell.getValue();
            if (!v) return '<span class="text-base-content/25 text-xs">—</span>';
            return `<span class="text-xs">${esc(new Date(v).toLocaleString('vi-VN'))}</span>`;
        },
    },
    {
        title: 'Ưu tiên', field: 'priority', width: 110, hozAlign: 'center', sorter: 'number',
        formatter(cell) {
            const v = cell.getValue();
            const label = PRIORITY_MAP[v] ?? v;
            return `<span class="text-xs">${esc(label)}</span>`;
        },
    },
    {
        title: 'Thao tác', field: 'id', width: 130, hozAlign: 'center', headerSort: false,
        formatter(cell) {
            const d   = cell.getRow().getData();
            const cfg = LIST_CFG ?? {};
            let   html = '<div class="flex items-center justify-center gap-1">';
            html += `<a href="/dashboard/workflows/${d.id}" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-info" title="Xem">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </a>`;
            if (cfg.canEdit) {
                html += `<a href="/dashboard/workflows/${d.id}/edit" class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-warning" title="Sửa">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </a>`;
                const toggleIcon = d.is_active
                    ? '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>'
                    : '<svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
                html += `<button class="btn btn-ghost btn-xs btn-square text-base-content/40 hover:text-base-content" title="Bật/Tắt" onclick="wfToggle(${d.id}, this)">${toggleIcon}</button>`;
            }
            if (cfg.canDelete) {
                const safeName = esc(d.name).replace(/'/g, "\\'");
                html += `<button class="btn btn-ghost btn-xs btn-square text-error/30 hover:text-error" title="Xóa" onclick="wfDeleteConfirm(${d.id}, '${safeName}')">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>`;
            }
            html += '</div>';
            return html;
        },
    },
];

// ── Alpine components ──────────────────────────────────────────────────────
document.addEventListener('alpine:init', () => {

    // ── wfListPage ──────────────────────────────────────────────────────
    Alpine.data('wfListPage', () => ({
        filters: { search: '', is_active: '' },

        get hasFilters() { return !!(this.filters.search || this.filters.is_active !== ''); },

        init() { this.$nextTick(() => this._setup()); },

        _setup() {
            const self = this;
            wfTableInst = new window.Tabulator('#wf-table', {
                ajaxURL:    LIST_CFG?.apiUrl ?? '',
                ajaxConfig: { headers: { 'X-Requested-With': 'XMLHttpRequest' } },
                ajaxParams() {
                    const p = {}, f = self.filters;
                    if (f.search)           p.search    = f.search;
                    if (f.is_active !== '') p.is_active = f.is_active;
                    return p;
                },
                ajaxResponse(_u, _p, res) { return res; },
                pagination:             true,
                paginationMode:         'remote',
                paginationSize:         20,
                paginationSizeSelector: [10, 20, 50],
                paginationCounter:      'rows',
                sortMode:               'remote',
                initialSort:            [{ column: 'created_at', dir: 'desc' }],
                layout:                 'fitColumns',
                height:                 '65vh',
                locale:                 'vi-VN',
                langs: { 'vi-VN': { pagination: {
                    page_size: 'Dòng/trang', first: '«', last: '»', prev: '‹', next: '›',
                    counter: { showing: '', of: 'trong', rows: 'dòng', pages: 'trang' },
                }}},
                columns: WF_COLUMNS,
                placeholder: '<div class="py-16 text-center opacity-40"><p class="text-sm">Chưa có workflow nào</p></div>',
            });
        },

        refresh()      { wfTableInst?.replaceData(); },
        resetFilters() { this.filters = { search: '', is_active: '' }; this.refresh(); },
    }));

    // ── wfBuilderPage ────────────────────────────────────────────────────
    Alpine.data('wfBuilderPage', (sd) => {
        const metaUrl  = sd.metaUrl    ?? '';
        const initData = sd.initData   ?? {};
        const initStep = sd.initialStep ?? 1;

        return {
            // ── Wizard mixin ─────────────────────────────────────────────
            ...makeWizardController({
                steps: ['Cơ bản', 'Trigger', 'Điều kiện', 'Hành động', 'Cài đặt'],
                validators: [
                    function () { return this._validateStep1(); },
                    function () { return this._validateStep2(); },
                    null, null, null,
                ],
                initialStep: initStep,
            }),

            // ── State ─────────────────────────────────────────────────────
            meta:          { trigger_groups: {}, action_groups: {}, operators: [], cooldown_types: [], subjects: {} },
            metaLoading:   true,
            metaError:     false,
            loading:       false,
            triggerConfig: {},

            form: Object.assign({
                name: '', description: '', trigger_type: '', trigger_params: [],
                condition_match: 3, cooldown_type: 0, is_active: false, priority: 5,
                conditions: [], steps: [],
            }, initData),

            // ── Computed ──────────────────────────────────────────────────
            get currentTrigger() {
                if (!this.form.trigger_type) return null;
                for (const [, triggers] of Object.entries(this.meta.trigger_groups ?? {})) {
                    const found = triggers.find(t => t.type === this.form.trigger_type);
                    if (found) return found;
                }
                return null;
            },

            // ── Init ──────────────────────────────────────────────────────
            init() {
                if (!this.form.steps)      this.form.steps      = [];
                if (!this.form.conditions) this.form.conditions = [];

                // Rebuild triggerConfig from saved trigger_params
                for (const p of this.form.trigger_params ?? []) {
                    this.triggerConfig[p.param_key] = p.param_value;
                }

                // Normalize each step to v2 shape
                for (const s of this.form.steps) {
                    if (!s.headers)          s.headers          = [];
                    if (!s.action_config)    s.action_config    = {};
                    if (!s.step_type)        s.step_type        = 1;
                    if (s.halt_on_fail == null) s.halt_on_fail  = false;
                    if (!s.step_output_key)  s.step_output_key  = '';
                    if (!s.step_label)       s.step_label       = '';
                    if (!s.condition_config) s.condition_config = { match: 'ALL', conditions: [] };
                    if (!s._show_advanced)   s._show_advanced   = false;
                    if (!s._show_cond)       s._show_cond       = false;
                }

                this._fetchMeta();
            },

            // ── Meta fetch ────────────────────────────────────────────────
            async _fetchMeta() {
                this.metaLoading = true;
                try {
                    const r = await fetch(metaUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!r.ok) throw new Error(`HTTP ${r.status}`);
                    this.meta        = await r.json();
                    this.metaLoading = false;
                } catch {
                    this.metaError   = true;
                    this.metaLoading = false;
                }
            },

            // ── Step validators ───────────────────────────────────────────
            _validateStep1() {
                if (!this.form.name?.trim()) {
                    window.Toast?.warning('Vui lòng nhập tên workflow trước khi tiếp tục', { duration: 3000 });
                    this.$nextTick(() => document.querySelector('[name="name"]')?.focus());
                    return false;
                }
                return true;
            },

            _validateStep2() {
                if (!this.form.trigger_type) {
                    window.Toast?.warning('Vui lòng chọn loại trigger trước khi tiếp tục', { duration: 3000 });
                    this.$nextTick(() => document.querySelector('[name="trigger_type"]')?.focus());
                    return false;
                }
                return true;
            },

            // ── Priority label ────────────────────────────────────────────
            priorityLabel(val) {
                return PRIORITY_MAP[val] ?? `Ưu tiên ${val}`;
            },

            // ── Trigger ───────────────────────────────────────────────────
            onTriggerChange() {
                this.triggerConfig       = {};
                this.form.trigger_params = [];
                this.form.conditions     = [];
            },

            syncTriggerParams() {
                const fields = this.currentTrigger?.config_fields ?? [];
                this.form.trigger_params = fields
                    .filter(f => {
                        const v = this.triggerConfig[f.key];
                        return v !== undefined && v !== '';
                    })
                    .map(f => ({
                        param_key:   f.key,
                        param_value: String(this.triggerConfig[f.key]),
                        param_type:  f.type === 'number' ? 2 : 1,
                    }));
            },

            // ── Conditions (workflow-level) ────────────────────────────────
            addCondition() {
                this.form.conditions.push({ field: '', operator: '=', value: '', value_type: 1 });
            },
            removeCondition(i) { this.form.conditions.splice(i, 1); },

            // ── Steps ─────────────────────────────────────────────────────
            _makeStep() {
                return {
                    action_type:      '',
                    step_type:        1,
                    step_label:       '',
                    delay_minutes:    0,
                    halt_on_fail:     false,
                    step_output_key:  '',
                    action_config:    {},
                    condition_config: { match: 'ALL', conditions: [] },
                    headers:          [],
                    _show_advanced:   false,
                    _show_cond:       false,
                };
            },

            addStep() {
                this.form.steps.push(this._makeStep());
            },

            removeStep(i) {
                this.form.steps.splice(i, 1);
            },

            moveStep(i, dir) {
                const steps = this.form.steps;
                const j     = i + dir;
                if (j < 0 || j >= steps.length) return;
                [steps[i], steps[j]] = [steps[j], steps[i]];
                this.form.steps = [...steps]; // trigger reactivity
            },

            // Called when action_type select changes on a step card
            onStepActionChange(step) {
                // Reset action_config when action type changes
                step.action_config = {};
                step.headers       = [];

                // Auto-set step_type based on action_type
                step.step_type = USER_TASK_ACTION_TYPES.has(step.action_type) ? 2 : 1;
            },

            addHeader(step) {
                step.headers.push({ header_key: '', header_value: '' });
            },

            // ── Per-step conditions ───────────────────────────────────────
            addStepCondition(step) {
                if (!step.condition_config) {
                    step.condition_config = { match: 'ALL', conditions: [] };
                }
                step.condition_config.conditions.push({
                    field:    '',
                    operator: '=',
                    value:    '',
                    type:     'string',
                });
                step._show_advanced = true;
                step._show_cond     = true;
            },

            // ── Action config fields helper ────────────────────────────────
            stepConfigFields(actionType) {
                if (!actionType) return [];
                for (const [, actions] of Object.entries(this.meta.action_groups ?? {})) {
                    const action = actions.find(a => a.type === actionType);
                    if (action?.config_fields) return action.config_fields;
                }
                return [];
            },

            // ── Submit ────────────────────────────────────────────────────
            submitForm() {
                this.syncTriggerParams();
                this.loading = true;
                this.$nextTick(() => document.getElementById('wf-form')?.submit());
            },
        };
    });

});
