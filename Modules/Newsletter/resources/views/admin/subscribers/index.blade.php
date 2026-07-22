@extends('layouts.backend')
@section('title', 'Người đăng ký bản tin')

@section('content')
<div x-data="subscriberListPage({{ Js::from([
    'apiUrl' => route('backend.api.newsletter.subscribers'),
]) }})">

@foreach(['success','error'] as $type)
    @if(session($type))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         x-transition.opacity.duration.500ms class="alert alert-{{ $type }} mb-4 text-sm">
        <span>{{ session($type) }}</span>
        <button @click="show = false" class="btn btn-ghost btn-xs ml-auto">✕</button>
    </div>
    @endif
@endforeach

<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Người đăng ký bản tin</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Đồng bộ 2 chiều với Resend Contacts.</p>
    </div>
    @can('sendBroadcast', \Modules\Newsletter\Models\NewsletterBroadcastLog::class)
    <a href="{{ route('backend.newsletter.broadcast.create') }}" class="btn btn-primary btn-sm gap-1.5">
        + Soạn bản tin
    </a>
    @endcan
</div>

{{-- ── Filter bar ───────────────────────────────────────────────────── --}}
<div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
    <div class="card-body py-3 px-4">
        <div class="flex flex-wrap gap-3 items-end">

            <div class="form-control flex-1 min-w-52">
                <label class="label py-0.5">
                    <span class="label-text text-xs font-medium">Tìm kiếm</span>
                    <span class="label-text-alt text-xs text-base-content/40">Họ tên, email</span>
                </label>
                <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                    <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" x-model="filters.search" @input.debounce.350ms="onFilterChange()"
                           placeholder="Nhập họ tên/email..." class="grow bg-transparent outline-none text-sm">
                    <button x-show="filters.search" @click="clearSearch()"
                            class="text-base-content/30 hover:text-base-content transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="form-control w-56">
                <label class="label py-0.5"><span class="label-text text-xs font-medium">Trạng thái</span></label>
                <select x-model="filters.status" @change="onFilterChange()" class="select select-sm select-bordered w-full">
                    <option value="">— Tất cả trạng thái —</option>
                    @foreach(\Modules\Newsletter\Enums\SubscriberStatus::cases() as $s)
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
        <div id="subscriber-table"></div>
    </div>
</div>

</div>

{{-- ── Delete confirm modal ─────────────────────────────────────────────── --}}
<dialog id="subscriberDeleteModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xoá</h3>
        <p class="py-3 text-sm text-base-content/70">
            Bạn có chắc muốn xoá subscriber <strong id="subscriberDeleteItemEmail" class="text-base-content"></strong>?
            Người này sẽ ngừng nhận bản tin.
        </p>
        <div class="modal-action mt-4">
            <button id="subscriberConfirmDeleteBtn" class="btn btn-error btn-sm">Xoá</button>
            <button class="btn btn-ghost btn-sm" onclick="subscriberDeleteModal.close()">Hủy</button>
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
        'Modules/Newsletter/resources/assets/js/newsletter.js',
    ], 'build/backend')
@endpush
