@extends('layouts.backend')
@section('title', 'AccessTrade — Voucher & Khuyến mãi')

@section('content')
<div x-data="offerListPage({{ Js::from([
    'apiUrl' => route('backend.api.accesstrade.offers'),
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
            <h1 class="text-2xl font-bold text-base-content">Voucher / Coupon / Khuyến mãi</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Dữ liệu đồng bộ từ AccessTrade Publisher API (offers_informations) — chỉ đọc, tự động đồng bộ mỗi 3 giờ.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('backend.accesstrade.top-products.index') }}" class="btn btn-ghost btn-sm">Top sản phẩm bán chạy</a>
            <form action="{{ route('backend.accesstrade.sync') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary btn-sm gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Đồng bộ ngay
                </button>
            </form>
        </div>
    </div>

    {{-- ── Filter bar ───────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3 px-4">
            <div class="flex flex-wrap gap-3 items-end">

                <div class="form-control w-56">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Merchant</span></label>
                    <input type="text" x-model="filters.merchant" @input.debounce.400ms="onFilterChange()"
                           placeholder="vd: lazada" class="input input-sm input-bordered w-full">
                </div>

                <div class="form-control w-56">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Domain</span></label>
                    <input type="text" x-model="filters.domain" @input.debounce.400ms="onFilterChange()"
                           placeholder="vd: lazada.vn" class="input input-sm input-bordered w-full">
                </div>

                <div class="form-control w-56">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Loại</span></label>
                    <select x-model="filters.has_coupon" @change="onFilterChange()" class="select select-sm select-bordered w-full">
                        <option value="">— Tất cả —</option>
                        <option value="1">Có mã coupon</option>
                        <option value="0">Không có mã coupon</option>
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
            <div id="accesstrade-offer-table"></div>
        </div>
    </div>

</div>
@endsection

@push('styles')
    <x-tabulator-theme />
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'Modules/AccessTrade/resources/assets/js/accesstrade.js',
    ], 'build/backend')
@endpush
