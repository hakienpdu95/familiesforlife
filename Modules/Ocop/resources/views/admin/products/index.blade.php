@extends('layouts.backend')
@section('title', 'Sản phẩm OCOP')

@section('content')
<div x-data="ocopProductListPage({{ Js::from([
    'apiUrl' => route('backend.api.ocop.products'),
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

    @if(session('import_errors'))
    <div x-data="{ open: false }" class="alert alert-warning mb-4 text-sm flex-col items-start">
        <div class="flex w-full items-center">
            <span>Có {{ count(session('import_errors')) }} dòng không import được.</span>
            <button @click="open = !open" class="btn btn-ghost btn-xs ml-auto" x-text="open ? 'Ẩn' : 'Xem chi tiết'"></button>
        </div>
        <ul x-show="open" x-cloak class="list-disc pl-5 max-h-64 overflow-y-auto w-full">
            @foreach(session('import_errors') as $importError)
            <li>{{ $importError }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($errors->hasAny(['file', 'province_code']))
    <div class="alert alert-error mb-4 text-sm">{{ $errors->first('file') ?: $errors->first('province_code') }}</div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Sản phẩm OCOP</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Sản phẩm đặc trưng OCOP theo tỉnh</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('backend.ocop.categories.index') }}" class="btn btn-ghost btn-sm">Danh mục OCOP</a>
            @can('create', \Modules\Ocop\Models\OcopProduct::class)
            <button type="button" class="btn btn-outline btn-sm" onclick="ocopProductImportModal.showModal()">Import Excel</button>
            <a href="{{ route('backend.ocop.products.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm sản phẩm
            </a>
            @endcan
        </div>
    </div>

    <div class="section-page">
        <div class="card bg-base-100 mb-4">
            <div class="card-body filter-bar py-3 px-3">
                <div class="filter-grid">

                    <div class="form-control filter-grid-wide">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Tìm kiếm</span></label>
                        <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                            <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" x-model="filters.search" @input.debounce.350ms="onFilterChange()"
                                   placeholder="Nhập tên sản phẩm..." class="grow bg-transparent outline-none text-sm">
                            <button x-show="filters.search" @click="clearSearch()"
                                    class="text-base-content/30 hover:text-base-content transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Danh mục</span></label>
                        <select id="ts-category" x-model="filters.category_id" @change="onFilterChange()"
                                data-ts-placeholder="Tất cả danh mục"
                                class="select select-sm select-bordered w-full">
                            <option value="">Tất cả</option>
                            @foreach($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Trạng thái</span></label>
                        <select id="ts-status" x-model="filters.status" @change="onFilterChange()"
                                data-ts-placeholder="Tất cả trạng thái"
                                class="select select-sm select-bordered w-full">
                            <option value="">Tất cả</option>
                            <option value="draft">Nháp</option>
                            <option value="published">Đã xuất bản</option>
                        </select>
                    </div>

                </div>

                <div class="flex justify-end">
                    <button @click="reset()" x-show="hasFilters" x-transition
                            class="btn btn-ghost btn-sm gap-1.5 text-error mt-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Đặt lại
                    </button>
                </div>

                <div x-show="activeChips.length > 0" x-transition
                     class="flex flex-wrap gap-2 pt-3 mt-3 border-t border-base-200">
                    <span class="text-xs text-base-content/40 self-center">Đang lọc:</span>
                    <template x-for="chip in activeChips" :key="chip.key">
                        <span class="badge badge-sm gap-1 cursor-pointer hover:badge-error transition-colors"
                              @click="removeChip(chip.key)">
                            <span x-text="chip.label"></span>
                            <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </span>
                    </template>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0 overflow-hidden tabulator-daisy">
                <div id="ocop-product-table"></div>
            </div>
        </div>
    </div>

</div>

{{-- ── Delete confirm modal ─────────────────────────────────────────────── --}}
@can('create', \Modules\Ocop\Models\OcopProduct::class)
<dialog id="ocopProductImportModal" class="modal">
    <div class="modal-box max-w-2xl overflow-visible focus:outline-none" tabindex="-1" autofocus>
        <h3 class="font-bold text-lg">Import danh sách sản phẩm OCOP</h3>
        <form method="POST" action="{{ route('backend.ocop.products.import') }}" enctype="multipart/form-data"
              x-data="{ busy: false }" @submit="busy = true" class="space-y-4 mt-4">
            @csrf
            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">Tỉnh/thành <span class="text-error">*</span></span></label>
                <select id="ts-import-province" name="province_code" required class="select select-bordered select-sm w-full"
                        data-ts-placeholder="— Chọn tỉnh/thành —">
                    <option value="">— Chọn tỉnh/thành —</option>
                    @foreach($provinces as $province)
                    <option value="{{ $province->province_code }}" @selected(old('province_code') === $province->province_code)>{{ $province->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-control">
                <label class="label py-0 pb-1.5"><span class="label-text font-medium">File Excel <span class="text-error">*</span></span></label>
                <input type="file" name="file" required accept=".xlsx,.csv" class="file-input file-input-bordered file-input-sm w-full">
                <p class="text-xs text-base-content/50 mt-1.5">File cần có dòng tiêu đề gồm các cột "Tên sản phẩm", "Tên cơ sở sản xuất" và "Hạng sao" (vd "4 sao"); các dòng tiêu đề gộp ô phía trên được tự bỏ qua. Chủ thể phải được import trước. Sản phẩm trùng tên trong cùng chủ thể sẽ chỉ cập nhật hạng sao. Sản phẩm mới được tạo ở trạng thái nháp.</p>
            </div>
            <div class="modal-action mt-4">
                <button type="submit" class="btn btn-primary btn-sm" :disabled="busy">
                    <span x-show="busy" class="loading loading-spinner loading-xs"></span> Import
                </button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="ocopProductImportModal.close()">Hủy</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endcan

<dialog id="ocopProductDeleteModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xoá</h3>
        <p class="py-3 text-sm text-base-content/70">
            Bạn có chắc muốn xoá sản phẩm
            <strong id="ocopProductDeleteItemName" class="text-base-content"></strong>?
        </p>
        <div class="modal-action mt-4">
            <button id="ocopProductConfirmDeleteBtn" class="btn btn-error btn-sm">Xoá</button>
            <button class="btn btn-ghost btn-sm" onclick="ocopProductDeleteModal.close()">Hủy</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endsection

@push('styles')
    <x-tabulator-theme />
    @vite(['Modules/Ocop/resources/assets/sass/ocop.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'resources/js/modules/tom-select.js',
        'Modules/Ocop/resources/assets/js/ocop.js',
    ], 'build/backend')
@endpush
