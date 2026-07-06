@extends('layouts.backend')
@section('title', 'Chỉnh sửa: ' . $lead->displayTitle())


@section('content')

{{-- Delete form — ngoài main form để tránh lồng form, submit bằng JS --}}
@can('delete', $lead)
<form id="form-delete-lead" method="POST" action="{{ route('lead.destroy', $lead) }}" class="hidden">
    @csrf
    @method('DELETE')
</form>
@endcan

{{-- Page header --}}
<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Chỉnh sửa cơ hội</h1>
        <p class="text-sm text-base-content/50 mt-0.5 flex items-center gap-2">
            {{ $lead->displayTitle() }}
            @if($lead->stage)
                <span class="badge badge-ghost badge-sm">{{ $lead->stage->label }}</span>
            @endif
        </p>
    </div>
    <a href="{{ route('lead.show', $lead) }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
    </a>
</div>

{{-- Contact banner — read-only --}}
<div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-base-200/60 border border-base-200 mb-5">
    <div class="w-9 h-9 rounded-full bg-primary/15 text-primary font-bold text-sm flex items-center justify-center shrink-0 select-none">
        {{ mb_strtoupper(mb_substr($lead->contact_name, 0, 1)) }}
    </div>
    <div class="flex-1 min-w-0">
        <p class="font-semibold text-sm truncate">{{ $lead->contact_name }}</p>
        <p class="text-xs text-base-content/50 truncate">
            @if($lead->contact_phone){{ $lead->contact_phone }}@endif
            @if($lead->contact_phone && $lead->contact_company) &bull; @endif
            @if($lead->contact_company){{ $lead->contact_company }}@endif
        </p>
    </div>
    <a href="{{ route('lead.show', $lead) }}" class="badge badge-ghost badge-sm shrink-0 hover:badge-primary transition-colors">
        Xem chi tiết →
    </a>
</div>

@if($errors->any())
<div class="alert alert-error py-3 px-4 mb-5 flex items-start gap-3 text-sm">
    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
    </svg>
    <div>
        <p class="font-semibold">Có {{ $errors->count() }} lỗi cần kiểm tra:</p>
        <ul class="mt-1.5 list-disc list-inside space-y-0.5 text-xs opacity-90">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
</div>
@endif

