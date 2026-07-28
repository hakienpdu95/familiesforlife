@extends('layouts.backend')
@section('title', 'Thêm sản phẩm')


@section('content')
<div x-data="{
    tab: 'basic',
    tabFields: {
        basic:     ['name', 'category_id', 'type', 'sku', 'short_description', 'description', 'cover_image_url'],
        pricing:   ['price', 'currency', 'price_label'],
        affiliate: ['shopee_url', 'tiktok_url', 'supplier_url', 'supplier_homepage_url'],
    },
    errs: {{ Js::from($errors->keys()) }},
    errCount(t) {
        return this.tabFields[t].filter(f => this.errs.includes(f)).length;
    },
    init() {
        const order = ['basic', 'pricing', 'affiliate'];
        for (const t of order) {
            if (this.errCount(t) > 0) { this.tab = t; break; }
        }
    }
}">

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thêm sản phẩm</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Sản phẩm/dịch vụ sẽ dùng để gọi ra trong Product CTA Box của bài viết</p>
    </div>
    <a href="{{ route('backend.products.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.products.store') }}" novalidate data-product-form>
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-6 items-start">

        {{-- ── Card chính với tab ───────────────────────────────────────── --}}
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
                        Thông tin cơ bản
                        <span x-show="errCount('basic') > 0" x-text="errCount('basic')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'pricing'"
                            @click="tab = 'pricing'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'pricing'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Giá
                        <span x-show="errCount('pricing') > 0" x-text="errCount('pricing')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'affiliate'"
                            @click="tab = 'affiliate'"
                            class="flex items-center gap-1.5 px-1 py-4 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'affiliate'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        Link affiliate
                        <span x-show="errCount('affiliate') > 0" x-text="errCount('affiliate')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                </nav>
            </div>

            {{-- Tab panels --}}
            <div class="p-3">

                {{-- Panel: Thông tin cơ bản --}}
                <div x-show="tab === 'basic'" data-tab-label="Thông tin cơ bản" class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tên sản phẩm <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}"
                               data-req="Vui lòng nhập tên sản phẩm"
                               data-val-maxlength="250"
                               class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                               placeholder="VD: Bộ đồ sơ sinh cotton hữu cơ" maxlength="250" autofocus>
                        @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Danh mục</span>
                            </label>
                            <select id="ts-category" name="category_id"
                                    class="select select-bordered select-sm w-full ts-init @error('category_id') select-error @enderror"
                                    data-ts-placeholder="— Chưa phân loại —">
                                <option value="">— Chưa phân loại —</option>
                                @foreach($categories as $c)
                                <option value="{{ $c->id }}" {{ old('category_id') == $c->id ? 'selected' : '' }}>
                                    {{ $c->parent ? $c->parent->name . ' › ' : '' }}{{ $c->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('category_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Loại <span class="text-error">*</span></span>
                            </label>
                            <select id="ts-type" name="type"
                                    class="select select-bordered select-sm w-full ts-init @error('type') select-error @enderror"
                                    data-ts-placeholder="— Chọn loại —">
                                @foreach(\Modules\Product\Enums\ProductType::cases() as $t)
                                <option value="{{ $t->value }}" {{ old('type', 'physical') === $t->value ? 'selected' : '' }}>{{ $t->label() }}</option>
                                @endforeach
                            </select>
                            @error('type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">SKU</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <input type="text" name="sku" value="{{ old('sku') }}"
                               data-val-maxlength="60"
                               class="input input-bordered input-sm w-full font-mono @error('sku') input-error @enderror"
                               placeholder="VD: SP-001" maxlength="60">
                        @error('sku')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mô tả ngắn</span>
                            <span class="label-text-alt text-xs text-base-content/40">Hiện trong dialog chọn sản phẩm + box gọn</span>
                        </label>
                        <input type="text" name="short_description" value="{{ old('short_description') }}"
                               data-val-maxlength="300"
                               class="input input-bordered input-sm w-full @error('short_description') input-error @enderror"
                               placeholder="VD: Cotton hữu cơ, an toàn cho da bé" maxlength="300">
                        @error('short_description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mô tả đầy đủ</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <textarea name="description" rows="4"
                                  class="textarea textarea-bordered textarea-sm w-full"
                                  placeholder="Mô tả chi tiết sản phẩm...">{{ old('description') }}</textarea>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Ảnh đại diện (URL)</span>
                        </label>
                        <input type="text" name="cover_image_url" value="{{ old('cover_image_url') }}"
                               class="input input-bordered input-sm w-full @error('cover_image_url') input-error @enderror"
                               placeholder="https://...">
                        @error('cover_image_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Tab footer: next --}}
                    <div class="flex justify-end pt-2">
                        <button type="button" @click="tab = 'pricing'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Giá
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- Panel: Giá --}}
                <div x-show="tab === 'pricing'" data-tab-label="Giá" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Giá</span>
                            </label>
                            <input type="number" step="0.01" min="0" name="price"
                                   value="{{ old('price') }}"
                                   class="input input-bordered input-sm w-full @error('price') input-error @enderror"
                                   placeholder="0">
                            @error('price')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tiền tệ</span>
                            </label>
                            <input type="text" name="currency" value="{{ old('currency', 'VND') }}"
                                   data-val-maxlength="3"
                                   class="input input-bordered input-sm w-full font-mono uppercase @error('currency') input-error @enderror"
                                   placeholder="VND" maxlength="3">
                            @error('currency')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Nhãn giá tuỳ chỉnh</span>
                            <span class="label-text-alt text-xs text-base-content/40">Ưu tiên hiển thị thay cho giá số</span>
                        </label>
                        <input type="text" name="price_label" value="{{ old('price_label') }}"
                               data-val-maxlength="100"
                               class="input input-bordered input-sm w-full @error('price_label') input-error @enderror"
                               placeholder="VD: Liên hệ báo giá" maxlength="100">
                        <p class="mt-1 text-xs text-base-content/40">Để trống nếu muốn hiển thị giá số ở trên</p>
                        @error('price_label')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Tab footer: prev / next --}}
                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'basic'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Thông tin cơ bản
                        </button>
                        <button type="button" @click="tab = 'affiliate'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Link affiliate
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- Panel: Link affiliate --}}
                <div x-show="tab === 'affiliate'" data-tab-label="Link affiliate" class="space-y-4">

                    <p class="text-xs text-base-content/50 -mt-1">
                        Cấu hình 1 lần ở đây — bài viết chỉ chọn dùng link nào, không cần nhập lại URL cho từng vị trí chèn.
                    </p>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Shopee</span>
                        </label>
                        <input type="url" name="shopee_url" value="{{ old('shopee_url') }}"
                               data-val-url="URL phải bắt đầu bằng https://"
                               class="input input-bordered input-sm w-full @error('shopee_url') input-error @enderror"
                               placeholder="https://shopee.vn/...">
                        @error('shopee_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">TikTok Shop</span>
                        </label>
                        <input type="url" name="tiktok_url" value="{{ old('tiktok_url') }}"
                               data-val-url="URL phải bắt đầu bằng https://"
                               class="input input-bordered input-sm w-full @error('tiktok_url') input-error @enderror"
                               placeholder="https://vt.tiktok.com/...">
                        @error('tiktok_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Link sản phẩm tại NCC</span>
                        </label>
                        <input type="url" name="supplier_url" value="{{ old('supplier_url') }}"
                               data-val-url="URL phải bắt đầu bằng https://"
                               class="input input-bordered input-sm w-full @error('supplier_url') input-error @enderror"
                               placeholder="https://...">
                        @error('supplier_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Website nhà cung cấp</span>
                            <span class="label-text-alt text-xs text-base-content/40">Fallback khi NCC không có trang sản phẩm riêng</span>
                        </label>
                        <input type="url" name="supplier_homepage_url" value="{{ old('supplier_homepage_url') }}"
                               data-val-url="URL phải bắt đầu bằng https://"
                               class="input input-bordered input-sm w-full @error('supplier_homepage_url') input-error @enderror"
                               placeholder="https://...">
                        @error('supplier_homepage_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    {{-- Tab footer: prev --}}
                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'pricing'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Giá
                        </button>
                        <span class="text-xs text-base-content/40">Điền xong? Nhấn <strong>Tạo sản phẩm</strong> ở bên phải</span>
                    </div>

                </div>

            </div>{{-- /tab panels --}}
        </div>{{-- /card chính --}}

        {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">

            {{-- Xuất bản --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-3">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>

                    <div class="form-control mb-4">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">Trạng thái <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-status" name="status"
                                class="select select-bordered select-sm w-full ts-init @error('status') select-error @enderror"
                                data-ts-placeholder="— Chọn trạng thái —">
                            @foreach(\Modules\Product\Enums\ProductStatus::cases() as $s)
                            <option value="{{ $s->value }}" {{ old('status', 'active') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                            @endforeach
                        </select>
                        @error('status')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control mb-3">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">Thứ tự hiển thị</span>
                        </label>
                        <input type="number" name="sort_order" min="0"
                               value="{{ old('sort_order', 0) }}"
                               class="input input-bordered input-sm w-full @error('sort_order') input-error @enderror">
                        @error('sort_order')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-start gap-2.5 cursor-pointer select-none group mb-4">
                        <input type="hidden" name="is_featured" value="0">
                        <input type="checkbox" name="is_featured" value="1"
                               class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                               {{ old('is_featured') ? 'checked' : '' }}>
                        <div>
                            <span class="text-sm font-medium group-hover:text-primary transition-colors">Sản phẩm nổi bật</span>
                            <p class="text-xs text-base-content/50 mt-0.5">Ưu tiên hiển thị trong picker</p>
                        </div>
                    </label>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.products.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tạo sản phẩm
                        </button>
                    </div>

                    <p class="text-center text-xs text-base-content/30 mt-2.5">
                        <span class="text-error">*</span> là trường bắt buộc
                    </p>

                </div>
            </div>

        </div>{{-- /sidebar --}}

    </div>{{-- /grid --}}

</form>
</div>
@endsection

@push('styles')
    @vite(['Modules/Product/resources/assets/sass/product.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'Modules/Product/resources/assets/js/product.js',
    ], 'build/backend')
@endpush
