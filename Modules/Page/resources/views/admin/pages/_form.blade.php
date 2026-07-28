{{-- Dùng chung create/edit — spec/Page_Static_Pages_Technical_Specification.md §4.2. --}}
@php
    $selectedTemplate = old('template', $page?->template ?? 'default');
    $isPublished = $page && $page->status === \Modules\Page\Enums\PageStatus::Published;
@endphp

<div class="grid grid-cols-1 xl:grid-cols-[1fr_320px] gap-6 items-start"
     x-data="{
        template: '{{ $selectedTemplate }}',
        slug: '{{ old('slug', $page?->slug) }}',
        originalTemplate: '{{ $page?->template ?? 'default' }}',
        originalSlug: '{{ $page?->slug }}',
        isPublished: {{ $isPublished ? 'true' : 'false' }},
     }">

    {{-- ── Card chính ──────────────────────────────────────────────── --}}
    <div class="space-y-5">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Nội dung trang
                </h2>

                <div class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tiêu đề <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="title" id="page-title" value="{{ old('title', $page?->title) }}"
                               class="input input-bordered input-sm w-full @error('title') input-error @enderror"
                               maxlength="200" placeholder="Vd: Giới thiệu">
                        @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Đường dẫn (slug)</span>
                            <span class="label-text-alt text-xs text-base-content/40">Để trống sẽ tự sinh từ tiêu đề</span>
                        </label>
                        <div class="flex items-center gap-1.5">
                            <span class="text-sm text-base-content/40">{{ url('/') }}/</span>
                            <input type="text" name="slug" id="page-slug" x-model="slug"
                                   value="{{ old('slug', $page?->slug) }}"
                                   class="input input-bordered input-sm w-full font-mono @error('slug') input-error @enderror"
                                   maxlength="160" placeholder="gioi-thieu">
                        </div>
                        @error('slug')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        <p class="mt-1.5 text-xs text-warning"
                           x-show="isPublished && slug !== originalSlug && slug !== ''" x-cloak>
                            ⚠ Trang đang "Đã xuất bản" — đổi đường dẫn sẽ khiến các liên kết cũ (menu, mạng xã hội, backlink) trỏ tới URL cũ (<span class="font-mono" x-text="'/'+originalSlug"></span>) bị lỗi 404.
                        </p>
                        @if($page)
                        <div class="mt-2 flex items-center gap-2">
                            <input type="text" readonly value="{{ url('/'.$page->slug) }}"
                                   id="page-public-url" class="input input-bordered input-xs w-full font-mono text-xs bg-base-200/50">
                            <button type="button" class="btn btn-ghost btn-xs shrink-0"
                                    onclick="navigator.clipboard.writeText(document.getElementById('page-public-url').value)">
                                Sao chép
                            </button>
                        </div>
                        @endif
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Thiết kế (template) <span class="text-error">*</span></span>
                        </label>
                        <select name="template" x-model="template"
                                class="select select-bordered select-sm w-full @error('template') select-error @enderror">
                            @foreach($templateOptions as $value => $labelText)
                            <option value="{{ $value }}">{{ $labelText }}</option>
                            @endforeach
                        </select>
                        @error('template')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        <p class="text-xs text-base-content/40 mt-1.5" x-show="template !== 'default'" x-cloak>
                            Trang này dùng thiết kế riêng do lập trình viên dựng sẵn — nội dung ở khối "Nội dung (WYSIWYG)" bên dưới có thể không hiển thị trực tiếp.
                        </p>
                        <p class="mt-1.5 text-xs text-warning"
                           x-show="isPublished && template !== originalTemplate" x-cloak>
                            ⚠ Trang đang "Đã xuất bản" — đổi thiết kế sẽ thay đổi cách hiển thị ngay khi lưu. Nếu đổi từ "Mặc định" sang 1 thiết kế riêng, nội dung ở khối WYSIWYG bên dưới sẽ không còn hiển thị (trừ khi template đó có chủ động dùng lại content/excerpt). Nội dung cũ vẫn còn nguyên trong DB, chỉ ngừng hiển thị — đổi lại template như cũ sẽ thấy lại.
                        </p>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tóm tắt ngắn</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc — dùng làm mô tả ngắn/fallback SEO</span>
                        </label>
                        <textarea name="excerpt" rows="2" maxlength="500"
                                  class="textarea textarea-bordered textarea-sm w-full @error('excerpt') textarea-error @enderror">{{ old('excerpt', $page?->excerpt) }}</textarea>
                        @error('excerpt')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Ảnh đại diện</span>
                            <span class="label-text-alt text-xs text-base-content/40">og:image — không bắt buộc</span>
                        </label>
                        @if($page?->getFirstMediaUrl('cover'))
                        <img src="{{ $page->getFirstMediaUrl('cover') }}" alt=""
                             class="h-16 w-auto rounded border border-base-300 mb-2 object-cover">
                        <div id="cover-filepond" data-context-type="page" data-context-id="{{ $page->id }}"></div>
                        @else
                        <div id="cover-filepond"></div>
                        <input type="hidden" name="cover_media_uuid" id="cover-media-uuid" value="{{ old('cover_media_uuid') }}">
                        @endif
                    </div>

                    <div class="divider my-1"></div>

                    <div class="form-control" x-show="template === 'default'">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Nội dung (WYSIWYG)</span>
                        </label>
                        <textarea name="content" class="jodit-editor" data-jodit-preset="full"
                                  data-jodit-context-type="page">{{ old('content', $page?->content) }}</textarea>
                        @error('content')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">
                <h2 class="card-title text-base mb-4">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
                    </svg>
                    SEO
                </h2>

                <div class="space-y-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">SEO title</span></label>
                        <input type="text" name="seo_title" value="{{ old('seo_title', $page?->seo_title) }}"
                               class="input input-bordered input-sm w-full @error('seo_title') input-error @enderror"
                               maxlength="200" placeholder="Để trống sẽ dùng Tiêu đề trang">
                        @error('seo_title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">SEO description</span></label>
                        <textarea name="seo_description" rows="2" maxlength="300"
                                  class="textarea textarea-bordered textarea-sm w-full @error('seo_description') textarea-error @enderror"
                                  placeholder="Để trống sẽ dùng Tóm tắt ngắn">{{ old('seo_description', $page?->seo_description) }}</textarea>
                        @error('seo_description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                        <input type="hidden" name="seo_noindex" value="0">
                        <input type="checkbox" name="seo_noindex" value="1"
                               class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                               {{ old('seo_noindex', $page?->seo_noindex) ? 'checked' : '' }}>
                        <div>
                            <span class="text-sm font-medium group-hover:text-primary transition-colors">Không lập chỉ mục (noindex)</span>
                            <p class="text-xs text-base-content/50 mt-0.5">Trang vẫn truy cập được nhưng không lên Google</p>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
    <div class="xl:sticky xl:top-4 space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-3">

                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
                    Trạng thái
                </p>

                @if($page)
                <div class="mb-3">
                    @if($page->status === \Modules\Page\Enums\PageStatus::Published)
                    <span class="badge badge-success badge-sm">{{ $page->status->label() }}</span>
                    @else
                    <span class="badge badge-ghost badge-sm">{{ $page->status->label() }}</span>
                    @endif
                    @if($page->is_system)
                    <span class="badge badge-ghost badge-sm ml-1">Hệ thống</span>
                    @endif
                </div>
                <p class="text-xs text-base-content/40 mb-3">
                    Lưu thay đổi trước, sau đó dùng nút "Xuất bản"/"Gỡ xuất bản" ở trang danh sách để đổi trạng thái — đảm bảo thời điểm xuất bản được ghi lại chính xác.
                </p>
                @else
                <p class="text-xs text-base-content/40 mb-3">Trang mới luôn ở trạng thái Nháp — xuất bản sau khi đã lưu.</p>
                @endif

                <div class="flex gap-2">
                    <a href="{{ route('backend.page.items.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                    <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $page ? 'Lưu thay đổi' : 'Tạo mới' }}
                    </button>
                </div>

                <p class="text-center text-xs text-base-content/30 mt-2.5">
                    <span class="text-error">*</span> là trường bắt buộc
                </p>

            </div>
        </div>
    </div>

</div>

@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Auto-slug từ title — chỉ khi field slug còn trống (không ghi đè giá trị đã có/đã sửa tay).
    const titleEl = document.getElementById('page-title');
    const slugEl  = document.getElementById('page-slug');
    let slugTouched = slugEl && slugEl.value !== '';

    slugEl?.addEventListener('input', () => { slugTouched = true; });
    titleEl?.addEventListener('input', () => {
        if (slugTouched) return;
        slugEl.value = titleEl.value
            .toLowerCase()
            .normalize('NFD').replace(/[̀-ͯ]/g, '')
            .replace(/đ/g, 'd').replace(/Đ/g, 'D')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '');
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
