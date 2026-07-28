@extends('layouts.backend')
@section('title', 'Sửa bài viết')

@section('content')
@php
    // spec/Post_VersionHistory_Technical_Specification.md §13 — không thêm permission mới,
    // tái dùng post_article.view (xem lịch sử) và post_article.edit (khôi phục), xem
    // PostArticlePolicy::viewHistory()/restoreVersion().
    $historyIndexUrl    = $translation ? route('backend.post.translations.versions.index', $translation) : null;
    $canViewHistory     = $translation && auth()->user()->can('viewHistory', $translation);
    $canRestoreVersion  = $translation && auth()->user()->can('restoreVersion', $translation);
@endphp
<script>
function postVersionHistory(indexUrl, translationStatus, knownLatestId, canRestore) {
    return {
        indexUrl: indexUrl,
        translationStatus: translationStatus,
        canRestore: canRestore,
        open: false,
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
        concurrentWarning: null,

        // spec §13.4 — banner cảnh báo concurrent-edit, poll nhẹ mỗi 30s (không chặn submit).
        init() {
            if (! this.indexUrl || ! knownLatestId) return;
            setInterval(() => this.checkConcurrentEdit(), 30000);
        },

        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        },

        openHistory() {
            this.open = true;
            this.view = 'list';
            this.selected = [];
            this.error = null;
            this.loadList();
        },

        closeHistory() {
            this.open = false;
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
                // §13.3 — response không trả version mới (ghi bất đồng bộ) — reload để lấy
                // đúng nội dung translation vừa khôi phục trên toàn trang.
                window.location.reload();
            } catch (e) {
                this.error = e.message;
            } finally {
                this.loading = false;
            }
        },

        // §13.4 — không chặn submit, chỉ cảnh báo mềm; lỗi mạng khi poll bỏ qua im lặng.
        async checkConcurrentEdit() {
            if (! this.indexUrl || ! knownLatestId) return;
            try {
                const res = await fetch(`${this.indexUrl}?limit=1`);
                if (! res.ok) return;
                const json = await res.json();
                const latest = json.data[0];
                if (latest && String(latest.id) !== String(knownLatestId)) {
                    this.concurrentWarning = latest;
                }
            } catch (e) { /* im lặng */ }
        },
    };
}
</script>
<div @if($translation) x-data="postVersionHistory(@js($historyIndexUrl), @js($translation->status->value), @js($translation->latestVersion()?->id), @js($canRestoreVersion))" @endif>

@foreach(['success','error'] as $type)
    @if(session($type))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         x-transition.opacity.duration.500ms class="alert alert-{{ $type }} mb-4 text-sm">
        <span>{{ session($type) }}</span>
        <button @click="show = false" class="btn btn-ghost btn-xs ml-auto">✕</button>
    </div>
    @endif
@endforeach

{{-- Page header --}}
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Sửa bài viết</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Ngôn ngữ chính: {{ config('post.locales')[$article->main_locale] ?? $article->main_locale }}</p>
    </div>
    <a href="{{ route('backend.post.articles.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

{{-- ── Locale tabs (server-side — đổi tab load lại trang) ─────────────────── --}}
<div class="tabs tabs-boxed mb-4 inline-flex">
    @foreach(config('post.locales') as $code => $label)
        @php
            $t = $article->translation($code);
        @endphp
        <a href="{{ route('backend.post.articles.edit', $article) }}?locale={{ $code }}"
           class="tab gap-1.5 {{ $activeLocale === $code ? 'tab-active' : '' }}">
            {{ $label }}
            @if($t)
                <span class="badge badge-xs {{ $t->status->badgeClass() }}">{{ $t->status->label() }}</span>
            @else
                <span class="badge badge-xs badge-ghost">Chưa có</span>
            @endif
            @if($article->is_sponsored)
                {{-- §11 — dùng cờ is_sponsored thô (không phải isCurrentlySponsored()) vì đây là
                     badge quản trị nội bộ báo "bài đang cấu hình tài trợ", khác badge công khai
                     ở §12 vốn cần tôn trọng cửa sổ start/end date. --}}
                <span class="badge badge-xs {{ $article->sponsor_label?->badgeClass() ?? 'badge-warning' }}" title="Bài viết tài trợ">🏷</span>
            @endif
        </a>
    @endforeach
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

@if($translation)
{{-- spec/Post_VersionHistory_Technical_Specification.md §13.4 — cảnh báo concurrent-edit,
     KHÔNG chặn submit, chỉ báo "nội dung có thể đã cũ", xem lịch sử để tự quyết. --}}
