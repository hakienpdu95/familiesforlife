@csrf
@if(isset($article)) @method('PUT') @endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ── Nội dung ─────────────────────────────────────────────────── --}}
    <div class="lg:col-span-2 flex flex-col gap-4">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5 space-y-4">
                <div class="form-control">
                    <label class="label label-text text-xs">Tiêu đề <span class="text-error">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $article->title ?? '') }}"
                           placeholder="VD: 5 mẹo giúp bé ngủ ngon" class="input input-bordered input-sm" required>
                    @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label label-text text-xs">Tóm tắt</label>
                    <textarea name="excerpt" rows="2" maxlength="500" class="textarea textarea-bordered textarea-sm"
                              placeholder="Đoạn giới thiệu ngắn hiển thị ở trang danh sách...">{{ old('excerpt', $article->excerpt ?? '') }}</textarea>
                </div>

                <div class="form-control">
                    <div class="flex items-center justify-between mb-1">
                        <label class="label label-text text-xs !p-0">Nội dung</label>
                        <span class="text-xs text-base-content/40">Tối đa 3 khối sản phẩm/bài</span>
                    </div>

                    @if($errors->has('blocks'))
                    <div class="alert alert-error text-xs mb-2">
                        <ul class="list-disc pl-4">
                            @foreach($errors->get('blocks') as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

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
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5 space-y-4">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">SEO</p>
                <div class="form-control">
                    <label class="label label-text text-xs">SEO Title</label>
                    <input type="text" name="seo_title" value="{{ old('seo_title', $article->seo_title ?? '') }}"
                           class="input input-bordered input-sm">
                </div>
                <div class="form-control">
                    <label class="label label-text text-xs">SEO Description</label>
                    <textarea name="seo_description" rows="2" maxlength="300"
                              class="textarea textarea-bordered textarea-sm">{{ old('seo_description', $article->seo_description ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
    <aside class="space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 space-y-4">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Xuất bản</p>

                <div class="form-control">
                    <label class="label label-text text-xs">Định dạng nội dung</label>
                    <select name="format" class="select select-bordered select-sm">
                        @foreach(\Modules\Post\Enums\ArticleFormat::cases() as $f)
                        <option value="{{ $f->value }}" @selected(old('format', $article->format->value ?? 'article') === $f->value)>{{ $f->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control">
                    <label class="label label-text text-xs">Ảnh đại diện (URL)</label>
                    <input type="text" name="cover_image_url" value="{{ old('cover_image_url', $article->cover_image_url ?? '') }}"
                           placeholder="https://..." class="input input-bordered input-sm">
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="is_featured" value="0">
                    <input type="checkbox" name="is_featured" value="1" class="checkbox checkbox-sm"
                           @checked(old('is_featured', $article->is_featured ?? false))>
                    <span class="text-sm">Bài viết nổi bật</span>
                </label>

                <div class="flex flex-col gap-2 pt-2 border-t border-base-200">
                    <button type="submit" class="btn btn-primary btn-sm w-full">Lưu</button>
                    <a href="{{ route('backend.post.articles.index') }}" class="btn btn-ghost btn-sm w-full">Hủy</a>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 space-y-3">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Danh mục</p>
                <p class="text-xs text-base-content/40 -mt-2">Tick chọn danh mục, bấm ★ để đặt làm danh mục chính (dùng cho breadcrumb).</p>

                @php
                    $selectedCategoryIds = old('category_ids', isset($article) ? $article->categories->pluck('id')->all() : []);
                    $primaryCategoryId   = old('is_primary_category_id', isset($article) ? $article->categories->firstWhere('pivot.is_primary', true)?->id : null);
                @endphp

                <div class="max-h-56 overflow-y-auto flex flex-col gap-1.5">
                    @forelse($categories as $c)
                    <label class="flex items-center gap-2 cursor-pointer text-sm py-0.5">
                        <input type="checkbox" name="category_ids[]" value="{{ $c->id }}" class="checkbox checkbox-xs"
                               @checked(in_array($c->id, $selectedCategoryIds))>
                        <span>{{ $c->name }}</span>
                        <label class="ml-auto flex items-center gap-1 cursor-pointer" title="Đặt làm danh mục chính">
                            <input type="radio" name="is_primary_category_id" value="{{ $c->id }}" class="radio radio-xs"
                                   @checked((string) $primaryCategoryId === (string) $c->id)>
                        </label>
                    </label>
                    @empty
                    <p class="text-xs text-base-content/30">Chưa có danh mục nào — <a href="{{ route('backend.post.categories.create') }}" class="link">tạo danh mục</a>.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 space-y-3">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Tags</p>
                <input type="text" name="tags"
                       value="{{ old('tags', isset($article) ? $article->tags->pluck('name')->implode(', ') : '') }}"
                       placeholder="ngủ, sơ sinh, mẹo hay" class="input input-bordered input-sm">
                <p class="text-xs text-base-content/40">Phân tách bằng dấu phẩy — tag chưa có sẽ tự tạo.</p>
            </div>
        </div>
    </aside>
</div>
