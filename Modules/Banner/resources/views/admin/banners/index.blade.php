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

    <div class="section-page">
        <div class="card bg-base-100 mb-4">
            <div class="card-body filter-bar py-3 px-3">
                <div class="filter-grid">

                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Vị trí</span></label>
                        <select id="ts-placement" x-model="filters.placement" @change="onFilterChange()"
                                data-ts-placeholder="Tất cả vị trí"
                                class="select select-sm select-bordered w-full">
                            <option value="">Tất cả</option>
                            @foreach($placements as $key => $p)
                            <option value="{{ $key }}">{{ $p['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">Target</span></label>
                        <select id="ts-target_type" x-model="filters.target_type" @change="onFilterChange()"
                                data-ts-placeholder="Tất cả target"
                                class="select select-sm select-bordered w-full">
                            <option value="">Tất cả</option>
                            @foreach($targetTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
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
                <div id="banner-table"></div>
            </div>
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
    @vite(['Modules/Banner/resources/assets/sass/banner.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'resources/js/modules/tom-select.js',
        'Modules/Banner/resources/assets/js/banner.js',
    ], 'build/backend')
@endpush
