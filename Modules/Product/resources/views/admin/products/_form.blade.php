@csrf
@if(isset($product)) @method('PUT') @endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ── Thông tin cơ bản ─────────────────────────────────────────── --}}
    <div class="lg:col-span-2 flex flex-col gap-4">

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5 space-y-4">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Thông tin cơ bản</p>

                <div class="form-control">
                    <label class="label label-text text-xs">Tên sản phẩm <span class="text-error">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}"
                           placeholder="VD: Bộ đồ sơ sinh cotton hữu cơ" class="input input-bordered input-sm" required>
                    @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label label-text text-xs">Danh mục</label>
                        <select name="category_id" class="select select-bordered select-sm">
                            <option value="">— Chưa phân loại —</option>
                            @foreach($categories as $c)
                            <option value="{{ $c->id }}" @selected(old('category_id', $product->category_id ?? '') == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label label-text text-xs">SKU</label>
                        <input type="text" name="sku" value="{{ old('sku', $product->sku ?? '') }}"
                               class="input input-bordered input-sm font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label label-text text-xs">Loại <span class="text-error">*</span></label>
                        <select name="type" class="select select-bordered select-sm" required>
                            @foreach(\Modules\Product\Enums\ProductType::cases() as $t)
                            <option value="{{ $t->value }}" @selected(old('type', $product->type->value ?? 'physical') === $t->value)>{{ $t->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label label-text text-xs">Trạng thái <span class="text-error">*</span></label>
                        <select name="status" class="select select-bordered select-sm" required>
                            @foreach(\Modules\Product\Enums\ProductStatus::cases() as $s)
                            <option value="{{ $s->value }}" @selected(old('status', $product->status->value ?? 'active') === $s->value)>{{ $s->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label label-text text-xs">Mô tả ngắn</label>
                    <input type="text" name="short_description" maxlength="300"
                           value="{{ old('short_description', $product->short_description ?? '') }}"
                           placeholder="Hiển thị trong dialog chọn sản phẩm + box gọn"
                           class="input input-bordered input-sm">
                </div>

                <div class="form-control">
                    <label class="label label-text text-xs">Mô tả đầy đủ</label>
                    <textarea name="description" rows="4" class="textarea textarea-bordered textarea-sm">{{ old('description', $product->description ?? '') }}</textarea>
                </div>

                <div class="form-control">
                    <label class="label label-text text-xs">Ảnh đại diện (URL)</label>
                    <input type="text" name="cover_image_url" value="{{ old('cover_image_url', $product->cover_image_url ?? '') }}"
                           placeholder="https://..." class="input input-bordered input-sm">
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5 space-y-4">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Giá</p>

                <div class="grid grid-cols-3 gap-4">
                    <div class="form-control">
                        <label class="label label-text text-xs">Giá</label>
                        <input type="number" step="0.01" min="0" name="price"
                               value="{{ old('price', $product->price ?? '') }}" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label label-text text-xs">Tiền tệ</label>
                        <input type="text" name="currency" maxlength="3"
                               value="{{ old('currency', $product->currency ?? 'VND') }}" class="input input-bordered input-sm">
                    </div>
                    <div class="form-control">
                        <label class="label label-text text-xs">Nhãn giá tuỳ chỉnh</label>
                        <input type="text" name="price_label" value="{{ old('price_label', $product->price_label ?? '') }}"
                               placeholder="VD: Liên hệ báo giá" class="input input-bordered input-sm">
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5 space-y-4">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Link affiliate</p>
                <p class="text-xs text-base-content/50 -mt-2">
                    Cấu hình 1 lần ở đây — bài viết chỉ chọn dùng link nào, không cần nhập lại URL cho từng vị trí chèn.
                </p>

                <div class="form-control">
                    <label class="label label-text text-xs">Shopee</label>
                    <input type="url" name="shopee_url" value="{{ old('shopee_url', $product->shopee_url ?? '') }}"
                           placeholder="https://shopee.vn/..." class="input input-bordered input-sm">
                    @error('shopee_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-control">
                    <label class="label label-text text-xs">TikTok Shop</label>
                    <input type="url" name="tiktok_url" value="{{ old('tiktok_url', $product->tiktok_url ?? '') }}"
                           placeholder="https://vt.tiktok.com/..." class="input input-bordered input-sm">
                    @error('tiktok_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-control">
                    <label class="label label-text text-xs">Link sản phẩm tại NCC</label>
                    <input type="url" name="supplier_url" value="{{ old('supplier_url', $product->supplier_url ?? '') }}"
                           placeholder="https://..." class="input input-bordered input-sm">
                    @error('supplier_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-control">
                    <label class="label label-text text-xs">Website nhà cung cấp</label>
                    <input type="url" name="supplier_homepage_url" value="{{ old('supplier_homepage_url', $product->supplier_homepage_url ?? '') }}"
                           placeholder="https://..." class="input input-bordered input-sm">
                    @error('supplier_homepage_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
    <aside class="space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 space-y-4">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Tuỳ chọn</p>

                <div class="form-control">
                    <label class="label label-text text-xs">Thứ tự hiển thị</label>
                    <input type="number" name="sort_order" min="0"
                           value="{{ old('sort_order', $product->sort_order ?? 0) }}" class="input input-bordered input-sm">
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="hidden" name="is_featured" value="0">
                    <input type="checkbox" name="is_featured" value="1" class="checkbox checkbox-sm"
                           @checked(old('is_featured', $product->is_featured ?? false))>
                    <span class="text-sm">Sản phẩm nổi bật</span>
                </label>

                <div class="flex flex-col gap-2 pt-2 border-t border-base-200">
                    <button type="submit" class="btn btn-primary btn-sm w-full">Lưu</button>
                    <a href="{{ route('backend.products.index') }}" class="btn btn-ghost btn-sm w-full">Hủy</a>
                </div>
            </div>
        </div>

        @if(isset($product) && $product->used_in_articles_count > 0)
        <div class="alert alert-info text-xs">
            Sản phẩm đang được <strong>{{ $product->used_in_articles_count }}</strong> bài viết tham chiếu — không thể xoá cứng, chỉ có thể đổi trạng thái.
        </div>
        @endif
    </aside>
</div>
