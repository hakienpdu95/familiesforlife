@extends('layouts.backend')
@section('title', 'Thêm cơ hội mới')


@section('content')
<div x-data="{
    tab: 'customer',
    tabFields: {
        customer:    ['contact_name', 'organization_id'],
        opportunity: [],
        classify:    ['tag_ids']
    },
    errs: {{ Js::from($errors->keys()) }},
    errCount(t) {
        return this.tabFields[t].filter(f => this.errs.includes(f)).length;
    },
    init() {
        const order = ['customer', 'opportunity', 'classify'];
        for (const t of order) {
            if (this.errCount(t) > 0) { this.tab = t; break; }
        }
    }
}">

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-base-content">Thêm cơ hội mới</h1>
        <p class="text-sm text-base-content/50 mt-0.5">Tạo cơ hội kinh doanh và bắt đầu theo dõi</p>
    </div>
    <a href="{{ route('lead.index') }}" class="btn btn-ghost btn-sm gap-1.5">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Quay lại
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

<form method="POST" action="{{ route('lead.store') }}" novalidate data-lead-form>
    @csrf

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

        {{-- ── Card chính: tab nav + panels ───────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">

            {{-- Tab navigation --}}
            <div class="border-b border-base-200 px-6">
                <nav class="flex -mb-px" role="tablist" aria-label="Form sections">

                    <button type="button" role="tab" :aria-selected="tab === 'customer'"
                            @click="tab = 'customer'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'customer'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Khách hàng
                        <span x-show="errCount('customer') > 0" x-text="errCount('customer')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'opportunity'"
                            @click="tab = 'opportunity'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'opportunity'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Cơ hội
                        <span x-show="errCount('opportunity') > 0" x-text="errCount('opportunity')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                    <button type="button" role="tab" :aria-selected="tab === 'classify'"
                            @click="tab = 'classify'"
                            class="flex items-center gap-1.5 px-1 py-4 text-sm font-medium border-b-2 transition-colors"
                            :class="tab === 'classify'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        Phân loại
                        <span x-show="errCount('classify') > 0" x-text="errCount('classify')"
                              class="badge badge-error badge-xs"></span>
                    </button>

                </nav>
            </div>

            {{-- Tab panels --}}
            <div class="p-6">

                {{-- ── Panel: Khách hàng ─────────────────────────────── --}}
                <div x-show="tab === 'customer'" data-tab-label="Khách hàng" class="space-y-4">

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
                                    <option value="{{ $org->id }}" {{ old('organization_id', $defaultOrgId ?? '') == $org->id ? 'selected' : '' }}>
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
                            <span class="label-text font-medium">Họ và tên <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="contact_name"
                               value="{{ old('contact_name') }}"
                               data-req="Vui lòng nhập họ và tên khách hàng"
                               class="input input-bordered input-sm w-full @error('contact_name') input-error @enderror"
                               placeholder="VD: Nguyễn Văn A" autofocus>
                        @error('contact_name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Điện thoại chính</span>
                            </label>
                            <input type="text" name="contact_phone"
                                   value="{{ old('contact_phone') }}"
                                   class="input input-bordered input-sm w-full @error('contact_phone') input-error @enderror"
                                   placeholder="0901 234 567">
                            @error('contact_phone')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Điện thoại phụ</span>
                            </label>
                            <input type="text" name="contact_phone_alt"
                                   value="{{ old('contact_phone_alt') }}"
                                   class="input input-bordered input-sm w-full"
                                   placeholder="0901 234 568">
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Email</span>
                        </label>
                        <input type="email" name="contact_email"
                               value="{{ old('contact_email') }}"
                               data-val-email="Email không đúng định dạng"
                               class="input input-bordered input-sm w-full @error('contact_email') input-error @enderror"
                               placeholder="email@company.com">
                        @error('contact_email')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Công ty</span>
                            </label>
                            <input type="text" name="contact_company"
                                   value="{{ old('contact_company') }}"
                                   class="input input-bordered input-sm w-full"
                                   placeholder="VD: Công ty TNHH ABC">
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Chức danh</span>
                            </label>
                            <input type="text" name="contact_job_title"
                                   value="{{ old('contact_job_title') }}"
                                   class="input input-bordered input-sm w-full"
                                   placeholder="VD: Giám đốc, Trưởng phòng...">
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Website</span>
                        </label>
                        <input type="url" name="contact_website"
                               value="{{ old('contact_website') }}"
                               data-val-url="URL không hợp lệ — phải bắt đầu bằng https://"
                               class="input input-bordered input-sm w-full @error('contact_website') input-error @enderror"
                               placeholder="https://company.vn">
                        @error('contact_website')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="divider my-1 text-xs text-base-content/30">Địa chỉ</div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Tỉnh / Thành phố</span>
                            </label>
                            <select id="ts-province" name="province_code"
                                    class="select select-bordered select-sm w-full @error('province_code') select-error @enderror"
                                    data-selected-province="{{ old('province_code', '') }}">
                                <option value="">Chọn tỉnh / thành phố...</option>
                                @foreach ($provinces as $p)
                                <option value="{{ $p->province_code }}"
                                        {{ old('province_code') === $p->province_code ? 'selected' : '' }}>
                                    {{ $p->name }}
                                </option>
                                @endforeach
                            </select>
                            <input type="hidden" name="province_name" id="lead-province-name"
                                   value="{{ old('province_name') }}">
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Phường / Xã</span>
                            </label>
                            <select id="ts-ward" name="ward_code"
                                    class="select select-bordered select-sm w-full @error('ward_code') select-error @enderror"
                                    data-ward-init="{{ old('ward_code', '') }}"
                                    data-ward-name-init="{{ old('ward_name', '') }}"
                                    {{ !old('province_code') ? 'disabled' : '' }}>
                                <option value="">Chọn phường / xã...</option>
                            </select>
                            <input type="hidden" name="ward_name" id="lead-ward-name"
                                   value="{{ old('ward_name') }}">
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Địa chỉ cụ thể</span>
                            <span class="label-text-alt text-xs text-base-content/40">Số nhà, tên đường...</span>
                        </label>
                        <input type="text" name="contact_address"
                               value="{{ old('contact_address') }}"
                               class="input input-bordered input-sm w-full"
                               placeholder="VD: 123 Nguyễn Trãi, Phường Bến Thành">
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="button" @click="tab = 'opportunity'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Cơ hội
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- ── Panel: Cơ hội ─────────────────────────────────── --}}
                <div x-show="tab === 'opportunity'" data-tab-label="Cơ hội" class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tiêu đề cơ hội</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <input type="text" name="title"
                               value="{{ old('title') }}"
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
                                <option value="{{ $source->id }}" {{ old('source_id') == $source->id ? 'selected' : '' }}>
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
                                   value="{{ old('source_detail') }}"
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
                                       value="{{ old('expected_value') }}"
                                       min="0" step="1000"
                                       class="input input-bordered input-sm join-item flex-1"
                                       placeholder="0">
                                <select name="currency"
                                        class="select select-bordered select-sm join-item w-24">
                                    <option value="VND" {{ old('currency', 'VND') === 'VND' ? 'selected' : '' }}>VND</option>
                                    <option value="USD" {{ old('currency') === 'USD'         ? 'selected' : '' }}>USD</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Ngày chốt dự kiến</span>
                            </label>
                            <input type="text" name="expected_close_date"
                                   id="lead-close-date"
                                   value="{{ old('expected_close_date') }}"
                                   class="input input-bordered input-sm w-full @error('expected_close_date') input-error @enderror"
                                   placeholder="DD/MM/YYYY" readonly>
                            @error('expected_close_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'customer'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Khách hàng
                        </button>
                        <button type="button" @click="tab = 'classify'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Phân loại
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>

                </div>

                {{-- ── Panel: Phân loại ──────────────────────────────── --}}
                <div x-show="tab === 'classify'" data-tab-label="Phân loại" class="space-y-4">

                    @if($tags->count())
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tags</span>
                        </label>
                        <select id="ts-tag-ids" name="tag_ids[]" multiple
                                class="select select-bordered select-sm w-full @error('tag_ids') select-error @enderror"
                                data-ts-placeholder="— Chọn tags —">
                            @foreach($tags as $tag)
                            <option value="{{ $tag->id }}"
                                    {{ in_array($tag->id, old('tag_ids', [])) ? 'selected' : '' }}>
                                {{ $tag->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('tag_ids')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>
                    @endif

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mô tả / Ghi chú</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <textarea name="description" rows="5"
                                  class="textarea textarea-bordered textarea-sm w-full"
                                  placeholder="Mô tả chi tiết về cơ hội này...">{{ old('description') }}</textarea>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = 'opportunity'"
                                class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Cơ hội
                        </button>
                        <span class="text-xs text-base-content/40">
                            Nhấn <strong>Tạo cơ hội</strong> ở bên phải khi xong
                        </span>
                    </div>

                </div>

            </div>{{-- /tab panels --}}
        </div>{{-- /card chính --}}

        {{-- ── Sidebar ──────────────────────────────────────────────── --}}
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
                            <option value="">— Chọn tình trạng —</option>
                            @foreach($stages as $stage)
                            <option value="{{ $stage->id }}" {{ old('stage_id') == $stage->id ? 'selected' : '' }}>
                                {{ $stage->label }}{{ $stage->probability ? ' ('.$stage->probability.'%)' : '' }}
                            </option>
                            @endforeach
                        </select>
                        @error('stage_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    @can('assign', \Modules\Lead\Models\Lead::class)
                    <div class="form-control mb-3">
                        <label class="label py-0 pb-1">
                            <span class="label-text text-xs font-medium">Người phụ trách</span>
                        </label>
                        <select name="assigned_to" id="lead-assigned"
                                class="select select-bordered select-sm w-full"
                                data-assignable-url="{{ route('api.api.lead.assignable-users') }}">
                            <option value="">— Chưa phân công —</option>
                        </select>
                    </div>
                    @endcan

                    <div class="divider my-2 text-xs text-base-content/30"></div>

                    <div class="flex gap-2">
                        <a href="{{ route('lead.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tạo cơ hội
                        </button>
                    </div>

                    <p class="text-center text-xs text-base-content/30 mt-2.5">
                        <span class="text-error">*</span> là trường bắt buộc
                    </p>

                </div>
            </div>

        </div>{{-- /sidebar --}}

    </div>{{-- /grid --}}

</form>
</div>{{-- /x-data --}}
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
