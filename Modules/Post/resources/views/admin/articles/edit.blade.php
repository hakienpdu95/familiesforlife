@extends('layouts.backend')
@section('title', 'Sửa bài viết')


@section('content')
<div x-data="{
    tab: 'noi_dung',
    tabFields: {
        noi_dung:   ['title', 'excerpt', 'blocks'],
        seo:        ['seo_title', 'seo_description'],
        phan_loai:  ['category_ids', 'is_primary_category_id', 'tags'],
    },
    errs: {{ Js::from($errors->keys()) }},
    errCount(t) {
        return this.tabFields[t].filter(f => this.errs.some(e => e === f || e.startsWith(f + '.'))).length;
    },
    init() {
        const order = ['noi_dung', 'seo', 'phan_loai'];
        for (const t of order) {
            if (this.errCount(t) > 0) { this.tab = t; break; }
        }
    }
}">

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content flex items-center gap-2">
            Sửa bài viết
            <span class="badge badge-sm {{ $article->status->badgeClass() }}">{{ $article->status->label() }}</span>
        </h1>
        <p class="text-sm text-base-content/50 mt-0.5">{{ $article->title }}</p>
    </div>
    <a href="{{ route('backend.post.articles.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

{{-- Error banner --}}
@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('backend.post.articles.update', $article) }}" novalidate data-article-form>
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Card chính với tab ───────────────────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">

            {{-- Tab navigation --}}
            <div class="border-b border-base-200 px-6">
                <nav class="flex -mb-px" role="tablist" aria-label="Form sections">

                    <button type="button" role="tab" :aria-selected="tab === 'noi_dung'"
                            @click="tab = 'noi_dung'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'noi_dung'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Nội dung
                        <span x-show="errCount('noi_dung') > 0" x-text="errCount('noi_dung')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'seo'"
                            @click="tab = 'seo'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'seo'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        SEO
                        <span x-show="errCount('seo') > 0" x-text="errCount('seo')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'phan_loai'"
                            @click="tab = 'phan_loai'"
                            class="flex items-center gap-1.5 px-1 py-4 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'phan_loai'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Phân loại
                        <span x-show="errCount('phan_loai') > 0" x-text="errCount('phan_loai')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                </nav>
            </div>

            {{-- Tab panels --}}
            <div class="p-6">

                {{-- Panel: Nội dung --}}
                <div x-show="tab === 'noi_dung'" data-tab-label="Nội dung" class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tiêu đề <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="title" value="{{ old('title', $article->title) }}"
                               data-req="Vui lòng nhập tiêu đề"
                               data-val-maxlength="300"
                               class="input input-bordered input-sm w-full @error('title') input-error @enderror"
                               maxlength="300">
                        @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tóm tắt</span>
                            <span class="label-text-alt text-xs text-base-content/40">Hiện ở trang danh sách</span>
                        </label>
                        <textarea name="excerpt" rows="2"
                                  data-val-maxlength="500"
                                  class="textarea textarea-bordered textarea-sm w-full @error('excerpt') textarea-error @enderror"
                                  maxlength="500">{{ old('excerpt', $article->excerpt) }}</textarea>
                        @error('excerpt')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <div class="flex items-center justify-between mb-1">
                            <label class="label py-0 !p-0">
                                <span class="label-text font-medium">Nội dung</span>
                            </label>
                            <span class="text-xs text-base-content/40">Tối đa 3 khối sản phẩm/bài</span>
                        </div>

                        @error('blocks')
                        <div class="alert alert-error text-xs mb-2">{{ $message }}</div>
                        @enderror

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

                    <div class="flex justify-end pt-2">
                        <button type="button" @click="tab = 'seo'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: SEO
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- Panel: SEO --}}
                <div x-show="tab === 'seo'" data-tab-label="SEO" class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">SEO Title</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <input type="text" name="seo_title" value="{{ old('seo_title', $article->seo_title) }}"
                               data-val-maxlength="200"
                               class="input input-bordered input-sm w-full @error('seo_title') input-error @enderror"
                               placeholder="Để trống sẽ dùng tiêu đề bài viết" maxlength="200">
                        @error('seo_title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">SEO Description</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <textarea name="seo_description" rows="3"
                                  data-val-maxlength="300"
                                  class="textarea textarea-bordered textarea-sm w-full @error('seo_description') textarea-error @enderror"
                                  placeholder="Mô tả hiển thị trên kết quả tìm kiếm..." maxlength="300">{{ old('seo_description', $article->seo_description) }}</textarea>
                        <p class="mt-1 text-xs text-base-content/40">Nên trong khoảng 150–160 ký tự để hiển thị đủ trên Google</p>
                        @error('seo_description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'noi_dung'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Nội dung
                        </button>
                        <button type="button" @click="tab = 'phan_loai'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Phân loại
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- Panel: Phân loại --}}
                <div x-show="tab === 'phan_loai'" data-tab-label="Phân loại" class="space-y-5">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Danh mục</span>
                            <span class="label-text-alt text-xs text-base-content/40">Bấm ★ để đặt danh mục chính</span>
                        </label>

                        @php
                            $selectedCategoryIds = old('category_ids', $article->categories->pluck('id')->all());
                            $primaryCategoryId   = old('is_primary_category_id', $article->categories->firstWhere('pivot.is_primary', true)?->id);
                        @endphp

                        <div class="max-h-72 overflow-y-auto flex flex-col gap-1 border border-base-200 rounded-lg p-3">
                            @forelse($categories as $c)
                            <label class="flex items-center gap-2.5 cursor-pointer text-sm py-1 px-1 rounded hover:bg-base-200/60 transition-colors">
                                <input type="checkbox" name="category_ids[]" value="{{ $c->id }}"
                                       class="checkbox checkbox-sm shrink-0"
                                       {{ in_array($c->id, $selectedCategoryIds) ? 'checked' : '' }}>
                                <span class="flex-1">{{ $c->parent ? $c->parent->name . ' › ' : '' }}{{ $c->name }}</span>
                                <label class="flex items-center gap-1 cursor-pointer shrink-0" title="Đặt làm danh mục chính">
                                    <input type="radio" name="is_primary_category_id" value="{{ $c->id }}"
                                           class="radio radio-xs radio-warning"
                                           {{ (string) $primaryCategoryId === (string) $c->id ? 'checked' : '' }}>
                                </label>
                            </label>
                            @empty
                            <p class="text-xs text-base-content/30 py-2">
                                Chưa có danh mục nào — <a href="{{ route('backend.post.categories.create') }}" class="link">tạo danh mục</a>.
                            </p>
                            @endforelse
                        </div>
                        @error('category_ids')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tags</span>
                            <span class="label-text-alt text-xs text-base-content/40">Gõ rồi Enter — tag chưa có sẽ tự tạo</span>
                        </label>
                        <input type="text" name="tags"
                               value="{{ old('tags', $article->tags->pluck('name')->implode(', ')) }}"
                               class="input input-bordered input-sm w-full @error('tags') input-error @enderror"
                               placeholder="ngủ, sơ sinh, mẹo hay">
                        @error('tags')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'seo'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            SEO
                        </button>
                        <span class="text-xs text-base-content/40">Nhấn <strong>Lưu lại</strong> ở bên phải khi xong</span>
                    </div>

                </div>

            </div>{{-- /tab panels --}}
        </div>{{-- /card chính --}}

        {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">

            {{-- Xuất bản --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>

                    <div class="form-control mb-3">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">Định dạng nội dung</span>
                        </label>
                        <select id="ts-format" name="format"
                                class="select select-bordered select-sm w-full ts-init @error('format') select-error @enderror"
                                data-ts-placeholder="— Chọn định dạng —">
                            @foreach(\Modules\Post\Enums\ArticleFormat::cases() as $f)
                            <option value="{{ $f->value }}" {{ old('format', $article->format->value) === $f->value ? 'selected' : '' }}>{{ $f->label() }}</option>
                            @endforeach
                        </select>
                        @error('format')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control mb-4">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">Ảnh đại diện (URL)</span>
                        </label>
                        <input type="text" name="cover_image_url" value="{{ old('cover_image_url', $article->cover_image_url) }}"
                               class="input input-bordered input-sm w-full @error('cover_image_url') input-error @enderror"
                               placeholder="https://...">
                        @error('cover_image_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-start gap-2.5 cursor-pointer select-none group mb-4">
                        <input type="hidden" name="is_featured" value="0">
                        <input type="checkbox" name="is_featured" value="1"
                               class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                               {{ old('is_featured', $article->is_featured) ? 'checked' : '' }}>
                        <div>
                            <span class="text-sm font-medium group-hover:text-primary transition-colors">Bài viết nổi bật</span>
                            <p class="text-xs text-base-content/50 mt-0.5">Ưu tiên hiển thị ở trang chủ</p>
                        </div>
                    </label>

                    <div class="flex justify-between text-xs text-base-content/40 mb-4 px-0.5">
                        <span>Tạo {{ $article->created_at->format('d/m/Y') }}</span>
                        <span>Sửa {{ $article->updated_at->diffForHumans() }}</span>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.post.articles.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Lưu lại
                        </button>
                    </div>

                    <p class="text-center text-xs text-base-content/30 mt-2.5">
                        <span class="text-error">*</span> là trường bắt buộc
                    </p>

                </div>
            </div>

            {{-- Quy trình duyệt bài — form riêng, tách biệt form chính --}}
            @canany(['submitForReview', 'publish', 'schedule', 'archive'], $article)
            <div class="card bg-base-100 shadow-sm border border-base-200" x-data="{ showSchedule: false }">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Quy trình duyệt bài</p>

                    <div class="flex flex-col gap-2">

                        @can('submitForReview', $article)
                        @if($article->status === \Modules\Post\Enums\ArticleStatus::Draft)
                        <form method="POST" action="{{ route('backend.post.articles.submit', $article) }}">
                            @csrf
                            <button class="btn btn-outline btn-sm w-full">Gửi duyệt</button>
                        </form>
                        @endif
                        @endcan

                        @can('publish', $article)
                        @if($article->status !== \Modules\Post\Enums\ArticleStatus::Published)
                        <form method="POST" action="{{ route('backend.post.articles.publish', $article) }}">
                            @csrf
                            <button class="btn btn-success btn-sm w-full">Xuất bản ngay</button>
                        </form>
                        @endif

                        <button type="button" class="btn btn-info btn-sm w-full" @click="showSchedule = !showSchedule">
                            Lên lịch xuất bản
                        </button>

                        <div x-show="showSchedule" x-cloak x-transition class="pt-1">
                            <form method="POST" action="{{ route('backend.post.articles.schedule', $article) }}" class="flex flex-col gap-2">
                                @csrf
                                <div class="form-control">
                                    <label class="label py-0 pb-1">
                                        <span class="label-text text-xs font-medium">Xuất bản lúc</span>
                                    </label>
                                    <input type="datetime-local" name="published_at"
                                           class="input input-bordered input-sm w-full" required>
                                </div>
                                <button class="btn btn-primary btn-sm w-full">Xác nhận lên lịch</button>
                            </form>
                        </div>

                        @if($article->status !== \Modules\Post\Enums\ArticleStatus::Archived)
                        <form method="POST" action="{{ route('backend.post.articles.archive', $article) }}"
                              onsubmit="return confirm('Lưu trữ bài viết này?')">
                            @csrf
                            <button class="btn btn-ghost btn-sm w-full">Lưu trữ</button>
                        </form>
                        @endif
                        @endcan

                    </div>

                </div>
            </div>
            @endcanany

        </div>{{-- /sidebar --}}

    </div>{{-- /grid --}}

</form>
</div>
@endsection

@push('styles')
    @vite(['Modules/Post/resources/assets/sass/post.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/jodit.js',
        'Modules/Post/resources/assets/js/post.js',
    ], 'build/backend')
@endpush
