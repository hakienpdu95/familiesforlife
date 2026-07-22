@extends('layouts.backend')
@section('title', 'Danh mục sản phẩm')

@section('content')
<div x-data="categoryListPage({{ Js::from([
    'apiUrl' => route('backend.api.products.categories'),
]) }})">

    @foreach(['success','error'] as $type)
        @if(session($type))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition.opacity.duration.500ms
             class="alert alert-{{ $type }} mb-4 text-sm">
            <span>{{ session($type) }}</span>
            <button @click="show = false" class="btn btn-ghost btn-xs ml-auto">✕</button>
        </div>
        @endif
    @endforeach

    @if($errors->any())
    <div class="alert alert-error mb-4 text-sm">
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Danh mục sản phẩm</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Cây danh mục dùng cho catalog sản phẩm/dịch vụ</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('backend.products.index') }}" class="btn btn-ghost btn-sm">← Sản phẩm</a>
            @can(\App\Enums\PermissionEnum::PRODUCT_CATEGORY_MANAGE->value)
            <a href="{{ route('backend.products.categories.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm danh mục
            </a>
            @endcan
        </div>
    </div>

    {{-- ── Search ───────────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3 px-4">
            <div class="form-control max-w-sm">
                <label class="label py-0.5">
                    <span class="label-text text-xs font-medium">Tìm kiếm</span>
                    <span class="label-text-alt text-xs text-base-content/40">Có tìm kiếm sẽ hiện danh sách phẳng, không giữ cây</span>
                </label>
                <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                    <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" x-model="filters.search" @input.debounce.350ms="onFilterChange()"
                           placeholder="Nhập tên danh mục..." class="grow bg-transparent outline-none text-sm">
                    <button x-show="filters.search" @click="clearSearch()"
                            class="text-base-content/30 hover:text-base-content transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tabulator tree table ────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-hidden tabulator-daisy">
            <div id="product-category-table"></div>
        </div>
    </div>

</div>

{{-- ── Delete confirm modal ─────────────────────────────────────────────── --}}
<dialog id="productCategoryDeleteModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xoá</h3>
        <p class="py-3 text-sm text-base-content/70">
            Bạn có chắc muốn xoá danh mục
            <strong id="productCategoryDeleteItemName" class="text-base-content"></strong>?
        </p>
        <div class="modal-action mt-4">
            <button id="productCategoryConfirmDeleteBtn" class="btn btn-error btn-sm">Xoá</button>
            <button class="btn btn-ghost btn-sm" onclick="productCategoryDeleteModal.close()">Hủy</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endsection

@push('styles')
    <x-tabulator-theme />
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'Modules/Product/resources/assets/js/product.js',
    ], 'build/backend')
@endpush
