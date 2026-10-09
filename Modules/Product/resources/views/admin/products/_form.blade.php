{{-- Dùng chung create/edit — docs/form-ui-spec.md v5.1: §10 Tab-Based Form + §11 Sticky Submit Bar.
     Ảnh = FilePond (luồng draft, gắn khi Lưu), select = TomSelect. --}}
@php
    $currentCurrency = strtoupper((string) old('currency', $product?->currency ?? 'VND'));
    $currencies = collect(['VND', 'USD'])->push($currentCurrency)->unique();
    $affiliateFields = [
        ['shopee_url',            'Shopee',                 'https://shopee.vn/...',     null],
        ['tiktok_url',            'TikTok Shop',            'https://vt.tiktok.com/...', null],
        ['supplier_url',          'Link sản phẩm tại NCC',  'https://...',               null],
        ['supplier_homepage_url', 'Website nhà cung cấp',   'https://...',               'Dùng khi NCC không có trang sản phẩm riêng'],
    ];
    $contentLocked = $product?->isApprovalArchived() ?? false;
@endphp

<div x-data="{
    tab: 'basic',
    tabFields: {
        basic:     ['name', 'category_id', 'type', 'sku', 'short_description', 'description', 'content', 'cover_media_uuid', 'remove_cover'],
        pricing:   ['price', 'currency', 'price_label', 'is_featured', 'sort_order'],
        affiliate: ['shopee_url', 'tiktok_url', 'supplier_url', 'supplier_homepage_url'],
    },
    errs: {{ Js::from($errors->keys()) }},
    errCount(t) {
        return this.tabFields[t].filter(f => this.errs.includes(f)).length;
    },
    init() {
        for (const t of Object.keys(this.tabFields)) {
            if (this.errCount(t) > 0) { this.tab = t; break; }
        }
    }
}">

