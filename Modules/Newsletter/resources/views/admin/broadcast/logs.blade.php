@extends('layouts.backend')
@section('title', 'Lịch sử gửi bản tin')

@section('content')
<div x-data="broadcastLogListPage({{ Js::from([
    'apiUrl' => route('backend.api.newsletter.broadcast-logs'),
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
        <h1 class="text-2xl font-bold text-base-content">Lịch sử gửi bản tin</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Số liệu mở/click chi tiết xem trực tiếp trên Resend Dashboard.</p>
    </div>
    @can('sendBroadcast', \Modules\Newsletter\Models\NewsletterBroadcastLog::class)
    <a href="{{ route('backend.newsletter.broadcast.create') }}" class="btn btn-primary btn-sm gap-1.5">
        + Soạn bản tin mới
    </a>
    @endcan
</div>

{{-- ── Search ───────────────────────────────────────────────────────── --}}
<div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
    <div class="card-body py-3 px-4">
        <div class="form-control max-w-sm">
            <label class="label py-0.5"><span class="label-text text-xs font-medium">Tìm kiếm</span></label>
            <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" x-model="filters.search" @input.debounce.350ms="onFilterChange()"
                       placeholder="Nhập chủ đề bản tin..." class="grow bg-transparent outline-none text-sm">
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

{{-- ── Tabulator table ──────────────────────────────────────────────── --}}
<div class="card bg-base-100 shadow-sm border border-base-200">
    <div class="card-body p-0 overflow-hidden tabulator-daisy">
        <div id="broadcast-log-table"></div>
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
        'Modules/Newsletter/resources/assets/js/newsletter.js',
    ], 'build/backend')
@endpush
