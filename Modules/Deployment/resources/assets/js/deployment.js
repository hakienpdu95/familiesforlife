/**
 * deployment.js
 *
 * Responsibilities:
 *   1. Alpine component `targetCreate` — MST lookup + org auto-fill
 *   2. Alpine component `verticalTemplateBuilder` — phase/checklist builder
 *      (dùng chung bởi backend.vertical-templates.edit VÀ
 *      organization::verticals.config — xem docs/form-ui-spec.md §4.2:
 *      Alpine.data đăng ký trong JS file, không inline trong blade)
 *   3. initFormValidation — client-side required check
 *   4. TomSelect — auto-init all select.ts-init
 *   5. Flatpickr — auto-init all input.fp-init
 *   6. Code input — auto-uppercase (project form) / auto-fill từ label (vertical template)
 */

import { initAllTomSelects } from '@shared/tom-select-factory.js';

// ── Constants ──────────────────────────────────────────────────────────────

const PROJECT_FORM_SEL          = '[data-project-form]';
const TARGET_FORM_SEL           = '[data-target-form]';
const PROGRESS_FORM_SEL         = '[data-progress-form]';
const VERTICAL_TEMPLATE_FORM_SEL = '[data-vertical-template-form]';

/**
 * Bảng chuyển ký tự tiếng Việt → Latin cho auto-fill mã (code) từ tên (label).
 * Object.freeze() để V8 tối ưu làm hidden class cố định, tránh re-shape.
 */
const VI_MAP = Object.freeze({
    à:'a', á:'a', ả:'a', ã:'a', ạ:'a',
    ă:'a', ằ:'a', ắ:'a', ẳ:'a', ẵ:'a', ặ:'a',
    â:'a', ầ:'a', ấ:'a', ẩ:'a', ẫ:'a', ậ:'a',
    è:'e', é:'e', ẻ:'e', ẽ:'e', ẹ:'e',
    ê:'e', ề:'e', ế:'e', ể:'e', ễ:'e', ệ:'e',
    ì:'i', í:'i', ỉ:'i', ĩ:'i', ị:'i',
    ò:'o', ó:'o', ỏ:'o', õ:'o', ọ:'o',
    ô:'o', ồ:'o', ố:'o', ổ:'o', ỗ:'o', ộ:'o',
    ơ:'o', ờ:'o', ớ:'o', ở:'o', ỡ:'o', ợ:'o',
    ù:'u', ú:'u', ủ:'u', ũ:'u', ụ:'u',
    ư:'u', ừ:'u', ứ:'u', ử:'u', ữ:'u', ự:'u',
    ỳ:'y', ý:'y', ỷ:'y', ỹ:'y', ỵ:'y',
    đ:'d',
});

// ── Alpine components ────────────────────────────────────────────────────────