<form method="POST" action="{{ route('lead.update', $lead) }}"
      novalidate data-lead-form>
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Main card ────────────────────────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Chi tiết cơ hội
                </h2>

                <div class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control sm:col-span-2">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tổ chức <span class="text-error">*</span></span>
                            </label>
                            @if($orgLocked)
                                <input type="hidden" name="organization_id" value="{{ $organizations->first()->id }}">
                                <input type="text" value="{{ $organizations->first()->name }}" readonly
                                       class="input input-bordered input-sm w-full bg-base-200 cursor-not-allowed">
                                <p class="mt-1 text-xs text-base-content/40">Xác định từ tài khoản của bạn.</p>
                            @else
                                <select id="ts-organization" name="organization_id"
                                        class="select select-bordered select-sm w-full ts-init @error('organization_id') select-error @enderror"
                                        data-ts-placeholder="— Chọn tổ chức —"
                                        data-req="Vui lòng chọn tổ chức">
                                    <option value="">— Chọn tổ chức —</option>
                                    @foreach($organizations as $org)
                                    <option value="{{ $org->id }}" {{ old('organization_id', $lead->organization_id) == $org->id ? 'selected' : '' }}>
                                        {{ $org->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @error('organization_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            @endif
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tiêu đề cơ hội</span>
                        </label>
                        <input type="text" name="title"
                               value="{{ old('title', $lead->title) }}"
                               class="input input-bordered input-sm w-full"
                               placeholder="VD: Tư vấn giải pháp ERP — ABC Corp">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Nguồn</span>
                            </label>
                            <select name="source_id" id="lead-source"
                                    class="select select-bordered select-sm w-full">
                                <option value="">— Chọn nguồn —</option>
                                @foreach($sources as $source)
                                <option value="{{ $source->id }}"
                                        {{ old('source_id', $lead->source_id) == $source->id ? 'selected' : '' }}>
                                    {{ $source->label }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Chi tiết nguồn</span>
                            </label>
                            <input type="text" name="source_detail"
                                   value="{{ old('source_detail', $lead->source_detail) }}"
                                   class="input input-bordered input-sm w-full"
                                   placeholder="VD: Giới thiệu bởi anh Nguyễn...">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Giá trị dự kiến</span>
                            </label>
                            <div class="join w-full">
                                <input type="number" name="expected_value"
                                       value="{{ old('expected_value', $lead->expected_value) }}"
                                       min="0" step="1000"
                                       class="input input-bordered input-sm join-item flex-1"
                                       placeholder="0">
                                <select name="currency"
                                        class="select select-bordered select-sm join-item w-24">
                                    <option value="VND" {{ old('currency', $lead->currency) === 'VND' ? 'selected' : '' }}>VND</option>
                                    <option value="USD" {{ old('currency', $lead->currency) === 'USD' ? 'selected' : '' }}>USD</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ngày chốt dự kiến</span>
                            </label>
                            <input type="text" name="expected_close_date"
                                   id="lead-close-date"
                                   value="{{ old('expected_close_date', $lead->expected_close_date?->format('d/m/Y')) }}"
                                   class="input input-bordered input-sm w-full @error('expected_close_date') input-error @enderror"
                                   placeholder="DD/MM/YYYY" readonly>
                            @error('expected_close_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mô tả / Ghi chú</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <textarea name="description" rows="5"
                                  class="textarea textarea-bordered textarea-sm w-full"
                                  placeholder="Mô tả chi tiết về cơ hội này...">{{ old('description', $lead->description) }}</textarea>
                    </div>

                </div>
            </div>
        </div>{{-- /main card --}}

        {{-- ── Sidebar ────────────────────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">

            {{-- Pipeline & Submit --}}
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
                        Pipeline
                    </p>

                    <div class="form-control mb-3">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">
                                Tình trạng <span class="text-error">*</span>
                            </span>
                        </label>
                        <select name="stage_id" id="lead-stage"
                                class="select select-bordered select-sm w-full @error('stage_id') select-error @enderror">
                            @foreach($stages as $stage)
                            <option value="{{ $stage->id }}"
                                    {{ old('stage_id', $lead->stage_id) == $stage->id ? 'selected' : '' }}>
                                {{ $stage->label }}{{ $stage->probability ? ' ('.$stage->probability.'%)' : '' }}
                            </option>
                            @endforeach
                        </select>
                        @error('stage_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    @can('assign', $lead)
                    <div class="form-control mb-3">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">Người phụ trách</span>
                        </label>
                        <select name="assigned_to" id="lead-assigned"
                                class="select select-bordered select-sm w-full"
                                data-assignable-url="{{ route('api.api.lead.assignable-users') }}">
                            @if($lead->assignee)
                                <option value="{{ $lead->assigned_to }}" selected>{{ $lead->assignee->name }}</option>
                            @else
                                <option value="">— Chưa phân công —</option>
                            @endif
                        </select>
                    </div>
                    @endcan

                    <div class="flex justify-between text-xs text-base-content/40 mb-4 px-0.5">
                        <span>Tạo {{ $lead->created_at->format('d/m/Y') }}</span>
                        <span>Sửa {{ $lead->updated_at->diffForHumans() }}</span>
                    </div>

                    <div class="divider my-2 text-xs text-base-content/30"></div>

                    <div class="flex gap-2">
                        <a href="{{ route('lead.show', $lead) }}"
                           class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Lưu lại
                        </button>
                    </div>

                    <p class="text-center text-xs text-base-content/30 mt-2.5">
                        <span class="text-error">*</span> là trường bắt buộc
                    </p>

                </div>
            </div>

            {{-- Danger zone --}}
            @can('delete', $lead)
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-4">
                    <p class="text-xs font-semibold text-error/60 uppercase tracking-wide mb-2">
                        Thao tác nguy hiểm
                    </p>
                    <button type="button"
                            onclick="deleteLeadModal.showModal()"
                            class="btn btn-ghost btn-sm w-full text-error gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Xóa cơ hội này
                    </button>
                </div>
            </div>
            @endcan

        </div>{{-- /sidebar --}}

    </div>{{-- /grid --}}

</form>

{{-- Delete confirm modal --}}
@can('delete', $lead)
<dialog id="deleteLeadModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Xác nhận xóa</h3>
        <p class="py-3 text-sm text-base-content/70">
            Bạn có chắc muốn xóa cơ hội
            <strong class="text-base-content">{{ $lead->displayTitle() }}</strong>?
        </p>
        <p class="text-xs text-error/70">Toàn bộ hoạt động, ghi chú và lịch sử liên quan sẽ bị xóa theo.</p>
        <div class="modal-action mt-4">
            <button class="btn btn-error btn-sm gap-1.5"
                    onclick="document.getElementById('form-delete-lead').submit()">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Xóa
            </button>
            <button class="btn btn-ghost btn-sm" onclick="deleteLeadModal.close()">Hủy</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endcan

@endsection

@push('styles')
    @vite(['Modules/Lead/resources/assets/sass/lead.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/flatpickr.js',
        'Modules/Lead/resources/assets/js/lead.js',
    ], 'build/backend')
@endpush
