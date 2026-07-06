/**
 * user-form.js — Alpine components for User create / edit forms.
 *
 * Server data is injected via Blade:
 *   x-data="createUserPage({{ Js::from($sd) }})"
 *   x-data="editUserPage({{ Js::from($sd) }})"
 *
 * Requires globals (core bundle): window.Alpine, window.TomSelect
 */

// ── Module-level constants (compile once) ───────────────────────────────────

const LEVEL_META = Object.freeze({
    'Full':          { cls: 'badge badge-xs badge-success',                       desc: 'Toàn quyền CRUD + gán' },
    'Assigned':      { cls: 'badge badge-xs badge-info',                          desc: 'Chỉ record được gán' },
    'Full team':     { cls: 'badge badge-xs badge-success',                       desc: 'Toàn quyền cả team' },
    'Limited':       { cls: 'badge badge-xs badge-warning',                       desc: 'Xem, không sửa/xóa' },
    'Source view':   { cls: 'badge badge-xs badge-primary',                       desc: 'Xem nguồn, ẩn thông tin cá nhân' },
    'Config':        { cls: 'badge badge-xs badge-ghost border border-base-300',  desc: 'Cấu hình module' },
    'Config prompt': { cls: 'badge badge-xs badge-ghost border border-base-300',  desc: 'Chỉnh prompt AI' },
    'Admin config':  { cls: 'badge badge-xs badge-ghost border border-base-300',  desc: 'Cấu hình admin' },
    'Full config':   { cls: 'badge badge-xs badge-ghost border border-base-300',  desc: 'Toàn quyền cấu hình' },
    'AI config':     { cls: 'badge badge-xs badge-ghost border border-base-300',  desc: 'Cấu hình AI' },
    'Use':           { cls: 'badge badge-xs badge-accent',                        desc: 'Dùng AI output, không config' },
    'Monitor':       { cls: 'badge badge-xs badge-warning',                       desc: 'Xem trạng thái, không sửa' },
    'Monitor/Edit':  { cls: 'badge badge-xs badge-warning',                       desc: 'Xem + sửa workflow' },
    'Approve/View':  { cls: 'badge badge-xs badge-secondary',                     desc: 'Xem + phê duyệt' },
    'View':          { cls: 'badge badge-xs badge-ghost',                         desc: 'Chỉ xem' },
    'View related':  { cls: 'badge badge-xs badge-ghost',                         desc: 'Xem tài liệu liên quan' },
    'View summary':  { cls: 'badge badge-xs badge-ghost',                         desc: 'Xem tóm tắt' },
    'View ltd':      { cls: 'badge badge-xs badge-ghost',                         desc: 'Xem giới hạn' },
    'HR tasks':      { cls: 'badge badge-xs badge-info',                          desc: 'Chỉ task bộ phận HR' },
    'Create/Edit':   { cls: 'badge badge-xs badge-success',                       desc: 'Tạo và sửa' },
    'Create HR SOP': { cls: 'badge badge-xs badge-info',                          desc: 'Tạo SOP HR' },
    'Personal/team': { cls: 'badge badge-xs badge-info',                          desc: 'Báo cáo cá nhân/team' },
    'Operations':    { cls: 'badge badge-xs badge-info',                          desc: 'Báo cáo vận hành' },
    'Marketing':     { cls: 'badge badge-xs badge-info',                          desc: 'Báo cáo marketing' },
    'HR':            { cls: 'badge badge-xs badge-info',                          desc: 'Báo cáo nhân sự' },
    'AI usage':      { cls: 'badge badge-xs badge-info',                          desc: 'Báo cáo dùng AI' },
    'Shared only':   { cls: 'badge badge-xs badge-ghost',                         desc: 'Chỉ dữ liệu được chia sẻ' },
});

