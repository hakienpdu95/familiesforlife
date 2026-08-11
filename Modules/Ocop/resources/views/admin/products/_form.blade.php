{{-- Dùng chung create/edit — cùng convention Modules/Banner/resources/views/admin/banners/_form.blade.php.
     Tab-based form (docs/form-ui-spec.md §10) — 13 trường / 2 nhóm rõ ràng (cơ bản + nhà sản xuất). --}}
<div class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-6 items-start"
     x-data="{
        tab: 'basic',
        tabFields: {
            basic:    ['name', 'category_id', 'star_rating', 'description', 'image'],
            producer: ['province_code', 'ward_code', 'producer_name', 'producer_address', 'purchase_url'],
        },
        errs: {{ Js::from($errors->keys()) }},
        errCount(t) {
            return this.tabFields[t].filter(f => this.errs.includes(f)).length;
        },
        init() {
            const order = ['basic', 'producer'];
            for (const t of order) {
                if (this.errCount(t) > 0) { this.tab = t; break; }
            }
        }
     }">

    {{-- ── Card chính với tab ──────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">

        {{-- Tab navigation --}}
        <div class="border-b border-base-200 px-3">
            <nav class="flex -mb-px" role="tablist" aria-label="Form sections">

                <button type="button" role="tab" :aria-selected="tab === 'basic'"
                        @click="tab = 'basic'"
                        class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                        :class="tab === 'basic'
                            ? 'border-primary text-primary'
                            : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                    Thông tin sản phẩm
                    <span x-show="errCount('basic') > 0" x-text="errCount('basic')"
                          class="badge badge-error badge-xs"></span>
                </button>

                <button type="button" role="tab" :aria-selected="tab === 'producer'"
                        @click="tab = 'producer'"
                        class="flex items-center gap-1.5 px-1 py-4 text-sm font-medium border-b-2 transition-colors"
                        :class="tab === 'producer'
                            ? 'border-primary text-primary'
                            : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                    Nhà sản xuất
                    <span x-show="errCount('producer') > 0" x-text="errCount('producer')"
                          class="badge badge-error badge-xs"></span>
                </button>

            </nav>
        </div>

        {{-- Tab panels --}}
        <div class="p-3">

            {{-- Panel: Thông tin sản phẩm --}}
            <div x-show="tab === 'basic'" data-tab-label="Thông tin sản phẩm" class="space-y-4">

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Tên sản phẩm <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $product?->name) }}"
                           data-req="Vui lòng nhập tên sản phẩm"
                           data-val-maxlength="150"
                           class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                           placeholder="VD: Mè xửng Huế" maxlength="150" autofocus>
                    @error('name')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Danh mục <span class="text-error">*</span></span>
                        </label>
                        {{-- spec/danhmuc.html — danh mục OCOP chính thức 3 cấp (Nhóm lớn → Nhóm →
                             Phân nhóm), $categoryTree đã phẳng hóa kèm depth (OcopCategory::flatTree()),
                             thụt lề bằng ideographic space (không bị trình duyệt gộp khoảng trắng
                             như space thường). Nhóm còn "con" (không phải cấp sâu nhất của nhánh)
                             bị disable — sản phẩm chỉ được gán vào đúng phân nhóm cụ thể nhất. --}}
                        <select id="ts-category_id" name="category_id"
                                data-req="Vui lòng chọn danh mục"
                                class="select select-bordered select-sm w-full ts-init @error('category_id') select-error @enderror"
                                data-ts-placeholder="— Chọn danh mục —">
                            <option value="">— Chọn danh mục —</option>
                            @foreach($categoryTree as $row)
                            @php($cat = $row['category'])
                            <option value="{{ $cat->id }}"
                                    {{ (string) old('category_id', $product?->category_id) === (string) $cat->id ? 'selected' : '' }}
                                    {{ $cat->children->isNotEmpty() ? 'disabled' : '' }}>
                                {{ str_repeat('　', $row['depth']) }}{{ $row['depth'] > 0 ? '– ' : '' }}{{ $cat->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('category_id')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Hạng sao <span class="text-error">*</span></span>
                            <span class="label-text-alt text-xs text-base-content/40">OCOP quốc gia: 3–5 sao</span>
                        </label>
                        <select id="ts-star_rating" name="star_rating"
                                data-req="Vui lòng chọn hạng sao"
                                class="select select-bordered select-sm w-full ts-init @error('star_rating') select-error @enderror"
                                data-ts-placeholder="— Chọn —">
                            <option value="">— Chọn —</option>
                            @foreach([3, 4, 5] as $star)
                            <option value="{{ $star }}" {{ (string) old('star_rating', $product?->star_rating) === (string) $star ? 'selected' : '' }}>{{ $star }} sao</option>
                            @endforeach
                        </select>
                        @error('star_rating')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Mô tả</span>
                        <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                    </label>
                    <textarea name="description" rows="4"
                              class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror">{{ old('description', $product?->description) }}</textarea>
                    @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Ảnh sản phẩm</span>
                    </label>
                    @if($product?->getFirstMediaUrl('cover'))
                    <img src="{{ $product->getFirstMediaUrl('cover', 'thumb') }}" alt=""
                         class="h-20 w-auto rounded border border-base-300 mb-2 object-cover">
                    @endif
                    @if($product)
                    <div id="cover-filepond" data-context-type="ocop_product" data-context-id="{{ $product->id }}"></div>
                    <p class="text-xs text-base-content/40 mt-1.5">Tải ảnh mới sẽ tự động thay ảnh hiện tại.</p>
                    @else
                    <div id="cover-filepond"></div>
                    <input type="hidden" name="cover_media_uuid" id="cover-media-uuid" value="{{ old('cover_media_uuid') }}">
                    @endif
                    @error('cover_media_uuid')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                {{-- Tab footer: next --}}
                <div class="flex justify-end pt-2">
                    <button type="button" @click="tab = 'producer'" class="btn btn-ghost btn-sm gap-1.5">
                        Tiếp theo: Nhà sản xuất
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>

            </div>

            {{-- Panel: Nhà sản xuất --}}
            <div x-show="tab === 'producer'" data-tab-label="Nhà sản xuất" class="space-y-4">

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
                           data-val-maxlength="150"
                           class="input input-bordered input-sm w-full @error('producer_name') input-error @enderror"
                           maxlength="150">
                    @error('producer_name')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Địa chỉ nhà sản xuất</span>
                    </label>
                    <input type="text" name="producer_address" value="{{ old('producer_address', $product?->producer_address) }}"
                           data-val-maxlength="255"
                           class="input input-bordered input-sm w-full @error('producer_address') input-error @enderror"
                           maxlength="255">
                    @error('producer_address')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>

                {{-- spec/Heritage_Technical_Specification.md §8.2 — tuỳ chọn, ưu tiên hiện di
                     tích/làng nghề (intangible/historical_monument) đầu danh sách. --}}
                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Làng nghề/di tích liên quan</span>
                        <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                    </label>
                    <select id="ts-heritage_site_id" name="heritage_site_id"
                            class="select select-bordered select-sm w-full ts-init @error('heritage_site_id') select-error @enderror"
                            data-ts-placeholder="— Không gắn di tích —">
                        <option value=""></option>
                        @foreach($heritageSites as $s)
                        <option value="{{ $s->id }}" {{ (string) old('heritage_site_id', $product?->heritage_site_id) === (string) $s->id ? 'selected' : '' }}>
                            {{ $s->name }}{{ $s->province_name ? " ({$s->province_name})" : '' }}
                        </option>
                        @endforeach
                    </select>
                    @error('heritage_site_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="divider my-1"></div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Link mua hàng</span>
                        <span class="label-text-alt text-xs text-base-content/40">Sàn TMĐT/liên hệ mua</span>
                    </label>
                    <input type="url" name="purchase_url" value="{{ old('purchase_url', $product?->purchase_url) }}"
                           data-val-url="URL không hợp lệ — phải bắt đầu bằng https://"
                           class="input input-bordered input-sm w-full @error('purchase_url') input-error @enderror"
                           maxlength="500" placeholder="https://...">
                    @error('purchase_url')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>

                {{-- Tab footer: prev --}}
                <div class="flex items-center justify-between pt-2">
                    <button type="button" @click="tab = 'basic'" class="btn btn-ghost btn-sm gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Thông tin sản phẩm
                    </button>
                    <span class="text-xs text-base-content/40">Điền xong? Nhấn <strong>{{ $product ? 'Lưu thay đổi' : 'Tạo mới' }}</strong> ở bên phải</span>
                </div>

            </div>

        </div>{{-- /tab panels --}}
    </div>{{-- /card chính --}}

    {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
    <div class="xl:sticky xl:top-4 space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-3">

                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>

                <div class="form-control mb-4">
                    <label class="label py-0 pb-1">
                        <span class="label-text text-xs font-medium">Trạng thái <span class="text-error">*</span></span>
                    </label>
                    <select id="ts-status" name="status"
                            data-req="Vui lòng chọn trạng thái"
                            class="select select-bordered select-sm w-full ts-init @error('status') select-error @enderror"
                            data-ts-placeholder="— Chọn trạng thái —">
                        @foreach($statuses as $s)
                        <option value="{{ $s->value }}" {{ old('status', $product?->status?->value ?? 'draft') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                    @error('status')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <label class="flex items-start gap-2.5 cursor-pointer select-none group mb-4">
                    <input type="hidden" name="is_featured" value="0">
                    <input type="checkbox" name="is_featured" value="1"
                           class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0" {{ old('is_featured', $product?->is_featured) ? 'checked' : '' }}>
                    <span class="text-sm font-medium group-hover:text-primary transition-colors">Sản phẩm nổi bật</span>
                </label>

                <div class="form-control mb-3">
                    <label class="label py-0 pb-1">
                        <span class="label-text text-xs font-medium">Thứ tự hiển thị</span>
                    </label>
                    <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $product?->sort_order ?? 0) }}"
                           class="input input-bordered input-sm w-full @error('sort_order') input-error @enderror">
                    @error('sort_order')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('backend.ocop.products.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                    <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            @if($product)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            @endif
                        </svg>
                        {{ $product ? 'Lưu thay đổi' : 'Tạo mới' }}
                    </button>
                </div>

                <p class="text-center text-xs text-base-content/30 mt-2.5">
                    <span class="text-error">*</span> là trường bắt buộc
                </p>

            </div>
        </div>
    </div>

</div>
