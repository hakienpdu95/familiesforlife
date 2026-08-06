@extends('layouts.backend')
@section('title', 'Log n8n')

@section('content')
<div x-data="n8nLogsPage({{ Js::from([
    'inboundApiUrl'  => route('backend.api.n8n.logs.inbound'),
    'outboundApiUrl' => route('backend.api.n8n.logs.outbound'),
]) }})">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h1 class="text-2xl font-bold text-base-content">Log n8n</h1>
            <p class="text-sm text-base-content/50 mt-0.5">Audit cả 2 chiều — inbound ghi cả lệnh gọi KHÔNG khớp gì (sai token/chữ ký), outbound ghi mọi lần gọi ra</p>
        </div>
        <a href="{{ route('backend.n8n.connections.index') }}" class="btn btn-ghost btn-sm gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Danh sách kết nối
        </a>
    </div>

    {{-- ── Tabs (chiều) ─────────────────────────────────────────────────── --}}
    <div role="tablist" class="tabs tabs-boxed mb-4 w-fit">
        <a role="tab" class="tab" :class="direction === 'inbound' ? 'tab-active' : ''" @click="switchDirection('inbound')">Inbound (n8n &rarr; app)</a>
        <a role="tab" class="tab" :class="direction === 'outbound' ? 'tab-active' : ''" @click="switchDirection('outbound')">Outbound (app &rarr; n8n)</a>
    </div>

    {{-- ── Filter bar ───────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-4">
        <div class="card-body py-3 px-4">
            <div class="flex flex-wrap gap-3 items-end">

                <div class="form-control w-64">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Kết nối</span></label>
                    <select x-model="filters.connection_id" @change="onFilterChange()" class="select select-sm select-bordered w-full">
                        <option value="">— Tất cả kết nối —</option>
                        @foreach($connections as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}{{ $c->deleted_at ? ' (đã xoá)' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control w-56" x-show="direction === 'inbound'">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Chữ ký</span></label>
                    <select x-model="filters.signature_valid" @change="onFilterChange()" class="select select-sm select-bordered w-full">
                        <option value="">— Tất cả —</option>
                        <option value="1">Hợp lệ</option>
                        <option value="0">Không hợp lệ / thất bại</option>
                    </select>
                </div>

                <div class="form-control w-56" x-show="direction === 'outbound'">
                    <label class="label py-0.5"><span class="label-text text-xs font-medium">Trạng thái</span></label>
                    <select x-model="filters.success" @change="onFilterChange()" class="select select-sm select-bordered w-full">
                        <option value="">— Tất cả —</option>
                        <option value="1">Thành công</option>
                        <option value="0">Thất bại</option>
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

    {{-- ── Tabulator tables (2, chỉ 1 hiện tại 1 thời điểm) ────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200" x-show="direction === 'inbound'">
        <div class="card-body p-0 overflow-hidden tabulator-daisy">
            <div id="n8n-inbound-logs-table"></div>
        </div>
    </div>
    <div class="card bg-base-100 shadow-sm border border-base-200" x-show="direction === 'outbound'" x-cloak>
        <div class="card-body p-0 overflow-hidden tabulator-daisy">
            <div id="n8n-outbound-logs-table"></div>
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
        'Modules/N8n/resources/assets/js/n8n.js',
    ], 'build/backend')
@endpush
