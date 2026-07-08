/**
 * pages/vertical-builder.js
 *
 * Alpine component `verticalTemplateBuilder` — phase/checklist builder dùng bởi
 * organizations.verticals.config (Modules/Organization/resources/views/verticals/config.blade.php
 * → @include('backend.vertical-templates.builder')).
 *
 * Chuyển từ Modules/Deployment/resources/assets/js/deployment.js khi module Deployment bị xoá —
 * component này không có liên quan gì tới Deployment, chỉ tình cờ sống chung file trước đây.
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('verticalTemplateBuilder', (phasesData, templateId, csrfToken) => ({
        phases: phasesData.map(p => ({ ...p, _open: true })),
        templateId,
        csrfToken,
        saving: false,
        flash: { text: '', type: 'success' },

        // ── Phase modal ────────────────────────────────────────────────
        pModal: { open: false, id: null, key: '', label: '', isInitial: false, autoAssign: false },

        openPhaseModal(phase = null) {
            this.pModal = {
                open:       true,
                id:         phase?.id ?? null,
                key:        phase?.key ?? '',
                label:      phase?.label ?? '',
                isInitial:  phase?.is_initial ?? false,
                autoAssign: phase?.auto_assign_data_collection ?? false,
            };
            this.$nextTick(() => this.$refs.pModalKeyInput?.focus());
        },

        async savePhase() {
            if (!this.pModal.key.trim() || !this.pModal.label.trim()) return;
            const url    = this.pModal.id
                ? `/dashboard/vertical-templates/${this.templateId}/phases/${this.pModal.id}`
                : `/dashboard/vertical-templates/${this.templateId}/phases`;
            const method = this.pModal.id ? 'PUT' : 'POST';
            const body   = {
                key: this.pModal.key, label: this.pModal.label,
                is_initial: this.pModal.isInitial, auto_assign_data_collection: this.pModal.autoAssign,
            };
            const res = await this.api(url, method, body);
            if (!res) return;
            if (this.pModal.id) {
                const p = this.phases.find(p => p.id === this.pModal.id);
                if (p) {
                    Object.assign(p, res.data);
                    if (p.is_initial) this.phases.forEach(o => { if (o.id !== p.id) o.is_initial = false; });
                }
            } else {
                this.phases.push({ ...res.data, checklist_items: [], _open: true });
            }
            this.pModal.open = false;
            this.ok(res.message);
        },

        async deletePhase(phase, idx) {
            if (!confirm(`Xóa phase "${phase.label}"?\n\nTất cả checklist item bên trong cũng sẽ bị xóa. Hành động không thể hoàn tác.`)) return;
            const res = await this.api(`/dashboard/vertical-templates/${this.templateId}/phases/${phase.id}`, 'DELETE');
            if (!res) return;
            this.phases.splice(idx, 1);
            this.ok(res.message);
        },

        async movePhaseUp(idx) {
            if (idx === 0) return;
            [this.phases[idx - 1], this.phases[idx]] = [this.phases[idx], this.phases[idx - 1]];
            await this.reorder('phases', this.phases);
        },

        async movePhaseDown(idx) {
            if (idx === this.phases.length - 1) return;
            [this.phases[idx + 1], this.phases[idx]] = [this.phases[idx], this.phases[idx + 1]];
            await this.reorder('phases', this.phases);
        },

        // ── Checklist item modal ──────────────────────────────────────
        ciModal: { open: false, id: null, phaseId: null, key: '', label: '', isRequired: true },

        openChecklistItemModal(phase, item = null) {
            this.ciModal = {
                open:        true,
                id:          item?.id ?? null,
                phaseId:     phase.id,
                key:         item?.key ?? '',
                label:       item?.label ?? '',
                isRequired:  item?.is_required ?? true,
            };
            this.$nextTick(() => this.$refs.ciModalKeyInput?.focus());
        },

        async saveChecklistItem() {
            if (!this.ciModal.key.trim() || !this.ciModal.label.trim()) return;
            const url    = this.ciModal.id
                ? `/dashboard/vertical-templates/${this.templateId}/phases/${this.ciModal.phaseId}/checklist-items/${this.ciModal.id}`
                : `/dashboard/vertical-templates/${this.templateId}/phases/${this.ciModal.phaseId}/checklist-items`;
            const method = this.ciModal.id ? 'PUT' : 'POST';
            const body   = { key: this.ciModal.key, label: this.ciModal.label, is_required: this.ciModal.isRequired };
            const res    = await this.api(url, method, body);
            if (!res) return;
            const phase = this.phases.find(p => p.id === this.ciModal.phaseId);
            if (!phase) return;
            if (this.ciModal.id) {
                const idx = phase.checklist_items.findIndex(i => i.id === this.ciModal.id);
                if (idx >= 0) phase.checklist_items[idx] = res.data;
            } else {
                phase.checklist_items.push(res.data);
            }
            this.ciModal.open = false;
            this.ok(res.message);
        },

        async deleteChecklistItem(phase, item, idx) {
            if (!confirm(`Xóa mục checklist "${item.label}"?`)) return;
            const res = await this.api(`/dashboard/vertical-templates/${this.templateId}/phases/${phase.id}/checklist-items/${item.id}`, 'DELETE');
            if (!res) return;
            phase.checklist_items.splice(idx, 1);
            this.ok(res.message);
        },

        async moveChecklistItemUp(phase, idx) {
            if (idx === 0) return;
            [phase.checklist_items[idx - 1], phase.checklist_items[idx]] = [phase.checklist_items[idx], phase.checklist_items[idx - 1]];
            await this.reorder('checklist_items', phase.checklist_items, phase.id);
        },

        async moveChecklistItemDown(phase, idx) {
            if (idx === phase.checklist_items.length - 1) return;
            [phase.checklist_items[idx + 1], phase.checklist_items[idx]] = [phase.checklist_items[idx], phase.checklist_items[idx + 1]];
            await this.reorder('checklist_items', phase.checklist_items, phase.id);
        },

        // ── Reorder ────────────────────────────────────────────────────
        async reorder(type, items, phaseId = null) {
            const payload = items.map((item, i) => ({ id: item.id, sort_order: i + 1 }));
            const url = type === 'phases'
                ? `/dashboard/vertical-templates/${this.templateId}/phases/reorder`
                : `/dashboard/vertical-templates/${this.templateId}/phases/${phaseId}/checklist-items/reorder`;
            await this.api(url, 'PATCH', { items: payload });
        },

        // ── Helpers ────────────────────────────────────────────────────
        ok(msg)  { this.flash = { text: msg, type: 'success' }; setTimeout(() => this.flash.text = '', 3000); },
        err(msg) { this.flash = { text: msg, type: 'error' };   setTimeout(() => this.flash.text = '', 5000); },

        async api(url, method, body = null) {
            this.saving = true;
            try {
                const opts = {
                    method,
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                };
                if (body && method !== 'GET') opts.body = JSON.stringify(body);
                const response = await fetch(url, opts);
                const json     = await response.json();
                if (!response.ok) {
                    const msg = json.message || (json.errors ? Object.values(json.errors).flat().join(' ') : 'Có lỗi xảy ra.');
                    this.err(msg);
                    return null;
                }
                return json;
            } catch {
                this.err('Lỗi kết nối. Vui lòng thử lại.');
                return null;
            } finally {
                this.saving = false;
            }
        },
    }));
});
