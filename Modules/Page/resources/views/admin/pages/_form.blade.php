{{-- Dùng chung create/edit — spec/Page_Static_Pages_Technical_Specification.md §4.2. --}}
@php
    $selectedTemplate = old('template', $page?->template ?? 'default');
    $isPublished = $page && $page->status === \Modules\Page\Enums\PageStatus::Published;
    $publicUrl = $page ? url('/'.$page->slug) : null;
    $coverUrl = $page?->getFirstMediaUrl('cover');
@endphp

<div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_320px] gap-6 items-start"
     x-data="{
        template: @js($selectedTemplate),
        title: @js((string) old('title', $page?->title)),
        slug: @js((string) old('slug', $page?->slug)),
        excerpt: @js((string) old('excerpt', $page?->excerpt)),
        seoTitle: @js((string) old('seo_title', $page?->seo_title)),
        seoDescription: @js((string) old('seo_description', $page?->seo_description)),
        originalTemplate: @js($page?->template ?? 'default'),
        originalSlug: @js((string) $page?->slug),
        isPublished: @js($isPublished),
        copied: false,
        copy(text) { navigator.clipboard.writeText(text); this.copied = true; setTimeout(() => this.copied = false, 1500) },
     }">

    <div class="space-y-6 min-w-0">

        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5 sm:p-6 gap-0">
                <header class="flex items-start gap-3 mb-5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-primary/10 text-primary shrink-0">
                        <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold leading-tight">{{ __('Nội dung trang') }}</h2>
                        <p class="text-xs text-base-content/50 mt-0.5">{{ __('Bản gốc :language', ['language' => \App\Support\I18n\PortalLocale::label(\App\Support\I18n\PortalLocale::default())]) }}</p>
                    </div>
                </header>

                <div class="space-y-5">
                    <div class="form-control">
                        <div class="flex items-center justify-between pb-1.5">
                            <label for="page-title" class="label-text font-medium">{{ __('Tiêu đề') }} <span class="text-error">*</span></label>
                            <span class="text-[11px] tabular-nums text-base-content/40" x-text="`${title.length}/200`"></span>
                        </div>
                        <input type="text" name="title" id="page-title" x-model="title" value="{{ old('title', $page?->title) }}"
                               class="input input-bordered w-full text-base font-medium @error('title') input-error @enderror"
                               maxlength="200" placeholder="{{ __('Vd: Giới thiệu') }}">
                        @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <div class="flex items-center justify-between pb-1.5">
                            <label for="page-slug" class="label-text font-medium">{{ __('Đường dẫn (slug)') }}</label>
                            <span class="text-xs text-base-content/40">{{ __('Để trống sẽ tự sinh từ tiêu đề') }}</span>
                        </div>
                        <div class="join w-full">
                            <span class="join-item hidden sm:inline-flex items-center px-3 border border-base-300 bg-base-200/60 text-xs font-mono text-base-content/50 whitespace-nowrap">{{ url('/') }}/</span>
                            <input type="text" name="slug" id="page-slug" x-model="slug"
                                   value="{{ old('slug', $page?->slug) }}"
                                   class="input input-bordered join-item w-full font-mono text-sm @error('slug') input-error @enderror"
                                   maxlength="160" placeholder="gioi-thieu">
                        </div>
                        @error('slug')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        <div class="mt-2 flex items-start gap-2 rounded-lg bg-warning/10 px-3 py-2 text-xs text-base-content/70"
                             x-show="isPublished && slug !== originalSlug && slug !== ''" x-cloak>
                            <svg class="w-4 h-4 text-warning shrink-0 mt-px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <p>{{ __('Trang đang xuất bản — đổi đường dẫn khiến liên kết cũ (menu, mạng xã hội, backlink) tới :url bị lỗi 404.', ['url' => '/'.($page?->slug ?? '')]) }}</p>
                        </div>
                    </div>

                    <div class="form-control">
                        <div class="flex items-center justify-between pb-1.5">
                            <label for="page-excerpt" class="label-text font-medium">{{ __('Tóm tắt ngắn') }}</label>
                            <span class="text-[11px] tabular-nums text-base-content/40" x-text="`${excerpt.length}/500`"></span>
                        </div>
                        <textarea name="excerpt" id="page-excerpt" rows="2" maxlength="500" x-model="excerpt"
                                  placeholder="{{ __('Không bắt buộc — dùng làm mô tả ngắn/fallback SEO') }}"
                                  class="textarea textarea-bordered w-full leading-relaxed @error('excerpt') textarea-error @enderror">{{ old('excerpt', $page?->excerpt) }}</textarea>
                        @error('excerpt')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control" x-show="template === 'default'">
                        <label class="label-text font-medium pb-1.5">{{ __('Nội dung (WYSIWYG)') }}</label>
                        <textarea name="content" class="jodit-editor" data-jodit-preset="full"
                                  data-jodit-context-type="page">{{ old('content', $page?->content) }}</textarea>
                        @error('content')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-start gap-2 rounded-lg bg-info/5 px-3 py-2.5 text-xs text-base-content/60" x-show="template !== 'default'" x-cloak>
                        <svg class="w-4 h-4 text-info shrink-0 mt-px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p>{{ __('Trang này dùng thiết kế riêng do lập trình viên dựng sẵn — nội dung WYSIWYG không áp dụng.') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5 sm:p-6 gap-0">
                <header class="flex items-start gap-3 mb-5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-primary/10 text-primary shrink-0">
                        <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold leading-tight">SEO</h2>
                        <p class="text-xs text-base-content/50 mt-0.5">{{ __('Cách trang hiển thị trên kết quả tìm kiếm') }}</p>
                    </div>
                </header>

                <div class="mb-5 rounded-box border border-base-200 bg-base-200/30 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-base-content/40 mb-2">{{ __('Xem trước trên Google') }}</p>
                    <p class="text-xs text-base-content/60 truncate font-mono" x-text="@js(url('/')) + '/' + (slug || '…')">{{ $publicUrl }}</p>
                    <p class="mt-0.5 text-lg leading-snug text-[#1a0dab] dark:text-[#8ab4f8] truncate" x-text="seoTitle || title || @js(__('Tiêu đề trang'))">{{ $page?->metaTitle() }}</p>
                    <p class="mt-1 text-sm text-base-content/70 line-clamp-2" x-text="seoDescription || excerpt || @js(__('Chưa có mô tả — Google sẽ tự trích một đoạn nội dung.'))">{{ $page?->metaDescription() }}</p>
                </div>

                <div class="space-y-4">
                    <div class="form-control">
                        <div class="flex items-center justify-between pb-1.5">
                            <label for="page-seo-title" class="label-text font-medium">{{ __('Tiêu đề SEO') }}</label>
                            <span class="text-[11px] tabular-nums" :class="seoTitle.length > 60 ? 'text-warning' : 'text-base-content/40'" x-text="`${seoTitle.length}/60`"></span>
                        </div>
                        <input type="text" name="seo_title" id="page-seo-title" x-model="seoTitle" value="{{ old('seo_title', $page?->seo_title) }}"
                               class="input input-bordered w-full @error('seo_title') input-error @enderror"
                               maxlength="200" placeholder="{{ __('Để trống sẽ dùng Tiêu đề trang') }}">
                        @error('seo_title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <div class="flex items-center justify-between pb-1.5">
                            <label for="page-seo-description" class="label-text font-medium">{{ __('Mô tả SEO') }}</label>
                            <span class="text-[11px] tabular-nums" :class="seoDescription.length > 160 ? 'text-warning' : 'text-base-content/40'" x-text="`${seoDescription.length}/160`"></span>
                        </div>
                        <textarea name="seo_description" id="page-seo-description" rows="2" maxlength="300" x-model="seoDescription"
                                  class="textarea textarea-bordered w-full leading-relaxed @error('seo_description') textarea-error @enderror"
                                  placeholder="{{ __('Để trống sẽ dùng Tóm tắt ngắn') }}">{{ old('seo_description', $page?->seo_description) }}</textarea>
                        @error('seo_description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-center justify-between gap-3 rounded-lg border border-base-200 px-3 py-2.5 cursor-pointer select-none">
                        <span>
                            <span class="block text-sm font-medium">{{ __('Không lập chỉ mục (noindex)') }}</span>
                            <span class="block text-xs text-base-content/50 mt-0.5">{{ __('Trang vẫn truy cập được nhưng không lên Google') }}</span>
                        </span>
                        <input type="hidden" name="seo_noindex" value="0">
                        <input type="checkbox" name="seo_noindex" value="1" class="toggle toggle-warning toggle-sm shrink-0"
                               {{ old('seo_noindex', $page?->seo_noindex) ? 'checked' : '' }}>
                    </label>
                </div>
            </div>
        </section>

        @if(\App\Support\I18n\PortalLocale::prefixed())
        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5 sm:p-6 gap-0">
                <header class="flex items-start gap-3 mb-5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-primary/10 text-primary shrink-0">
                        <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold leading-tight">{{ __('Bản dịch') }}</h2>
                        <p class="text-xs text-base-content/50 mt-0.5">{{ __('Nội dung cho khách xem trang ở ngôn ngữ khác') }}</p>
                    </div>
                </header>

                <div class="space-y-3">
                    <x-admin.translation-fields :model="$page" :fields="[
                        'title' => ['label' => __('Tiêu đề'), 'maxlength' => 200],
                        'slug' => ['label' => __('Đường dẫn (slug)'), 'hint' => __('Để trống sẽ tự sinh từ tiêu đề'), 'maxlength' => 160, 'prefix' => url('/').'/:locale/', 'mono' => true],
                        'excerpt' => ['label' => __('Tóm tắt ngắn'), 'type' => 'textarea', 'rows' => 2, 'maxlength' => 500],
                        'content' => ['label' => __('Nội dung (WYSIWYG)'), 'type' => 'editor', 'context' => 'page'],
                        'seo_title' => ['label' => __('Tiêu đề SEO'), 'maxlength' => 200, 'section' => 'SEO'],
                        'seo_description' => ['label' => __('Mô tả SEO'), 'type' => 'textarea', 'rows' => 2, 'maxlength' => 300],
                    ]" />
                </div>
            </div>
        </section>
        @endif
    </div>

    <aside class="xl:sticky xl:top-4 space-y-4">

        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 gap-0">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">{{ __('Xuất bản') }}</p>
                    <div class="flex items-center gap-1">
                        @if($page)
                        <span class="badge badge-sm badge-soft {{ $isPublished ? 'badge-success' : 'badge-warning' }}">{{ $page->status->label() }}</span>
                        @if($page->is_system)
                        <span class="badge badge-sm badge-ghost">{{ __('Hệ thống') }}</span>
                        @endif
                        @else
                        <span class="badge badge-sm badge-soft badge-warning">{{ __('Nháp') }}</span>
                        @endif
                    </div>
                </div>

                @if($page)
                <div class="mt-3 rounded-lg bg-base-200/60 px-3 py-2.5">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-base-content/40">{{ __('Đường dẫn công khai') }}</p>
                    <div class="mt-1 flex items-center gap-1">
                        <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="min-w-0 flex-1 truncate text-xs font-mono link link-hover link-primary" title="{{ $publicUrl }}">/{{ $page->slug }}</a>
                        <button type="button" class="btn btn-ghost btn-xs btn-square" @click="copy(@js($publicUrl))" :title="copied ? @js(__('Đã sao chép')) : @js(__('Sao chép'))">
                            <svg x-show="! copied" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            <svg x-show="copied" x-cloak class="w-3.5 h-3.5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        </button>
                    </div>
                </div>
                <p class="mt-3 text-xs text-base-content/50 leading-relaxed">{{ __('Đổi trạng thái bằng nút "Xuất bản"/"Gỡ xuất bản" ở trang danh sách để thời điểm xuất bản được ghi lại chính xác.') }}</p>
                @else
                <p class="mt-3 text-xs text-base-content/50 leading-relaxed">{{ __('Trang mới luôn ở trạng thái Nháp — xuất bản sau khi đã lưu.') }}</p>
                @endif

                <div class="flex gap-2 mt-4">
                    <a href="{{ route('backend.page.items.index') }}" class="btn btn-ghost btn-sm flex-1">{{ __('Hủy') }}</a>
                    <button type="submit" class="btn btn-primary btn-sm flex-[2] gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ $page ? __('Lưu thay đổi') : __('Tạo mới') }}
                    </button>
                </div>
            </div>
        </section>

        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 gap-0">
                <label for="page-template" class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">{{ __('Thiết kế (template)') }} <span class="text-error">*</span></label>
                <select name="template" id="page-template" x-model="template"
                        class="select select-bordered select-sm w-full @error('template') select-error @enderror">
                    @foreach($templateOptions as $value => $labelText)
                    <option value="{{ $value }}" @selected($selectedTemplate === $value)>{{ $labelText }}</option>
                    @endforeach
                </select>
                @error('template')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                <div class="mt-2 flex items-start gap-2 rounded-lg bg-warning/10 px-3 py-2 text-xs text-base-content/70"
                     x-show="isPublished && template !== originalTemplate" x-cloak>
                    <svg class="w-4 h-4 text-warning shrink-0 mt-px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p class="leading-relaxed">{{ __('Trang đang xuất bản — đổi thiết kế thay đổi cách hiển thị ngay khi lưu. Nội dung WYSIWYG vẫn được giữ trong DB, đổi lại thiết kế cũ sẽ hiển thị lại.') }}</p>
                </div>
            </div>
        </section>

        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 gap-0">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">{{ __('Ảnh đại diện') }}</p>
                    <span class="text-[11px] text-base-content/40">og:image</span>
                </div>
                @if($coverUrl)
                <img src="{{ $coverUrl }}" alt="" width="288" height="151"
                     class="w-full aspect-[1.91/1] h-auto rounded-lg border border-base-200 object-cover mb-3">
                <div id="cover-filepond" data-context-type="page" data-context-id="{{ $page->id }}"></div>
                @else
                <div id="cover-filepond"></div>
                <input type="hidden" name="cover_media_uuid" id="cover-media-uuid" value="{{ old('cover_media_uuid') }}">
                @endif
                <p class="mt-1 text-xs text-base-content/40">{{ __('Khuyến nghị 1200×630px') }}</p>
            </div>
        </section>

        @if($page)
        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 gap-0">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">{{ __('Thông tin') }}</p>
                <dl class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2 text-xs">
                    @if($page->published_at)
                    <dt class="text-base-content/50">{{ __('Xuất bản') }}</dt>
                    <dd class="text-right">{{ format_datetime($page->published_at) }}</dd>
                    @endif
                    <dt class="text-base-content/50">{{ __('Lượt xem') }}</dt>
                    <dd class="text-right font-medium tabular-nums">{{ number_format((int) $page->view_count) }}</dd>
                    <dt class="text-base-content/50">{{ __('Tạo lúc') }}</dt>
                    <dd class="text-right truncate">{{ format_datetime($page->created_at) }}@if($page->createdBy) · {{ $page->createdBy->name }}@endif</dd>
                    <dt class="text-base-content/50">{{ __('Cập nhật') }}</dt>
                    <dd class="text-right truncate">{{ format_datetime($page->updated_at) }}@if($page->updatedBy) · {{ $page->updatedBy->name }}@endif</dd>
                </dl>
            </div>
        </section>
        @endif

    </aside>

</div>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Auto-slug từ title — chỉ khi field slug còn trống (không ghi đè giá trị đã có/đã sửa tay).
    const titleEl = document.getElementById('page-title');
    const slugEl  = document.getElementById('page-slug');
    let slugTouched = slugEl && slugEl.value !== '';

    slugEl?.addEventListener('input', (e) => { if (e.isTrusted) slugTouched = true; });
    titleEl?.addEventListener('input', () => {
        if (slugTouched) return;
        slugEl.value = titleEl.value
            .toLowerCase()
            .normalize('NFD').replace(/[̀-ͯ]/g, '')
            .replace(/đ/g, 'd').replace(/Đ/g, 'D')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '');
        slugEl.dispatchEvent(new Event('input'));
    });

    if (window.initJoditAll) {
        initJoditAll('.jodit-editor');
    }

    const coverEl = document.getElementById('cover-filepond');
    if (window.initFilePondUpload && coverEl) {
        initFilePondUpload(coverEl, {
            collection: 'cover',
            bindTo: '#cover-media-uuid',
            contextType: coverEl.dataset.contextType,
            contextId: coverEl.dataset.contextId,
        });
    }
});
</script>
@endpush
@endonce
