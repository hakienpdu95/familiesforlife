@extends('layouts.backend')

@section('title', 'Turnstile Sites')


@section('content')
<div
    x-data="turnstileSiteManager(
        @js($sites->map(fn($s) => [
            'id'            => $s->id,
            'name'          => $s->name,
            'site_key'      => $s->site_key,
            'is_active'     => $s->is_active,
            'surveys_count' => $s->surveys_count,
            'created_at'    => $s->created_at->format('d/m/Y H:i'),
        ])->values()->all()),
        @js(csrf_token())
    )"
    class="space-y-5"
>

    {{-- Page header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Turnstile Sites</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Mỗi site = 1 widget Cloudflare Turnstile của 1 domain bên ngoài (vd thuchocvn.vn).
                Nhiều survey có thể dùng chung 1 site — không cần tạo key riêng cho từng survey.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('backend.surveys.index') }}" class="btn btn-ghost btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                Quay lại Surveys
            </a>
            <button @click="openCreate()" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Thêm site mới
            </button>
        </div>
    </div>

    {{-- Flash --}}
    <div x-show="flash.text" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         :class="flash.type === 'error' ? 'alert-error' : 'alert-success'"
         class="alert text-sm py-2 px-4 rounded-lg" role="alert">
        <span x-text="flash.text"></span>
    </div>

    {{-- How-to card --}}
    <div class="alert alert-info alert-soft text-sm gap-3">
        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div>
            <p class="font-semibold mb-1">Site key / Secret key lấy ở đâu?</p>
            <p>Site bên ngoài (vd thuchocvn.vn) đã tự cấu hình <code class="font-mono bg-info/20 px-1 rounded">TURNSTILE_SITE_KEY</code> /
               <code class="font-mono bg-info/20 px-1 rounded">TURNSTILE_SECRET_KEY</code> trong <code class="font-mono bg-info/20 px-1 rounded">.env</code> của họ, tương ứng với domain của họ trên Cloudflare.
               Xin 2 giá trị này từ đội phụ trách site đó rồi nhập vào đây — backend dùng secret key để verify, site key được trả về qua API <code class="font-mono bg-info/20 px-1 rounded">/schema</code> để frontend render widget.</p>
        </div>
    </div>

    {{-- Site list --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0">

            {{-- Empty state --}}
            <div x-show="sites.length === 0" class="text-center py-14">
                <svg class="w-10 h-10 mx-auto mb-3 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="font-semibold text-base-content/40">Chưa có Turnstile site nào</p>
                <p class="text-xs text-base-content/30 mt-1">Thêm site để gán bot protection cho các survey nhúng ở domain đó</p>
            </div>

            {{-- Table --}}
            <div x-show="sites.length > 0" class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr class="text-xs text-base-content/50 uppercase tracking-wide border-b border-base-200">
                            <th class="pl-5">Tên site</th>
                            <th>Site key</th>
                            <th>Trạng thái</th>
                            <th>Survey đang dùng</th>
                            <th>Tạo lúc</th>
                            <th class="pr-5 text-right">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="site in sites" :key="site.id">
                            <tr class="border-b border-base-200 last:border-0 hover:bg-base-50 transition-colors">

                                <td class="pl-5">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 shrink-0" :class="site.is_active ? 'text-success' : 'text-base-content/25'"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span class="font-medium text-sm" x-text="site.name"></span>
                                    </div>
                                </td>

                                <td class="font-mono text-xs text-base-content/60" x-text="site.site_key"></td>

                                <td>
                                    <span class="badge badge-xs badge-soft" :class="site.is_active ? 'badge-success' : 'badge-error'"
                                          x-text="site.is_active ? 'Active' : 'Tắt'"></span>
                                </td>

                                <td class="text-sm text-base-content/60" x-text="site.surveys_count"></td>
                                <td class="text-sm text-base-content/50 font-mono text-xs" x-text="site.created_at"></td>

                                <td class="pr-5">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="openEdit(site)" :disabled="saving"
                                                class="btn btn-ghost btn-xs gap-1 text-base-content/50 hover:text-primary"
                                                title="Sửa">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            Sửa
                                        </button>
                                        <button @click="revealSecret(site)" :disabled="saving"
                                                class="btn btn-ghost btn-xs gap-1 text-base-content/50 hover:text-info"
                                                title="Xem secret key">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            Xem
                                        </button>
                                        <button @click="openDelete(site)" :disabled="saving"
                                                class="btn btn-ghost btn-xs gap-1 text-base-content/30 hover:text-error"
                                                title="Xóa site">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            Xóa
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ─────────── MODAL: Tạo / Sửa site ─────────── --}}
    <dialog class="modal" :class="{ 'modal-open': formModal.open }">
        <div class="modal-box max-w-md">
            <h3 class="font-bold text-lg mb-5" x-text="formModal.id ? 'Sửa Turnstile site' : 'Thêm Turnstile site mới'"></h3>
            <div class="space-y-4">
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Tên site <span class="text-error">*</span></legend>
                    <input type="text" x-model="formModal.name" class="input w-full" placeholder="VD: thuchocvn.vn" x-ref="formModalInput">
                    <p class="fieldset-label">Tên domain bên ngoài để dễ nhận biết</p>
                </fieldset>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Site key <span class="text-error">*</span></legend>
                    <input type="text" x-model="formModal.site_key" class="input w-full font-mono text-sm" placeholder="0x4AAAAAAA...">
                    <p class="fieldset-label">Public — site ngoài đã có sẵn trong .env (TURNSTILE_SITE_KEY) của họ</p>
                </fieldset>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">
                        Secret key <span x-show="!formModal.id" class="text-error">*</span>
                    </legend>
                    <input type="text" x-model="formModal.secret_key" class="input w-full font-mono text-sm"
                           :placeholder="formModal.id ? 'Để trống nếu không đổi' : '0x4AAAAAAA...'">
                    <p class="fieldset-label">Bí mật — lấy từ .env (TURNSTILE_SECRET_KEY) của site ngoài, dùng để backend verify</p>
                </fieldset>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button @click="formModal.open = false" class="btn btn-ghost btn-sm">Hủy</button>
                <button @click="submitForm()" :disabled="saving || !canSubmitForm" class="btn btn-primary btn-sm min-w-[100px]">
                    <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                    <span x-show="!saving" x-text="formModal.id ? 'Lưu lại' : 'Tạo site'"></span>
                </button>
            </div>
        </div>
        <div class="modal-backdrop" @click="formModal.open = false"><button>close</button></div>
    </dialog>

    {{-- ─────────── MODAL: Xem secret key ─────────── --}}
    <dialog class="modal" :class="{ 'modal-open': revealModal.open }">
        <div class="modal-box max-w-lg">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-info/15 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </div>
                <div>
                    <h3 class="font-bold text-lg leading-tight">Secret key</h3>
                    <p class="text-sm text-base-content/50" x-text="'Site: ' + revealModal.siteName"></p>
                </div>
            </div>

            <div x-show="revealModal.loading" class="flex items-center justify-center py-8 gap-3 text-base-content/50">
                <span class="loading loading-spinner loading-sm"></span>
                <span class="text-sm">Đang giải mã...</span>
            </div>

            <div x-show="!revealModal.loading">
                <div class="rounded-xl bg-base-200 border border-base-300 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-base-content/50 uppercase tracking-wide">Secret Key</span>
                        <button @click="copySecret()" class="btn btn-ghost btn-xs gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <span x-text="copied ? 'Đã copy!' : 'Copy'"></span>
                        </button>
                    </div>
                    <code class="block font-mono text-xs break-all text-base-content leading-relaxed select-all" x-text="revealModal.plain"></code>
                </div>
            </div>

            <div class="flex justify-end mt-5">
                <button @click="closeReveal()" class="btn btn-primary btn-sm min-w-[100px]">Đóng</button>
            </div>
        </div>
        <div class="modal-backdrop" @click="closeReveal()"><button>close</button></div>
    </dialog>

    {{-- ─────────── MODAL: Xác nhận xóa ─────────── --}}
    <dialog class="modal" :class="{ 'modal-open': deleteModal.open }">
        <div class="modal-box max-w-sm">
            <div class="flex items-start gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-error/15 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-5 h-5 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h3 class="font-bold text-base">Xóa site?</h3>
                    <p class="text-sm text-base-content/60 mt-1">
                        Site <strong x-text="deleteModal.siteName"></strong> sẽ bị xóa vĩnh viễn.
                        Chỉ xóa được nếu không còn survey nào đang gắn site này.
                    </p>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button @click="deleteModal.open = false" class="btn btn-ghost btn-sm">Hủy</button>
                <button @click="confirmDelete()" :disabled="saving" class="btn btn-error btn-sm min-w-[80px]">
                    <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                    <span x-show="!saving">Xóa</span>
                </button>
            </div>
        </div>
        <div class="modal-backdrop" @click="deleteModal.open = false"><button>close</button></div>
    </dialog>

