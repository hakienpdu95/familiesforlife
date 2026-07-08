@extends('layouts.backend')
@section('title', 'Sửa danh mục sản phẩm')


@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Sửa danh mục</h1>
        <p class="text-sm text-base-content/50 mt-0.5 flex items-center gap-2">
            {{ $category->name }}
            <span class="badge badge-sm {{ $category->is_active ? 'badge-success' : 'badge-ghost' }}">
                {{ $category->is_active ? 'Hiện' : 'Ẩn' }}
            </span>
        </p>
    </div>
    <a href="{{ route('backend.products.categories.index') }}" class="btn btn-ghost btn-sm gap-1.5">
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

<form method="POST" action="{{ route('backend.products.categories.update', $category) }}" novalidate data-category-form>
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Card chính ──────────────────────────────────────────────── --}}
        <div class="space-y-5">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body">

                    <h2 class="card-title text-base mb-5">
                        <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        Thông tin danh mục
                    </h2>

                    <div class="space-y-4">

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tên danh mục <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="name" value="{{ old('name', $category->name) }}"
                                   data-req="Vui lòng nhập tên danh mục"
                                   data-val-maxlength="150"
                                   class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                                   maxlength="150">
                            @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Danh mục cha</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                            </label>
                            <select id="ts-parent" name="parent_id"
                                    class="select select-bordered select-sm w-full ts-init @error('parent_id') select-error @enderror"
                                    data-ts-placeholder="— Danh mục gốc —">
                                <option value="">— Danh mục gốc —</option>
                                @foreach($categories as $c)
                                    @continue($c->id === $category->id)
                                    <option value="{{ $c->id }}" {{ old('parent_id', $category->parent_id) == $c->id ? 'selected' : '' }}>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-base-content/40">Không thể chọn chính danh mục này làm cha</p>
                            @error('parent_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mô tả</span>
                                <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                            </label>
                            <textarea name="description" rows="3"
                                      class="textarea textarea-bordered textarea-sm w-full"
                                      placeholder="Mô tả ngắn về danh mục...">{{ old('description', $category->description) }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Icon</span>
                                </label>
                                <input type="text" name="icon" value="{{ old('icon', $category->icon) }}"
                                       class="input input-bordered input-sm w-full font-mono @error('icon') input-error @enderror"
                                       placeholder="ti-folder">
                                @error('icon')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="form-control">
                                <label class="label py-0 pb-1.5">
                                    <span class="label-text font-medium">Thứ tự hiển thị</span>
                                </label>
                                <input type="number" name="sort_order" min="0"
                                       value="{{ old('sort_order', $category->sort_order) }}"
                                       class="input input-bordered input-sm w-full @error('sort_order') input-error @enderror">
                                @error('sort_order')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Hiển thị danh mục</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Tắt để tạm ẩn khỏi bộ lọc chọn danh mục</p>
                            </div>
                        </label>

                    </div>
                </div>
            </div>
        </div>

        {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
                        Xuất bản
                    </p>

                    <div class="flex justify-between text-xs text-base-content/40 mb-4 px-0.5">
                        <span>Tạo {{ $category->created_at->format('d/m/Y') }}</span>
                        <span>Sửa {{ $category->updated_at->diffForHumans() }}</span>
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.products.categories.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
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

            @php($productsCount = $category->products()->count())
            @if($productsCount > 0)
            <div class="alert alert-info text-xs">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Danh mục đang chứa <strong>{{ $productsCount }}</strong> sản phẩm.</span>
            </div>
            @endif

        </div>

    </div>
</form>

@endsection

@push('styles')
    @vite(['Modules/Product/resources/assets/sass/product.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tom-select.js',
        'Modules/Product/resources/assets/js/product.js',
    ], 'build/backend')
@endpush
