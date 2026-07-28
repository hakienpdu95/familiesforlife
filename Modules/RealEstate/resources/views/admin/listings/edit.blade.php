@extends('layouts.backend')
@section('title', 'Sửa tin — ' . $listing->title)

@php
    $val = function (string $field, $default = null) use ($listing) {
        $raw = old($field, $listing->{$field});
        return $raw instanceof \BackedEnum ? $raw->value : ($raw ?? $default);
    };

    $status = $listing->approvalStatus();

    $tabs = [
        'basic'    => 'Loại tin & Cơ bản',
        'location' => 'Vị trí',
        'price'    => 'Giá',
        'area'     => 'Diện tích & phòng',
        'details'  => 'Đặc điểm',
        'media'    => 'Hình ảnh',
    ];
@endphp

@section('content')
<div x-data="{
        tab: 'basic',
        tabOrder: {{ Js::from(array_keys($tabs)) }},
        listingType: '{{ $val('listing_type', 'sale') }}',
        propertyType: '{{ $val('property_type', '') }}',
        isNegotiable: {{ $val('is_price_negotiable') ? 'true' : 'false' }},
        isUrgent: {{ $val('is_urgent') ? 'true' : 'false' }},
        tabFields: {
            basic:    ['listing_type', 'property_type', 'title'],
            location: ['province_code', 'ward_code'],
            price:    ['price', 'monthly_rent'],
            area:     ['area'],
            details:  [],
            media:    [],
        },
        errs: {{ Js::from($errors->keys()) }},
        errCount(t) { return this.tabFields[t].filter(f => this.errs.includes(f)).length; },
        next() { const i = this.tabOrder.indexOf(this.tab); return this.tabOrder[Math.min(i + 1, this.tabOrder.length - 1)]; },
        prev() { const i = this.tabOrder.indexOf(this.tab); return this.tabOrder[Math.max(i - 1, 0)]; },
        init() {
            for (const t of this.tabOrder) { if (this.errCount(t) > 0) { this.tab = t; break; } }
        }
    }">

<div class="flex items-center gap-2 text-sm text-base-content/50 mb-3">
    <a href="{{ route('backend.real-estate.index') }}" class="hover:text-primary">Bất động sản</a>
    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span>Sửa tin</span>
</div>

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <h1 class="text-2xl font-bold text-base-content truncate">{{ $listing->title }}</h1>
    @if($status)
    <span class="badge {{ $status->badgeClass() }}">{{ $status->label() }}</span>
    @endif
</div>

@foreach(['success','error'] as $type)
    @if(session($type))
    <div class="alert alert-{{ $type }} mb-4 text-sm"><span>{{ session($type) }}</span></div>
    @endif
@endforeach

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

{{-- ── Workflow duyệt (§5.5/§6 spec Bán) — Modules\Approval dùng chung, KHÔNG thuộc form này ──── --}}
<div class="card bg-base-100 border border-base-200 shadow-sm mb-6">
    <div class="card-body p-4 flex-row flex-wrap items-center gap-2">
        @can('submitForApproval', $listing)
        <form method="POST" action="{{ route('backend.real-estate.submit-approval', $listing) }}">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm">Gửi duyệt</button>
        </form>
        @endcan
        @can('approve', $listing)
        <form method="POST" action="{{ route('backend.real-estate.approve-content', $listing) }}">
            @csrf
            <button type="submit" class="btn btn-info btn-sm">Duyệt nội dung</button>
        </form>
        @endcan
        @can('publishApproval', $listing)
        <form method="POST" action="{{ route('backend.real-estate.publish-content', $listing) }}">
            @csrf
            <button type="submit" class="btn btn-success btn-sm">Xuất bản</button>
        </form>
        @endcan
        @can('reject', $listing)
        <button type="button" class="btn btn-error btn-outline btn-sm" onclick="rejectRealEstateModal.showModal()">Từ chối</button>
        @endcan
        @can('archiveApproval', $listing)
        <form method="POST" action="{{ route('backend.real-estate.archive-content', $listing) }}" onsubmit="return confirm('Lưu trữ tin này?')">
            @csrf
            <button type="submit" class="btn btn-ghost btn-sm">Lưu trữ</button>
        </form>
        @endcan
    </div>
</div>