</div>
@endsection

@push('scripts')
<script>
function turnstileSiteManager(initialSites, csrfToken) {
    return {
        sites:   initialSites,
        saving:  false,
        copied:  false,
        flash:   { text: '', type: 'success' },

        formModal:   { open: false, id: null, name: '', site_key: '', secret_key: '' },
        revealModal: { open: false, loading: false, plain: '', siteName: '' },
        deleteModal: { open: false, siteId: null, siteName: '' },

        get canSubmitForm() {
            const f = this.formModal;
            return f.name.trim() && f.site_key.trim() && (f.id || f.secret_key.trim());
        },

        // ── Create / Edit ─────────────────────────────────────────────
        openCreate() {
            this.formModal = { open: true, id: null, name: '', site_key: '', secret_key: '' };
            this.$nextTick(() => this.$refs.formModalInput?.focus());
        },

        openEdit(site) {
            this.formModal = { open: true, id: site.id, name: site.name, site_key: site.site_key, secret_key: '' };
            this.$nextTick(() => this.$refs.formModalInput?.focus());
        },

        async submitForm() {
            if (!this.canSubmitForm) return;

            const body = { name: this.formModal.name, site_key: this.formModal.site_key };
            if (this.formModal.secret_key) body.secret_key = this.formModal.secret_key;

            const isEdit = !!this.formModal.id;
            const url    = isEdit ? `/dashboard/turnstile-sites/${this.formModal.id}` : '/dashboard/turnstile-sites';
            const res    = await this.api(url, isEdit ? 'PUT' : 'POST', body);
            if (!res) return;

            if (isEdit) {
                const idx = this.sites.findIndex(s => s.id === res.site.id);
                if (idx !== -1) this.sites[idx] = { ...this.sites[idx], ...res.site };
            } else {
                this.sites.unshift(res.site);
            }

            this.formModal.open = false;
            this.ok(res.message);
        },

        // ── Reveal ────────────────────────────────────────────────────
        async revealSecret(site) {
            this.revealModal = { open: true, loading: true, plain: '', siteName: site.name };

            const res = await this.api(`/dashboard/turnstile-sites/${site.id}/reveal`, 'GET');
            if (!res) {
                this.revealModal.open = false;
                return;
            }

            this.revealModal.loading = false;
            this.revealModal.plain   = res.plain;
        },

        closeReveal() {
            this.revealModal.open  = false;
            this.revealModal.plain = '';
        },

        // ── Delete ────────────────────────────────────────────────────
        openDelete(site) {
            this.deleteModal = { open: true, siteId: site.id, siteName: site.name };
        },

        async confirmDelete() {
            const { siteId, siteName } = this.deleteModal;
            this.deleteModal.open = false;

            const res = await this.api(`/dashboard/turnstile-sites/${siteId}`, 'DELETE');
            if (!res) return;

            this.sites = this.sites.filter(s => s.id !== siteId);
            this.ok(res.message);
        },

        // ── Copy ──────────────────────────────────────────────────────
        async copySecret() {
            try {
                await navigator.clipboard.writeText(this.revealModal.plain);
                this.copied = true;
                setTimeout(() => this.copied = false, 2000);
            } catch {
                // clipboard API bị block — user select-all thủ công
            }
        },

        // ── Flash & API helper ────────────────────────────────────────
        ok(msg)  { this.flash = { text: msg, type: 'success' }; setTimeout(() => this.flash.text = '', 4000); },
        err(msg) { this.flash = { text: msg, type: 'error' };   setTimeout(() => this.flash.text = '', 6000); },

        async api(url, method, body = null) {
            this.saving = true;
            try {
                const res  = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept':       'application/json',
                    },
                    body: body ? JSON.stringify(body) : null,
                });
                const json = await res.json();
                if (!res.ok) {
                    const msg = json.message || json.error || (json.errors ? Object.values(json.errors).flat().join(' ') : 'Có lỗi xảy ra.');
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
    };
}
</script>
@endpush
