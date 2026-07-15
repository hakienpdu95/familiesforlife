{{-- Dùng chung create/edit — cùng convention Modules/Banner/resources/views/admin/banners/_form.blade.php. --}}
<div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

    {{-- ── Card chính ──────────────────────────────────────────────── --}}
    <div class="space-y-5">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body space-y-4">

                <h2 class="card-title text-base mb-1">Thông tin sản phẩm</h2>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Tên sản phẩm <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $product?->name) }}"
                           class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                           placeholder="VD: Mè xửng Huế" maxlength="150" autofocus>
                    @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Danh mục <span class="text-error">*</span></span>
                        </label>
                        <select name="category_id" class="select select-bordered select-sm w-full @error('category_id') select-error @enderror">
                            <option value="">— Chọn danh mục —</option>
                            @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ (string) old('category_id', $product?->category_id) === (string) $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Hạng sao <span class="text-error">*</span></span>
                            <span class="label-text-alt text-xs text-base-content/40">OCOP quốc gia: 3–5 sao</span>
                        </label>
                        <select name="star_rating" class="select select-bordered select-sm w-full @error('star_rating') select-error @enderror">
                            <option value="">— Chọn —</option>
                            @foreach([3, 4, 5] as $star)
                            <option value="{{ $star }}" {{ (string) old('star_rating', $product?->star_rating) === (string) $star ? 'selected' : '' }}>{{ $star }} sao</option>
                            @endforeach
                        </select>
                        @error('star_rating')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Mô tả</span>
                    </label>
                    <textarea name="description" rows="4"
                              class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror">{{ old('description', $product?->description) }}</textarea>
                    @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">
                            {{ $product?->image_path ? 'Đổi ảnh sản phẩm' : 'Ảnh sản phẩm' }}
                        </span>
                    </label>
                    @if($product?->image_path)
                    <img src="{{ Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt=""
                         class="h-20 w-auto rounded border border-base-300 mb-2 object-cover">
                    @endif
                    <input type="file" name="image" accept="image/*"
                           class="file-input file-input-bordered file-input-sm w-full @error('image') file-input-error @enderror">
                    @error('image')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    @if($product?->image_path)
                    <p class="text-xs text-base-content/40 mt-1.5">Để trống nếu muốn giữ ảnh hiện tại.</p>
                    @endif
                </div>

                <div class="divider my-1"></div>

                <h2 class="card-title text-base mb-1">Nhà sản xuất</h2>

                <x-address-picker
                    instance-id="ocop-product"
                    :province-value="old('province_code', $product?->province_code)"
                    :ward-value="old('ward_code', $product?->ward_code)"
                />

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Tên nhà sản xuất</span>
                    </label>
                    <input type="text" name="producer_name" value="{{ old('producer_name', $product?->producer_name) }}"
                           class="input input-bordered input-sm w-full @error('producer_name') input-error @enderror"
                           maxlength="150">
                    @error('producer_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Địa chỉ nhà sản xuất</span>
                    </label>
                    <input type="text" name="producer_address" value="{{ old('producer_address', $product?->producer_address) }}"
                           class="input input-bordered input-sm w-full @error('producer_address') input-error @enderror"
                           maxlength="255">
                    @error('producer_address')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="divider my-1"></div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Link mua hàng</span>
                        <span class="label-text-alt text-xs text-base-content/40">Sàn TMĐT/liên hệ mua</span>
                    </label>
                    <input type="url" name="purchase_url" value="{{ old('purchase_url', $product?->purchase_url) }}"
                           class="input input-bordered input-sm w-full @error('purchase_url') input-error @enderror"
                           maxlength="500" placeholder="https://...">
                    @error('purchase_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

            </div>
        </div>
    </div>

    {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
    <div class="xl:sticky xl:top-4 space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 space-y-4">

                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Xuất bản</p>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Trạng thái</span>
                    </label>
                    <select name="status" class="select select-bordered select-sm w-full @error('status') select-error @enderror">
                        @foreach($statuses as $s)
                        <option value="{{ $s->value }}" {{ old('status', $product?->status?->value ?? 'draft') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                    @error('status')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                    <input type="hidden" name="is_featured" value="0">
                    <input type="checkbox" name="is_featured" value="1"
                           class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0" {{ old('is_featured', $product?->is_featured) ? 'checked' : '' }}>
                    <span class="text-sm font-medium group-hover:text-primary transition-colors">Sản phẩm nổi bật</span>
                </label>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Thứ tự hiển thị</span>
                    </label>
                    <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $product?->sort_order ?? 0) }}"
                           class="input input-bordered input-sm w-full @error('sort_order') input-error @enderror">
                    @error('sort_order')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="flex gap-2 pt-2">
                    <a href="{{ route('backend.ocop.products.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                    <button type="submit" class="btn btn-primary btn-sm flex-1">{{ $product ? 'Lưu thay đổi' : 'Tạo mới' }}</button>
                </div>

            </div>
        </div>
    </div>

</div>
