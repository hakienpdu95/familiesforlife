@extends('layouts.backend')
@section('title', 'Workflow Automation')


@section('content')
<div x-data="wfListPage">

    {{-- ── Page header ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Workflow Automation</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Tự động hóa quy trình theo sự kiện</p>
        </div>
        <div class="flex items-center gap-2">
            @can(\App\Enums\PermissionEnum::WORKFLOW_EDIT->value)
            <a href="{{ route('workflows.create') }}" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tạo Workflow
            </a>
            @endcan
        </div>
    </div>

    {{-- ── Flash messages ────────────────────────────────────────────────────── --}}
    @foreach(['success','info','error'] as $type)
    @if(session($type))
    <div class="alert alert-{{ $type }} text-sm py-2 px-4 rounded-lg mb-4">{{ session($type) }}</div>
    @endif
    @endforeach

    {{-- ── Filter bar ──────────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3 px-4">
            <div class="flex flex-wrap gap-3 items-end">
                <div class="form-control flex-1 min-w-52">
                    <label class="label py-0.5">
                        <span class="label-text text-xs font-medium">Tìm kiếm</span>
                    </label>
                    <div class="input input-sm input-bordered flex items-center gap-2 bg-base-100">
                        <svg class="w-3.5 h-3.5 text-base-content/40 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" x-model="filters.search" @input.debounce.350ms="refresh()"
                               placeholder="Tên, trigger type..." class="grow bg-transparent outline-none text-sm"/>
                    </div>
                </div>
                <div class="form-control w-44">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Trạng thái</span></label>
                    <select class="select select-sm select-bordered" x-model="filters.is_active" @change="refresh()">
                        <option value="">Tất cả</option>
                        <option value="1">Đang bật</option>
                        <option value="0">Đang tắt</option>
                    </select>
                </div>
                <div class="form-control ml-auto">
                    <label class="label py-0.5 invisible"><span class="label-text text-xs">.</span></label>
                    <button @click="resetFilters()" x-show="hasFilters" x-transition
                            class="btn btn-ghost btn-sm gap-1.5 text-error">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Đặt lại
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Tabulator table ──────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-0 overflow-hidden rounded-2xl tabulator-daisy">
            <div id="wf-table"></div>
        </div>
    </div>

</div>

{{-- ── Delete confirm modal ──────────────────────────────────────────────────── --}}
<dialog id="wfDeleteModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xóa</h3>
        <p class="py-3 text-sm text-base-content/70">
            Bạn có chắc muốn xóa workflow
            <strong id="wfDeleteName" class="text-base-content"></strong>?
        </p>
        <div class="modal-action mt-4">
            <button id="wfConfirmDelete" class="btn btn-error btn-sm">Xóa</button>
            <button class="btn btn-ghost btn-sm" onclick="wfDeleteModal.close()">Hủy</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endsection

@push('styles')
<x-tabulator-theme />
@endpush

@push('scripts')
<script id="wf-list-data" type="application/json">{!! json_encode([
    'apiUrl'    => route('backend.api.workflows.index'),
    'csrf'      => csrf_token(),
    'canEdit'   => auth()->user()->can(\App\Enums\PermissionEnum::WORKFLOW_EDIT->value),
    'canDelete' => auth()->user()->can(\App\Enums\PermissionEnum::WORKFLOW_FULL_CONFIG->value),
]) !!}</script>
@vite(['resources/js/modules/tabulator.js', 'Modules/WorkflowAutomation/resources/assets/js/workflow-automation.js'], 'build/backend')
@endpush
