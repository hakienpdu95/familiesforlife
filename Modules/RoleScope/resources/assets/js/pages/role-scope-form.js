/**
 * Modules/RoleScope/resources/assets/js/pages/role-scope-form.js
 *
 * Responsibilities:
 *   1. Inline validation — delegate to global initFormValidation
 *   2. Flatpickr — datetime picker cho trường expires_at
 *   3. Alpine component grantRoleScopeForm — TomSelect manual init + cascade branch → dept
 *      + dynamic org selection → load users/branches/departments via API
 *
 * Requires globals (core): initFormValidation, window.Alpine
 * Requires globals (lazy): window.TomSelect (tom-select.js), initDateTimePicker (flatpickr.js)
 */

import { createTs } from '@shared/tom-select-factory.js';

const FORM_SEL = '[data-role-scope-form]';
const API_ORG_DATA = '/dashboard/role-scopes/api/org-data';

// ── Entry point ────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector(FORM_SEL);
    if (!form) return;

    initFormValidation(FORM_SEL);
    _initFlatpickr(form);
});

// ── Flatpickr datetime picker ──────────────────────────────────────────────────

function _initFlatpickr(form) {
    if (typeof initDateTimePicker !== 'function') return;
    const opts = { altInput: true, altFormat: 'd/m/Y H:i', dateFormat: 'Y-m-d H:i' };
    const el = form.querySelector('#fp-expires-at');
    if (el) initDateTimePicker(el, opts);
}

// ── Alpine component: Grant Role Scope (create form) ──────────────────────────

