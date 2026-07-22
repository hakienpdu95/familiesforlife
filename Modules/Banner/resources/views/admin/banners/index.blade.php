@extends('layouts.backend')
@section('title', 'Banner')

@section('content')
<div x-data="bannerListPage({{ Js::from([
    'apiUrl' => route('backend.api.banners.items'),
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

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Banner</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Banner quảng cáo/thông báo hiển thị ở nhiều vị trí trên cổng thông tin</p>
        </div>
        <div class="flex items-center gap-2">
            @can('create', \Modules\Banner\Models\Banner::class)
            <a href="{{ route('backend.banner.items.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm banner
            </a>
            @endcan
        </div>
    </div>

    {{-- ── Filter bar ───────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3 px-4">
            <div class="flex flex-wrap gap-3 items-end">

                <div class="form-control w-64">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Vị trí</span></label>
                    <select x-model="filters.placement" @change="onFilterChange()" class="select select-sm select-bordered w-full">
                        <option value="">— Tất cả vị trí —</option>
                        @foreach($placements as $key => $p)
                        <option value="{{ $key }}">{{ $p['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control w-56">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Target</span></label>
                    <select x-model="filters.target_type" @change="onFilterChange()" class="select select-sm select-bordered w-full">
                        <option value="">— Tất cả target —</option>
                        @foreach($targetTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control">
                    <button @click="reset()" x-show="hasFilters" x-transition
                            class="btn btn-ghost btn-sm gap-1.5 text-error">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Đặt lại
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- ── Tabulator table ──────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-hidden tabulator-daisy">
            <div id="banner-table"></div>
        </div>
    </div>

</div>

{{-- ── Delete confirm modal ─────────────────────────────────────────────── --}}
<dialog id="bannerDeleteModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xoá</h3>
        <p class="py-3 text-sm text-base-content/70">Bạn có chắc muốn xoá banner này?</p>
        <div class="modal-action mt-4">
            <button id="bannerConfirmDeleteBtn" class="btn btn-error btn-sm">Xoá</button>
            <button class="btn btn-ghost btn-sm" onclick="bannerDeleteModal.close()">Hủy</button>
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
        'Modules/Banner/resources/assets/js/banner.js',
    ], 'build/backend')
@endpush
