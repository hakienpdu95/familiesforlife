@extends('layouts.backend')
@section('title', 'Chủ thể OCOP')

@section('content')
<div x-data="ocopSubjectListPage({{ Js::from(['apiUrl' => route('backend.api.ocop-subjects')]) }})">

    @foreach(['success', 'error'] as $type)
        @if(session($type))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition.opacity.duration.500ms class="alert alert-{{ $type }} mb-4 text-sm">
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

    @error('ocop_subject')
    <div class="alert alert-error mb-4 text-sm">{{ $message }}</div>
    @enderror

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Chủ thể OCOP</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Doanh nghiệp, HTX, hộ kinh doanh sở hữu sản phẩm OCOP</p>
        </div>
        <div class="flex items-center gap-2">
            @can('viewAny', \Modules\Ocop\Models\OcopProduct::class)
            <a href="{{ route('backend.ocop.products.index') }}" class="btn btn-ghost btn-sm">Sản phẩm OCOP</a>
            @endcan
            @can('create', \Modules\OcopSubject\Models\OcopSubject::class)
            <button type="button" class="btn btn-outline btn-sm" onclick="ocopSubjectImportModal.showModal()">Import Excel</button>
            <a href="{{ route('backend.ocop-subjects.create') }}" class="btn btn-primary btn-sm">+ Thêm chủ thể</a>
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
                                   placeholder="Tên hoặc mã số định danh..." class="grow bg-transparent outline-none text-sm">
                            <button x-show="filters.search" @click="clearSearch()"
                                    class="text-base-content/30 hover:text-base-content transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Loại hình</span></label>
                        <select id="ts-organization_type" x-model="filters.organization_type" @change="onFilterChange()"
                                data-ts-placeholder="Tất cả loại hình"
                                class="select select-sm select-bordered w-full">
                            <option value="">Tất cả</option>
                            @foreach($types as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <div class="flex justify-end">
                    <button @click="reset()" x-show="hasFilters" x-cloak x-transition
                            class="btn btn-ghost btn-sm gap-1.5 text-error mt-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Đặt lại
                    </button>
                </div>

                <div x-show="activeChips.length > 0" x-cloak x-transition
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
                <div id="ocop-subject-table"></div>
            </div>
        </div>
    </div>

</div>

@can('create', \Modules\OcopSubject\Models\OcopSubject::class)
<dialog id="ocopSubjectImportModal" class="modal">
    <div class="modal-box max-w-2xl overflow-visible focus:outline-none" tabindex="-1" autofocus>
        <h3 class="font-bold text-lg">Import danh sách chủ thể</h3>
        <form method="POST" action="{{ route('backend.ocop-subjects.import') }}" enctype="multipart/form-data"
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
                <p class="text-xs text-base-content/50 mt-1.5">Dòng đầu là tiêu đề. Cột A: Tên cơ sở sản xuất, cột B: Phường/xã (vd "Phường Trường Thi"). Chủ thể trùng tên trong cùng phường/xã sẽ được bỏ qua.</p>
            </div>
            <div class="modal-action mt-4">
                <button type="submit" class="btn btn-primary btn-sm" :disabled="busy">
                    <span x-show="busy" class="loading loading-spinner loading-xs"></span> Import
                </button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="ocopSubjectImportModal.close()">Hủy</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endcan

<dialog id="ocopSubjectDeleteModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xoá</h3>
        <p class="py-3 text-sm text-base-content/70">
            Bạn có chắc muốn xoá chủ thể <strong id="ocopSubjectDeleteItemName" class="text-base-content"></strong>?
        </p>
        <div class="modal-action mt-4">
            <button id="ocopSubjectConfirmDeleteBtn" class="btn btn-error btn-sm">Xoá</button>
            <button class="btn btn-ghost btn-sm" onclick="ocopSubjectDeleteModal.close()">Hủy</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endsection

@push('styles')
    <x-tabulator-theme />
    @vite(['Modules/OcopSubject/resources/assets/sass/ocop-subject.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'resources/js/modules/tom-select.js',
        'Modules/OcopSubject/resources/assets/js/ocop-subject.js',
    ], 'build/backend')
@endpush
