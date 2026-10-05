@php
    $P = \Modules\OcopSubject\Models\OcopSubject::class;
    $tabs = [
        'basic' => 'Thông tin cơ bản',
        'location' => 'Địa chỉ & Định vị',
        'certs' => 'Chứng nhận & Hồ sơ',
        'story' => 'Câu chuyện',
        'contact' => 'Liên hệ',
    ];
    $tabKeys = array_keys($tabs);
@endphp
<div class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-6 items-start"
     x-data="{
        tab: 'basic',
        orgType: {{ Js::from(old('organization_type', $ocopSubject?->organization_type?->value ?? '')) }},
        isFood: {{ Js::from((bool) old('is_food_business', $ocopSubject?->is_food_business ?? false)) }},
        tabFields: {
            basic: ['name', 'name_en', 'tax_code', 'organization_type', 'legal_representative', 'position'],
            location: ['address', 'province_code', 'ward_code', 'gps_coordinates', 'factory_code'],
            certs: ['is_food_business', 'ocop_star', 'ocop_cert_expiry', 'documents', 'media_uuids'],
            story: ['story'],
            contact: ['hotline', 'email', 'website'],
        },
        errs: {{ Js::from($errors->keys()) }},
        errCount(t) {
            return this.tabFields[t].filter(f => this.errs.some(e => e === f || e.startsWith(f + '.'))).length;
        },
        init() {
            for (const t of {{ Js::from($tabKeys) }}) {
                if (this.errCount(t) > 0) { this.tab = t; break; }
            }
        }
     }">

    <div class="card bg-base-100 shadow-sm border border-base-200">

        <div class="border-b border-base-200 px-3">
            <nav class="flex -mb-px overflow-x-auto whitespace-nowrap" role="tablist">
                @foreach($tabs as $key => $label)
                <button type="button" role="tab" :aria-selected="tab === '{{ $key }}'"
                        @click="tab = '{{ $key }}'"
                        class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
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

            <div x-show="tab === 'basic'" data-tab-label="Thông tin cơ bản" class="space-y-4">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tên chủ thể <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $ocopSubject?->name) }}"
                               data-req="Vui lòng nhập tên chủ thể" maxlength="255" autofocus
                               placeholder="VD: HTX Nông nghiệp Thủy Thanh"
                               class="input input-bordered input-sm w-full @error('name') input-error @enderror">
                        @error('name')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tên tiếng Anh</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <input type="text" name="name_en" value="{{ old('name_en', $ocopSubject?->name_en) }}" maxlength="255"
                               class="input input-bordered input-sm w-full @error('name_en') input-error @enderror">
                        @error('name_en')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Loại hình tổ chức <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-organization_type" name="organization_type"
                                data-req="Vui lòng chọn loại hình tổ chức"
                                @change="orgType = $event.target.value"
                                class="select select-bordered select-sm w-full ts-init @error('organization_type') select-error @enderror"
                                data-ts-placeholder="— Chọn loại hình —">
                            <option value=""></option>
                            @foreach($types as $type)
                            <option value="{{ $type->value }}" @selected(old('organization_type', $ocopSubject?->organization_type?->value) === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                        @error('organization_type')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mã số định danh <span class="text-error">*</span></span>
                            <span class="label-text-alt text-xs text-base-content/40">MST hoặc CCCD (hộ kinh doanh)</span>
                        </label>
                        <input type="text" name="tax_code" value="{{ old('tax_code', $ocopSubject?->tax_code) }}"
                               data-req="Vui lòng nhập mã số định danh" maxlength="20" inputmode="numeric"
                               placeholder="VD: 3301234567"
                               class="input input-bordered input-sm w-full font-mono @error('tax_code') input-error @enderror">
                        @error('tax_code')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Người đại diện pháp luật <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="legal_representative" value="{{ old('legal_representative', $ocopSubject?->legal_representative) }}"
                               data-req="Vui lòng nhập người đại diện pháp luật" maxlength="150"
                               class="input input-bordered input-sm w-full @error('legal_representative') input-error @enderror">
                        @error('legal_representative')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Chức vụ</span>
                        </label>
                        <input type="text" name="position" value="{{ old('position', $ocopSubject?->position) }}" maxlength="100"
                               placeholder="VD: Giám đốc, Chủ nhiệm HTX, Chủ hộ"
                               class="input input-bordered input-sm w-full @error('position') input-error @enderror">
                        @error('position')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Ảnh cơ sở sản xuất</span>
                    </label>
                    @php($images = $ocopSubject?->getMedia($P::IMAGE_COLLECTION) ?? collect())
                    @if($images->isNotEmpty())
                    <div class="flex flex-wrap gap-2 mb-2">
                        @foreach($images as $image)
                        <label class="relative cursor-pointer" title="Đánh dấu để xoá ảnh này khi lưu">
                            <input type="checkbox" name="remove_media_uuids[]" value="{{ $image->uuid }}" class="peer sr-only"
                                   @checked(in_array($image->uuid, old('remove_media_uuids', []), true))>
                            <img src="{{ app(\App\Services\Media\MediaUrlService::class)->url($image, 'thumb') }}" alt=""
                                 class="h-20 w-20 rounded border border-base-300 object-cover peer-checked:opacity-30 peer-checked:border-error">
                            <span class="absolute top-1 right-1 flex h-5 w-5 items-center justify-center rounded-full bg-base-100/90 text-xs text-error shadow peer-checked:bg-error peer-checked:text-white">✕</span>
                        </label>
                        @endforeach
                    </div>
                    @endif
                    @if($ocopSubject)
                    <div id="ocop-subject-gallery-filepond" data-context-type="ocop_subject" data-context-id="{{ $ocopSubject->id }}"
                         data-max-files="{{ max(1, $P::MAX_IMAGES - $images->count()) }}"></div>
                    <p class="text-xs text-base-content/40 mt-1.5">Tối đa {{ $P::MAX_IMAGES }} ảnh. Bấm ✕ trên ảnh để xoá khi lưu.</p>
                    @else
                    <div id="ocop-subject-gallery-filepond"></div>
                    <input type="hidden" name="media_uuids" id="ocop-subject-gallery-media-uuids" value="">
                    <p class="text-xs text-base-content/40 mt-1.5">Tối đa {{ $P::MAX_IMAGES }} ảnh.</p>
                    @endif
                    @error('media_uuids')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

            </div>

            <div x-show="tab === 'location'" x-cloak data-tab-label="Địa chỉ &amp; Định vị" class="space-y-4">

                <x-address-picker
                    instance-id="ocop-subject"
                    :required="true"
                    :province-value="old('province_code', $ocopSubject?->province_code)"
                    :ward-value="old('ward_code', $ocopSubject?->ward_code)"
                />

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Địa chỉ trụ sở <span class="text-error">*</span></span>
                        <span class="label-text-alt text-xs text-base-content/40">Số nhà, đường, thôn/xóm</span>
                    </label>
                    <input type="text" name="address" value="{{ old('address', $ocopSubject?->address) }}"
                           data-req="Vui lòng nhập địa chỉ trụ sở" maxlength="255"
                           class="input input-bordered input-sm w-full @error('address') input-error @enderror">
                    @error('address')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Toạ độ GPS <span class="text-error">*</span></span>
                            <span class="label-text-alt text-xs text-base-content/40">vĩ độ, kinh độ</span>
                        </label>
                        <input type="text" name="gps_coordinates" value="{{ old('gps_coordinates', $ocopSubject?->gps_coordinates) }}"
                               data-req="Vui lòng nhập toạ độ GPS" maxlength="60" placeholder="VD: 16.4637, 107.5909"
                               class="input input-bordered input-sm w-full font-mono @error('gps_coordinates') input-error @enderror">
                        @error('gps_coordinates')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Mã cơ sở / Mã vùng trồng</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <input type="text" name="factory_code" value="{{ old('factory_code', $ocopSubject?->factory_code) }}" maxlength="50"
                               class="input input-bordered input-sm w-full font-mono @error('factory_code') input-error @enderror">
                        @error('factory_code')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                </div>

            </div>

            <div x-show="tab === 'certs'" x-cloak data-tab-label="Chứng nhận &amp; Hồ sơ" class="space-y-5">

                <label class="flex items-start gap-2.5 cursor-pointer select-none">
                    <input type="hidden" name="is_food_business" value="0">
                    <input type="checkbox" name="is_food_business" value="1" x-model="isFood"
                           class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0">
                    <span>
                        <span class="text-sm font-medium">Ngành thực phẩm</span>
                        <span class="block text-xs text-base-content/50">Bắt buộc có Giấy chứng nhận cơ sở đủ điều kiện ATTP.</span>
                    </span>
                </label>

                @include('ocopsubject::admin._document-field', [
                    'collection' => $P::DOC_BUSINESS_LICENSE,
                    'label' => 'Giấy chứng nhận đăng ký kinh doanh',
                    'hint' => 'Giấy ĐKDN, giấy đăng ký HTX hoặc giấy đăng ký hộ kinh doanh.',
                ])

                <div class="divider my-1 text-xs text-base-content/40">Chứng nhận OCOP</div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Hạng sao OCOP <span x-show="orgType === 'household'" x-cloak class="text-error">*</span></span>
                        </label>
                        <select id="ts-ocop_star" name="ocop_star"
                                class="select select-bordered select-sm w-full ts-init @error('ocop_star') select-error @enderror"
                                data-ts-placeholder="— Chưa xếp hạng —">
                            <option value=""></option>
                            @foreach([3, 4, 5] as $star)
                            <option value="{{ $star }}" @selected((string) old('ocop_star', $ocopSubject?->ocop_star) === (string) $star)>{{ $star }} sao</option>
                            @endforeach
                        </select>
                        @error('ocop_star')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Ngày hết hạn chứng nhận</span>
                        </label>
                        <input type="date" name="ocop_cert_expiry" value="{{ old('ocop_cert_expiry', $ocopSubject?->ocop_cert_expiry?->format('Y-m-d')) }}"
                               class="input input-bordered input-sm w-full @error('ocop_cert_expiry') input-error @enderror">
                        @error('ocop_cert_expiry')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                </div>

                @include('ocopsubject::admin._document-field', [
                    'collection' => $P::DOC_OCOP_CERT,
                    'label' => 'Quyết định phê duyệt hạng sao OCOP',
                    'hint' => 'Bắt buộc với Hộ kinh doanh, tuỳ chọn với Doanh nghiệp/HTX.',
                    'requiredWhen' => "orgType === 'household'",
                ])

                <div class="divider my-1 text-xs text-base-content/40">Chất lượng &amp; An toàn thực phẩm</div>

                @include('ocopsubject::admin._document-field', [
                    'collection' => $P::DOC_QUALITY_CERT,
                    'label' => 'Chứng nhận chất lượng',
                    'hint' => 'ISO, HACCP, VietGAP, GlobalGAP, hữu cơ...',
                ])

                @include('ocopsubject::admin._document-field', [
                    'collection' => $P::DOC_FOOD_SAFETY_CERT,
                    'label' => 'Giấy chứng nhận cơ sở đủ điều kiện ATTP',
                    'requiredWhen' => 'isFood',
                ])

            </div>

            <div x-show="tab === 'story'" x-cloak data-tab-label="Câu chuyện" class="space-y-4">
                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Câu chuyện thương hiệu</span>
                        <span class="label-text-alt text-xs text-base-content/40">Lịch sử, truyền thống, triết lý sản xuất — hiển thị ở trang thương hiệu công khai</span>
                    </label>
                    <textarea id="ocop-subject-story" name="story" class="jodit-editor" data-jodit-preset="standard">{{ old('story', $ocopSubject?->story) }}</textarea>
                    @error('story')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>
            </div>

            <div x-show="tab === 'contact'" x-cloak data-tab-label="Liên hệ" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Hotline</span></label>
                        <input type="tel" name="hotline" value="{{ old('hotline', $ocopSubject?->hotline) }}" maxlength="20"
                               class="input input-bordered input-sm w-full @error('hotline') input-error @enderror">
                        @error('hotline')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1.5"><span class="label-text font-medium">Email</span></label>
                        <input type="email" name="email" value="{{ old('email', $ocopSubject?->email) }}" maxlength="150"
                               class="input input-bordered input-sm w-full @error('email') input-error @enderror">
                        @error('email')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1.5"><span class="label-text font-medium">Website</span></label>
                    <input type="url" name="website" value="{{ old('website', $ocopSubject?->website) }}" maxlength="255"
                           placeholder="https://..."
                           class="input input-bordered input-sm w-full @error('website') input-error @enderror">
                    @error('website')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="flex items-center justify-between pt-4">
                <button type="button" x-show="tab !== 'basic'"
                        @click="tab = {{ Js::from($tabKeys) }}[{{ Js::from($tabKeys) }}.indexOf(tab) - 1]"
                        class="btn btn-ghost btn-sm">‹ Quay lại</button>
                <span></span>
                <button type="button" x-show="tab !== 'contact'"
                        @click="tab = {{ Js::from($tabKeys) }}[{{ Js::from($tabKeys) }}.indexOf(tab) + 1]"
                        class="btn btn-ghost btn-sm">Tiếp theo ›</button>
            </div>

        </div>
    </div>

    <div class="xl:sticky xl:top-4 space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-3">

                <label class="flex items-start gap-2.5 cursor-pointer select-none mb-4">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0" @checked(old('is_active', $ocopSubject?->is_active ?? true))>
                    <span class="text-sm font-medium">Đang hoạt động</span>
                </label>

                @if($ocopSubject)
                <p class="text-xs text-base-content/50 mb-4">Đang có <strong>{{ $ocopSubject->products()->count() }}</strong> sản phẩm OCOP.</p>
                @endif

                <div class="flex gap-2">
                    <a href="{{ route('backend.ocop-subjects.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                    <button type="submit" class="btn btn-primary btn-sm flex-1">{{ $ocopSubject ? 'Lưu thay đổi' : 'Tạo mới' }}</button>
                </div>

                <p class="text-center text-xs text-base-content/30 mt-2.5">
                    <span class="text-error">*</span> là trường bắt buộc
                </p>

            </div>
        </div>
    </div>

</div>
