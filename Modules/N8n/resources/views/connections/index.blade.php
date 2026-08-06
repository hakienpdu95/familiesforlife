@extends('layouts.backend')
@section('title', 'Kết nối n8n')

@section('content')
<div x-data="n8nConnectionsListPage({{ Js::from([
    'apiUrl'    => route('backend.api.n8n.connections'),
    'canManage' => auth()->user()?->can('manage-n8n') ?? false,
]) }})">

    @foreach(['success','error'] as $type)
        @if(session($type))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)"
             x-transition.opacity.duration.500ms
             class="alert alert-{{ $type }} mb-4 text-sm">
            <span>{{ session($type) }}</span>
            <button @click="show = false" class="btn btn-ghost btn-xs ml-auto">✕</button>
        </div>
        @endif
    @endforeach

    {{-- spec §3.2 — hiển thị plaintext inbound_token/inbound_secret ĐÚNG 1 LẦN ngay sau khi
         tạo mới, đọc từ flash session (n8n_reveal) do N8nConnectionController::store() gán. --}}
    @if(session('n8n_reveal'))
    @php($reveal = session('n8n_reveal'))
    <div class="alert py-4 px-4 mb-5 bg-warning/10 border border-warning/40 flex-col items-start gap-2">
        <div class="flex items-center gap-2 font-semibold text-warning-content">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Sao chép ngay — sẽ không hiển thị lại sau khi rời trang này
        </div>
        <div class="grid gap-2 w-full text-sm">
            <div class="flex items-center gap-2">
                <span class="w-32 shrink-0 text-xs text-base-content/50">Webhook URL</span>
                <code class="flex-1 bg-base-200 rounded px-2 py-1 text-xs overflow-x-auto">{{ url('api/n8n/in/' . $reveal['inbound_token']) }}</code>
                <button type="button" class="btn btn-xs" onclick="navigator.clipboard.writeText('{{ url('api/n8n/in/' . $reveal['inbound_token']) }}')">Copy</button>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-32 shrink-0 text-xs text-base-content/50">Inbound secret</span>
                <code class="flex-1 bg-base-200 rounded px-2 py-1 text-xs overflow-x-auto">{{ $reveal['inbound_secret'] }}</code>
                <button type="button" class="btn btn-xs" onclick="navigator.clipboard.writeText('{{ $reveal['inbound_secret'] }}')">Copy</button>
            </div>
        </div>
    </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Kết nối n8n</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Hạ tầng tích hợp thuộc hệ thống — inbound webhook có xác thực + outbound theo kết nối đặt tên</p>
        </div>
        <div class="flex items-center gap-2">
            @can('manage-n8n')
            <a href="{{ route('backend.n8n.connections.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Thêm kết nối
            </a>
            @endcan
            <a href="{{ route('backend.n8n.logs.index') }}" class="btn btn-ghost btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Xem log
            </a>
        </div>
    </div>

    {{-- ── Filter bar ───────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3 px-4">
            <div class="flex flex-wrap gap-3 items-end">
                <div class="form-control w-64">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Tìm theo tên/ghi chú</span></label>
                    <input type="text" x-model.debounce.400ms="filters.search"
                           class="input input-sm input-bordered w-full" placeholder="Nhập tên kết nối...">
                </div>
                <label class="flex items-center gap-2 cursor-pointer select-none mb-1.5">
                    <input type="checkbox" x-model="filters.include_trashed" class="checkbox checkbox-sm">
                    <span class="text-xs font-medium">Hiện cả kết nối đã xoá</span>
                </label>
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
            <div id="n8n-connections-table"></div>
        </div>
    </div>

</div>

{{-- ── Delete confirm modal ─────────────────────────────────────────────── --}}
<dialog id="n8nConnectionDeleteModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xoá</h3>
        <p class="py-3 text-sm text-base-content/70">Xoá mềm kết nối này — tên sẽ KHÔNG thể dùng lại cho kết nối mới, log lịch sử vẫn giữ nguyên. Có thể khôi phục lại sau.</p>
        <div class="modal-action mt-4">
            <button id="n8nConnectionConfirmDeleteBtn" class="btn btn-error btn-sm">Xoá</button>
            <button class="btn btn-ghost btn-sm" onclick="n8nConnectionDeleteModal.close()">Hủy</button>
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
        'Modules/N8n/resources/assets/js/n8n.js',
    ], 'build/backend')
@endpush