<div x-show="concurrentWarning" x-cloak x-transition class="alert alert-warning py-3 px-4 mb-4 text-sm items-start">
    <span x-text="concurrentWarning ? ('Bài viết đã được ' + (concurrentWarning.created_by?.name ?? 'người khác') + ' cập nhật lúc ' + (concurrentWarning.created_at_human ?? '') + ' kể từ khi bạn mở trang này — nội dung bạn đang sửa có thể đã cũ. Xem lịch sử phiên bản trước khi lưu.') : ''"></span>
    <button type="button" class="btn btn-ghost btn-xs ml-auto" @click="concurrentWarning = null">✕</button>
</div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

    {{-- ── Cột chính: form bản dịch (hoặc lời mời tạo bản dịch) ──────────── --}}
    <div x-data="{
        tab: 'noi_dung',
        tabFields: { noi_dung: ['title', 'excerpt', 'direct_answer', 'blocks', 'disclosure_text', 'cta_text', 'cta_url'], seo: ['seo_title', 'seo_description'] },
        errs: {{ Js::from($errors->keys()) }},
        errCount(t) { return this.tabFields[t].filter(f => this.errs.some(e => e === f || e.startsWith(f + '.'))).length; },
        init() { for (const t of ['noi_dung', 'seo']) { if (this.errCount(t) > 0) { this.tab = t; break; } } }
    }">

    @if($translation)
    <form method="POST" action="{{ route('backend.post.translations.update', $translation) }}" novalidate data-article-form
          class="card bg-base-100 shadow-sm border border-base-200">
        @csrf
        @method('PUT')

        <div class="border-b border-base-200 px-6">
            <nav class="flex -mb-px" role="tablist">
                <button type="button" role="tab" @click="tab = 'noi_dung'"
                        class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                        :class="tab === 'noi_dung' ? 'border-primary text-primary' : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                    Nội dung
                    <span x-show="errCount('noi_dung') > 0" x-text="errCount('noi_dung')" class="badge badge-error badge-xs"></span>
                </button>
                <button type="button" role="tab" @click="tab = 'seo'"
                        class="flex items-center gap-1.5 px-1 py-4 text-sm font-medium border-b-2 transition-colors"
                        :class="tab === 'seo' ? 'border-primary text-primary' : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                    SEO
                    <span x-show="errCount('seo') > 0" x-text="errCount('seo')" class="badge badge-error badge-xs"></span>
                </button>
            </nav>
        </div>

        <div class="p-6">

            <div x-show="tab === 'noi_dung'" data-tab-label="Nội dung" class="space-y-4">

                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Tiêu đề <span class="text-error">*</span></span></label>
                    <input type="text" name="title" value="{{ old('title', $translation->title) }}"
                           data-req="Vui lòng nhập tiêu đề" data-val-maxlength="300"
                           class="input input-bordered input-sm w-full @error('title') input-error @enderror" maxlength="300">
                    @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Slug</span>
                        <span class="label-text-alt text-xs text-base-content/40">Để trống giữ nguyên</span>
                    </label>
                    <input type="text" name="slug" value="{{ old('slug', $translation->slug) }}"
                           class="input input-bordered input-sm w-full @error('slug') input-error @enderror" maxlength="320">
                    @error('slug')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Tóm tắt</span></label>
                    <textarea name="excerpt" rows="2" data-val-maxlength="500"
                              class="textarea textarea-bordered textarea-sm w-full @error('excerpt') textarea-error @enderror"
                              maxlength="500">{{ old('excerpt', $translation->excerpt) }}</textarea>
                    @error('excerpt')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Câu trả lời trực tiếp (AEO)</span>
                        <span class="label-text-alt text-xs text-base-content/40">~60 từ, trả lời thẳng câu hỏi chính của bài</span>
                    </label>
                    <textarea name="direct_answer" rows="2" data-val-maxlength="500"
                              placeholder="VD: Kỷ luật tích cực là phương pháp dạy con thiết lập ranh giới rõ ràng mà không cần la mắng, dựa trên tôn trọng và hợp tác thay vì trừng phạt."
                              class="textarea textarea-bordered textarea-sm w-full @error('direct_answer') textarea-error @enderror"
                              maxlength="500">{{ old('direct_answer', $translation->direct_answer) }}</textarea>
                    @error('direct_answer')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    <p class="text-xs text-base-content/40 mt-1">
                        Hiển thị nổi bật ngay đầu bài viết công khai — AI answer engine (Google AI Overview,
                        ChatGPT...) ưu tiên trích dẫn câu trả lời trực tiếp xuất hiện sớm trong bài.
                    </p>
                </div>

                <div class="form-control">
                    <div class="flex items-center justify-between mb-1">
                        <label class="label py-0 !p-0"><span class="label-text font-medium">Nội dung</span></label>
                        <span class="text-xs text-base-content/40">
                            Tối đa 3 khối sản phẩm/bài ·
                            <span class="pbc-total-word-count font-medium"></span>
                        </span>
                    </div>
                    @error('blocks')<div class="alert alert-error text-xs mb-2">{{ $message }}</div>@enderror

                    <script>window.PostExistingBlocks = @json($existingBlocks ?? []);</script>

                    <div class="pbc-composer">
                        <div class="pbc-block-list"></div>
                        <div class="pbc-add-row">
                            <button type="button" class="btn btn-sm btn-outline pbc-add-text">+ Thêm đoạn văn bản</button>
                            <button type="button" class="btn btn-sm btn-outline btn-primary pbc-add-product">+ Thêm khối sản phẩm</button>
                            <button type="button" class="btn btn-sm btn-outline btn-secondary pbc-add-faq">+ Thêm câu hỏi thường gặp</button>
                            <button type="button" class="btn btn-sm btn-outline btn-accent pbc-add-citation">+ Thêm trích dẫn có nguồn</button>
                            <button type="button" class="btn btn-sm btn-outline pbc-add-howto">+ Thêm hướng dẫn từng bước</button>
                        </div>
                        {{-- BUGFIX (không thuộc phần Version History) — phải nằm TRONG .pbc-composer:
                             post-block-composer.js dùng composerEl.querySelector('input[name="blocks_json"]')
                             (chỉ tìm descendant), input này trước đây là SIBLING của .pbc-composer nên
                             luôn null → submit ném "Cannot set properties of null (setting 'value')" và
                             lặng lẽ gửi blocks_json rỗng, xoá sạch content_blocks ở MỌI lần lưu bài. --}}
                        <input type="hidden" name="blocks_json">
                    </div>
                </div>

                @if($article->is_sponsored)
                {{-- §11 — chỉ hiện khi article.is_sponsored (đọc thẳng từ Blade, không cần JS
                     phức tạp vì đây là server-rendered per-locale form). --}}
                <div class="divider text-xs text-base-content/40 my-2">Thông tin tài trợ ({{ config('post.locales')[$activeLocale] }})</div>

                <div class="form-control">
                    <div class="flex items-center justify-between mb-1">
                        <label class="label py-0 !p-0"><span class="label-text font-medium">Nội dung công bố tài trợ <span class="text-error">*</span></span></label>
                        <button type="button" class="btn btn-ghost btn-xs"
                                @click="$refs.disclosureText.value = 'Nội dung tài trợ bởi {{ addslashes($article->sponsor_name) }}'">
                            Dùng mẫu
                        </button>
                    </div>
                    <textarea name="disclosure_text" rows="2" x-ref="disclosureText" data-val-maxlength="500"
                              class="textarea textarea-bordered textarea-sm w-full @error('disclosure_text') textarea-error @enderror"
                              maxlength="500">{{ old('disclosure_text', $translation->disclosure_text) }}</textarea>
                    @error('disclosure_text')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Nút CTA — nhãn</span></label>
                    <input type="text" name="cta_text" value="{{ old('cta_text', $translation->cta_text) }}"
                           data-val-maxlength="100"
                           class="input input-bordered input-sm w-full @error('cta_text') input-error @enderror" maxlength="100">
                    @error('cta_text')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Nút CTA — liên kết</span></label>
                    <input type="url" name="cta_url" value="{{ old('cta_url', $translation->cta_url) }}"
                           data-val-maxlength="500"
                           class="input input-bordered input-sm w-full @error('cta_url') input-error @enderror" maxlength="500">
                    @error('cta_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
                @endif

            </div>

            <div x-show="tab === 'seo'" data-tab-label="SEO" class="space-y-4">
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">SEO Title</span></label>
                    <input type="text" name="seo_title" value="{{ old('seo_title', $translation->seo_title) }}"
                           data-val-maxlength="200"
                           class="input input-bordered input-sm w-full @error('seo_title') input-error @enderror" maxlength="200">
                    @error('seo_title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">SEO Description</span></label>
                    <textarea name="seo_description" rows="3" data-val-maxlength="300"
                              class="textarea textarea-bordered textarea-sm w-full @error('seo_description') textarea-error @enderror"
                              maxlength="300">{{ old('seo_description', $translation->seo_description) }}</textarea>
                    @error('seo_description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-base-200 mt-4">
                <button type="submit" class="btn btn-primary btn-sm">Lưu bản dịch ({{ config('post.locales')[$activeLocale] }})</button>
            </div>

        </div>
    </form>
    @else
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-6">
            <p class="text-sm text-base-content/60 mb-4">
                Chưa có bản dịch <strong>{{ config('post.locales')[$activeLocale] }}</strong> — tạo mới bên dưới
                @if($article->translations->isNotEmpty())
                    (nội dung sẽ copy từ bản dịch chính làm nháp khởi điểm).
                @endif
            </p>
            <form method="POST" action="{{ route('backend.post.articles.translations.store', $article) }}" novalidate data-article-form>
                @csrf
                <input type="hidden" name="locale" value="{{ $activeLocale }}">
                <div class="form-control mb-3">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Tiêu đề <span class="text-error">*</span></span></label>
                    <input type="text" name="title" value="{{ old('title', $article->mainTranslation()?->title) }}"
                           data-req="Vui lòng nhập tiêu đề"
                           class="input input-bordered input-sm w-full @error('title') input-error @enderror" maxlength="300" autofocus>
                    @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                    + Tạo bản dịch {{ config('post.locales')[$activeLocale] }}
                </button>
            </form>
        </div>
    </div>
    @endif

    @if($translation)
    {{-- AICEM — subject = PostArticleTranslation (per-locale) từ Publishing Engine Phase 13,
         không phải PostArticle — xem config/aicem_subjects.php --}}
    <div class="mt-6">
        <x-aicem::panel
            :subject-type="'post_article'"
            :subject-id="$translation->id"
            :allowed-fields="config('aicem_subjects.post_article.fields')"
            :allow-block-edit="config('aicem_subjects.post_article.has_blocks')"
            :subject-taxonomy-preview="['category_slugs' => $article->categories->pluck('slug'), 'format' => [$article->format->value], 'tag_slugs' => $article->tags->pluck('slug')]"
        />
    </div>
    @endif

    </div>{{-- /cột chính --}}

    {{-- ── Sidebar ──────────────────────────────────────────────────────── --}}
    <div class="xl:sticky xl:top-4 space-y-4">

        {{-- Cài đặt chung (PostArticle — dùng chung mọi ngôn ngữ) --}}
        @php
            // Dùng dạng khối (không phải dạng 1 dòng rút gọn) cho nhất quán với chỗ khác trong
            // cùng file — trộn 2 dạng khai báo PHP-in-Blade khác nhau trong cùng 1 file từng gây
            // lỗi parse thật (Blade compile theo regex trên text thô, kể cả bên trong comment).
            $canManageSponsorship = auth()->user()->can('post_article.manage_sponsorship');
        @endphp
        <form method="POST" action="{{ route('backend.post.articles.update', $article) }}" class="card bg-base-100 shadow-sm border border-base-200"
              x-data="{ isSponsored: {{ old('is_sponsored', $article->is_sponsored) ? 'true' : 'false' }}, format: '{{ old('format', $article->format->value) }}' }">
            @csrf
            @method('PUT')
            <div class="card-body p-4">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Cài đặt chung</p>

                <div class="form-control mb-3">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Định dạng nội dung</span></label>
                    <select name="format" x-model="format" class="select select-bordered select-sm w-full @error('format') select-error @enderror">
                        @foreach(\Modules\Post\Enums\ArticleFormat::cases() as $f)
                        <option value="{{ $f->value }}" {{ old('format', $article->format->value) === $f->value ? 'selected' : '' }}>{{ $f->label() }}</option>
                        @endforeach
                    </select>
                    @error('format')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror

                    <div x-show="format === 'redirect'" x-cloak class="mt-3">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">URL đích <span class="text-error">*</span></span>
                        </label>
                        <input type="url" name="redirect_url" value="{{ old('redirect_url', $article->redirect_url) }}"
                               class="input input-bordered input-sm w-full @error('redirect_url') input-error @enderror"
                               placeholder="https://nguon-khac.vn/bai-viet-goc">
                        @error('redirect_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        <p class="mt-1 text-xs text-base-content/50">Nội dung soạn bên dưới (nếu có) sẽ không hiển thị công khai — bấm vào bài viết sẽ chuyển thẳng ra URL này.</p>
                        @if($article->isRedirect())
                        <a href="{{ route('backend.post.articles.clicks', $article) }}" class="mt-1.5 inline-flex items-center gap-1 text-xs text-primary hover:underline">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                            Xem thống kê click ({{ number_format($article->redirectClicks()->count()) }})
                        </a>
                        @endif
                    </div>
                </div>

                <div class="form-control mb-3">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Ảnh đại diện</span></label>
                    @if($article->cover_image_url)
                    <div class="mb-2 aspect-video w-full max-w-xs rounded-lg overflow-hidden bg-base-200">
                        <img src="{{ $article->cover_image_url }}" alt="Ảnh đại diện hiện tại" class="h-full w-full object-cover">
                    </div>
                    @endif
                    <div id="cover-filepond" data-context-type="post_article" data-context-id="{{ $article->id }}"></div>
                    <p class="mt-1 text-xs text-base-content/50">Tải ảnh mới sẽ tự động thay ảnh hiện tại.</p>
                </div>

                {{-- spec/Province_Showcase_Technical_Specification.md §6.3 — tuỳ chọn, không bắt buộc. --}}
                <div class="mb-3">
                    <x-address-picker
                        :required="false"
                        instance-id="article-edit"
                        name-province="province_code"
                        name-ward="ward_code"
                        :province-value="old('province_code', $article->province_code)"
                        :ward-value="old('ward_code', $article->ward_code)"
                    />
                </div>

                {{-- spec/Province_Showcase_Technical_Specification.md §3.4.1 — không bắt buộc.
                     $ocopProducts render sẵn theo ward_code/province_code hiện tại của bài (nếu
                     có) — article-form.js (_setupOcopProductPicker) lắng nghe sự kiện
                     "address-picker:change" (instanceId=article-edit) từ <x-address-picker> phía
                     trên và tự gọi lại GET /api/ocop-products/picker để load lại danh sách mỗi
                     khi người dùng đổi tỉnh/phường — ưu tiên ward_code (chuyên sâu hơn), fallback
                     province_code. Sản phẩm đã tick vẫn được giữ lại dù không còn khớp bộ lọc mới
                     (tránh mất liên kết cũ ngoài ý muốn). --}}
                <div class="form-control mb-3" id="ocop-product-picker" data-instance-id="article-edit">
                    <label class="label py-0 pb-1.5"><span class="label-text text-xs font-medium">Sản phẩm OCOP liên quan</span></label>
                    @php
                        $selectedOcopIds = old('ocop_product_ids', $article->ocopProducts->pluck('id')->all());
                    @endphp
                    <div class="max-h-40 overflow-y-auto flex flex-col gap-1 border border-base-200 rounded-lg p-2" data-ocop-picker-list>
                        @forelse($ocopProducts as $p)
                        <label class="flex items-center gap-2 cursor-pointer text-xs py-0.5">
                            <input type="checkbox" name="ocop_product_ids[]" value="{{ $p->id }}"
                                   data-name="{{ $p->name }}" data-place="{{ $p->ward_name ?: $p->province_name }}"
                                   class="checkbox checkbox-xs shrink-0" {{ in_array($p->id, $selectedOcopIds) ? 'checked' : '' }}>
                            <span class="flex-1">{{ $p->name }}</span>
                            @if($p->ward_name || $p->province_name)
                            <span class="text-base-content/40">{{ $p->ward_name ?: $p->province_name }}</span>
                            @endif
                        </label>
                        @empty
                        <p class="text-xs text-base-content/30 py-1" data-ocop-picker-empty>
                            @if($article->ward_code || $article->province_code)
                            Không có sản phẩm OCOP nào khớp tỉnh/phường đã chọn.
                            @else
                            Chọn tỉnh/thành hoặc phường/xã ở trên để xem sản phẩm OCOP tương ứng.
                            @endif
                        </p>
                        @endforelse
                    </div>
                </div>

                <div class="form-control mb-3">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text text-xs font-medium">Danh mục</span>
                        <span class="label-text-alt text-xs text-base-content/40">Bấm ★ để đặt danh mục chính</span>
                    </label>
                    @include('post::admin.articles._category-picker', [
                        'categoryTree' => $categoryTree,
                        'selectedCategoryIds' => old('category_ids', $article->categories->pluck('id')->all()),
                        'primaryCategoryId' => old('is_primary_category_id', $article->categories->firstWhere('pivot.is_primary', true)?->id),
                        'maxHeightClass' => 'max-h-40',
                        'size' => 'xs',
                    ])
                </div>

                <div class="form-control mb-3">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Tags</span></label>
                    <input type="text" name="tags" value="{{ old('tags', $article->tags->pluck('name')->implode(', ')) }}"
                           class="input input-bordered input-sm w-full">
                </div>

                <label class="flex items-start gap-2.5 cursor-pointer select-none group mb-3">
                    <input type="hidden" name="is_featured" value="0">
                    <input type="checkbox" name="is_featured" value="1"
                           class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0" {{ old('is_featured', $article->is_featured) ? 'checked' : '' }}>
                    <span class="text-sm font-medium group-hover:text-primary transition-colors">Bài viết nổi bật</span>
                </label>

                {{-- §11/§9 — checkbox luôn hiện nếu bài ĐANG sponsored (để không ai mất quyền xem
                     cấu hình hiện tại), nhưng disabled khi user không có quyền
                     post_article.manage_sponsorship — gate thật nằm ở server
                     (ArticleAdminController::update(), test §14 mục 8), disabled ở đây chỉ là UX. --}}
                @if($canManageSponsorship || $article->is_sponsored)
                <label class="flex items-start gap-2.5 select-none group mb-3 {{ $canManageSponsorship ? 'cursor-pointer' : 'opacity-60' }}">
                    <input type="hidden" name="is_sponsored" value="0">
                    <input type="checkbox" name="is_sponsored" value="1" x-model="isSponsored"
                           {{ $canManageSponsorship ? '' : 'disabled' }}
                           class="checkbox checkbox-sm checkbox-warning mt-0.5 shrink-0"
                           {{ old('is_sponsored', $article->is_sponsored) ? 'checked' : '' }}>
                    <span class="text-sm font-medium group-hover:text-primary transition-colors">Đây là bài viết tài trợ</span>
                </label>

                <div x-show="isSponsored" x-cloak class="space-y-3 mb-3 pl-2.5 border-l-2 border-warning/30">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Thông tin tài trợ</p>

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Tên nhãn hàng <span class="text-error">*</span></span></label>
                        <input type="text" name="sponsor_name" value="{{ old('sponsor_name', $article->sponsor_name) }}"
                               {{ $canManageSponsorship ? '' : 'disabled' }}
                               class="input input-bordered input-sm w-full @error('sponsor_name') input-error @enderror" maxlength="255">
                        @error('sponsor_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Logo nhãn hàng (URL)</span></label>
                        <input type="text" name="sponsor_logo_url" value="{{ old('sponsor_logo_url', $article->sponsor_logo_url) }}"
                               {{ $canManageSponsorship ? '' : 'disabled' }}
                               class="input input-bordered input-sm w-full @error('sponsor_logo_url') input-error @enderror" maxlength="500">
                        @error('sponsor_logo_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Loại nhãn <span class="text-error">*</span></span></label>
                        <select name="sponsor_label" {{ $canManageSponsorship ? '' : 'disabled' }}
                                class="select select-bordered select-sm w-full @error('sponsor_label') select-error @enderror">
                            <option value="">— Chọn —</option>
                            @foreach(\Modules\Post\Enums\SponsorLabel::cases() as $sl)
                            <option value="{{ $sl->value }}" {{ old('sponsor_label', $article->sponsor_label?->value) === $sl->value ? 'selected' : '' }}>{{ $sl->label() }}</option>
                            @endforeach
                        </select>
                        @error('sponsor_label')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Mã campaign</span></label>
                        <input type="text" name="campaign_code" value="{{ old('campaign_code', $article->campaign_code) }}"
                               {{ $canManageSponsorship ? '' : 'disabled' }}
                               class="input input-bordered input-sm w-full @error('campaign_code') input-error @enderror" maxlength="50">
                        @error('campaign_code')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div class="form-control">
                            <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Bắt đầu</span></label>
                            <input type="date" name="sponsored_start_date" value="{{ old('sponsored_start_date', $article->sponsored_start_date?->toDateString()) }}"
                                   {{ $canManageSponsorship ? '' : 'disabled' }}
                                   class="input input-bordered input-sm w-full @error('sponsored_start_date') input-error @enderror">
                            @error('sponsored_start_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Kết thúc</span></label>
                            <input type="date" name="sponsored_end_date" value="{{ old('sponsored_end_date', $article->sponsored_end_date?->toDateString()) }}"
                                   {{ $canManageSponsorship ? '' : 'disabled' }}
                                   class="input input-bordered input-sm w-full @error('sponsored_end_date') input-error @enderror">
                            @error('sponsored_end_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
                @endif

                <button type="submit" class="btn btn-outline btn-sm w-full">Lưu cài đặt chung</button>
            </div>
        </form>

        {{-- Publish All Languages --}}
        @php
            $hasPublishable = $article->translations->contains(fn ($t) => $t->isPublishable());
        @endphp
        <form method="POST" action="{{ route('backend.post.articles.publish-all', $article) }}"
              onsubmit="return confirm('Xuất bản tất cả bản dịch đã sẵn sàng (đã duyệt/đã lên lịch)?')">
            @csrf
            <button type="submit" class="btn btn-success btn-sm w-full" {{ $hasPublishable ? '' : 'disabled' }}>
                Xuất bản tất cả ngôn ngữ
            </button>
        </form>

        {{-- Trạng thái & hành động cho bản dịch đang xem --}}
        @if($translation)
        <div class="card bg-base-100 shadow-sm border border-base-200"
             x-data="{ showSchedule: false, showUnpublish: false, showTakedown: false }">
            <div class="card-body p-4">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Trạng thái ({{ config('post.locales')[$translation->locale] }})</p>
                    <span class="badge badge-sm {{ $translation->status->badgeClass() }}">{{ $translation->status->label() }}</span>
                </div>

                <div class="flex flex-col gap-2">

                    @can('submitForReview', $translation)
                    @if($translation->status === \Modules\Post\Enums\TranslationStatus::Draft)
                    <form method="POST" action="{{ route('backend.post.translations.submit', $translation) }}">
                        @csrf
                        <button class="btn btn-outline btn-sm w-full">Gửi duyệt</button>
                    </form>
                    @endif
                    @endcan

                    @can('approve', $translation)
                    @if($translation->status === \Modules\Post\Enums\TranslationStatus::Submitted)
                    <form method="POST" action="{{ route('backend.post.translations.approve', $translation) }}">
                        @csrf
                        <button class="btn btn-info btn-sm w-full">Duyệt</button>
                    </form>
                    @endif
                    @endcan

                    @can('publish', $translation)
                    @if($translation->isPublishable())
                    <form method="POST" action="{{ route('backend.post.translations.publish', $translation) }}">
                        @csrf
                        <button class="btn btn-success btn-sm w-full">Đăng ngay</button>
                    </form>
                    @endif
                    @endcan

                    @can('schedule', $translation)
                    @if($translation->status === \Modules\Post\Enums\TranslationStatus::Draft || $translation->status === \Modules\Post\Enums\TranslationStatus::Approved)
                    <button type="button" class="btn btn-info btn-sm w-full" @click="showSchedule = !showSchedule">Lên lịch xuất bản</button>
                    <div x-show="showSchedule" x-cloak x-transition class="pt-1">
                        <form method="POST" action="{{ route('backend.post.translations.schedule', $translation) }}" class="flex flex-col gap-2">
                            @csrf
                            <input type="text" name="scheduled_at" id="fp-scheduled-at"
                                   class="input input-bordered input-sm w-full fp-init" data-fp-mode="datetime"
                                   placeholder="DD/MM/YYYY HH:mm" required>
                            <button class="btn btn-primary btn-sm w-full">Xác nhận lên lịch</button>
                        </form>
                    </div>
                    @endif

                    @if($translation->status === \Modules\Post\Enums\TranslationStatus::Scheduled)
                    <form method="POST" action="{{ route('backend.post.translations.cancel-schedule', $translation) }}">
                        @csrf
                        <button class="btn btn-ghost btn-sm w-full">Huỷ lịch</button>
                    </form>
                    @endif
                    @endcan

                    @can('unpublish', $translation)
                    @if($translation->status === \Modules\Post\Enums\TranslationStatus::Published)
                    <button type="button" class="btn btn-warning btn-sm w-full" @click="showUnpublish = !showUnpublish">Gỡ bài</button>
                    <div x-show="showUnpublish" x-cloak x-transition class="pt-1">
                        <form method="POST" action="{{ route('backend.post.translations.unpublish', $translation) }}" class="flex flex-col gap-2">
                            @csrf
                            <textarea name="reason" rows="2" required minlength="10" placeholder="Lý do gỡ bài (bắt buộc, tối thiểu 10 ký tự)..."
                                      class="textarea textarea-bordered textarea-sm w-full"></textarea>
                            <button class="btn btn-warning btn-sm w-full">Xác nhận gỡ bài</button>
                        </form>
                    </div>
                    @endif

                    @if(in_array($translation->status, [\Modules\Post\Enums\TranslationStatus::Published, \Modules\Post\Enums\TranslationStatus::Unpublished], true))
                    <button type="button" class="btn btn-error btn-sm w-full" @click="showTakedown = !showTakedown">Gỡ khẩn cấp</button>
                    <div x-show="showTakedown" x-cloak x-transition class="pt-1">
                        <form method="POST" action="{{ route('backend.post.translations.takedown', $translation) }}" class="flex flex-col gap-2"
                              onsubmit="return confirm('Gỡ khẩn cấp sẽ lưu trữ vĩnh viễn bản dịch này và gửi thông báo cho CEO/AI Operator. Tiếp tục?')">
                            @csrf
                            <textarea name="reason" rows="2" required minlength="10" placeholder="Lý do gỡ khẩn cấp (bắt buộc, tối thiểu 10 ký tự)..."
                                      class="textarea textarea-bordered textarea-sm w-full"></textarea>
                            <button class="btn btn-error btn-sm w-full">Xác nhận gỡ khẩn cấp</button>
                        </form>
                    </div>
                    @endif
                    @endcan

                    @can('archive', $translation)
                    @if(in_array($translation->status, [\Modules\Post\Enums\TranslationStatus::Published, \Modules\Post\Enums\TranslationStatus::Unpublished], true))
                    <form method="POST" action="{{ route('backend.post.translations.archive', $translation) }}"
                          onsubmit="return confirm('Lưu trữ bản dịch này?')">
                        @csrf
                        <button class="btn btn-ghost btn-sm w-full">Lưu trữ</button>
                    </form>
                    @endif
                    @endcan

                    @can('delete', $translation)
                    <form method="POST" action="{{ route('backend.post.translations.destroy', $translation) }}"
                          onsubmit="return confirm('Xoá bản dịch {{ $translation->locale }}? Nếu đây là bản dịch cuối cùng, cả bài viết sẽ bị xoá.')">
                        @csrf @method('DELETE')
                        <button class="btn btn-ghost btn-sm w-full text-error">Xoá bản dịch này</button>
                    </form>
                    @endcan

                    {{-- §11 — chỉ hiện khi article.is_sponsored, ẩn hoàn toàn (không disabled)
                         nếu user không có quyền manage_sponsorship — khác checkbox ở "Cài đặt
                         chung" (vẫn hiện disabled để xem cấu hình), vì đây là nút HÀNH ĐỘNG
                         (thay đổi dữ liệu ngay khi bấm), không phải trường xem thông tin. --}}
                    @if($article->is_sponsored && $canManageSponsorship)
                    <form method="POST" action="{{ route('backend.post.articles.remove-sponsor', $article) }}"
                          onsubmit="return confirm('Gỡ tài trợ khỏi bài viết này? Toàn bộ thông tin sponsor sẽ bị xoá (không ảnh hưởng trạng thái xuất bản).')">
                        @csrf
                        <button class="btn btn-ghost btn-sm w-full text-warning">Gỡ tài trợ</button>
                    </form>
                    @endif

                </div>
            </div>
        </div>
        @endif

        {{-- spec/Post_VersionHistory_Technical_Specification.md §13.4 — không thêm permission
             mới, tái dùng post_article.view (§0). --}}
        @if($canViewHistory)
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Lịch sử phiên bản</p>
                <button type="button" class="btn btn-outline btn-sm w-full" @click="openHistory()">Xem lịch sử phiên bản</button>
            </div>
        </div>
        @endif

    </div>{{-- /sidebar --}}

</div>{{-- /grid --}}

@if($canViewHistory)
{{-- ── Modal: Lịch sử phiên bản (list / preview / compare) ────────────────── --}}
<div class="modal" :class="open ? 'modal-open' : ''">
    {{-- w-[90vw] max-w-[90vw] — nội dung so sánh (diff block cạnh nhau, bảng field before/after)
         cần bề ngang rộng để đọc thoải mái, modal-box mặc định (max-w-3xl ~ 48rem) quá chật. --}}
    <div class="modal-box w-[90vw] max-w-[90vw] max-h-[90vh]">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg"
                x-text="view === 'list' ? 'Lịch sử phiên bản' : (view === 'preview' ? 'Xem phiên bản' : 'So sánh phiên bản')"></h3>
            <button type="button" class="btn btn-ghost btn-sm btn-circle" @click="closeHistory()">✕</button>
        </div>

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
                    <div class="prose prose-sm max-w-none border border-base-200 rounded-lg p-4 max-h-96 overflow-y-auto" x-html="previewData.rendered_html"></div>
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
@endif

</div>
@endsection

@push('styles')
    @vite(['Modules/Post/resources/assets/sass/post.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/flatpickr.js',
        'resources/js/modules/jodit.js',
        'resources/js/modules/filepond.js',
        'Modules/Post/resources/assets/js/post.js',
    ], 'build/backend')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const coverEl = document.getElementById('cover-filepond');
        if (window.initFilePondUpload && coverEl) {
            initFilePondUpload(coverEl, {
                collection: 'cover',
                contextType: coverEl.dataset.contextType,
                contextId: coverEl.dataset.contextId,
                allowRevert: true,
            });
        }
    });
    </script>
@endpush
