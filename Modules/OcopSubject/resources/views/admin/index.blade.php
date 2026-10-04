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
            <a href="{{ route('backend.ocop-subjects.create') }}" class="btn btn-primary btn-sm">+ Thêm chủ thể</a>
            @endcan
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3 px-4">
            <div class="flex flex-wrap gap-3 items-end">
                <div class="form-control flex-1 min-w-52">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Tìm kiếm</span></label>
                    <input type="text" x-model="filters.search" @input.debounce.350ms="onFilterChange()"
                           placeholder="Tên hoặc mã số định danh..." class="input input-sm input-bordered w-full">
                </div>
                <div class="form-control w-52">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Loại hình</span></label>
                    <select x-model="filters.organization_type" @change="onFilterChange()" class="select select-sm select-bordered w-full">
                        <option value="">— Tất cả —</option>
                        @foreach($types as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <button @click="reset()" x-show="hasFilters" x-transition class="btn btn-ghost btn-sm text-error">Đặt lại</button>
            </div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-hidden tabulator-daisy">
            <div id="ocop-subject-table"></div>
        </div>
    </div>

</div>

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
        'Modules/OcopSubject/resources/assets/js/ocop-subject.js',
    ], 'build/backend')
@endpush
