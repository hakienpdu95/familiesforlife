@extends('layouts.backend')
@section('title','Thêm sản phẩm')
@push('styles')
@vite(['resources/js/modules/filepond.js','resources/js/modules/jodit.js','resources/js/modules/tom-select.js','resources/js/modules/flatpickr.js'])
@endpush
@section('breadcrumb')
<nav class="breadcrumb-nav">
    <a href="{{ route('backend.dashboard') }}">Trang chủ</a><span class="sep">›</span>
    <a href="{{ route('backend.products.index') }}">Sản phẩm</a><span class="sep">›</span>
    <span class="current">Thêm mới</span>
</nav>
@endsection
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div><h1 class="text-2xl font-bold text-base-content">Thêm sản phẩm</h1><p class="text-sm text-base-content/50 mt-0.5">Điền thông tin sản phẩm mới</p></div>
    <a href="{{ route('backend.products.index') }}" class="btn btn-ghost btn-sm gap-2">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Quay lại
    </a>
</div>

<form action="{{ route('backend.products.store') }}" method="POST" enctype="multipart/form-data">
@csrf
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

    {{-- Main column --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Basic info --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h2 class="font-bold text-base-content mb-4">Thông tin cơ bản</h2>
                <div class="space-y-4">
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text font-semibold">Tên sản phẩm <span class="text-error">*</span></span></label>
                        <input type="text" name="name" class="input input-bordered @error('name') input-error @enderror" placeholder="Nhập tên sản phẩm..." value="{{ old('name') }}" required>
                        @error('name')<span class="text-error text-xs mt-1">{{ $message }}</span>@enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label pb-1"><span class="label-text font-semibold">SKU</span></label>
                            <input type="text" name="sku" class="input input-bordered" placeholder="VD: SP-001" value="{{ old('sku') }}">
                        </div>
                        <div class="form-control">
                            <label class="label pb-1"><span class="label-text font-semibold">Barcode</span></label>
                            <input type="text" name="barcode" class="input input-bordered" placeholder="EAN, UPC..." value="{{ old('barcode') }}">
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text font-semibold">Mô tả ngắn</span></label>
                        <textarea name="short_desc" class="textarea textarea-bordered h-20" placeholder="Mô tả ngắn gọn...">{{ old('short_desc') }}</textarea>
                    </div>
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text font-semibold">Mô tả chi tiết</span></label>
                        <textarea name="description" id="editor-desc">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pricing --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h2 class="font-bold text-base-content mb-4">Giá bán</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text font-semibold">Giá bán <span class="text-error">*</span></span></label>
                        <label class="input input-bordered flex items-center gap-2">
                            <span class="text-base-content/50 text-sm">₫</span>
                            <input type="number" name="price" class="grow" placeholder="0" value="{{ old('price') }}" required>
                        </label>
                    </div>
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text font-semibold">Giá gốc</span></label>
                        <label class="input input-bordered flex items-center gap-2">
                            <span class="text-base-content/50 text-sm">₫</span>
                            <input type="number" name="compare_price" class="grow" placeholder="0" value="{{ old('compare_price') }}">
                        </label>
                    </div>
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text font-semibold">Giá vốn</span></label>
                        <label class="input input-bordered flex items-center gap-2">
                            <span class="text-base-content/50 text-sm">₫</span>
                            <input type="number" name="cost_price" class="grow" placeholder="0" value="{{ old('cost_price') }}">
                        </label>
                    </div>
                </div>
                <div class="form-control mt-3">
                    <label class="cursor-pointer flex items-center gap-2">
                        <input type="checkbox" name="taxable" class="checkbox checkbox-sm" value="1">
                        <span class="label-text">Áp dụng thuế VAT (10%)</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Inventory --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h2 class="font-bold text-base-content mb-4">Kho hàng</h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text font-semibold">Số lượng</span></label>
                        <input type="number" name="quantity" class="input input-bordered" placeholder="0" value="{{ old('quantity',0) }}" min="0">
                    </div>
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text font-semibold">Cảnh báo kho thấp</span></label>
                        <input type="number" name="low_stock" class="input input-bordered" placeholder="5" value="{{ old('low_stock',5) }}" min="0">
                    </div>
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text font-semibold">Đơn vị</span></label>
                        <input type="text" name="unit" class="input input-bordered" placeholder="Cái, Hộp..." value="{{ old('unit') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Media --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h2 class="font-bold text-base-content mb-4">Hình ảnh sản phẩm</h2>
                <input type="file" name="images[]" id="product-images" multiple accept="image/*" class="hidden">
                <div class="text-xs text-base-content/50 mt-2">Hỗ trợ JPG, PNG, WebP. Tối đa 5MB/ảnh.</div>
            </div>
        </div>

    </div>

    {{-- Sidebar column --}}
    <div class="space-y-5">

        {{-- Publish --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h2 class="font-bold text-base-content mb-4">Xuất bản</h2>
                <div class="form-control mb-3">
                    <label class="label pb-1"><span class="label-text font-semibold">Trạng thái</span></label>
                    <select name="status" class="select select-bordered">
                        <option value="draft" {{ old('status')=='draft'?'selected':'' }}>Nháp</option>
                        <option value="active" {{ old('status','active')=='active'?'selected':'' }}>Đang bán</option>
                        <option value="inactive" {{ old('status')=='inactive'?'selected':'' }}>Ẩn</option>
                    </select>
                </div>
                <div class="form-control mb-4">
                    <label class="label pb-1"><span class="label-text font-semibold">Ngày xuất bản</span></label>
                    <input type="text" name="published_at" id="publish-date" class="input input-bordered input-sm" placeholder="dd/mm/yyyy" value="{{ old('published_at') }}">
                </div>
                <div class="flex gap-2">
                    <button type="button" class="btn btn-ghost btn-sm flex-1">Lưu nháp</button>
                    <button type="submit" class="btn btn-primary btn-sm flex-1 gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Lưu
                    </button>
                </div>
            </div>
        </div>

        {{-- Category --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h2 class="font-bold text-base-content mb-4">Danh mục</h2>
                <div class="form-control">
                    <select name="category_id[]" id="category-select" multiple class="select select-bordered">
                        <option value="1">Điện thoại</option>
                        <option value="2">Laptop</option>
                        <option value="3">Phụ kiện</option>
                        <option value="4">Âm thanh</option>
                        <option value="5">Tablet</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Tags --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h2 class="font-bold text-base-content mb-4">Tags</h2>
                <input type="text" name="tags" id="tags-input" placeholder="Nhập tag, Enter để thêm..." class="input input-bordered w-full input-sm" value="{{ old('tags') }}">
            </div>
        </div>

        {{-- SEO --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5">
                <h2 class="font-bold text-base-content mb-4">SEO</h2>
                <div class="space-y-3">
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text text-sm font-semibold">Meta title</span></label>
                        <input type="text" name="meta_title" class="input input-bordered input-sm" placeholder="Tiêu đề SEO..." value="{{ old('meta_title') }}">
                    </div>
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text text-sm font-semibold">Meta description</span></label>
                        <textarea name="meta_desc" class="textarea textarea-bordered textarea-sm h-20" placeholder="Mô tả SEO...">{{ old('meta_desc') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
</form>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.initFilePond)   initFilePond('#product-images', { maxFiles: 10 });
    if (window.initJodit)      initJodit('#editor-desc');
    if (window.initTomSelect)  initTomSelect('#category-select', { placeholder: 'Chọn danh mục...' });
    if (window.initTagsInput)  initTagsInput('#tags-input');
    if (window.initDatePicker) initDatePicker('#publish-date');
});
</script>
@endpush
