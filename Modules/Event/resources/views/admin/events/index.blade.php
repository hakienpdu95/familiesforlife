@extends('layouts.backend')
@section('title', 'Sự kiện')

@section('content')
<div x-data="eventListPage({{ Js::from([
    'apiUrl' => route('backend.api.events.items'),
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
            <h1 class="text-2xl font-bold text-base-content">Sự kiện</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Sự kiện độc giả nộp qua cổng thông tin, hoặc toà soạn/vận hành tự nhập trực tiếp</p>
        </div>
        <div class="flex items-center gap-2">
            @can('event_category.manage')
            <a href="{{ route('backend.event.categories.index') }}" class="btn btn-ghost btn-sm">Danh mục sự kiện</a>
            @endcan
            @can('create', \Modules\Event\Models\Event::class)
            <a href="{{ route('backend.event.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm sự kiện
            </a>
            @endcan
        </div>
    </div>

    {{-- ── Filter bar ───────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3 px-4">
            <div class="flex flex-wrap gap-3 items-end">

                <div class="form-control flex-1 min-w-52">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Tìm kiếm</span></label>
                    <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                        <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" x-model="filters.search" @input.debounce.350ms="onFilterChange()"
                               placeholder="Nhập tiêu đề sự kiện..." class="grow bg-transparent outline-none text-sm">
                        <button x-show="filters.search" @click="clearSearch()"
                                class="text-base-content/30 hover:text-base-content transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-control w-52">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Trạng thái</span></label>
                    <select x-model="filters.status" @change="onFilterChange()" class="select select-sm select-bordered w-full">
                        <option value="">— Tất cả trạng thái —</option>
                        @foreach(\Modules\Event\Enums\EventStatus::cases() as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
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
            <div id="event-table"></div>
        </div>
    </div>

</div>

{{-- ── Modal Từ chối — dùng chung 1 modal cho mọi hàng (window.eventRejectConfirm) ────── --}}
<dialog id="eventRejectModal" class="modal">
    <div class="modal-box">
        <h3 class="font-bold text-lg">Từ chối sự kiện</h3>
        <p class="text-sm text-base-content/60 mt-1" id="eventRejectTitle"></p>
        <div class="form-control mt-4">
            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Lý do từ chối <span class="text-error">*</span></span></label>
            <textarea id="eventRejectReason" rows="3" maxlength="255"
                      class="textarea textarea-bordered textarea-sm w-full"
                      placeholder="VD: Thiếu thông tin địa điểm, thông tin chưa chính xác..."></textarea>
            <p class="text-xs text-base-content/40 mt-1">Lý do này sẽ được gửi qua email cho người nộp.</p>
        </div>
        <div class="modal-action mt-4">
            <button id="eventConfirmRejectBtn" class="btn btn-error btn-sm">Từ chối sự kiện</button>
            <button class="btn btn-ghost btn-sm" onclick="eventRejectModal.close()">Huỷ</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>

{{-- ── Modal Lưu trữ — thay window.confirm() bằng modal chung ─────────────────────── --}}
<dialog id="eventArchiveModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg">Lưu trữ sự kiện</h3>
        <p class="py-3 text-sm text-base-content/70">
            Lưu trữ <strong id="eventArchiveTitle" class="text-base-content"></strong>?
            Sự kiện sẽ không còn hiển thị công khai.
        </p>
        <div class="modal-action mt-4">
            <button id="eventConfirmArchiveBtn" class="btn btn-error btn-sm">Lưu trữ sự kiện</button>
            <button class="btn btn-ghost btn-sm" onclick="eventArchiveModal.close()">Huỷ</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endsection

@push('styles')
    <x-tabulator-theme />
    @vite(['Modules/Event/resources/assets/sass/event.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/tabulator.js',
        'Modules/Event/resources/assets/js/event.js',
    ], 'build/backend')
@endpush
