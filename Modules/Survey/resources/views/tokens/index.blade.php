@extends('layouts.backend')

@section('title', 'API Tokens — ' . $survey->title)


@section('content')
<div
    x-data="tokenManager(
        @js($tokens->map(fn($t) => [
            'id'           => $t->id,
            'name'         => $t->name,
            'is_active'    => $t->is_active,
            'can_reveal'   => ! is_null($t->token_encrypted),
            'is_expired'   => $t->isExpired(),
            'last_used_at' => $t->last_used_at?->diffForHumans(),
            'expires_at'   => $t->expires_at?->format('d/m/Y H:i'),
            'created_at'   => $t->created_at->format('d/m/Y H:i'),
        ])->values()->all()),
        @js(csrf_token()),
        @js($survey->id)
    )"
    class="space-y-5"
>

    {{-- Page header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-base-content">API Tokens</h1>
            <p class="text-sm text-base-content/50 mt-0.5">
                Survey: <span class="font-semibold text-base-content/70">{{ $survey->title }}</span>
                <span class="font-mono text-xs ml-2 text-base-content/30">{{ $survey->status->label() }}</span>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('backend.surveys.edit', $survey) }}"
               class="btn btn-ghost btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                Quay lại Builder
            </a>
            @can('survey.manage_tokens')
            <button @click="openCreate()" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tạo token mới
            </button>
            @endcan
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
            <p class="font-semibold mb-1">Cách sử dụng token</p>
            <p>Gắn vào header mỗi request tới <code class="font-mono bg-info/20 px-1 rounded">/api/v1/surveys/{{ $survey->slug }}/schema</code> và <code class="font-mono bg-info/20 px-1 rounded">/submit</code>:</p>
            <code class="block mt-1.5 font-mono text-xs bg-base-100 rounded px-3 py-1.5">Authorization: Bearer &lt;token&gt;</code>
        </div>
    </div>

    {{-- Token list --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0">

            {{-- Empty state --}}
            <div x-show="tokens.length === 0" class="text-center py-14">
                <svg class="w-10 h-10 mx-auto mb-3 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                <p class="font-semibold text-base-content/40">Chưa có token nào</p>
                <p class="text-xs text-base-content/30 mt-1">Tạo token để frontend có thể gọi API schema + submit</p>
            </div>

            {{-- Table --}}
            <div x-show="tokens.length > 0" class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr class="text-xs text-base-content/50 uppercase tracking-wide border-b border-base-200">
                            <th class="pl-5">Tên token</th>
                            <th>Trạng thái</th>
                            <th>Lần cuối dùng</th>
                            <th>Hết hạn</th>
                            <th>Tạo lúc</th>
                            <th class="pr-5 text-right">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="token in tokens" :key="token.id">
                            <tr class="border-b border-base-200 last:border-0 hover:bg-base-50 transition-colors">

                                {{-- Name + icon --}}
                                <td class="pl-5">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 shrink-0"
                                             :class="token.is_active && !token.is_expired ? 'text-success' : 'text-base-content/25'"
                                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                        </svg>
                                        <span class="font-medium text-sm" x-text="token.name"></span>
                                    </div>
                                </td>

                                {{-- Status badge --}}
                                <td>
                                    <span class="badge badge-xs badge-soft"
                                          :class="{
                                              'badge-success': token.is_active && !token.is_expired,
                                              'badge-error':   !token.is_active,
                                              'badge-warning':  token.is_active && token.is_expired,
                                          }"
                                          x-text="token.is_expired ? 'Hết hạn' : (token.is_active ? 'Active' : 'Đã thu hồi')">
                                    </span>
                                </td>

                                <td class="text-sm text-base-content/60" x-text="token.last_used_at ?? '—'"></td>
                                <td class="text-sm text-base-content/60 font-mono text-xs" x-text="token.expires_at ?? 'Không hết hạn'"></td>
                                <td class="text-sm text-base-content/50 font-mono text-xs" x-text="token.created_at"></td>

                                {{-- Actions --}}
                                <td class="pr-5">
                                    <div class="flex items-center justify-end gap-1">

                                        {{-- Xem token (chỉ khi can_reveal) --}}
                                        <button x-show="token.can_reveal"
                                                @click="reveal(token)"
                                                :disabled="saving"
                                                class="btn btn-ghost btn-xs gap-1 text-base-content/50 hover:text-info"
                                                title="Xem lại token">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            Xem
                                        </button>

                                        {{-- Thu hồi (chỉ active + chưa hết hạn) --}}
                                        <button x-show="token.is_active && !token.is_expired"
                                                @click="revoke(token)"
                                                :disabled="saving"
                                                class="btn btn-ghost btn-xs gap-1 text-base-content/50 hover:text-warning"
                                                title="Thu hồi token">
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            Thu hồi
                                        </button>

                                        {{-- Xóa (luôn hiển thị) --}}
                                        <button @click="openDelete(token)"
                                                :disabled="saving"
                                                class="btn btn-ghost btn-xs gap-1 text-base-content/30 hover:text-error"
                                                title="Xóa token">
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

    {{-- ─────────── MODAL: Tạo token ─────────── --}}
    <dialog class="modal" :class="{ 'modal-open': createModal.open }">
        <div class="modal-box max-w-md">
            <h3 class="font-bold text-lg mb-5">Tạo API Token mới</h3>
            <div class="space-y-4">
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Tên token <span class="text-error">*</span></legend>
                    <input type="text" x-model="createModal.name"
                           class="input w-full"
                           placeholder="VD: Website chính, App mobile..."
                           x-ref="createModalInput"
                           @keydown.enter="submitCreate()">
                    <p class="fieldset-label">Dùng để phân biệt nguồn gọi API</p>
                </fieldset>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Hết hạn vào (không bắt buộc)</legend>
                    <input type="datetime-local" x-model="createModal.expires_at"
                           class="input w-full"
                           :min="new Date().toISOString().slice(0,16)">
                    <p class="fieldset-label">Để trống = token không hết hạn</p>
                </fieldset>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button @click="createModal.open = false" class="btn btn-ghost btn-sm">Hủy</button>
                <button @click="submitCreate()" :disabled="saving || !createModal.name.trim()"
                        class="btn btn-primary btn-sm min-w-[100px]">
                    <span x-show="saving" class="loading loading-spinner loading-xs"></span>
                    <span x-show="!saving">Tạo token</span>
                </button>
            </div>
        </div>
        <div class="modal-backdrop" @click="createModal.open = false"><button>close</button></div>
    </dialog>

    {{-- ─────────── MODAL: Hiển thị / xem lại plaintext ─────────── --}}
    <dialog class="modal" :class="{ 'modal-open': revealModal.open }">
        <div class="modal-box max-w-lg">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-success/15 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                </div>
                <div>
                    <h3 class="font-bold text-lg leading-tight" x-text="revealModal.title"></h3>
                    <p class="text-sm text-base-content/50" x-text="'Token: ' + revealModal.tokenName"></p>
                </div>
            </div>

            {{-- Loading state --}}
            <div x-show="revealModal.loading" class="flex items-center justify-center py-8 gap-3 text-base-content/50">
                <span class="loading loading-spinner loading-sm"></span>
                <span class="text-sm">Đang tải token...</span>
            </div>

            {{-- Token content --}}
            <div x-show="!revealModal.loading">
                <div class="rounded-xl bg-base-200 border border-base-300 p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold text-base-content/50 uppercase tracking-wide">Bearer Token</span>
                        <button @click="copyPlain()" class="btn btn-ghost btn-xs gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <span x-text="copied ? 'Đã copy!' : 'Copy'"></span>
                        </button>
                    </div>
                    <code class="block font-mono text-xs break-all text-base-content leading-relaxed select-all"
                          x-text="revealModal.plain"></code>
                </div>
                <div class="mt-4 rounded-lg bg-base-300/50 px-4 py-3">
                    <p class="text-xs font-semibold text-base-content/50 mb-1.5">Ví dụ sử dụng:</p>
                    <code class="text-xs font-mono text-base-content/70 break-all"
                          x-text="`Authorization: Bearer ${revealModal.plain}`"></code>
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
                    <h3 class="font-bold text-base">Xóa token?</h3>
                    <p class="text-sm text-base-content/60 mt-1">
                        Token <strong x-text="deleteModal.tokenName"></strong> sẽ bị xóa vĩnh viễn khỏi hệ thống.
                        Mọi request đang dùng token này sẽ bị từ chối ngay lập tức.
                    </p>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button @click="deleteModal.open = false" class="btn btn-ghost btn-sm">Hủy</button>
                <button @click="confirmDelete()" :disabled="saving"
                        class="btn btn-error btn-sm min-w-[80px]">
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
function tokenManager(initialTokens, csrfToken, surveyId) {
    return {
        tokens:  initialTokens,
        saving:  false,
        copied:  false,
        flash:   { text: '', type: 'success' },

        createModal: { open: false, name: '', expires_at: '' },
        revealModal: { open: false, loading: false, plain: '', tokenName: '', title: '' },
        deleteModal: { open: false, tokenId: null, tokenName: '' },

        // ── Create ────────────────────────────────────────────────────
        openCreate() {
            this.createModal = { open: true, name: '', expires_at: '' };
            this.$nextTick(() => this.$refs.createModalInput?.focus());
        },

        async submitCreate() {
            if (!this.createModal.name.trim()) return;

            const body = { name: this.createModal.name };
            if (this.createModal.expires_at) body.expires_at = this.createModal.expires_at;

            const res = await this.api(`/dashboard/surveys/${surveyId}/tokens`, 'POST', body);
            if (!res) return;

            this.tokens.unshift(res.token);
            this.createModal.open = false;

            // Hiển thị plaintext — có thể xem lại sau qua nút "Xem"
            this.revealModal = {
                open: true, loading: false,
                plain: res.plain,
                tokenName: res.token.name,
                title: 'Token đã được tạo!',
            };
        },

        // ── Reveal ────────────────────────────────────────────────────
        async reveal(token) {
            this.revealModal = { open: true, loading: true, plain: '', tokenName: token.name, title: 'Xem lại token' };

            const res = await this.api(`/dashboard/surveys/${surveyId}/tokens/${token.id}/reveal`, 'GET');
            if (!res) {
                this.revealModal.open = false;
                return;
            }

            this.revealModal.loading = false;
            this.revealModal.plain   = res.plain;
        },

        closeReveal() {
            this.revealModal.open  = false;
            this.revealModal.plain = '';   // xóa khỏi bộ nhớ Alpine
        },

        // ── Revoke ────────────────────────────────────────────────────
        async revoke(token) {
            const res = await this.api(`/dashboard/surveys/${surveyId}/tokens/${token.id}/revoke`, 'PATCH');
            if (!res) return;

            const found = this.tokens.find(t => t.id === token.id);
            if (found) found.is_active = false;

            this.ok(res.message);
        },

        // ── Delete ────────────────────────────────────────────────────
        openDelete(token) {
            this.deleteModal = { open: true, tokenId: token.id, tokenName: token.name };
        },

        async confirmDelete() {
            const { tokenId, tokenName } = this.deleteModal;
            this.deleteModal.open = false;

            const res = await this.api(`/dashboard/surveys/${surveyId}/tokens/${tokenId}`, 'DELETE');
            if (!res) return;

            this.tokens = this.tokens.filter(t => t.id !== tokenId);
            this.ok(res.message);
        },

        // ── Copy ──────────────────────────────────────────────────────
        async copyPlain() {
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
    };
}
</script>
@endpush
