@extends('layouts.backend')
@section('title', 'Sửa bài viết')

@section('content')
<div>

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

<div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

    {{-- ── Cột chính: form bản dịch (hoặc lời mời tạo bản dịch) ──────────── --}}
    <div x-data="{
        tab: 'noi_dung',
        tabFields: { noi_dung: ['title', 'excerpt', 'blocks', 'disclosure_text', 'cta_text', 'cta_url'], seo: ['seo_title', 'seo_description'] },
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
                    <div class="flex items-center justify-between mb-1">
                        <label class="label py-0 !p-0"><span class="label-text font-medium">Nội dung</span></label>
                        <span class="text-xs text-base-content/40">Tối đa 3 khối sản phẩm/bài</span>
                    </div>
                    @error('blocks')<div class="alert alert-error text-xs mb-2">{{ $message }}</div>@enderror

                    <script>window.PostExistingBlocks = @json($existingBlocks ?? []);</script>

                    <div class="pbc-composer">
                        <div class="pbc-block-list"></div>
                        <div class="pbc-add-row">
                            <button type="button" class="btn btn-sm btn-outline pbc-add-text">+ Thêm đoạn văn bản</button>
                            <button type="button" class="btn btn-sm btn-outline btn-primary pbc-add-product">+ Thêm khối sản phẩm</button>
                        </div>
                    </div>
                    <input type="hidden" name="blocks_json">
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
              x-data="{ isSponsored: {{ old('is_sponsored', $article->is_sponsored) ? 'true' : 'false' }} }">
            @csrf
            @method('PUT')
            <div class="card-body p-4">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Cài đặt chung</p>

                <div class="form-control mb-3">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Định dạng nội dung</span></label>
                    <select name="format" class="select select-bordered select-sm w-full @error('format') select-error @enderror">
                        @foreach(\Modules\Post\Enums\ArticleFormat::cases() as $f)
                        <option value="{{ $f->value }}" {{ old('format', $article->format->value) === $f->value ? 'selected' : '' }}>{{ $f->label() }}</option>
                        @endforeach
                    </select>
                    @error('format')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control mb-3">
                    <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Ảnh đại diện (URL)</span></label>
                    <input type="text" name="cover_image_url" value="{{ old('cover_image_url', $article->cover_image_url) }}"
                           class="input input-bordered input-sm w-full @error('cover_image_url') input-error @enderror">
                    @error('cover_image_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control mb-3">
                    <label class="label py-0 pb-1.5"><span class="label-text text-xs font-medium">Danh mục</span></label>
                    @php
                        $selectedCategoryIds = old('category_ids', $article->categories->pluck('id')->all());
                        $primaryCategoryId   = old('is_primary_category_id', $article->categories->firstWhere('pivot.is_primary', true)?->id);
                    @endphp
                    <div class="max-h-40 overflow-y-auto flex flex-col gap-1 border border-base-200 rounded-lg p-2">
                        @forelse($categories as $c)
                        <label class="flex items-center gap-2 cursor-pointer text-xs py-0.5">
                            <input type="checkbox" name="category_ids[]" value="{{ $c->id }}"
                                   class="checkbox checkbox-xs shrink-0" {{ in_array($c->id, $selectedCategoryIds) ? 'checked' : '' }}>
                            <span class="flex-1">{{ $c->name }}</span>
                            <input type="radio" name="is_primary_category_id" value="{{ $c->id }}"
                                   class="radio radio-xs radio-warning" {{ (string) $primaryCategoryId === (string) $c->id ? 'checked' : '' }}>
                        </label>
                        @empty
                        <p class="text-xs text-base-content/30 py-1">Chưa có danh mục.</p>
                        @endforelse
                    </div>
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

    </div>{{-- /sidebar --}}

</div>{{-- /grid --}}

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
        'Modules/Post/resources/assets/js/post.js',
    ], 'build/backend')
@endpush