const SIDEBAR_MODULES = Object.freeze({
    ceo:          ['CEO Dashboard','CRM','Sales AI','Tasks','SOP','Workflow','Prompt Mgmt','AI Logs','Users','Reports'],
    sales:        ['CRM','Sales AI','Tasks','SOP','Reports'],
    ops:          ['CEO Dashboard','CRM','Tasks','SOP','Workflow','AI Logs','Reports'],
    marketing:    ['CRM','Sales AI','Tasks','SOP','Reports'],
    hr:           ['Tasks','SOP','Users','Reports'],
    ai_operator:  ['CEO Dashboard','CRM','Tasks','SOP','Workflow','Prompt Mgmt','AI Logs','Reports'],
    system_admin: ['CEO Dashboard','CRM','Sales AI','Tasks','SOP','Workflow','Prompt Mgmt','AI Logs','Users','Roles/Perms','Reports'],
    viewer:       ['CEO Dashboard','Tasks','SOP','Reports'],
});

const ALL_PRESETS = Object.freeze([
    { role: 'ceo',          label: 'CEO / Điều hành', icon: '👔' },
    { role: 'sales',        label: 'Kinh doanh',       icon: '💼' },
    { role: 'ops',          label: 'Vận hành',         icon: '⚙️'  },
    { role: 'marketing',    label: 'Marketing',        icon: '📢' },
    { role: 'hr',           label: 'Nhân sự (HR)',     icon: '👥' },
    { role: 'ai_operator',  label: 'AI Operator',      icon: '🤖' },
    { role: 'system_admin', label: 'Quản trị HT',      icon: '🛡️'  },
    { role: 'viewer',       label: 'Xem giới hạn',     icon: '👁️'  },
]);

const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

const PW_LEVELS = Object.freeze([
    { label: '',           barCls: 'progress-base-300', textCls: 'text-base-content/30', pct: 0   },
    { label: 'Yếu',        barCls: 'progress-error',    textCls: 'text-error',            pct: 20  },
    { label: 'Trung bình', barCls: 'progress-warning',  textCls: 'text-warning',          pct: 40  },
    { label: 'Tốt',        barCls: 'progress-info',     textCls: 'text-info',             pct: 65  },
    { label: 'Mạnh',       barCls: 'progress-success',  textCls: 'text-success',          pct: 85  },
    { label: 'Rất mạnh',   barCls: 'progress-success',  textCls: 'text-success',          pct: 100 },
]);

// ── Shared helpers ──────────────────────────────────────────────────────────

function _genPassword() {
    const chars = {
        upper:   'ABCDEFGHJKMNPQRSTUVWXYZ',
        lower:   'abcdefghjkmnpqrstuvwxyz',
        digits:  '23456789',
        special: '!@#$%&*',
    };
    const all = Object.values(chars).join('');
    let   pwd = Object.values(chars).map(s => s[Math.floor(Math.random() * s.length)]).join('');
    for (let i = 4; i < 14; i++) pwd += all[Math.floor(Math.random() * all.length)];
    return pwd.split('').sort(() => .5 - Math.random()).join('');
}

function _pwChecks(pw) {
    return {
        length:  pw.length >= 8,
        upper:   /[A-Z]/.test(pw),
        lower:   /[a-z]/.test(pw),
        number:  /[0-9]/.test(pw),
        special: /[^A-Za-z0-9]/.test(pw),
    };
}

function _strength(pw) {
    const c     = _pwChecks(pw);
    const score = [c.length, c.upper && c.lower, c.number, c.special, pw.length >= 12].filter(Boolean).length;
    return PW_LEVELS[Math.min(score, 5)];
}

function _buildMatrix(matrix, role) {
    if (!role) return [];
    return Object.entries(matrix)
        .filter(([, perms]) => perms[role])
        .map(([mod, perms]) => {
            const level = perms[role];
            const meta  = LEVEL_META[level] ?? { cls: 'badge badge-xs badge-ghost', desc: '' };
            return { module: mod, level, badgeClass: meta.cls, desc: meta.desc };
        });
}

function _avatarUrl(name) {
    return name.trim()
        ? 'https://api.dicebear.com/9.x/initials/svg?seed='
            + encodeURIComponent(name.trim())
            + '&backgroundColor=6366f1&fontFamily=Arial&fontSize=40&fontWeight=700'
        : '';
}

