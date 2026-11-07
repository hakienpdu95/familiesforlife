@extends('layouts.backend')
@section('title', 'Lịch sử phiên bản')

@section('content')
@php
    $editUrl = route('backend.post.articles.edit', $translation->article) . '?locale=' . $translation->locale;
@endphp
<script>
function postVersionHistoryPage(indexUrl, translationStatus, canRestore, editUrl) {
    return {
        indexUrl: indexUrl,
        translationStatus: translationStatus,
        canRestore: canRestore,
        view: 'list',
        loading: false,
        error: null,
        versions: [],
        meta: {},
        selected: [],
        previewData: null,
        compareData: null,
        restoreTarget: null,
        showRestoreConfirm: false,

        init() {
            this.loadList();
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        },

        async loadList() {
            this.loading = true;
            this.error = null;
            try {
                const res = await fetch(this.indexUrl);
                if (! res.ok) throw new Error('Không tải được lịch sử phiên bản.');
                const json = await res.json();
                this.versions = json.data;
                this.meta = json.meta;
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        // §13.4 — nút "Khôi phục" chỉ hiện trên version KHÔNG phải version mới nhất (trang 1).
        isLatest(version) {
            return this.meta.current_page === 1 && this.versions.length > 0 && version.id === this.versions[0].id;
        },

        toggleSelect(id) {
            const idx = this.selected.indexOf(id);
            if (idx >= 0) {
                this.selected.splice(idx, 1);
                return;
            }
            if (this.selected.length >= 2) {
                this.selected.shift();
            }
            this.selected.push(id);
        },

        async viewPreview(id) {
            this.view = 'preview';
            this.loading = true;
            this.error = null;
            try {
                const res = await fetch(`${this.indexUrl}/${id}`);
                if (! res.ok) throw new Error('Không tải được bản xem trước.');
                this.previewData = await res.json();
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        // Bấm vào 1 phiên bản trong danh sách → mặc định so sánh NGAY với bản liền trước
        // (version_number - 1) để thấy highlight thêm/xoá/thay đổi, thay vì chỉ xem nội dung
        // thô không có gì để đối chiếu. Nếu không tìm thấy bản liền trước trong trang hiện tại
        // (bản đầu tiên, hoặc bản trước nằm ở trang phân trang khác) → rơi về xem nội dung thô.
        viewVersion(version) {
            const previous = this.versions.find((v) => v.version_number === version.version_number - 1);
            if (previous) {
                this.compareTwo(previous, version);
                return;
            }
            this.viewPreview(version.id);
        },

        async compareSelected() {
            if (this.selected.length !== 2) return;
            // Luôn so "cũ → mới" theo version_number, KHÔNG theo thứ tự người dùng tick chọn —
            // nếu không, tick #2 trước #1 sẽ hiển thị field/block before-after bị đảo ngược.
            const ordered = this.versions
                .filter((v) => this.selected.includes(v.id))
                .sort((v1, v2) => v1.version_number - v2.version_number);
            if (ordered.length !== 2) return;
            const [from, to] = ordered;
            await this.compareTwo(from, to);
        },

        async compareTwo(from, to) {
            this.view = 'compare';
            this.loading = true;
            this.error = null;
            try {
                const res = await fetch(`${this.indexUrl}/compare?from=${from.id}&to=${to.id}`);
                if (! res.ok) throw new Error('Không so sánh được 2 phiên bản này.');
                this.compareData = await res.json();
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        backToList() {
            this.view = 'list';
            this.previewData = null;
            this.compareData = null;
            this.loadList();
        },

        confirmRestore(version) {
            if (! this.canRestore) return;
            this.restoreTarget = version;
            this.showRestoreConfirm = true;
        },

        async doRestore() {
            if (! this.restoreTarget) return;
            this.loading = true;
            this.error = null;
            try {
                const res = await fetch(`${this.indexUrl}/${this.restoreTarget.id}/restore`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': this.csrfToken(),
                        'Accept': 'application/json',
                    },
                });
                const json = await res.json();
                if (! res.ok) {
                    this.error = json.message ?? 'Khôi phục thất bại.';
                    return;
                }
                this.showRestoreConfirm = false;
                window.location.href = editUrl;
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
<div x-data="postVersionHistoryPage(@js(route('backend.post.translations.versions.index', $translation)), @js($translation->status->value), @js($canRestore), @js($editUrl))">

<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold text-base-content"
            x-text="view === 'list' ? 'Lịch sử phiên bản' : (view === 'preview' ? 'Xem phiên bản' : 'So sánh phiên bản')">Lịch sử phiên bản</h1>
        <p class="text-sm text-base-content/50 mt-0.5">
            {{ $translation->title }} · {{ config('post.locales')[$translation->locale] ?? $translation->locale }}
            <span class="badge badge-sm {{ $translation->status->badgeClass() }} ml-1">{{ $translation->status->label() }}</span>
        </p>
    </div>
    <a href="{{ $editUrl }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại bài viết
    </a>
</div>

<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body p-4">
        <div x-show="error" x-cloak class="alert alert-error text-xs mb-3"><span x-text="error"></span></div>
        <div x-show="loading" x-cloak class="flex justify-center py-6"><span class="loading loading-spinner loading-md"></span></div>

        {{-- List --}}
        <div x-show="view === 'list' && !loading" x-cloak>
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs text-base-content/50">Tick chọn đúng 2 phiên bản để so sánh.</p>
                <button type="button" class="btn btn-primary btn-xs" :disabled="selected.length !== 2" @click="compareSelected()">
                    So sánh (<span x-text="selected.length"></span>/2)
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th class="w-8"></th>
                            <th>#</th>
                            <th>Trạng thái</th>
                            <th>Người sửa</th>
                            <th>Thời gian</th>
                            <th>+/-</th>
                            <th class="text-right">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="version in versions" :key="version.id">
                            <tr>
                                <td>
                                    <input type="checkbox" class="checkbox checkbox-xs"
                                           :checked="selected.includes(version.id)" @change="toggleSelect(version.id)">
                                </td>
                                <td class="cursor-pointer font-medium" @click="viewVersion(version)">
                                    #<span x-text="version.version_number"></span>
                                </td>
                                <td>
                                    <span class="badge badge-xs"
                                          :class="version.trigger === 'publish' ? 'badge-success' : (version.trigger === 'restore' ? 'badge-warning' : 'badge-ghost')"
                                          x-text="version.trigger_label"></span>
                                    <template x-if="version.restored_from_version_number">
                                        <span class="badge badge-ghost badge-xs ml-1">← #<span x-text="version.restored_from_version_number"></span></span>
                                    </template>
                                </td>
                                <td class="text-xs" x-text="version.created_by?.name ?? 'Hệ thống'"></td>
                                <td class="text-xs text-base-content/50" x-text="version.created_at_human"></td>
                                <td class="text-xs">
                                    <span :class="version.char_delta > 0 ? 'text-success' : (version.char_delta < 0 ? 'text-error' : 'text-base-content/40')"
                                          x-text="(version.char_delta > 0 ? '+' : '') + version.char_delta"></span>
                                </td>
                                <td class="text-right whitespace-nowrap">
                                    <button type="button" class="btn btn-ghost btn-xs" @click="viewVersion(version)">Xem</button>
                                    <button type="button" class="btn btn-ghost btn-xs" @click="viewPreview(version.id)" title="Xem nguyên văn phiên bản này, không so sánh">Nội dung gốc</button>
                                    <template x-if="!isLatest(version) && canRestore">
                                        <button type="button" class="btn btn-warning btn-xs" @click="confirmRestore(version)">Khôi phục</button>
                                    </template>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="versions.length === 0">
                            <td colspan="7" class="text-center text-xs text-base-content/40 py-4">Chưa có lịch sử phiên bản nào.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Preview --}}
        <div x-show="view === 'preview' && !loading" x-cloak>
            <button type="button" class="btn btn-ghost btn-xs mb-3" @click="backToList()">← Quay lại danh sách</button>
            <template x-if="previewData">
                <div>
                    <div class="mb-2 text-xs text-base-content/50">
                        Phiên bản #<span x-text="previewData.version.version_number"></span> —
                        <span x-text="previewData.version.created_by?.name ?? 'Hệ thống'"></span>
                    </div>
                    <template x-if="previewData.missing_products.length > 0">
                        <div class="alert alert-warning text-xs py-2 px-3 mb-3">
                            <span x-text="previewData.missing_products.length + ' sản phẩm trong bản này đã bị xoá khỏi hệ thống, hiển thị tạm bằng dữ liệu đã lưu tại thời điểm đó.'"></span>
                        </div>
                    </template>
                    <h4 class="font-semibold mb-2" x-text="previewData.translation_snapshot.title"></h4>
                    <div class="prose prose-sm max-w-none border border-base-200 rounded-lg p-4 " x-html="previewData.rendered_html"></div>
                    <div class="flex justify-end mt-3" x-show="canRestore">
                        <button type="button" class="btn btn-warning btn-sm" @click="confirmRestore(previewData.version)">Khôi phục phiên bản này</button>
                    </div>
                </div>
            </template>
        </div>

        {{-- Compare --}}
        <div x-show="view === 'compare' && !loading" x-cloak>
            <button type="button" class="btn btn-ghost btn-xs mb-3" @click="backToList()">← Quay lại danh sách</button>
            <template x-if="compareData">
                <div>
                    <p class="text-xs text-base-content/50 mb-2">
                        So sánh #<span x-text="compareData.from.version_number"></span> → #<span x-text="compareData.to.version_number"></span>
                    </p>

                    <template x-if="compareData.field_changes.length > 0">
                        <table class="table table-sm mb-4">
                            <thead><tr><th>Trường</th><th>Trước</th><th>Sau</th></tr></thead>
                            <tbody>
                                <template x-for="fc in compareData.field_changes" :key="fc.field">
                                    <tr>
                                        <td class="font-medium text-xs" x-text="fc.field"></td>
                                        <td class="text-xs text-error/80" x-text="fc.before || '(trống)'"></td>
                                        <td class="text-xs text-success/80" x-text="fc.after || '(trống)'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </template>
                    <p x-show="compareData.field_changes.length === 0" class="text-xs text-base-content/40 mb-4">Không có trường nào thay đổi.</p>

                    <div class="space-y-2">
                        <template x-for="bc in compareData.block_changes" :key="bc.index">
                            <div class="rounded-lg border p-3 text-xs"
                                 :class="{
                                     'bg-success/10 border-success/30': bc.status === 'added',
                                     'bg-error/10 border-error/30': bc.status === 'removed',
                                     'bg-warning/10 border-warning/30': bc.status === 'changed',
                                     'border-base-200': bc.status === 'unchanged',
                                 }">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="badge badge-xs" x-text="bc.status"></span>
                                    <span class="text-base-content/50">Khối #<span x-text="bc.index + 1"></span> (<span x-text="bc.type"></span>)</span>
                                </div>
                                <template x-if="bc.type === 'text' && bc.status === 'changed' && bc.diff_html">
                                    <div x-html="bc.diff_html"></div>
                                </template>
                                <template x-if="bc.type === 'text' && bc.status === 'changed' && !bc.diff_html">
                                    {{-- Đoạn quá dài để diff từng từ (an toàn hiệu năng, xem
                                         CompareArticleVersionsAction::MAX_WORD_DIFF_CELLS) — hiện
                                         nguyên khối cũ/mới cạnh nhau như trước. --}}
                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="line-through opacity-60" x-html="bc.before_html"></div>
                                        <div x-html="bc.after_html"></div>
                                    </div>
                                </template>
                                <template x-if="bc.type === 'text' && bc.status === 'added'">
                                    <div x-html="bc.after_html"></div>
                                </template>
                                <template x-if="bc.type === 'text' && bc.status === 'removed'">
                                    <div class="line-through opacity-60" x-html="bc.before_html"></div>
                                </template>
                                <template x-if="bc.type === 'product'">
                                    <div>
                                        <span x-show="bc.product_ids_added && bc.product_ids_added.length" class="text-success">
                                            + thêm sản phẩm #<span x-text="(bc.product_ids_added || []).join(', #')"></span>
                                        </span>
                                        <span x-show="bc.product_ids_removed && bc.product_ids_removed.length" class="text-error ml-2">
                                            - bớt sản phẩm #<span x-text="(bc.product_ids_removed || []).join(', #')"></span>
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

{{-- ── Modal: Xác nhận khôi phục ───────────────────────────────────────── --}}
<div class="modal" :class="showRestoreConfirm ? 'modal-open' : ''">
    <div class="modal-box max-w-md">
        <h3 class="font-bold text-lg text-warning">Xác nhận khôi phục</h3>
        <div class="py-3 text-sm" x-show="restoreTarget">
            <p>Khôi phục nội dung từ phiên bản #<span x-text="restoreTarget?.version_number"></span>?</p>
            <template x-if="translationStatus === 'published'">
                <p class="mt-2 text-error font-medium">Bài viết đang XUẤT BẢN — nội dung công khai sẽ thay đổi NGAY LẬP TỨC.</p>
            </template>
            <p class="mt-2 text-base-content/60 text-xs">Nội dung hiện tại vẫn được giữ lại trong lịch sử (không mất dữ liệu), và trạng thái xuất bản không bị thay đổi.</p>
        </div>
        <div class="modal-action">
            <button type="button" class="btn btn-ghost btn-sm" @click="showRestoreConfirm = false">Huỷ</button>
            <button type="button" class="btn btn-warning btn-sm" @click="doRestore()">Xác nhận khôi phục</button>
        </div>
    </div>
</div>
</div>
@endsection

@push('styles')
    @vite(['Modules/Post/resources/assets/sass/post.scss'], 'build/backend')
@endpush