<form method="POST" action="{{ route('backend.real-estate.update', $listing) }}" novalidate data-realestate-form>
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-6 items-start">

        {{-- ── Card chính với tab ───────────────────────────────────────── --}}
        <div class="card bg-base-100 shadow-sm border border-base-200">

            <div class="border-b border-base-200 px-3">
                <nav class="flex -mb-px overflow-x-auto" role="tablist" aria-label="Form sections">
                    @foreach($tabs as $key => $label)
                    <button type="button" role="tab" :aria-selected="tab === '{{ $key }}'"
                            @click="tab = '{{ $key }}'"
                            class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors whitespace-nowrap"
                            :class="tab === '{{ $key }}'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                        {{ $label }}
                        <span x-show="errCount('{{ $key }}') > 0" x-text="errCount('{{ $key }}')"
                              class="badge badge-error badge-xs"></span>
                    </button>
                    @endforeach
                </nav>
            </div>

            <div class="p-3">

                {{-- ══ Tab: Loại tin & Cơ bản ══════════════════════════════ --}}
                <div x-show="tab === 'basic'" data-tab-label="Loại tin & Cơ bản" class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Bán hay thuê <span class="text-error">*</span></span></label>
                            <select id="ts-listing_type" name="listing_type" x-model="listingType"
                                    data-req="Vui lòng chọn loại tin"
                                    class="select select-bordered select-sm w-full ts-init @error('listing_type') select-error @enderror"
                                    data-ts-placeholder="— Chọn —">
                                <option value="sale" {{ $val('listing_type') === 'sale' ? 'selected' : '' }}>Bán</option>
                                <option value="rent" {{ $val('listing_type') === 'rent' ? 'selected' : '' }}>Thuê</option>
                            </select>
                            @error('listing_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text font-medium">Loại hình <span class="text-error">*</span></span></label>
                            <select id="ts-property_type" name="property_type" x-model="propertyType"
                                    data-req="Vui lòng chọn loại hình"
                                    class="select select-bordered select-sm w-full @error('property_type') select-error @enderror"
                                    data-ts-placeholder="— Chọn loại hình —">
                                <option value="">— Chọn loại hình —</option>
                                @foreach(\Modules\RealEstate\Enums\PropertyType::cases() as $pt)
                                <option value="{{ $pt->value }}"
                                        data-listing-type="{{ implode(' ', array_map(fn ($lt) => $lt->value, array_filter(\Modules\RealEstate\Enums\ListingType::cases(), fn ($lt) => in_array($pt, \Modules\RealEstate\Enums\PropertyType::validFor($lt), true)))) }}"
                                        {{ $val('property_type') === $pt->value ? 'selected' : '' }}>{{ $pt->label() }}</option>
                                @endforeach
                            </select>
                            @error('property_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Tiêu đề tin <span class="text-error">*</span></span></label>
                        <input type="text" name="title" value="{{ $val('title') }}" maxlength="250"
                               data-req="Vui lòng nhập tiêu đề tin"
                               class="input input-bordered input-sm w-full @error('title') input-error @enderror">
                        @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Slug</span>
                        </label>
                        <input type="text" name="slug" value="{{ $val('slug') }}"
                               class="input input-bordered input-sm w-full font-mono @error('slug') input-error @enderror">
                        <p class="mt-1 text-xs text-base-content/40">Chỉ dùng chữ thường, số và dấu <code class="bg-base-200 px-1 rounded">-</code></p>
                        @error('slug')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Mô tả chi tiết</span></label>
                        <textarea name="description" rows="5"
                                  class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror">{{ $val('description') }}</textarea>
                        @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="button" @click="tab = next()" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Vị trí
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                {{-- ══ Tab: Vị trí ══════════════════════════════════════════ --}}
                <div x-show="tab === 'location'" data-tab-label="Vị trí" class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Số nhà, đường</span></label>
                        <input type="text" name="address_detail" value="{{ $val('address_detail') }}" maxlength="255"
                               placeholder="Ví dụ: 12 Nguyễn Văn A"
                               class="input input-bordered input-sm w-full @error('address_detail') input-error @enderror">
                        @error('address_detail')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <x-address-picker
                        :required="true"
                        instance-id="real-estate-listing-e"
                        name-province="province_code"
                        name-ward="ward_code"
                        :province-value="old('province_code', $listing->province_code)"
                        :ward-value="old('ward_code', $listing->ward_code)"
                    />

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = prev()" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Loại tin & Cơ bản
                        </button>
                        <button type="button" @click="tab = next()" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Giá
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                {{-- ══ Tab: Giá ══════════════════════════════════════════════ --}}
                <div x-show="tab === 'price'" data-tab-label="Giá" class="space-y-4">

                    <label class="label cursor-pointer justify-start gap-2">
                        <input type="hidden" name="is_price_negotiable" value="0">
                        <input type="checkbox" name="is_price_negotiable" value="1" x-model="isNegotiable" class="checkbox checkbox-sm">
                        <span class="label-text text-sm">Giá thoả thuận (không niêm yết số cố định)</span>
                    </label>

                    <div x-show="listingType === 'sale'" class="grid sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Giá bán (VNĐ) <span x-show="!isNegotiable" class="text-error">*</span></span></label>
                            <input type="number" name="price" value="{{ $val('price') }}" step="1000000" min="0"
                                   x-bind:disabled="isNegotiable"
                                   :data-req="!isNegotiable ? 'Vui lòng nhập giá bán' : null"
                                   class="input input-bordered input-sm w-full @error('price') input-error @enderror">
                            @error('price')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control">
                            <label class="label cursor-pointer justify-start gap-2 mt-6">
                                <input type="hidden" name="is_urgent" value="0">
                                <input type="checkbox" name="is_urgent" value="1" x-model="isUrgent" class="checkbox checkbox-sm">
                                <span class="label-text text-sm">Bán gấp</span>
                            </label>
                        </div>
                        <div class="form-control" x-show="isUrgent">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Muốn bán trong (số ngày)</span></label>
                            <input type="number" name="urgent_days" value="{{ $val('urgent_days') }}" min="1"
                                   class="input input-bordered input-sm w-full @error('urgent_days') input-error @enderror">
                            @error('urgent_days')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Đang cho thuê, giá thuê hiện tại (VNĐ/tháng)</span></label>
                            <input type="number" name="current_rental_income" value="{{ $val('current_rental_income') }}" step="100000" min="0"
                                   class="input input-bordered input-sm w-full @error('current_rental_income') input-error @enderror">
                            @error('current_rental_income')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div x-show="listingType === 'rent'" class="grid sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Giá thuê/tháng (VNĐ) <span x-show="!isNegotiable" class="text-error">*</span></span></label>
                            <input type="number" name="monthly_rent" value="{{ $val('monthly_rent') }}" step="100000" min="0"
                                   x-bind:disabled="isNegotiable"
                                   :data-req="!isNegotiable ? 'Vui lòng nhập giá thuê/tháng' : null"
                                   class="input input-bordered input-sm w-full @error('monthly_rent') input-error @enderror">
                            @error('monthly_rent')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Tiền cọc (VNĐ)</span></label>
                            <input type="number" name="deposit" value="{{ $val('deposit') }}" step="100000" min="0"
                                   class="input input-bordered input-sm w-full @error('deposit') input-error @enderror">
                            @error('deposit')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Thời hạn thuê (tháng, tối thiểu 3)</span></label>
                            <input type="number" name="rental_period_months" value="{{ $val('rental_period_months') }}" min="3"
                                   class="input input-bordered input-sm w-full @error('rental_period_months') input-error @enderror">
                            @error('rental_period_months')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control" x-show="propertyType === 'apartment'">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Phí quản lý/tháng (VNĐ)</span></label>
                            <input type="number" name="management_fee" value="{{ $val('management_fee') }}" step="10000" min="0"
                                   class="input input-bordered input-sm w-full @error('management_fee') input-error @enderror">
                            @error('management_fee')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = prev()" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Vị trí
                        </button>
                        <button type="button" @click="tab = next()" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Diện tích & phòng
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                {{-- ══ Tab: Diện tích & phòng ═══════════════════════════════ --}}
                <div x-show="tab === 'area'" data-tab-label="Diện tích & phòng" class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text text-sm">Diện tích sử dụng (m²) <span class="text-error">*</span></span></label>
                        <input type="number" name="area" value="{{ $val('area') }}" step="0.1" min="1"
                               data-req="Vui lòng nhập diện tích"
                               class="input input-bordered input-sm w-full @error('area') input-error @enderror">
                        @error('area')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div x-show="listingType === 'sale' && (propertyType === 'house' || propertyType === 'land')" class="grid sm:grid-cols-3 gap-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Chiều ngang (m)</span></label>
                            <input type="number" name="width" value="{{ $val('width') }}" step="0.01" min="0" class="input input-bordered input-sm w-full">
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Chiều dài (m)</span></label>
                            <input type="number" name="length" value="{{ $val('length') }}" step="0.01" min="0" class="input input-bordered input-sm w-full">
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Diện tích đất (m²)</span></label>
                            <input type="number" name="land_area" value="{{ $val('land_area') }}" step="0.1" min="0" class="input input-bordered input-sm w-full">
                        </div>
                    </div>

                    <div x-show="propertyType === 'apartment'" class="grid sm:grid-cols-2 gap-4">
                        <div class="form-control" x-show="listingType === 'sale'">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Diện tích tim tường (m²)</span></label>
                            <input type="number" name="usable_area" value="{{ $val('usable_area') }}" step="0.1" min="0" class="input input-bordered input-sm w-full">
                        </div>
                        <div class="form-control" x-show="listingType === 'sale'">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Diện tích thông thuỷ (m²)</span></label>
                            <input type="number" name="net_area" value="{{ $val('net_area') }}" step="0.1" min="0" class="input input-bordered input-sm w-full">
                        </div>
                    </div>
                    <p class="text-xs text-base-content/40" x-show="listingType === 'sale' && propertyType !== ''">
                        * Diện tích chính dùng để lọc/hiển thị được lấy từ diện tích đất (nhà riêng/đất) hoặc thông thuỷ/tim tường (căn hộ).
                    </p>

                    <div class="grid sm:grid-cols-3 gap-4">
                        <div class="form-control" x-show="propertyType === 'house' || propertyType === 'apartment'">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Số phòng ngủ</span></label>
                            <input type="number" name="bedrooms" value="{{ $val('bedrooms') }}" min="0" class="input input-bordered input-sm w-full">
                        </div>
                        <div class="form-control" x-show="propertyType === 'house' || propertyType === 'apartment'">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Số phòng tắm</span></label>
                            <input type="number" name="bathrooms" value="{{ $val('bathrooms') }}" min="0" class="input input-bordered input-sm w-full">
                        </div>
                        <div class="form-control" x-show="propertyType === 'house'">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Số tầng</span></label>
                            <input type="number" name="floors" value="{{ $val('floors') }}" min="1" class="input input-bordered input-sm w-full">
                        </div>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = prev()" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Giá
                        </button>
                        <button type="button" @click="tab = next()" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Đặc điểm
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                {{-- ══ Tab: Đặc điểm ═══════════════════════════════════════ --}}
                <div x-show="tab === 'details'" data-tab-label="Đặc điểm" class="space-y-4">

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="form-control" x-show="listingType === 'sale' && propertyType === 'house'">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Loại nhà riêng</span></label>
                            <select id="ts-house_subtype" name="house_subtype" data-ts-placeholder="— Chọn —"
                                    class="select select-bordered select-sm w-full">
                                <option value="">— Chọn —</option>
                                @foreach(\Modules\RealEstate\Enums\HouseSubtype::cases() as $c)
                                <option value="{{ $c->value }}" {{ $val('house_subtype') === $c->value ? 'selected' : '' }}>{{ $c->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-control" x-show="listingType === 'sale' && propertyType === 'apartment'">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Loại căn hộ</span></label>
                            <select id="ts-apartment_subtype" name="apartment_subtype" data-ts-placeholder="— Chọn —"
                                    class="select select-bordered select-sm w-full">
                                <option value="">— Chọn —</option>
                                @foreach(\Modules\RealEstate\Enums\ApartmentSubtype::cases() as $c)
                                <option value="{{ $c->value }}" {{ $val('apartment_subtype') === $c->value ? 'selected' : '' }}>{{ $c->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-control" x-show="propertyType !== 'layout' && propertyType !== ''">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Giấy tờ pháp lý</span></label>
                            <select id="ts-legal_status" name="legal_status" data-ts-placeholder="— Chọn —"
                                    class="select select-bordered select-sm w-full">
                                <option value="">— Chọn —</option>
                                @foreach(\Modules\RealEstate\Enums\LegalStatus::cases() as $c)
                                <option value="{{ $c->value }}" {{ $val('legal_status') === $c->value ? 'selected' : '' }}>{{ $c->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-control" x-show="propertyType !== 'layout' && propertyType !== ''">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Hướng</span></label>
                            <select id="ts-direction" name="direction" data-ts-placeholder="— Chọn —"
                                    class="select select-bordered select-sm w-full">
                                <option value="">— Chọn —</option>
                                @foreach(\Modules\RealEstate\Enums\CompassDirection::cases() as $c)
                                <option value="{{ $c->value }}" {{ $val('direction') === $c->value ? 'selected' : '' }}>{{ $c->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-control" x-show="propertyType === 'apartment'">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Hướng ban công</span></label>
                            <select id="ts-balcony_direction" name="balcony_direction" data-ts-placeholder="— Chọn —"
                                    class="select select-bordered select-sm w-full">
                                <option value="">— Chọn —</option>
                                @foreach(\Modules\RealEstate\Enums\CompassDirection::balconyOptions() as $c)
                                <option value="{{ $c->value }}" {{ $val('balcony_direction') === $c->value ? 'selected' : '' }}>{{ $c->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-control" x-show="propertyType === 'apartment'">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Tình trạng sử dụng</span></label>
                            <select id="ts-usage_status" name="usage_status" data-ts-placeholder="— Chọn —"
                                    class="select select-bordered select-sm w-full">
                                <option value="">— Chọn —</option>
                                @foreach(\Modules\RealEstate\Enums\UsageStatus::cases() as $c)
                                <option value="{{ $c->value }}" {{ $val('usage_status') === $c->value ? 'selected' : '' }}>{{ $c->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-control" x-show="propertyType === 'apartment'">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Tên dự án / toà nhà</span></label>
                            <input type="text" name="project_name" value="{{ $val('project_name') }}" maxlength="150" class="input input-bordered input-sm w-full">
                        </div>
                        <div class="form-control" x-show="propertyType === 'apartment'">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Số căn - Tầng - Block</span></label>
                            <input type="text" name="apartment_address" value="{{ $val('apartment_address') }}" maxlength="150" class="input input-bordered input-sm w-full">
                        </div>

                        <div class="form-control" x-show="listingType === 'sale' && propertyType === 'land'">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Đường trước nhà/đất (m)</span></label>
                            <input type="number" name="front_road_width" value="{{ $val('front_road_width') }}" step="0.1" min="0" class="input input-bordered input-sm w-full">
                        </div>

                        <div class="form-control" x-show="propertyType !== 'layout' && propertyType !== ''">
                            <label class="label py-0 pb-1.5"><span class="label-text text-sm">Nội thất</span></label>
                            <select id="ts-interior_status" name="interior_status" data-ts-placeholder="— Chọn —"
                                    class="select select-bordered select-sm w-full">
                                <option value="">— Chọn —</option>
                                @foreach(\Modules\RealEstate\Enums\InteriorStatus::cases() as $c)
                                <option value="{{ $c->value }}" {{ $val('interior_status') === $c->value ? 'selected' : '' }}>{{ $c->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <p class="text-xs text-base-content/40" x-show="propertyType === ''">Chọn loại hình ở tab "Loại tin & Cơ bản" để hiện các trường đặc điểm phù hợp.</p>

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = prev()" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Diện tích & phòng
                        </button>
                        <button type="button" @click="tab = next()" class="btn btn-ghost btn-sm gap-1.5">
                            Tiếp theo: Hình ảnh
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                {{-- ══ Tab: Hình ảnh ═══════════════════════════════════════ --}}
                <div x-show="tab === 'media'" data-tab-label="Hình ảnh" class="space-y-4">

                    <p class="text-xs text-base-content/50">Tối đa 6 ảnh — kéo-thả để sắp thứ tự hiển thị.</p>
                    <div class="flex flex-wrap gap-2 mb-1">
                        @foreach($listing->galleryUrls('thumb') as $url)
                        <img src="{{ $url }}" alt="" class="w-20 h-20 rounded-lg object-cover border border-base-200">
                        @endforeach
                    </div>
                    <div id="gallery-filepond" data-context-type="real_estate_listing" data-context-id="{{ $listing->id }}"></div>
                    <input type="hidden" name="gallery_media_uuids" id="gallery-media-uuids" value="">
                    @error('gallery_media_uuids')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror

                    <div class="flex items-center justify-between pt-2">
                        <button type="button" @click="tab = prev()" class="btn btn-ghost btn-sm gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            Đặc điểm
                        </button>
                        <span class="text-xs text-base-content/40">Điền xong? Nhấn <strong>Lưu thay đổi</strong> ở bên phải</span>
                    </div>
                </div>

            </div>{{-- /tab panels --}}
        </div>{{-- /card chính --}}

        {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
        <div class="xl:sticky xl:top-4 space-y-4">
            <div class="card bg-base-100 shadow-sm border border-base-200">
                <div class="card-body p-3">

                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>

                    @if($status)
                    <div class="flex items-center justify-between mb-3 text-xs">
                        <span class="text-base-content/50">Trạng thái duyệt</span>
                        <span class="badge {{ $status->badgeClass() }} badge-sm">{{ $status->label() }}</span>
                    </div>
                    @endif

                    <label class="label cursor-pointer justify-start gap-2 mb-3">
                        <input type="hidden" name="is_featured" value="0">
                        <input type="checkbox" name="is_featured" value="1" class="checkbox checkbox-sm" @checked($val('is_featured'))>
                        <span class="label-text text-sm">Ghim nổi bật</span>
                    </label>

                    <div class="form-control mb-4">
                        <label class="label py-0 pb-1"><span class="label-text text-xs font-medium">Thứ tự sắp xếp</span></label>
                        <input type="number" name="sort_order" value="{{ $val('sort_order', 0) }}" min="0"
                               class="input input-bordered input-sm w-full">
                    </div>

                    <p class="text-xs text-base-content/40 mb-4">
                        Tạo: {{ $listing->created_at?->format('d/m/Y H:i') }}
                        @if($listing->updated_at && $listing->updated_at != $listing->created_at)
                        · Sửa: {{ $listing->updated_at->format('d/m/Y H:i') }}
                        @endif
                    </p>

                    <div class="flex gap-2">
                        <a href="{{ route('backend.real-estate.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                        <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Lưu thay đổi
                        </button>
                    </div>

                    <p class="text-center text-xs text-base-content/30 mt-2.5">
                        <span class="text-error">*</span> là trường bắt buộc
                    </p>

                    @can('delete', $listing)
                    <div class="divider my-3"></div>
                    <form method="POST" action="{{ route('backend.real-estate.destroy', $listing) }}" onsubmit="return confirm('Xoá tin này? Hành động không thể hoàn tác.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-error btn-outline btn-sm w-full">Xoá tin</button>
                    </form>
                    @endcan

                </div>
            </div>
        </div>{{-- /sidebar --}}

    </div>{{-- /grid --}}
</form>
</div>

@can('reject', $listing)
<dialog id="rejectRealEstateModal" class="modal">
    <div class="modal-box max-w-sm">
        <h3 class="font-bold text-lg text-error">Từ chối duyệt</h3>
        <form method="POST" action="{{ route('backend.real-estate.reject-content', $listing) }}" class="mt-3">
            @csrf
            <textarea name="reason" rows="3" minlength="10" required class="textarea textarea-bordered w-full" placeholder="Lý do từ chối (tối thiểu 10 ký tự)"></textarea>
            <div class="modal-action mt-4">
                <button type="submit" class="btn btn-error btn-sm">Từ chối</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="rejectRealEstateModal.close()">Hủy</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
@endcan
@endsection

@push('styles')
    @vite(['Modules/RealEstate/resources/assets/sass/realestate.scss'], 'build/backend')
@endpush

@push('scripts')
    @vite([
        'resources/js/modules/toastify.js',
        'resources/js/modules/tom-select.js',
        'resources/js/modules/filepond.js',
        'Modules/RealEstate/resources/assets/js/realestate.js',
    ], 'build/backend')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const el = document.getElementById('gallery-filepond');
        const uuidsInput = document.getElementById('gallery-media-uuids');
        if (window.initFilePondUpload && el) {
            initFilePondUpload(el, {
                collection: 'real_estate_gallery',
                contextType: el.dataset.contextType,
                contextId: el.dataset.contextId,
                maxFiles: 6,
                bindTo: uuidsInput,
            });
        }
    });
    </script>
@endpush
