@php
    /** @var \Modules\RealEstate\Models\RealEstateListing|null $listing */
    // Nullsafe (?->) chỉ chặn khi giá trị là NULL, KHÔNG chặn khi là scalar (string/int/float) —
    // field enum-cast (property_type, direction...) cần lấy ->value, field thường (title, area...)
    // thì không có ->value nào để lấy — phải tự kiểm tra BackedEnum trước khi truy cập ->value.
    $val = function (string $field, $default = null) use ($listing) {
        $raw = old($field, $listing?->{$field});

        return $raw instanceof \BackedEnum ? $raw->value : ($raw ?? $default);
    };
@endphp

<div x-data="{
        listingType: '{{ old('listing_type', $listing?->listing_type?->value ?? 'sale') }}',
        propertyType: '{{ old('property_type', $listing?->property_type?->value ?? '') }}',
        isNegotiable: {{ old('is_price_negotiable', $listing?->is_price_negotiable ?? false) ? 'true' : 'false' }},
        isUrgent: {{ old('is_urgent', $listing?->is_urgent ?? false) ? 'true' : 'false' }},
        propertyOptions: {
            sale: [['house','Nhà riêng'],['apartment','Căn hộ chung cư'],['land','Đất thổ cư']],
            rent: [['house','Nhà riêng'],['apartment','Căn hộ chung cư'],['layout','Mặt bằng']],
        },
        get options() { return this.propertyOptions[this.listingType] ?? [] },
    }"
    x-init="$watch('listingType', () => { if (!options.some(o => o[0] === propertyType)) propertyType = '' })"
    class="space-y-6">

    {{-- ── Loại tin + Loại hình ─────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h3 class="font-semibold text-sm mb-3">Loại tin</h3>
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label py-1"><span class="label-text font-medium text-sm">Bán hay thuê <span class="text-error">*</span></span></label>
                    <select name="listing_type" x-model="listingType" class="select select-bordered @error('listing_type') select-error @enderror">
                        <option value="sale">Bán</option>
                        <option value="rent">Thuê</option>
                    </select>
                    @error('listing_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text font-medium text-sm">Loại hình <span class="text-error">*</span></span></label>
                    <select name="property_type" x-model="propertyType" class="select select-bordered @error('property_type') select-error @enderror">
                        <option value="">-- Chọn loại hình --</option>
                        <template x-for="opt in options" :key="opt[0]">
                            <option :value="opt[0]" x-text="opt[1]"></option>
                        </template>
                    </select>
                    @error('property_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- ── Thông tin cơ bản ─────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h3 class="font-semibold text-sm mb-3">Thông tin cơ bản</h3>
            <div class="form-control mb-3">
                <label class="label py-1"><span class="label-text font-medium text-sm">Tiêu đề tin <span class="text-error">*</span></span></label>
                <input type="text" name="title" value="{{ $val('title') }}" maxlength="250"
                       class="input input-bordered @error('title') input-error @enderror"/>
                @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>
            <div class="form-control">
                <label class="label py-1"><span class="label-text font-medium text-sm">Mô tả chi tiết</span></label>
                <textarea name="description" rows="5" class="textarea textarea-bordered @error('description') textarea-error @enderror">{{ $val('description') }}</textarea>
                @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- ── Vị trí — province/ward BẮT BUỘC (§0 v1.1) ───────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h3 class="font-semibold text-sm mb-3">Vị trí bất động sản</h3>
            <div class="form-control mb-3">
                <label class="label py-1"><span class="label-text font-medium text-sm">Số nhà, đường</span></label>
                <input type="text" name="address_detail" value="{{ $val('address_detail') }}" maxlength="255"
                       placeholder="Ví dụ: 12 Nguyễn Văn A"
                       class="input input-bordered @error('address_detail') input-error @enderror"/>
                @error('address_detail')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>
            <x-address-picker
                :required="true"
                instance-id="real-estate-listing"
                name-province="province_code"
                name-ward="ward_code"
                :province-value="old('province_code', $listing?->province_code)"
                :ward-value="old('ward_code', $listing?->ward_code)"
            />
        </div>
    </div>

    {{-- ── Giá — is_price_negotiable cho phép "Thoả thuận" (§0 v1.1) ───────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h3 class="font-semibold text-sm mb-3">Giá</h3>
            <label class="label cursor-pointer justify-start gap-2 mb-3">
                <input type="hidden" name="is_price_negotiable" value="0">
                <input type="checkbox" name="is_price_negotiable" value="1" x-model="isNegotiable" class="checkbox checkbox-sm">
                <span class="label-text text-sm">Giá thoả thuận (không niêm yết số cố định)</span>
            </label>

            <div x-show="listingType === 'sale'" class="grid sm:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-sm">Giá bán (VNĐ) <span x-show="!isNegotiable" class="text-error">*</span></span></label>
                    <input type="number" name="price" value="{{ $val('price') }}" step="1000000" min="0" x-bind:disabled="isNegotiable"
                           class="input input-bordered @error('price') input-error @enderror"/>
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
                    <label class="label py-1"><span class="label-text text-sm">Muốn bán trong (số ngày)</span></label>
                    <input type="number" name="urgent_days" value="{{ $val('urgent_days') }}" min="1"
                           class="input input-bordered @error('urgent_days') input-error @enderror"/>
                    @error('urgent_days')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-sm">Đang cho thuê, giá thuê hiện tại (VNĐ/tháng)</span></label>
                    <input type="number" name="current_rental_income" value="{{ $val('current_rental_income') }}" step="100000" min="0"
                           class="input input-bordered @error('current_rental_income') input-error @enderror"/>
                    @error('current_rental_income')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div x-show="listingType === 'rent'" class="grid sm:grid-cols-3 gap-4">
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-sm">Giá thuê/tháng (VNĐ) <span x-show="!isNegotiable" class="text-error">*</span></span></label>
                    <input type="number" name="monthly_rent" value="{{ $val('monthly_rent') }}" step="100000" min="0" x-bind:disabled="isNegotiable"
                           class="input input-bordered @error('monthly_rent') input-error @enderror"/>
                    @error('monthly_rent')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-sm">Tiền cọc (VNĐ)</span></label>
                    <input type="number" name="deposit" value="{{ $val('deposit') }}" step="100000" min="0"
                           class="input input-bordered @error('deposit') input-error @enderror"/>
                    @error('deposit')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-sm">Thời hạn thuê (tháng, tối thiểu 3)</span></label>
                    <input type="number" name="rental_period_months" value="{{ $val('rental_period_months') }}" min="3"
                           class="input input-bordered @error('rental_period_months') input-error @enderror"/>
                    @error('rental_period_months')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-control" x-show="propertyType === 'apartment'">
                    <label class="label py-1"><span class="label-text text-sm">Phí quản lý/tháng (VNĐ)</span></label>
                    <input type="number" name="management_fee" value="{{ $val('management_fee') }}" step="10000" min="0"
                           class="input input-bordered @error('management_fee') input-error @enderror"/>
                    @error('management_fee')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- ── Diện tích & phòng ────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h3 class="font-semibold text-sm mb-3">Diện tích &amp; phòng</h3>

            <div x-show="listingType === 'rent'" class="form-control mb-3">
                <label class="label py-1"><span class="label-text text-sm">Diện tích sử dụng (m²) <span class="text-error">*</span></span></label>
                <input type="number" name="area" value="{{ $val('area') }}" step="0.1" min="1"
                       class="input input-bordered @error('area') input-error @enderror"/>
                @error('area')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div x-show="listingType === 'sale' && (propertyType === 'house' || propertyType === 'land')" class="grid sm:grid-cols-3 gap-4 mb-3">
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-sm">Chiều ngang (m)</span></label>
                    <input type="number" name="width" value="{{ $val('width') }}" step="0.01" min="0" class="input input-bordered"/>
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-sm">Chiều dài (m)</span></label>
                    <input type="number" name="length" value="{{ $val('length') }}" step="0.01" min="0" class="input input-bordered"/>
                </div>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-sm">Diện tích đất (m²) <span class="text-error">*</span></span></label>
                    <input type="number" name="land_area" value="{{ $val('land_area') }}" step="0.1" min="0"
                           class="input input-bordered @error('area') input-error @enderror"/>
                </div>
            </div>

            <div x-show="propertyType === 'apartment'" class="grid sm:grid-cols-2 gap-4 mb-3">
                <div class="form-control" x-show="listingType === 'sale'">
                    <label class="label py-1"><span class="label-text text-sm">Diện tích tim tường (m²)</span></label>
                    <input type="number" name="usable_area" value="{{ $val('usable_area') }}" step="0.1" min="0" class="input input-bordered"/>
                </div>
                <div class="form-control" x-show="listingType === 'sale'">
                    <label class="label py-1"><span class="label-text text-sm">Diện tích thông thuỷ (m²) <span class="text-error">*</span></span></label>
                    <input type="number" name="net_area" value="{{ $val('net_area') }}" step="0.1" min="0"
                           class="input input-bordered @error('area') input-error @enderror"/>
                </div>
            </div>
            <p class="text-xs text-base-content/40 mb-3" x-show="listingType === 'sale' && (propertyType === 'house' || propertyType === 'land' || propertyType === 'apartment')">
                * Diện tích chính dùng để lọc/hiển thị được lấy từ diện tích đất (nhà riêng/đất) hoặc thông thuỷ/tim tường (căn hộ).
            </p>

            <div class="grid sm:grid-cols-3 gap-4">
                <div class="form-control" x-show="propertyType === 'house' || propertyType === 'apartment'">
                    <label class="label py-1"><span class="label-text text-sm">Số phòng ngủ</span></label>
                    <input type="number" name="bedrooms" value="{{ $val('bedrooms') }}" min="0" class="input input-bordered"/>
                </div>
                <div class="form-control" x-show="propertyType === 'house' || propertyType === 'apartment'">
                    <label class="label py-1"><span class="label-text text-sm">Số phòng tắm</span></label>
                    <input type="number" name="bathrooms" value="{{ $val('bathrooms') }}" min="0" class="input input-bordered"/>
                </div>
                <div class="form-control" x-show="propertyType === 'house'">
                    <label class="label py-1"><span class="label-text text-sm">Số tầng</span></label>
                    <input type="number" name="floors" value="{{ $val('floors') }}" min="1" class="input input-bordered"/>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Đặc điểm theo loại hình ──────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm" x-show="propertyType !== ''">
        <div class="card-body">
            <h3 class="font-semibold text-sm mb-3">Đặc điểm nhà đất</h3>

            <div class="grid sm:grid-cols-2 gap-4">
                <div class="form-control" x-show="listingType === 'sale' && propertyType === 'house'">
                    <label class="label py-1"><span class="label-text text-sm">Loại nhà riêng</span></label>
                    <select name="house_subtype" class="select select-bordered">
                        <option value="">-- Chọn --</option>
                        @foreach(\Modules\RealEstate\Enums\HouseSubtype::cases() as $c)
                        <option value="{{ $c->value }}" @selected($val('house_subtype') === $c->value)>{{ $c->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control" x-show="listingType === 'sale' && propertyType === 'apartment'">
                    <label class="label py-1"><span class="label-text text-sm">Loại căn hộ</span></label>
                    <select name="apartment_subtype" class="select select-bordered">
                        <option value="">-- Chọn --</option>
                        @foreach(\Modules\RealEstate\Enums\ApartmentSubtype::cases() as $c)
                        <option value="{{ $c->value }}" @selected($val('apartment_subtype') === $c->value)>{{ $c->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control" x-show="propertyType !== 'layout'">
                    <label class="label py-1"><span class="label-text text-sm">Giấy tờ pháp lý</span></label>
                    <select name="legal_status" class="select select-bordered">
                        <option value="">-- Chọn --</option>
                        @foreach(\Modules\RealEstate\Enums\LegalStatus::cases() as $c)
                        <option value="{{ $c->value }}" @selected($val('legal_status') === $c->value)>{{ $c->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control" x-show="propertyType !== 'layout'">
                    <label class="label py-1"><span class="label-text text-sm">Hướng</span></label>
                    <select name="direction" class="select select-bordered">
                        <option value="">-- Chọn --</option>
                        @foreach(\Modules\RealEstate\Enums\CompassDirection::cases() as $c)
                        <option value="{{ $c->value }}" @selected($val('direction') === $c->value)>{{ $c->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control" x-show="propertyType === 'apartment'">
                    <label class="label py-1"><span class="label-text text-sm">Hướng ban công</span></label>
                    <select name="balcony_direction" class="select select-bordered">
                        <option value="">-- Chọn --</option>
                        @foreach(\Modules\RealEstate\Enums\CompassDirection::balconyOptions() as $c)
                        <option value="{{ $c->value }}" @selected($val('balcony_direction') === $c->value)>{{ $c->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control" x-show="propertyType === 'apartment'">
                    <label class="label py-1"><span class="label-text text-sm">Tình trạng sử dụng</span></label>
                    <select name="usage_status" class="select select-bordered">
                        <option value="">-- Chọn --</option>
                        @foreach(\Modules\RealEstate\Enums\UsageStatus::cases() as $c)
                        <option value="{{ $c->value }}" @selected($val('usage_status') === $c->value)>{{ $c->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-control" x-show="propertyType === 'apartment'">
                    <label class="label py-1"><span class="label-text text-sm">Tên dự án / toà nhà</span></label>
                    <input type="text" name="project_name" value="{{ $val('project_name') }}" maxlength="150" class="input input-bordered"/>
                </div>
                <div class="form-control" x-show="propertyType === 'apartment'">
                    <label class="label py-1"><span class="label-text text-sm">Số căn - Tầng - Block</span></label>
                    <input type="text" name="apartment_address" value="{{ $val('apartment_address') }}" maxlength="150" class="input input-bordered"/>
                </div>

                <div class="form-control" x-show="listingType === 'sale' && propertyType === 'land'">
                    <label class="label py-1"><span class="label-text text-sm">Đường trước nhà/đất (m)</span></label>
                    <input type="number" name="front_road_width" value="{{ $val('front_road_width') }}" step="0.1" min="0" class="input input-bordered"/>
                </div>

                <div class="form-control" x-show="propertyType !== 'layout'">
                    <label class="label py-1"><span class="label-text text-sm">Nội thất</span></label>
                    <select name="interior_status" class="select select-bordered">
                        <option value="">-- Chọn --</option>
                        @foreach(\Modules\RealEstate\Enums\InteriorStatus::cases() as $c)
                        <option value="{{ $c->value }}" @selected($val('interior_status') === $c->value)>{{ $c->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Hình ảnh (max 6, kéo-thả sắp thứ tự — §0/§2.4 spec Bán) ─────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <h3 class="font-semibold text-sm mb-3">Hình ảnh (tối đa 6 ảnh)</h3>
            @if($listing)
            <div class="flex flex-wrap gap-2 mb-3">
                @foreach($listing->galleryUrls('thumb') as $url)
                <img src="{{ $url }}" alt="" class="w-20 h-20 rounded-lg object-cover border border-base-200">
                @endforeach
            </div>
            @endif
            <div id="gallery-filepond" @if($listing) data-context-type="real_estate_listing" data-context-id="{{ $listing->id }}" @endif></div>
            <input type="hidden" name="gallery_media_uuids" id="gallery-media-uuids" value="">
            @error('gallery_media_uuids')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- ── Ghim/sắp xếp ─────────────────────────────────────────────────── --}}
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        <div class="card-body">
            <div class="grid sm:grid-cols-2 gap-4">
                <label class="label cursor-pointer justify-start gap-2">
                    <input type="hidden" name="is_featured" value="0">
                    <input type="checkbox" name="is_featured" value="1" class="checkbox checkbox-sm" @checked($val('is_featured'))>
                    <span class="label-text text-sm">Ghim nổi bật</span>
                </label>
                <div class="form-control">
                    <label class="label py-1"><span class="label-text text-sm">Thứ tự sắp xếp</span></label>
                    <input type="number" name="sort_order" value="{{ $val('sort_order', 0) }}" min="0" class="input input-bordered input-sm"/>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    @vite(['resources/js/modules/filepond.js'], 'build/backend')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const el = document.getElementById('gallery-filepond');
        const uuidsInput = document.getElementById('gallery-media-uuids');
        if (window.initFilePondUpload && el) {
            initFilePondUpload(el, {
                collection: 'real_estate_gallery',
                contextType: el.dataset.contextType,
                contextId: el.dataset.contextId,
                bindTo: uuidsInput,
                maxFiles: 6,
            });
        }
    });
    </script>
@endpush