<form method="POST"
      action="{{ $product ? route('backend.products.update', $product) : route('backend.products.store') }}"
      novalidate data-product-form>
    @csrf
    @if($product) @method('PUT') @endif

    <div class="card bg-base-100 shadow-sm border border-base-200">

        {{-- Tab navigation --}}
        <div class="border-b border-base-200 px-3">
            <nav class="flex -mb-px overflow-x-auto" role="tablist" aria-label="Các nhóm thông tin sản phẩm">
                @foreach(['basic' => 'Thông tin cơ bản', 'pricing' => 'Giá & hiển thị', 'affiliate' => 'Link affiliate'] as $key => $label)
                <button type="button" role="tab" :aria-selected="tab === '{{ $key }}'"
                        @click="tab = '{{ $key }}'"
                        class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 whitespace-nowrap transition-colors"
                        :class="tab === '{{ $key }}'
                            ? 'border-primary text-primary'
                            : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                    {{ $label }}
                    <span x-show="errCount('{{ $key }}') > 0" x-text="errCount('{{ $key }}')" x-cloak
                          class="badge badge-error badge-xs"></span>
                </button>
                @endforeach
            </nav>
        </div>

        <div class="p-3">

            {{-- ── Tab 1: Thông tin cơ bản ─────────────────────────────────── --}}
            <div x-show="tab === 'basic'" data-tab-label="Thông tin cơ bản" class="space-y-4">

                @if($contentLocked)
                <div class="alert alert-warning py-2 px-3 text-xs">
                    Nội dung đã lưu trữ — tên, mô tả, nội dung và ảnh không thể sửa. Giá, trạng thái kinh doanh và link vẫn cập nhật được.
                </div>
                @endif

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Tên sản phẩm <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $product?->name) }}"
                           data-req="Vui lòng nhập tên sản phẩm"
                           data-val-maxlength="250"
                           class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                           placeholder="VD: Bộ đồ sơ sinh cotton hữu cơ" maxlength="250" @unless($product) autofocus @endunless>
                    @error('name')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Danh mục</span>
                            @can('create', \Modules\Product\Models\ProductCategory::class)
                            <a href="{{ route('backend.products.categories.create') }}" target="_blank" rel="noopener"
                               class="label-text-alt text-xs link link-primary">+ Tạo danh mục</a>
                            @endcan
                        </label>
                        <select id="ts-category" name="category_id"
                                class="select select-bordered select-sm w-full ts-init @error('category_id') select-error @enderror"
                                data-ts-placeholder="— Chưa phân loại —">
                            <option value="">— Chưa phân loại —</option>
                            @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ (string) old('category_id', $product?->category_id) === (string) $c->id ? 'selected' : '' }}>
                                {{ $c->parent ? $c->parent->name . ' › ' : '' }}{{ $c->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('category_id')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Loại <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-type" name="type"
                                data-req="Vui lòng chọn loại"
                                class="select select-bordered select-sm w-full ts-init @error('type') select-error @enderror"
                                data-ts-placeholder="— Chọn loại —">
                            @foreach(\Modules\Product\Enums\ProductType::cases() as $t)
                            <option value="{{ $t->value }}" {{ old('type', $product?->type?->value ?? 'physical') === $t->value ? 'selected' : '' }}>{{ $t->label() }}</option>
                            @endforeach
                        </select>
                        @error('type')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">SKU</span>
                            <span class="label-text-alt text-xs text-base-content/40">Mã nội bộ</span>
                        </label>
                        <input type="text" name="sku" value="{{ old('sku', $product?->sku) }}"
                               data-val-maxlength="60"
                               class="input input-bordered input-sm w-full font-mono @error('sku') input-error @enderror"
                               placeholder="VD: SP-001" maxlength="60">
                        @error('sku')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="form-control" x-data="{ len: {{ Js::from(mb_strlen((string) old('short_description', $product?->short_description))) }} }">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Mô tả ngắn</span>
                        <span class="label-text-alt text-xs text-base-content/40"><span x-text="len">0</span>/300</span>
                    </label>
                    <input type="text" name="short_description" value="{{ old('short_description', $product?->short_description) }}"
                           data-val-maxlength="300"
                           @input="len = $el.value.length"
                           class="input input-bordered input-sm w-full @error('short_description') input-error @enderror"
                           placeholder="VD: Cotton hữu cơ, an toàn cho da bé" maxlength="300">
                    <p class="mt-1 text-xs text-base-content/40">Hiện trong hộp thoại chọn sản phẩm và Product CTA Box dạng gọn.</p>
                    @error('short_description')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Mô tả đầy đủ</span>
                        <span class="label-text-alt text-xs text-base-content/40">Văn bản thường</span>
                    </label>
                    <textarea name="description" rows="4"
                              class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                              placeholder="Đặc điểm, chất liệu, kích thước, đối tượng phù hợp...">{{ old('description', $product?->description) }}</textarea>
                    @error('description')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Nội dung</span>
                        <span class="label-text-alt text-xs text-base-content/40">Giới thiệu chi tiết — có thể chèn ảnh, bảng, video</span>
                    </label>
                    <textarea id="product-content" name="content"
                              class="jodit-editor textarea textarea-bordered textarea-sm w-full @error('content') textarea-error @enderror"
                              data-jodit-preset="standard">{{ old('content', $product?->content) }}</textarea>
                    @error('content')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>

                {{-- Ảnh đại diện — docs/form-ui-spec.md §15 File & Image Upload --}}
                <div class="form-control"
                     x-data="{ remove: {{ Js::from((bool) old('remove_cover')) }}, hasCurrent: {{ Js::from((bool) $product?->cover_image_url) }} }">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Ảnh đại diện</span>
                        <span class="label-text-alt text-xs text-base-content/40">JPG, PNG, WEBP · tối đa 10MB</span>
                    </label>

                    @if($product?->cover_image_url)
                    <div x-show="hasCurrent && !remove" class="relative w-32 h-32 mb-2">
                        <img src="{{ $product->cover_image_url }}" alt="Ảnh đại diện hiện tại"
                             class="w-full h-full object-cover rounded-lg border border-base-200">
                        @unless($contentLocked)
                        <button type="button" @click="remove = true" title="Xóa ảnh hiện tại"
                                class="btn btn-circle btn-xs btn-error absolute -top-2 -right-2">✕</button>
                        @endunless
                    </div>
                    <div x-show="remove" x-cloak class="flex items-center gap-2 mb-2 text-xs text-warning">
                        Ảnh hiện tại sẽ bị xóa khi lưu.
                        <button type="button" @click="remove = false" class="link link-primary">Hoàn tác</button>
                    </div>
                    <input type="hidden" name="remove_cover" value="1" :disabled="!remove">
                    @endif

                    @unless($contentLocked)
                    <div id="cover-filepond"></div>
                    <input type="hidden" name="cover_media_uuid" id="cover-media-uuid" value="{{ old('cover_media_uuid') }}">
                    @if($product?->cover_image_url)
                    <p class="mt-1 text-xs text-base-content/40">Tải ảnh mới sẽ thay ảnh hiện tại khi bấm Lưu.</p>
                    @endif
                    @endunless
                    @error('cover_media_uuid')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>

                <div class="flex justify-end pt-2">
                    <button type="button" @click="tab = 'pricing'" class="btn btn-ghost btn-sm gap-1.5">
                        Tiếp theo: Giá & hiển thị
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>

            {{-- ── Tab 2: Giá & hiển thị ───────────────────────────────────── --}}
            <div x-show="tab === 'pricing'" x-cloak data-tab-label="Giá & hiển thị" class="space-y-4">

                <div class="grid grid-cols-1 sm:grid-cols-[1fr_8rem_1fr] gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Giá</span>
                        </label>
                        <input type="number" step="any" min="0" name="price" inputmode="decimal"
                               value="{{ old('price', $product?->price !== null ? (float) $product->price : null) }}" data-price-input
                               class="input input-bordered input-sm w-full font-mono @error('price') input-error @enderror"
                               placeholder="VD: 250000">
                        @error('price')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tiền tệ</span>
                        </label>
                        <select id="ts-currency" name="currency" data-currency-input
                                class="select select-bordered select-sm w-full ts-init @error('currency') select-error @enderror"
                                data-ts-placeholder="— Tiền tệ —">
                            @foreach($currencies as $cur)
                            <option value="{{ $cur }}" {{ $currentCurrency === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                            @endforeach
                        </select>
                        @error('currency')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Nhãn giá tuỳ chỉnh</span>
                            <span class="label-text-alt text-xs text-base-content/40">Ưu tiên hơn giá số</span>
                        </label>
                        <input type="text" name="price_label" value="{{ old('price_label', $product?->price_label) }}"
                               data-val-maxlength="100" data-price-label-input
                               class="input input-bordered input-sm w-full @error('price_label') input-error @enderror"
                               placeholder="VD: Liên hệ báo giá" maxlength="100">
                        @error('price_label')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex items-center gap-2 rounded-lg bg-base-200/60 px-3 py-2 text-sm">
                    <span class="text-base-content/50">Hiển thị trên CTA Box:</span>
                    <strong data-price-preview class="text-base-content">—</strong>
                </div>

                <div class="divider my-1 text-xs text-base-content/40">Hiển thị trong hộp chọn sản phẩm</div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Thứ tự hiển thị</span>
                            <span class="label-text-alt text-xs text-base-content/40">Số nhỏ hiển thị trước</span>
                        </label>
                        <input type="number" name="sort_order" min="0" step="1"
                               value="{{ old('sort_order', $product?->sort_order ?? 0) }}"
                               class="input input-bordered input-sm w-full @error('sort_order') input-error @enderror"
                               placeholder="0">
                        @error('sort_order')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control sm:pt-6">
                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="hidden" name="is_featured" value="0">
                            <input type="checkbox" name="is_featured" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('is_featured', $product?->is_featured) ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Sản phẩm nổi bật</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Ưu tiên hiển thị đầu danh sách khi biên tập viên chọn sản phẩm</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <button type="button" @click="tab = 'basic'" class="btn btn-ghost btn-sm gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Thông tin cơ bản
                    </button>
                    <button type="button" @click="tab = 'affiliate'" class="btn btn-ghost btn-sm gap-1.5">
                        Tiếp theo: Link affiliate
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>

            {{-- ── Tab 3: Link affiliate ───────────────────────────────────── --}}
            <div x-show="tab === 'affiliate'" x-cloak data-tab-label="Link affiliate" class="space-y-4">

                <p class="text-xs text-base-content/50">
                    Cấu hình 1 lần ở đây — bài viết chỉ chọn dùng link nào, không cần nhập lại URL cho từng vị trí chèn.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach($affiliateFields as [$field, $label, $placeholder, $hint])
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">{{ $label }}</span>
                            @if($hint)<span class="label-text-alt text-xs text-base-content/40">{{ $hint }}</span>@endif
                        </label>
                        <div class="join w-full">
                            <input type="url" name="{{ $field }}" value="{{ old($field, $product?->{$field}) }}"
                                   data-val-url="URL phải bắt đầu bằng https://"
                                   data-affiliate-input
                                   class="input input-bordered input-sm join-item flex-1 min-w-0 @error($field) input-error @enderror"
                                   placeholder="{{ $placeholder }}" maxlength="500">
                            <a href="#" target="_blank" rel="noopener noreferrer" data-affiliate-open
                               class="btn btn-sm join-item btn-disabled" aria-disabled="true" title="Mở link để kiểm tra">↗</a>
                        </div>
                        @error($field)<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-between pt-2">
                    <button type="button" @click="tab = 'pricing'" class="btn btn-ghost btn-sm gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Giá & hiển thị
                    </button>
                    <span class="text-xs text-base-content/40">Điền xong? Nhấn <strong>{{ $product ? 'Lưu lại' : 'Tạo sản phẩm' }}</strong> ở thanh dưới cùng</span>
                </div>
            </div>

        </div>
    </div>

    {{-- ── Sticky Submit Bar (§11) ─────────────────────────────────────────── --}}
    <div class="form-submit-bar form-submit-bar--sticky">

        {{-- Nút mặc định khi nhấn Enter: lưu rồi về danh sách --}}
        <button type="submit" class="sr-only" tabindex="-1" aria-hidden="true"></button>

        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
            <div class="flex items-center gap-2">
                <span class="text-xs font-medium text-base-content/60 whitespace-nowrap">Trạng thái <span class="text-error">*</span></span>
                <div class="w-44">
                    <select id="ts-status" name="status"
                            class="select select-bordered select-sm w-full @error('status') select-error @enderror">
                        @foreach(\Modules\Product\Enums\ProductStatus::cases() as $s)
                        <option value="{{ $s->value }}" {{ old('status', $product?->status?->value ?? 'active') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @if($product)
            <span class="text-xs text-base-content/40">
                Tạo {{ $product->created_at?->format('d/m/Y') }} · Sửa {{ $product->updated_at?->diffForHumans() }}
            </span>
            @if($product->isApproved() || $product->isApprovalPublished())
            <span class="text-xs text-warning" title="Sửa tên, mô tả, nội dung hoặc ảnh sẽ đưa sản phẩm về Chờ duyệt và tạm ẩn khỏi cổng thông tin">
                Sửa nội dung sẽ cần duyệt lại
            </span>
            @endif
            @else
            <span class="text-xs text-base-content/40" title="Sản phẩm chỉ hiển thị công khai sau khi đội kiểm duyệt duyệt và xuất bản">
                Sản phẩm mới tự động gửi duyệt nội dung
            </span>
            @endif
            @error('status')<p class="w-full text-xs text-error">{{ $message }}</p>@enderror
        </div>

        <div class="submit-actions">
            <a href="{{ route('backend.products.index') }}" class="btn btn-ghost btn-sm">Hủy</a>
            @if($product)
            <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Lưu lại
            </button>
            @else
            <button type="submit" name="after_save" value="new" class="btn btn-outline btn-sm">
                Tạo & thêm tiếp
            </button>
            <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tạo sản phẩm
            </button>
            @endif
        </div>

    </div>

</form>
</div>