document.addEventListener('alpine:init', () => {

    Alpine.data('grantRoleScopeForm', (cfg) => {
        let ALL_DEPTS = cfg.departments;

        // Module-level TomSelect instances — shared across methods
        let _orgTs    = null;
        let _userTs   = null;
        let _roleTs   = null;
        let _branchTs = null;
        let _deptTs   = null;

        const NO_RESULTS = () => '<div class="no-results p-3 text-sm opacity-50">Không tìm thấy</div>';

        return {
            selectedBranchId: cfg.oldBranchId ? parseInt(cfg.oldBranchId) : null,

            init() {
                this.$nextTick(() => this._initSelects());
            },

            _initSelects() {
                const self = this;

                // ── Org select (admin only) ──────────────────────────────────────
                const orgEl = document.querySelector('#select-org');
                if (orgEl && !cfg.orgLocked) {
                    _orgTs = createTs(orgEl, {
                        dropdownParent: 'body',
                        placeholder:    'Chọn tổ chức...',
                        maxOptions:     null,
                        searchField:    ['text'],
                        plugins:        ['clear_button'],
                        options:        cfg.organizations,
                        render: { no_results: NO_RESULTS },
                        onChange(val) {
                            if (val) {
                                self._loadOrgData(val);
                            } else {
                                self._clearOrgData();
                            }
                        },
                    });

                    // If there's an old value from failed validation, restore it and load data
                    const preselect = cfg.oldOrgId || cfg.defaultOrgId;
                    if (preselect) {
                        _orgTs.setValue(String(preselect), true); // silent=true to avoid triggering onChange
                        this._loadOrgData(preselect, true);       // isInitial=true to restore old field values
                    }
                }

                // ── User select ────────────────────────────────────────────────
                _userTs = createTs(document.querySelector('#select-user'), {
                    dropdownParent: 'body',
                    placeholder:    'Chọn user...',
                    maxOptions:     null,
                    searchField:    ['text'],
                    plugins:        ['clear_button'],
                    options:        cfg.users,
                    items:          cfg.oldUserId ? [String(cfg.oldUserId)] : [],
                    render: { no_results: NO_RESULTS },
                });

                // ── Role select ────────────────────────────────────────────────
                _roleTs = createTs(document.querySelector('#select-role'), {
                    dropdownParent: 'body',
                    placeholder:    'Chọn role...',
                    maxOptions:     null,
                    searchField:    ['text'],
                    plugins:        ['clear_button'],
                    options:        cfg.roles,
                    items:          cfg.oldRoleId ? [String(cfg.oldRoleId)] : [],
                    render: { no_results: NO_RESULTS },
                });

                // ── Branch select ──────────────────────────────────────────────
                _branchTs = createTs(document.querySelector('#select-branch'), {
                    dropdownParent: 'body',
                    placeholder:    '— Toàn tổ chức (không giới hạn chi nhánh) —',
                    maxOptions:     null,
                    searchField:    ['text'],
                    plugins:        ['clear_button'],
                    options:        cfg.branches,
                    items:          self.selectedBranchId ? [String(self.selectedBranchId)] : [],
                    onChange(val) {
                        self.selectedBranchId = val ? parseInt(val) : null;
                        self._refreshDepts();
                        if (_deptTs) _deptTs.clear(true);
                    },
                    render: { no_results: NO_RESULTS },
                });

                // ── Dept select ────────────────────────────────────────────────
                _deptTs = createTs(document.querySelector('#select-dept'), {
                    dropdownParent: 'body',
                    placeholder:    '— Toàn chi nhánh (không giới hạn phòng ban) —',
                    maxOptions:     null,
                    searchField:    ['text'],
                    plugins:        ['clear_button'],
                    options:        [],
                    items:          cfg.oldDeptId ? [String(cfg.oldDeptId)] : [],
                    render: { no_results: NO_RESULTS },
                });

                if (self.selectedBranchId) self._refreshDepts(cfg.oldDeptId);
            },

            /**
             * Fetch users/branches/departments for the chosen org,
             * then repopulate all three selects.
             *
             * isInitial=true: org was pre-selected (defaultOrgId or oldOrgId),
             * so restore old field values from cfg.
             */
            async _loadOrgData(orgId, isInitial = false) {
                const self = this;

                try {
                    const data = await fetch(`${API_ORG_DATA}?org_id=${encodeURIComponent(orgId)}`)
                        .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); });

                    // ── Repopulate user select ───────────────────────────────
                    if (_userTs) {
                        _userTs.clearOptions();
                        for (const u of data.users) _userTs.addOption(u);
                        _userTs.refreshOptions(false);
                        if (isInitial && cfg.oldUserId) {
                            _userTs.setValue(String(cfg.oldUserId), true);
                        } else if (!isInitial) {
                            _userTs.clear(true);
                        }
                    }

                    // ── Repopulate branch select ─────────────────────────────
                    if (_branchTs) {
                        _branchTs.clearOptions();
                        for (const b of data.branches) _branchTs.addOption(b);
                        _branchTs.refreshOptions(false);
                        if (isInitial && cfg.oldBranchId) {
                            _branchTs.setValue(String(cfg.oldBranchId), true);
                            self.selectedBranchId = parseInt(cfg.oldBranchId);
                        } else if (!isInitial) {
                            _branchTs.clear(true);
                            self.selectedBranchId = null;
                        }
                    }

                    // ── Update ALL_DEPTS pool + refresh dept select ───────────
                    ALL_DEPTS = data.departments;
                    if (_deptTs) {
                        _deptTs.clearOptions();
                        _deptTs.clear(true);
                    }
                    if (self.selectedBranchId) {
                        self._refreshDepts(isInitial ? cfg.oldDeptId : null);
                    }

                } catch (e) {
                    console.error('[RoleScope] Không thể tải dữ liệu tổ chức:', e);
                }
            },

            /** Called when org is cleared — empty all dependent selects */
            _clearOrgData() {
                if (_userTs) { _userTs.clearOptions(); _userTs.clear(true); }
                if (_branchTs) { _branchTs.clearOptions(); _branchTs.clear(true); }
                if (_deptTs) { _deptTs.clearOptions(); _deptTs.clear(true); }
                ALL_DEPTS = [];
                this.selectedBranchId = null;
            },

            _refreshDepts(pendingDept) {
                if (!_deptTs) return;
                _deptTs.clearOptions();
                if (!this.selectedBranchId) return;

                const filtered = ALL_DEPTS.filter(d =>
                    d.branch_id === null || d.branch_id === this.selectedBranchId
                );
                for (const d of filtered) {
                    _deptTs.addOption({ value: d.value, text: d.text });
                }
                _deptTs.refreshOptions(false);
                if (pendingDept) _deptTs.setValue(String(pendingDept), true);
            },
        };
    });

});
