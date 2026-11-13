@php
    $P = \Modules\OcopSubject\Models\OcopSubject::class;
    $tabKeys = ['basic', 'certs', 'story', 'translation'];
    $isActive = (bool) old('is_active', $ocopSubject?->is_active ?? true);
@endphp
<div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_320px] gap-6 items-start"
     x-data="{
        tab: 'basic',
        orgType: {{ Js::from(old('organization_type', $ocopSubject?->organization_type?->value ?? '')) }},
        isFood: {{ Js::from((bool) old('is_food_business', $ocopSubject?->is_food_business ?? false)) }},
        tabFields: {
            basic: ['name', 'tax_code', 'organization_type', 'legal_representative', 'position', 'address', 'province_code', 'ward_code', 'gps_coordinates', 'factory_code', 'hotline', 'email', 'website'],
            certs: ['is_food_business', 'ocop_star', 'ocop_cert_expiry', 'documents', 'document_uuids'],
            story: ['story'],
            translation: ['translations'],
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

    <div class="card bg-base-100 shadow-sm border border-base-200 min-w-0">

        <div class="border-b border-base-200 px-5">
            <nav class="flex -mb-px overflow-x-auto whitespace-nowrap" role="tablist" aria-label="{{ __('Nhóm thông tin') }}">
                <button type="button" role="tab" :aria-selected="tab === 'basic'" @click="tab = 'basic'"
                        class="flex items-center gap-2 px-1 py-3.5 mr-6 text-sm font-medium border-b-2 transition-colors"
                        :class="tab === 'basic' ? 'border-primary text-primary' : 'border-transparent text-base-content/55 hover:text-base-content hover:border-base-content/20'">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    {{ __('Thông tin chung') }}
                    <span x-show="errCount('basic') > 0" x-text="errCount('basic')" class="badge badge-error badge-xs" x-cloak></span>
                </button>
                <button type="button" role="tab" :aria-selected="tab === 'certs'" @click="tab = 'certs'"
                        class="flex items-center gap-2 px-1 py-3.5 mr-6 text-sm font-medium border-b-2 transition-colors"
                        :class="tab === 'certs' ? 'border-primary text-primary' : 'border-transparent text-base-content/55 hover:text-base-content hover:border-base-content/20'">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    {{ __('Chứng nhận & Hồ sơ') }}
                    <span x-show="errCount('certs') > 0" x-text="errCount('certs')" class="badge badge-error badge-xs" x-cloak></span>
                </button>
                <button type="button" role="tab" :aria-selected="tab === 'story'" @click="tab = 'story'"
                        class="flex items-center gap-2 px-1 py-3.5 mr-6 text-sm font-medium border-b-2 transition-colors"
                        :class="tab === 'story' ? 'border-primary text-primary' : 'border-transparent text-base-content/55 hover:text-base-content hover:border-base-content/20'">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    {{ __('Câu chuyện') }}
                    <span x-show="errCount('story') > 0" x-text="errCount('story')" class="badge badge-error badge-xs" x-cloak></span>
                </button>
                @if(\App\Support\I18n\PortalLocale::prefixed())
                <button type="button" role="tab" :aria-selected="tab === 'translation'" @click="tab = 'translation'"
                        class="flex items-center gap-2 px-1 py-3.5 mr-6 text-sm font-medium border-b-2 transition-colors"
                        :class="tab === 'translation' ? 'border-primary text-primary' : 'border-transparent text-base-content/55 hover:text-base-content hover:border-base-content/20'">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                    {{ __('Bản dịch') }}
                    <span x-show="errCount('translation') > 0" x-text="errCount('translation')" class="badge badge-error badge-xs" x-cloak></span>
                </button>
                @endif
            </nav>
        </div>

        <div class="p-5 sm:p-6">

            <div x-show="tab === 'basic'" data-tab-label="{{ __('Thông tin chung') }}" class="space-y-6">

                <div class="flex items-center gap-3">
                    <h3 class="text-sm font-semibold text-base-content">{{ __('Pháp lý') }}</h3>
                    <span class="h-px flex-1 bg-base-200"></span>
                </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">{{ __('Tên chủ thể') }} <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $ocopSubject?->name) }}"
                               data-req="{{ __('Vui lòng nhập tên chủ thể') }}" maxlength="255"
                               placeholder="{{ __('VD: HTX Nông nghiệp Thủy Thanh') }}"
                               class="input input-bordered w-full text-base font-medium @error('name') input-error @enderror">
                        @error('name')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">{{ __('Loại hình tổ chức') }}</span>
                        </label>
                        <select id="ts-organization_type" name="organization_type"
                                @change="orgType = $event.target.value"
                                class="select select-bordered select-sm w-full ts-init @error('organization_type') select-error @enderror"
                                data-ts-placeholder="{{ __('— Chọn loại hình —') }}">
                            <option value=""></option>
                            @foreach($types as $type)
                            <option value="{{ $type->value }}" @selected(old('organization_type', $ocopSubject?->organization_type?->value) === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                        @error('organization_type')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">{{ __('Mã số định danh') }}</span>
                            <span class="label-text-alt text-xs text-base-content/40">{{ __('MST hoặc CCCD (hộ kinh doanh)') }}</span>
                        </label>
                        <input type="text" name="tax_code" value="{{ old('tax_code', $ocopSubject?->tax_code) }}"
                               maxlength="20" inputmode="numeric"
                               placeholder="VD: 3301234567"
                               class="input input-bordered input-sm w-full font-mono @error('tax_code') input-error @enderror">
                        @error('tax_code')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">{{ __('Người đại diện pháp luật') }}</span>
                        </label>
                        <input type="text" name="legal_representative" value="{{ old('legal_representative', $ocopSubject?->legal_representative) }}"
                               maxlength="150"
                               class="input input-bordered input-sm w-full @error('legal_representative') input-error @enderror">
                        @error('legal_representative')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">{{ __('Chức vụ') }}</span>
                        </label>
                        <input type="text" name="position" value="{{ old('position', $ocopSubject?->position) }}" maxlength="100"
                               placeholder="{{ __('VD: Giám đốc, Chủ nhiệm HTX, Chủ hộ') }}"
                               class="input input-bordered input-sm w-full @error('position') input-error @enderror">
                        @error('position')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <h3 class="text-sm font-semibold text-base-content">{{ __('Địa chỉ & Định vị') }}</h3>
                    <span class="h-px flex-1 bg-base-200"></span>
                </div>

                <x-address-picker
                    instance-id="ocop-subject"
                    :required="true"
                    :province-value="old('province_code', $ocopSubject?->province_code)"
                    :ward-value="old('ward_code', $ocopSubject?->ward_code)"
                />

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">{{ __('Địa chỉ trụ sở') }}</span>
                        <span class="label-text-alt text-xs text-base-content/40">{{ __('Số nhà, đường, thôn/xóm') }}</span>
                    </label>
                    <input type="text" name="address" value="{{ old('address', $ocopSubject?->address) }}"
                           maxlength="255"
                           class="input input-bordered input-sm w-full @error('address') input-error @enderror">
                    @error('address')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">{{ __('Toạ độ GPS') }}</span>
                            <span class="label-text-alt text-xs text-base-content/40">{{ __('vĩ độ, kinh độ') }}</span>
                        </label>
                        <input type="text" name="gps_coordinates" value="{{ old('gps_coordinates', $ocopSubject?->gps_coordinates) }}"
                               maxlength="60" placeholder="VD: 16.4637, 107.5909"
                               class="input input-bordered input-sm w-full font-mono @error('gps_coordinates') input-error @enderror">
                        @error('gps_coordinates')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">{{ __('Mã cơ sở / Mã vùng trồng') }}</span>
                            <span class="label-text-alt text-xs text-base-content/40">{{ __('Không bắt buộc') }}</span>
                        </label>
                        <input type="text" name="factory_code" value="{{ old('factory_code', $ocopSubject?->factory_code) }}" maxlength="50"
                               class="input input-bordered input-sm w-full font-mono @error('factory_code') input-error @enderror">
                        @error('factory_code')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <h3 class="text-sm font-semibold text-base-content">{{ __('Liên hệ') }}</h3>
                    <span class="h-px flex-1 bg-base-200"></span>
                </div>

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

            <div x-show="tab === 'certs'" x-cloak data-tab-label="{{ __('Chứng nhận & Hồ sơ') }}" class="space-y-6">
                <label class="flex items-center justify-between gap-3 rounded-lg border border-base-200 px-3 py-2.5 cursor-pointer select-none">
                    <span>
                        <span class="block text-sm font-medium">{{ __('Ngành thực phẩm') }}</span>
                        <span class="block text-xs text-base-content/50 mt-0.5">{{ __('Bắt buộc có Giấy chứng nhận cơ sở đủ điều kiện ATTP.') }}</span>
                    </span>
                    <input type="hidden" name="is_food_business" value="0">
                    <input type="checkbox" name="is_food_business" value="1" x-model="isFood" class="toggle toggle-primary toggle-sm shrink-0">
                </label>

                @include('ocopsubject::admin._document-field', [
                    'collection' => $P::DOC_BUSINESS_LICENSE,
                    'label' => __('Giấy chứng nhận đăng ký kinh doanh'),
                    'hint' => __('Giấy ĐKDN, giấy đăng ký HTX hoặc giấy đăng ký hộ kinh doanh.'),
                ])

                <div class="flex items-center gap-3">
                    <h3 class="text-sm font-semibold text-base-content">{{ __('Chứng nhận OCOP') }}</h3>
                    <span class="h-px flex-1 bg-base-200"></span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">{{ __('Hạng sao OCOP') }} <span x-show="orgType === 'household'" x-cloak class="text-error">*</span></span>
                        </label>
                        <select id="ts-ocop_star" name="ocop_star"
                                class="select select-bordered select-sm w-full ts-init @error('ocop_star') select-error @enderror"
                                data-ts-placeholder="{{ __('— Chưa xếp hạng —') }}">
                            <option value=""></option>
                            @foreach([3, 4, 5] as $star)
                            <option value="{{ $star }}" @selected((string) old('ocop_star', $ocopSubject?->ocop_star) === (string) $star)>{{ $star }} sao</option>
                            @endforeach
                        </select>
                        @error('ocop_star')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">{{ __('Ngày hết hạn chứng nhận') }}</span>
                        </label>
                        <input type="date" name="ocop_cert_expiry" value="{{ old('ocop_cert_expiry', $ocopSubject?->ocop_cert_expiry?->format('Y-m-d')) }}"
                               class="input input-bordered input-sm w-full @error('ocop_cert_expiry') input-error @enderror">
                        @error('ocop_cert_expiry')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                </div>

                @include('ocopsubject::admin._document-field', [
                    'collection' => $P::DOC_OCOP_CERT,
                    'label' => __('Quyết định phê duyệt hạng sao OCOP'),
                    'hint' => __('Bắt buộc với Hộ kinh doanh, tuỳ chọn với Doanh nghiệp/HTX.'),
                    'requiredWhen' => "orgType === 'household'",
                ])

                <div class="flex items-center gap-3">
                    <h3 class="text-sm font-semibold text-base-content">{{ __('Chất lượng') }} &amp; {{ __('An toàn thực phẩm') }}</h3>
                    <span class="h-px flex-1 bg-base-200"></span>
                </div>

                @include('ocopsubject::admin._document-field', [
                    'collection' => $P::DOC_QUALITY_CERT,
                    'label' => __('Chứng nhận chất lượng'),
                    'hint' => __('ISO, HACCP, VietGAP, GlobalGAP, hữu cơ...'),
                ])

                @include('ocopsubject::admin._document-field', [
                    'collection' => $P::DOC_FOOD_SAFETY_CERT,
                    'label' => __('Giấy chứng nhận cơ sở đủ điều kiện ATTP'),
                    'requiredWhen' => 'isFood',
                ])

            </div>

            <div x-show="tab === 'story'" x-cloak data-tab-label="{{ __('Câu chuyện') }}" class="space-y-4">
                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">{{ __('Câu chuyện thương hiệu') }}</span>
                        <span class="label-text-alt text-xs text-base-content/40">{{ __('Lịch sử, truyền thống, triết lý sản xuất — hiển thị ở trang thương hiệu công khai') }}</span>
                    </label>
                    <textarea id="ocop-subject-story" name="story" class="jodit-editor" data-jodit-preset="standard">{{ old('story', $ocopSubject?->story) }}</textarea>
                    @error('story')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>
            </div>

            @if(\App\Support\I18n\PortalLocale::prefixed())
            <div x-show="tab === 'translation'" x-cloak data-tab-label="{{ __('Bản dịch') }}" class="space-y-4">
                <div class="flex items-start gap-2 rounded-lg bg-info/5 px-3 py-2.5 text-xs text-base-content/60">
                    <svg class="w-4 h-4 text-info shrink-0 mt-px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="leading-relaxed">{{ __('Thông tin pháp lý, địa chỉ, chứng nhận và liên hệ dùng chung cho mọi ngôn ngữ — chỉ cần dịch tên và câu chuyện thương hiệu.') }}</p>
                </div>
                <x-admin.translation-fields :model="$ocopSubject" :fields="[
                    'name' => ['label' => __('Tên chủ thể'), 'maxlength' => 255],
                    'story' => ['label' => __('Câu chuyện thương hiệu'), 'type' => 'editor', 'preset' => 'standard'],
                ]" />
            </div>
            @endif

        </div>
    </div>

    <aside class="xl:sticky xl:top-4 space-y-4">

        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 gap-0">
                <label class="flex items-center justify-between gap-3 cursor-pointer select-none">
                    <span>
                        <span class="block text-sm font-semibold">{{ __('Đang hoạt động') }}</span>
                        <span class="block text-xs text-base-content/50 mt-0.5">{{ __('Tắt để ngừng hiển thị trang thương hiệu') }}</span>
                    </span>
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="toggle toggle-success toggle-sm shrink-0" @checked($isActive)>
                </label>

                <div class="flex gap-2 mt-4">
                    <a href="{{ route('backend.ocop-subjects.index') }}" class="btn btn-ghost btn-sm flex-1">{{ __('Hủy') }}</a>
                    <button type="submit" class="btn btn-primary btn-sm flex-[2] gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $ocopSubject ? 'M5 13l4 4L19 7' : 'M12 4v16m8-8H4' }}"/></svg>
                        {{ $ocopSubject ? __('Lưu thay đổi') : __('Tạo mới') }}
                    </button>
                </div>
            </div>
        </section>

        @if($ocopSubject)
        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 gap-0">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">{{ __('Thông tin') }}</p>
                <dl class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2 text-xs">
                    <dt class="text-base-content/50">{{ __('Sản phẩm OCOP') }}</dt>
                    <dd class="text-right font-medium tabular-nums">{{ number_format((int) $ocopSubject->products_count) }}</dd>
                    @if($ocopSubject->qrIdentity)
                    <dt class="text-base-content/50">{{ __('Mã QR') }}</dt>
                    <dd class="text-right font-mono font-semibold">{{ $ocopSubject->qrIdentity->qr_code }}</dd>
                    @endif
                    <dt class="text-base-content/50">{{ __('Cập nhật') }}</dt>
                    <dd class="text-right">{{ format_datetime($ocopSubject->updated_at) }}</dd>
                </dl>
                @if($ocopSubject->qrIdentity)
                <a href="{{ $ocopSubject->qrIdentity->publicUrl() }}" target="_blank" rel="noopener" class="mt-3 text-xs link link-hover link-primary break-all">{{ $ocopSubject->qrIdentity->publicUrl() }}</a>
                @endif
            </div>
        </section>
        @endif

        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 gap-0">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">{{ __('Ảnh cơ sở sản xuất') }}</p>
                    <span class="text-[11px] text-base-content/40">{{ __('Tối đa :count', ['count' => $P::MAX_IMAGES]) }}</span>
                </div>
                    @php($images = $ocopSubject?->getMedia($P::IMAGE_COLLECTION) ?? collect())
                    @if($images->isNotEmpty())
                    <div class="grid grid-cols-3 gap-2 mb-3">
                        @foreach($images as $image)
                        <label class="relative cursor-pointer" title="{{ __('Đánh dấu để xoá ảnh này khi lưu') }}">
                            <input type="checkbox" name="remove_media_uuids[]" value="{{ $image->uuid }}" class="peer sr-only"
                                   @checked(in_array($image->uuid, old('remove_media_uuids', []), true))>
                            <img src="{{ app(\App\Services\Media\MediaUrlService::class)->url($image, 'thumb') }}" alt=""
                                 width="96" height="96" class="aspect-square w-full h-auto rounded-lg border border-base-300 object-cover peer-checked:opacity-30 peer-checked:border-error">
                            <span class="absolute top-1 right-1 flex h-5 w-5 items-center justify-center rounded-full bg-base-100/90 text-xs text-error shadow peer-checked:bg-error peer-checked:text-white">✕</span>
                        </label>
                        @endforeach
                    </div>
                    @endif
                    @if($ocopSubject)
                    <div id="ocop-subject-gallery-filepond" data-context-type="ocop_subject" data-context-id="{{ $ocopSubject->id }}"
                         data-max-files="{{ max(1, $P::MAX_IMAGES - $images->count()) }}"></div>
                    <p class="text-xs text-base-content/40 mt-1.5">{{ __('Bấm ✕ trên ảnh để xoá khi lưu.') }}</p>
                    @else
                    <div id="ocop-subject-gallery-filepond"></div>
                    <input type="hidden" name="media_uuids" id="ocop-subject-gallery-media-uuids" value="">
                    @endif
                    @error('media_uuids')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>
        </section>

    </aside>

</div>
