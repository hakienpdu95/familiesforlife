@extends('layouts.backend')
@section('title', __('Phản ánh mã QR'))

@section('content')
<div x-data="qrReportListPage({{ Js::from(['apiUrl' => route('backend.qr-reports.data')]) }})">

    {{-- ── Page header ──────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">{{ __('Phản ánh mã QR') }}</h1>
            <p class="text-sm text-base-content/50 mt-0.5">{{ __('Phản ánh do người quét gửi từ trang truy xuất /p/{mã}') }}</p>
        </div>
    </div>

    <div class="section-page">
        <div role="tablist" class="tabs tabs-boxed inline-flex mb-4">
            <button type="button" role="tab" class="tab gap-1.5" :class="filters.status === 'new' && 'tab-active'" @click="setStatus('new')">
                {{ __('Chưa xử lý') }} <span class="badge badge-sm" :class="counts.new > 0 ? 'badge-error' : 'badge-ghost'" x-text="counts.new">0</span>
            </button>
            <button type="button" role="tab" class="tab gap-1.5" :class="filters.status === 'resolved' && 'tab-active'" @click="setStatus('resolved')">
                {{ __('Đã xử lý') }} <span class="badge badge-sm badge-ghost" x-text="counts.resolved">0</span>
            </button>
            <button type="button" role="tab" class="tab" :class="filters.status === '' && 'tab-active'" @click="setStatus('')">{{ __('Tất cả') }}</button>
        </div>

        <div class="card bg-base-100 mb-4">
            <div class="card-body py-3 px-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">
                    <div class="form-control lg:col-span-2">
                        <label class="label py-0.5">
                            <span class="label-text text-xs font-medium">{{ __('Tìm kiếm') }}</span>
                            <span class="label-text-alt text-xs text-base-content/40">{{ __('Nội dung, liên hệ, mã QR') }}</span>
                        </label>
                        <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                            <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            <input type="text" x-model="filters.search" @input.debounce.350ms="onFilterChange()" placeholder="{{ __('Từ khoá, mã QR hoặc mã phản ánh (PA-…)') }}" class="grow bg-transparent outline-none text-sm">
                            <button x-show="filters.search" x-cloak @click="clearSearch()" class="text-base-content/30 hover:text-base-content transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0.5"><span class="label-text text-xs font-medium">{{ __('Lý do') }}</span></label>
                        <select id="ts-reason" x-model="filters.reason" @change="onFilterChange()" class="select select-sm select-bordered w-full">
                            <option value="">{{ __('Tất cả') }}</option>
                            @foreach($reasons as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex justify-end">
                        <button @click="reset()" x-show="hasFilters" x-cloak x-transition class="btn btn-ghost btn-sm gap-1.5 text-error">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            {{ __('Đặt lại') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0 overflow-hidden tabulator-daisy">
                <div id="qr-report-table"></div>
            </div>
        </div>
    </div>
</div>

<dialog id="resolveModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-success">{{ __('Đánh dấu đã xử lý') }}</h3>
        <p class="py-3 text-sm text-base-content/70">{{ __('Xác nhận phản ánh') }} <strong data-resolve-title class="text-base-content"></strong> {{ __('đã được kiểm tra và xử lý?') }}</p>
        <div class="modal-action mt-4">
            <button type="button" data-resolve-confirm class="btn btn-success btn-sm">{{ __('Đã xử lý') }}</button>
            <button type="button" class="btn btn-ghost btn-sm" onclick="resolveModal.close()">{{ __('Hủy') }}</button>
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
        'resources/js/modules/tom-select.js',
        'resources/js/pages/qr-report-index.js',
    ], 'build/backend')
@endpush