function _syncPasswordInputs(pw) {
    for (const id of ['password-input', 'password-confirm-input']) {
        const el = document.getElementById(id);
        if (el) el.value = pw;
    }
}

// ── TomSelect setup helpers ─────────────────────────────────────────────────

function _setupOrgTs(selector, orgs, initial, onChange) {
    return new window.TomSelect(selector, {
        dropdownParent: 'body',
        placeholder:    'Chọn tổ chức...',
        maxOptions:     null,
        searchField:    ['text'],
        options:        orgs.map(o => ({ value: String(o.id), text: o.name })),
        items:          initial ? [String(initial)] : [],
        render: { no_results: () => '<div class="p-3 text-sm opacity-50">Không tìm thấy</div>' },
        onChange,
    });
}

function _setupRoleTs(selector, roles, initial, onChange) {
    return new window.TomSelect(selector, {
        dropdownParent: 'body',
        placeholder:    'Chọn vai trò...',
        searchField:    ['text'],
        options:        roles.map(r => ({ value: r.value, text: r.label })),
        items:          initial ? [initial] : [],
        render: { no_results: () => '<div class="p-3 text-sm opacity-50">Không tìm thấy</div>' },
        onChange,
    });
}

// ── Alpine components ────────────────────────────────────────────────────────

document.addEventListener('alpine:init', () => {

    // ── CREATE ───────────────────────────────────────────────────────────────
    Alpine.data('createUserPage', (sd) => {
        const { organizations, roles, matrix } = sd;
        const filteredPresets = ALL_PRESETS.filter(p => roles.some(r => r.value === p.role));
        let roleTsInst = null;

        return {
            // Fields
            name:             sd.oldName  || '',
            email:            sd.oldEmail || '',
            password:         '',
            pwConfirm:        '',
            showPw:           false,
            sendWelcomeEmail: false,
            avatarUrl:        '',
            selectedOrg:      sd.oldOrg  || '',
            selectedRole:     sd.oldRole || '',
            selectedRoleLabel:'',
            rolePresets:      filteredPresets,

            // Validation tracking
            touched: {
                name:            !!sd.hasErrors,
                email:           !!sd.hasErrors,
                password:        !!sd.hasErrors,
                organization_id: !!sd.hasErrors,
                system_role:     !!sd.hasErrors,
            },
            attempted: !!sd.hasErrors,

            // Computed — validation errors
            get errors() {
                return {
                    name: !this.name.trim()             ? 'Họ và tên là bắt buộc'
                        : this.name.trim().length < 2   ? 'Tên phải có ít nhất 2 ký tự' : null,
                    email: !this.email.trim()           ? 'Email là bắt buộc'
                        : !EMAIL_RE.test(this.email)    ? 'Địa chỉ email không hợp lệ' : null,
                    password: !this.password            ? 'Mật khẩu là bắt buộc'
                        : this.password.length < 8      ? 'Tối thiểu 8 ký tự'
                        : !/[A-Z]/.test(this.password)  ? 'Cần ít nhất 1 chữ HOA'
                        : !/[a-z]/.test(this.password)  ? 'Cần ít nhất 1 chữ thường'
                        : !/[0-9]/.test(this.password)  ? 'Cần ít nhất 1 chữ số' : null,
                    organization_id: !this.selectedOrg  ? 'Vui lòng chọn tổ chức' : null,
                    system_role:     !this.selectedRole ? 'Vui lòng chọn vai trò'  : null,
                };
            },
            get isValid() {
                const e = this.errors;
                return !e.name && !e.email && !e.password && !e.organization_id && !e.system_role;
            },
            get pwChecks()       { return _pwChecks(this.password); },
            get strength()       { return _strength(this.password); },
            get currentMatrix()  { return _buildMatrix(matrix, this.selectedRole); },
            get sidebarModules() { return SIDEBAR_MODULES[this.selectedRole] || []; },

            // Validation helpers
            touch(field)  { this.touched[field] = true; },
            showErr(field) {
                return (this.touched[field] || this.attempted) && !!this.errors[field];
            },
            showOk(field) {
                const val = { name: this.name, email: this.email, password: this.password,
                              organization_id: this.selectedOrg, system_role: this.selectedRole }[field] || '';
                return this.touched[field] && !this.errors[field] && !!String(val).trim();
            },
            fieldCls(field) {
                return { 'input-error': this.showErr(field), 'input-success': this.showOk(field) };
            },

            // Actions
            handleSubmit(e) {
                this.attempted = true;
                Object.keys(this.touched).forEach(k => { this.touched[k] = true; });
                if (!this.isValid) {
                    e.preventDefault();
                    this.$nextTick(() => {
                        (document.querySelector('.input-error, .select-error')
                            || document.getElementById('submit-bar'))
                            ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    });
                }
            },

            updateAvatar() { this.avatarUrl = _avatarUrl(this.name); },

            generatePassword() {
                const pwd = _genPassword();
                this.password = this.pwConfirm = pwd;
                this.showPw   = true;
                this.touched.password = true;
                _syncPasswordInputs(pwd);
            },

            selectPreset(role) {
                this.selectedRole = role;
                this.touched.system_role = true;
                const found = roles.find(r => r.value === role);
                this.selectedRoleLabel = found?.label ?? '';
                roleTsInst?.setValue(role, false);
            },

            // Lifecycle
            init() {
                if (sd.oldRole) {
                    const found = roles.find(r => r.value === sd.oldRole);
                    if (found) this.selectedRoleLabel = found.label;
                }
                if (this.name) this.updateAvatar();
                this.$nextTick(() => this._setup());
            },

            _setup() {
                _setupOrgTs('#org-select', organizations, sd.oldOrg, val => {
                    this.selectedOrg = val || '';
                    this.touched.organization_id = true;
                });
                roleTsInst = _setupRoleTs('#role-select', roles, sd.oldRole, val => {
                    this.selectedRole = val || '';
                    this.touched.system_role = true;
                    const found = roles.find(r => r.value === val);
                    this.selectedRoleLabel = found?.label ?? '';
                });
            },
        };
    });

    // ── EDIT ─────────────────────────────────────────────────────────────────
    Alpine.data('editUserPage', (sd) => {
        const { organizations, roles, matrix } = sd;
        const filteredPresets = ALL_PRESETS.filter(p => roles.some(r => r.value === p.role));
        let roleTsInst = null;

        return {
            // Fields
            password:          '',
            pwConfirm:         '',
            showPw:            false,
            selectedRole:      sd.oldRole || '',
            selectedRoleLabel: '',
            rolePresets:       filteredPresets,

            // Computed
            get pwChecks()       { return _pwChecks(this.password); },
            get strength()       { return _strength(this.password); },
            get currentMatrix()  { return _buildMatrix(matrix, this.selectedRole); },
            get sidebarModules() { return SIDEBAR_MODULES[this.selectedRole] || []; },

            // Actions
            generatePassword() {
                const pwd = _genPassword();
                this.password = this.pwConfirm = pwd;
                this.showPw   = true;
                _syncPasswordInputs(pwd);
            },

            selectPreset(role) {
                this.selectedRole = role;
                const found = roles.find(r => r.value === role);
                this.selectedRoleLabel = found?.label ?? '';
                roleTsInst?.setValue(role, false);
            },

            // Lifecycle
            init() {
                if (sd.oldRole) {
                    const found = roles.find(r => r.value === sd.oldRole);
                    if (found) this.selectedRoleLabel = found.label;
                }
                this.$nextTick(() => this._setup());
            },

            _setup() {
                _setupOrgTs('#org-select', organizations, sd.oldOrg, null);
                roleTsInst = _setupRoleTs('#role-select', roles, sd.oldRole, val => {
                    this.selectedRole = val || '';
                    const found = roles.find(r => r.value === val);
                    this.selectedRoleLabel = found?.label ?? '';
                });
            },
        };
    });

});