document.addEventListener('alpine:init', () => {
    Alpine.data('targetCreate', (lookupUrl, initialTaxCode = '') => ({
        taxCode:     initialTaxCode,
        foundOrg:    null,
        useExisting: false,
        searching:   false,

        async lookup() {
            this.foundOrg    = null;
            this.useExisting = false;
            if (!this.taxCode || this.taxCode.length < 8) return;
            this.searching = true;
            try {
                const res  = await fetch(
                    lookupUrl + '?tax_code=' + encodeURIComponent(this.taxCode),
                    { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
                );
                const json = await res.json();
                this.foundOrg = json.found ? json.org : null;
            } finally {
                this.searching = false;
            }
        },

        applyOrg() {
            this.useExisting = true;
            this.$refs.orgName.value    = this.foundOrg.name         || '';
            this.$refs.orgPhone.value   = this.foundOrg.phone        || '';
            this.$refs.orgEmail.value   = this.foundOrg.email        || '';
            this.$refs.orgAddress.value = this.foundOrg.full_address || '';
        },
    }));

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

// ── Entry point ────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const projectForm = document.querySelector(PROJECT_FORM_SEL);
    if (projectForm) {
        initFormValidation(PROJECT_FORM_SEL);
        initAllTomSelects(projectForm);
        window.initAllDatePickers?.(projectForm);
        _setupCodeUppercase(projectForm);
    }

    const targetForm = document.querySelector(TARGET_FORM_SEL);
    if (targetForm) {
        initFormValidation(TARGET_FORM_SEL);
        initAllTomSelects(targetForm);
        _initOrgSlugsPreview(targetForm);
    }

    const progressForm = document.querySelector(PROGRESS_FORM_SEL);
    if (progressForm) {
        initFormValidation(PROGRESS_FORM_SEL);
        initAllTomSelects(progressForm);
        _setupTargetNav(progressForm);
    }

    const verticalTemplateForm = document.querySelector(VERTICAL_TEMPLATE_FORM_SEL);
    if (verticalTemplateForm) {
        initFormValidation(VERTICAL_TEMPLATE_FORM_SEL);
        initAllTomSelects(verticalTemplateForm);   // #ts-organization (select.ts-init) — #ts-default-roles không có class ts-init nên không bị ảnh hưởng
        _setupCodeAutoFill(verticalTemplateForm);
        _setupDefaultRolesTags(verticalTemplateForm);
        _initVerticalTemplateSurveySelects(verticalTemplateForm);
    }
});

// ── Helpers ────────────────────────────────────────────────────────────────

function _setupCodeUppercase(form) {
    const el = form.querySelector('[name="code"]');
    if (!el) return;
    el.addEventListener('input', () => {
        const pos = el.selectionStart;
        el.value = el.value.toUpperCase();
        el.setSelectionRange(pos, pos);
    }, { passive: true });
}

// When target select changes, refresh the log list by updating ?target_id param.
function _setupTargetNav(form) {
    const sel = form.querySelector('[data-target-nav]');
    if (!sel) return;

    // TomSelect is initialized; hook into its change event via the original <select> change.
    sel.addEventListener('change', () => {
        if (!sel.value) return;
        const url = new URL(window.location.href);
        url.searchParams.set('target_id', sel.value);
        window.location.href = url.toString();
    });
}

/**
 * Tự động điền mã (code) khi user gõ tên (label) — chỉ trên form tạo mới
 * (field code không tồn tại hoặc bị readonly trên form sửa → không auto-fill).
 * Dừng auto-fill ngay khi user tự chỉnh code (locked = true).
 */
function _setupCodeAutoFill(form) {
    const labelInput = form.querySelector('[name="label"]');
    const codeInput  = form.querySelector('[name="code"]:not([readonly])');
    if (!labelInput || !codeInput) return;

    let locked = codeInput.value.trim() !== '';

    codeInput.addEventListener('input', () => {
        locked = codeInput.value.trim() !== '';
    }, { passive: true });

    codeInput.addEventListener('change', () => {
        if (!codeInput.value.trim()) locked = false;
    }, { passive: true });

    labelInput.addEventListener('input', () => {
        if (locked) return;
        codeInput.value = _toSlug(labelInput.value);
    }, { passive: true });
}

/** Chuyển chuỗi tiếng Việt sang dạng mã url-safe: lowercase, bỏ dấu, chỉ giữ a-z0-9-. */
function _toSlug(str) {
    let out = '';
    for (const ch of str.toLowerCase()) {
        out += VI_MAP[ch] ?? ch;
    }
    return out
        .replace(/[^a-z0-9\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-')
        .replace(/-{2,}/g, '-');
}

/**
 * Tổ chức tham chiếu (#ts-organization) → preview slug khảo sát sẵn sàng /
 * thu thập dữ liệu đã cấu hình cho vertical hiện tại (readiness_template_slug /
 * data_collection_template_slug trên vertical_templates của tổ chức đó).
 * Cùng pattern với Department's _initOrgCascades — TomSelect .on('change') → fetch.
 */
function _initOrgSlugsPreview(form) {
    const apiUrl = form.dataset.orgSlugsApi;
    const preview = form.querySelector('#org-slugs-preview');
    const readinessEl = form.querySelector('#org-slug-readiness');
    const dataCollectionEl = form.querySelector('#org-slug-data-collection');
    if (!apiUrl || !preview || !readinessEl || !dataCollectionEl) return;

    const fetchSlugs = (orgId) => {
        if (!orgId) {
            preview.classList.add('hidden');
            return;
        }
        fetch(`${apiUrl}?organization_id=${encodeURIComponent(orgId)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then(r => r.ok ? r.json() : { found: false })
            .then(json => {
                if (!json.found) {
                    preview.classList.add('hidden');
                    return;
                }
                readinessEl.textContent = json.readiness_template_slug || '—';
                dataCollectionEl.textContent = json.data_collection_template_slug || '—';
                preview.classList.remove('hidden');
            })
            .catch(() => preview.classList.add('hidden'));
    };

    const orgSelect = form.querySelector('#ts-organization');
    if (orgSelect) {
        orgSelect.tomselect?.on('change', (orgId) => fetchSlugs(orgId));
        return;
    }

    // orgLocked = true → không có select, chỉ có hidden input với org id cố định
    const lockedInput = form.querySelector('#org-slugs-locked-id');
    if (lockedInput?.value) fetchSlugs(lockedInput.value);
}

/**
 * Slug khảo sát sẵn sàng / thu thập dữ liệu (#ts-readiness-slug, #ts-data-collection-slug)
 * trên form vertical-templates — nạp options từ surveys theo tổ chức đã chọn.
 * KHÔNG tự nạp danh sách toàn hệ thống khi chưa có tổ chức nào được chọn — chỉ nạp
 * khi có hành vi chọn tổ chức (đổi #ts-organization ở trang create, hoặc tổ chức đã
 * cố định sẵn của bản ghi ở trang edit).
 */
function _initVerticalTemplateSurveySelects(form) {
    const readinessEl      = form.querySelector('#ts-readiness-slug');
    const dataCollectionEl = form.querySelector('#ts-data-collection-slug');
    const tsReadiness      = readinessEl?.tomselect;
    const tsDataCollection = dataCollectionEl?.tomselect;
    if (!tsReadiness && !tsDataCollection) return;

    const apiUrl = readinessEl?.dataset.surveyOptionsApi || dataCollectionEl?.dataset.surveyOptionsApi;
    if (!apiUrl) return;

    const loadBoth = (orgId) => {
        if (tsReadiness) {
            _loadSurveyOptions(apiUrl, tsReadiness, orgId, readinessEl.dataset.selectedValue || '');
        }
        if (tsDataCollection) {
            _loadSurveyOptions(apiUrl, tsDataCollection, orgId, dataCollectionEl.dataset.selectedValue || '');
        }
    };

    const orgEl = form.querySelector('#ts-organization');
    if (orgEl) {
        // Trang create: nếu form re-render sau lỗi validate và đã có tổ chức chọn sẵn (old()) thì
        // nạp lại đúng lựa chọn đó; nếu chưa chọn gì thì để trống, không tự nạp toàn hệ thống.
        const initialOrgId = orgEl.tomselect?.getValue() ?? '';
        if (initialOrgId) loadBoth(initialOrgId);

        orgEl.tomselect?.on('change', (orgId) => {
            tsReadiness?.clear(true);
            tsReadiness?.clearOptions();
            tsDataCollection?.clear(true);
            tsDataCollection?.clearOptions();
            if (orgId) loadBoth(orgId);   // chỉ nạp khi user thực sự chọn 1 tổ chức
        });
    } else if (form.dataset.lockedOrgId) {
        // Trang edit: chỉ nạp khi bản ghi đã thuộc về 1 tổ chức cụ thể — mẫu thư viện
        // dùng chung (organization_id null) thì không có "tổ chức đã chọn" để nạp theo.
        loadBoth(form.dataset.lockedOrgId);
    }
}

function _loadSurveyOptions(apiUrl, tsInstance, orgId, pendingValue) {
    tsInstance.disable();
    fetch(`${apiUrl}?organization_id=${encodeURIComponent(orgId || '')}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    })
        .then(r => r.ok ? r.json() : [])
        .then(items => {
            tsInstance.clearOptions();
            tsInstance.addOptions(items.map(i => ({ value: i.id, text: i.text })));
            tsInstance.enable();
            if (pendingValue) tsInstance.setValue(pendingValue, true);
        })
        .catch(() => tsInstance.enable());
}

/** Vai trò mặc định (default_roles) — tag input tự do, không có danh sách cố định. */
function _setupDefaultRolesTags(form) {
    const el = form.querySelector('#ts-default-roles');
    if (!el || el.tomselect || typeof window.initTagsInput !== 'function') return;
    window.initTagsInput(el, { placeholder: 'VD: pm, surveyor, data_ops — Enter để thêm' });
}
