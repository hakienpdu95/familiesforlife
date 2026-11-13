{{-- Dùng chung create/edit — tab-based form (docs/form-ui-spec.md §10). --}}
@php
    $affiliateFields = [
        ['shopee_url', 'Shopee', 'https://shopee.vn/...'],
        ['tiktok_url', 'TikTok Shop', 'https://vt.tiktok.com/...'],
    ];
    $isFeatured = (bool) old('is_featured', $product?->is_featured);
@endphp
<div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_320px] gap-6 items-start"
     x-data="{
        tab: 'basic',
        tabFields: {
            basic:    ['ocop_subject_id', 'heritage_site_id', 'name', 'category_id', 'star_rating', 'shelf_life_value', 'shelf_life_unit', 'description'],
            details:  ['story', 'origin', 'production_date', 'ingredients', 'usage_instructions', 'storage_instructions'],
            documents: {{ Js::from(collect(\Modules\Ocop\Models\OcopProduct::DOCUMENT_COLLECTIONS)->flatMap(fn ($c) => ["documents.$c", "document_uuids.$c"])->values()) }},
            translation: ['translations'],
        },
        errs: {{ Js::from($errors->keys()) }},
        errCount(t) {
            return this.tabFields[t].filter(f => this.errs.some(e => e === f || e.startsWith(f + '.'))).length;
        },
        init() {
            for (const t of ['basic', 'details', 'documents', 'translation']) {
                if (this.errCount(t) > 0) { this.tab = t; break; }
            }
        }
     }">

    <div class="card bg-base-100 shadow-sm border border-base-200 min-w-0">

        <div class="border-b border-base-200 px-5">
            <nav class="flex -mb-px overflow-x-auto whitespace-nowrap" role="tablist" aria-label="{{ __('Nhóm thông tin') }}">
                <button type="button" role="tab" :aria-selected="tab === 'basic'" @click="tab = 'basic'"
                        class="group flex items-center gap-2 px-1 py-3.5 mr-6 text-sm font-medium border-b-2 transition-colors"
                        :class="tab === 'basic' ? 'border-primary text-primary' : 'border-transparent text-base-content/55 hover:text-base-content hover:border-base-content/20'">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ __('Thông tin chung') }}
                    <span x-show="errCount('basic') > 0" x-text="errCount('basic')" class="badge badge-error badge-xs" x-cloak></span>
                </button>
                <button type="button" role="tab" :aria-selected="tab === 'details'" @click="tab = 'details'"
                        class="group flex items-center gap-2 px-1 py-3.5 mr-6 text-sm font-medium border-b-2 transition-colors"
                        :class="tab === 'details' ? 'border-primary text-primary' : 'border-transparent text-base-content/55 hover:text-base-content hover:border-base-content/20'">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    {{ __('Chi tiết') }} &amp; {{ __('Câu chuyện') }}
                    <span x-show="errCount('details') > 0" x-text="errCount('details')" class="badge badge-error badge-xs" x-cloak></span>
                </button>
                <button type="button" role="tab" :aria-selected="tab === 'documents'" @click="tab = 'documents'"
                        class="group flex items-center gap-2 px-1 py-3.5 mr-6 text-sm font-medium border-b-2 transition-colors"
                        :class="tab === 'documents' ? 'border-primary text-primary' : 'border-transparent text-base-content/55 hover:text-base-content hover:border-base-content/20'">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    {{ __('Hồ sơ') }} &amp; {{ __('Tiêu chuẩn') }}
                    <span x-show="errCount('documents') > 0" x-text="errCount('documents')" class="badge badge-error badge-xs" x-cloak></span>
                </button>
                @if(\App\Support\I18n\PortalLocale::prefixed())
                <button type="button" role="tab" :aria-selected="tab === 'translation'" @click="tab = 'translation'"
                        class="group flex items-center gap-2 px-1 py-3.5 mr-6 text-sm font-medium border-b-2 transition-colors"
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
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-base-content">{{ __('Nhà sản xuất') }}</h3>
                    </div>
                    <span class="h-px flex-1 bg-base-200"></span>
                </div>

                @php($selectedSubjectId = (string) old('ocop_subject_id', $selectedSubjectId ?? $product?->ocop_subject_id ?? ''))
                <div class="form-control"
                     x-data="{
                        subjects: {{ Js::from($ocopSubjects->keyBy('id')) }},
                        selectedId: {{ Js::from($selectedSubjectId) }},
                        get selected() { return this.subjects[this.selectedId] ?? null; },
                     }">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">{{ __('Chủ thể sản xuất') }} <span class="text-error">*</span></span>
                        @if(Route::has('backend.ocop-subjects.create'))
                        @can('create', \Modules\OcopSubject\Models\OcopSubject::class)
                        <a href="{{ route('backend.ocop-subjects.create') }}" target="_blank" rel="noopener"
                           class="label-text-alt link link-primary text-xs">{{ __('+ Thêm chủ thể mới') }}</a>
                        @endcan
                        @endif
                    </label>
                    <select id="ts-ocop_subject_id" name="ocop_subject_id"
                            data-req="{{ __('Vui lòng chọn chủ thể sản xuất') }}"
                            @change="selectedId = $event.target.value"
                            class="select select-bordered select-sm w-full ts-init @error('ocop_subject_id') select-error @enderror"
                            data-ts-placeholder="{{ __('— Tìm theo tên hoặc mã số định danh —') }}">
                        <option value=""></option>
                        @foreach($ocopSubjects as $p)
                        <option value="{{ $p['id'] }}" @selected($selectedSubjectId === (string) $p['id'])>
                            {{ $p['name'] }} — {{ $p['tax_code'] }}
                        </option>
                        @endforeach
                    </select>
                    @error('ocop_subject_id')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror

                    <div x-show="selected" x-cloak x-transition.opacity
                         class="mt-3 rounded-lg border border-base-300 bg-base-200/40 p-3">
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-semibold leading-snug" x-text="selected?.name"></p>
                            <span class="badge badge-ghost badge-sm shrink-0" x-text="selected?.type"></span>
                        </div>
                        <dl class="mt-2 grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-sm">
                            <dt class="text-base-content/50">{{ __('Mã số định danh') }}</dt>
                            <dd class="font-mono" x-text="selected?.tax_code"></dd>
                            <dt class="text-base-content/50">{{ __('Địa chỉ') }}</dt>
                            <dd x-text="selected?.address || '—'"></dd>
                        </dl>
                        <p class="mt-2 text-xs text-base-content/40">{{ __('Tỉnh/thành và địa chỉ của sản phẩm lấy theo chủ thể này.') }}</p>
                    </div>

                    @if($ocopSubjects->isEmpty())
                    <p class="mt-2 text-xs text-warning">{{ __('Chưa có chủ thể nào — tạo chủ thể trước khi thêm sản phẩm.') }}</p>
                    @endif
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">{{ __('Làng nghề/di tích liên quan') }}</span>
                        <span class="label-text-alt text-xs text-base-content/40">{{ __('Không bắt buộc') }}</span>
                    </label>
                    <select id="ts-heritage_site_id" name="heritage_site_id"
                            class="select select-bordered select-sm w-full ts-init @error('heritage_site_id') select-error @enderror"
                            data-ts-placeholder="{{ __('— Không gắn di tích —') }}">
                        <option value=""></option>
                        @foreach($heritageSites as $s)
                        <option value="{{ $s->id }}" {{ (string) old('heritage_site_id', $product?->heritage_site_id) === (string) $s->id ? 'selected' : '' }}>
                            {{ $s->name }}{{ $s->province_name ? " ({$s->province_name})" : '' }}
                        </option>
                        @endforeach
                    </select>
                    @error('heritage_site_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-3">
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-base-content">{{ __('Sản phẩm') }}</h3>
                    </div>
                    <span class="h-px flex-1 bg-base-200"></span>
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">{{ __('Tên sản phẩm') }} <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $product?->name) }}"
                           data-req="{{ __('Vui lòng nhập tên sản phẩm') }}"
                           data-val-maxlength="150"
                           class="input input-bordered w-full text-base font-medium @error('name') input-error @enderror"
                           placeholder="{{ __('VD: Mè xửng Huế') }}" maxlength="150">
                    @error('name')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">{{ __('Danh mục') }}</span>
                        </label>
                        {{-- spec/danhmuc.html — danh mục OCOP chính thức 3 cấp (Nhóm lớn → Nhóm →
                             Phân nhóm), $categoryTree đã phẳng hóa kèm depth (OcopCategory::flatTree()),
                             truyền qua data-depth để TomSelect render thụt lề theo cấp
                             (resources/js/shared/ts-tree-option.js). Nhóm còn "con" (không phải cấp sâu nhất của nhánh)
                             bị disable — sản phẩm chỉ được gán vào đúng phân nhóm cụ thể nhất. --}}
                        <select id="ts-category_id" name="category_id"
                                class="select select-bordered select-sm w-full ts-init @error('category_id') select-error @enderror"
                                data-ts-placeholder="{{ __('— Chọn danh mục —') }}">
                            <option value="">{{ __('— Chọn danh mục —') }}</option>
                            @foreach($categoryTree as $row)
                            @php($cat = $row['category'])
                            <option value="{{ $cat->id }}"
                                    {{ (string) old('category_id', $product?->category_id) === (string) $cat->id ? 'selected' : '' }}
                                    data-depth="{{ $row['depth'] }}"
                                    {{ $cat->children->isNotEmpty() ? 'disabled' : '' }}>
                                {{ $cat->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('category_id')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">{{ __('Hạng sao') }} <span class="text-error">*</span></span>
                            <span class="label-text-alt text-xs text-base-content/40">{{ __('OCOP quốc gia: 3–5 sao') }}</span>
                        </label>
                        <select id="ts-star_rating" name="star_rating"
                                data-req="{{ __('Vui lòng chọn hạng sao') }}"
                                class="select select-bordered select-sm w-full ts-init @error('star_rating') select-error @enderror"
                                data-ts-placeholder="{{ __('— Chọn —') }}">
                            <option value="">{{ __('— Chọn —') }}</option>
                            @foreach([3, 4, 5] as $star)
                            <option value="{{ $star }}" {{ (string) old('star_rating', $product?->star_rating) === (string) $star ? 'selected' : '' }}>{{ $star }} sao</option>
                            @endforeach
                        </select>
                        @error('star_rating')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>

                    @php($shelfUnit = old('shelf_life_unit', $product?->shelf_life_unit))
                    <div class="form-control">
                        <label class="label py-0 pb-1.5" for="shelf_life_value">
                            <span class="label-text font-medium">{{ __('Hạn sử dụng') }}</span>
                            <span class="label-text-alt text-xs text-base-content/40">{{ __('kể từ ngày sản xuất') }}</span>
                        </label>
                        <div class="grid grid-cols-[minmax(0,1fr)_7.5rem] gap-2">
                            <input type="number" id="shelf_life_value" name="shelf_life_value" inputmode="numeric" min="1" max="9999" step="1"
                                   value="{{ old('shelf_life_value', $product?->shelf_life_value) }}" placeholder="VD: 12"
                                   class="input input-bordered input-sm w-full @error('shelf_life_value') input-error @enderror">
                            <select id="ts-shelf_life_unit" name="shelf_life_unit" aria-label="{{ __('Đơn vị hạn sử dụng') }}"
                                    class="select select-bordered select-sm w-full ts-init @error('shelf_life_unit') select-error @enderror"
                                    data-ts-placeholder="{{ __('Đơn vị') }}">
                                <option value="">{{ __('Đơn vị') }}</option>
                                @foreach(array_map('__', \Modules\Ocop\Models\OcopProduct::SHELF_LIFE_UNITS) as $unit => $unitLabel)
                                <option value="{{ $unit }}" @selected($shelfUnit === $unit)>{{ $unitLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                        @error('shelf_life_value')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                        @error('shelf_life_unit')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">{{ __('Mô tả') }}</span>
                        <span class="label-text-alt text-xs text-base-content/40">{{ __('Không bắt buộc') }}</span>
                    </label>
                    <textarea id="ocop-description" name="description" class="jodit-editor" data-jodit-preset="standard" data-jodit-height="220">{{ old('description', \Modules\Ocop\Models\OcopProduct::richText($product?->description)) }}</textarea>
                    @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
            </div>

            <div x-show="tab === 'details'" x-cloak data-tab-label="{{ __('Chi tiết & Câu chuyện') }}" class="space-y-5">
                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">{{ __('Câu chuyện sản phẩm') }}</span>
                        <span class="label-text-alt text-xs text-base-content/40">{{ __('Nguồn gốc, truyền thống, điểm khác biệt') }}</span>
                    </label>
                    <textarea id="ocop-story" name="story" class="jodit-editor" data-jodit-preset="standard">{{ old('story', $product?->story) }}</textarea>
                    @error('story')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <h3 class="text-sm font-semibold text-base-content">{{ __('Thông số kỹ thuật') }}</h3>
                    <span class="h-px flex-1 bg-base-200"></span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach(['origin' => ['Xuất xứ', 255, 'VD: Phú Vang, Thừa Thiên Huế'], 'production_date' => ['Ngày sản xuất', 100, 'VD: Xem trên bao bì']] as $field => [$label, $max, $placeholder])
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">{{ $label }}</span>
                        </label>
                        <input type="text" name="{{ $field }}" value="{{ old($field, $product?->{$field}) }}"
                               data-val-maxlength="{{ $max }}" maxlength="{{ $max }}" placeholder="{{ $placeholder }}"
                               class="input input-bordered input-sm w-full @error($field) input-error @enderror">
                        @error($field)<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                    @endforeach
                </div>

                @foreach(['ingredients' => ['Thành phần', 'VD: Trà xanh 70%, hoa sen 20%, cam thảo 10%'], 'usage_instructions' => ['Hướng dẫn sử dụng', 'VD: Hãm 5g trà với 200ml nước 90°C trong 3–5 phút'], 'storage_instructions' => ['Hướng dẫn bảo quản', 'VD: Nơi khô ráo, thoáng mát, tránh ánh nắng trực tiếp']] as $field => [$label, $placeholder])
                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">{{ $label }}</span>
                    </label>
                    <textarea id="ocop-{{ str_replace('_', '-', $field) }}" name="{{ $field }}" class="jodit-editor" data-jodit-preset="standard" data-jodit-height="200" placeholder="{{ $placeholder }}">{{ old($field, \Modules\Ocop\Models\OcopProduct::richText($product?->{$field})) }}</textarea>
                    @error($field)<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
                @endforeach
            </div>

            <div x-show="tab === 'documents'" x-cloak data-tab-label="{{ __('Hồ sơ & Tiêu chuẩn') }}" class="space-y-6">
                <div>
                    <div class="flex items-center gap-3 mb-4"><h3 class="text-sm font-semibold text-base-content">{{ __('Nhãn mác') }} &amp; {{ __('Bao bì') }}</h3><span class="h-px flex-1 bg-base-200"></span></div>
                    @include('ocop::admin.products._document-field', [
                        'collection' => 'ocop_label_docs',
                        'label' => __('Tài liệu thiết kế nhãn mác, bao bì'),
                        'hint' => __('Tài liệu thiết kế nhãn sản phẩm đúng quy định pháp luật (thể hiện rõ tên, logo OCOP, hạn sử dụng, hướng dẫn bảo quản) và quy cách bao bì phù hợp với thị trường.'),
                    ])
                </div>


                <div class="space-y-5">
                    <div class="flex items-center gap-3"><h3 class="text-sm font-semibold text-base-content">{{ __('Chất lượng') }} &amp; {{ __('Tiêu chuẩn kỹ thuật') }}</h3><span class="h-px flex-1 bg-base-200"></span></div>
                    @include('ocop::admin.products._document-field', [
                        'collection' => 'ocop_quality_declaration',
                        'label' => __('Hồ sơ công bố chất lượng'),
                        'hint' => __('Bản tự công bố sản phẩm hoặc số công bố tiêu chuẩn chất lượng (TCCS, TCVN, QCVN...).'),
                    ])
                    @include('ocop::admin.products._document-field', [
                        'collection' => 'ocop_test_reports',
                        'label' => __('Phiếu kiểm nghiệm định kỳ'),
                        'hint' => __('Kết quả kiểm nghiệm các chỉ tiêu an toàn thực phẩm, sinh học, hóa lý còn thời hạn.'),
                    ])
                    @include('ocop::admin.products._document-field', [
                        'collection' => 'ocop_quality_certs',
                        'label' => __('Chứng nhận quản lý chất lượng'),
                        'badge' => __('Bắt buộc với sản phẩm 4–5 sao'),
                        'hint' => __('Hồ sơ chứng minh cơ sở đạt điều kiện ATTP, hoặc các tiêu chuẩn nâng cao như ISO, HACCP, GMP. Đặc biệt bắt buộc đối với sản phẩm hướng tới 4–5 sao.'),
                    ])
                </div>
            </div>

            @if(\App\Support\I18n\PortalLocale::prefixed())
            <div x-show="tab === 'translation'" x-cloak data-tab-label="{{ __('Bản dịch') }}" class="space-y-4">
                <div class="flex items-start gap-2 rounded-lg bg-info/5 px-3 py-2.5 text-xs text-base-content/60">
                    <svg class="w-4 h-4 text-info shrink-0 mt-px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="leading-relaxed">{{ __('Ảnh, hạng sao, hồ sơ và link mua dùng chung cho mọi ngôn ngữ — chỉ cần dịch phần chữ bên dưới.') }}</p>
                </div>
                <x-admin.translation-fields :model="$product" :fields="[
                    'name' => ['label' => __('Tên sản phẩm'), 'maxlength' => 150, 'hint' => __('Đường dẫn bản dịch tự sinh từ tên'), 'section' => __('Giới thiệu')],
                    'description' => ['label' => __('Mô tả'), 'type' => 'editor', 'preset' => 'standard'],
                    'story' => ['label' => __('Câu chuyện sản phẩm'), 'type' => 'editor', 'preset' => 'standard'],
                    'origin' => ['label' => __('Xuất xứ'), 'maxlength' => 255, 'section' => __('Thông số kỹ thuật')],
                    'ingredients' => ['label' => __('Thành phần'), 'type' => 'editor', 'preset' => 'standard'],
                    'usage_instructions' => ['label' => __('Hướng dẫn sử dụng'), 'type' => 'editor', 'preset' => 'standard'],
                    'storage_instructions' => ['label' => __('Hướng dẫn bảo quản'), 'type' => 'editor', 'preset' => 'standard'],
                ]" />
            </div>
            @endif

        </div>
    </div>

    <aside class="xl:sticky xl:top-4 space-y-4">

        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 gap-0 space-y-4">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">{{ __('Xuất bản') }}</p>

                <div class="form-control mb-4">
                    <label class="label py-0 pb-1">
                        <span class="label-text text-xs font-medium">{{ __('Trạng thái') }} <span class="text-error">*</span></span>
                    </label>
                    <select id="ts-status" name="status"
                            data-req="{{ __('Vui lòng chọn trạng thái') }}"
                            class="select select-bordered select-sm w-full ts-init @error('status') select-error @enderror"
                            data-ts-placeholder="{{ __('— Chọn trạng thái —') }}">
                        @foreach($statuses as $s)
                        <option value="{{ $s->value }}" {{ old('status', $product?->status?->value ?? 'draft') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                    @error('status')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <label class="flex items-center justify-between gap-3 cursor-pointer select-none">
                    <span>
                        <span class="block text-sm font-medium">{{ __('Sản phẩm nổi bật') }}</span>
                        <span class="block text-xs text-base-content/50 mt-0.5">{{ __('Ưu tiên hiển thị ở trang OCOP') }}</span>
                    </span>
                    <input type="hidden" name="is_featured" value="0">
                    <input type="checkbox" name="is_featured" value="1" class="toggle toggle-primary toggle-sm shrink-0" @checked($isFeatured)>
                </label>

                <div class="form-control">
                    <label class="label py-0 pb-1.5" for="ocop-sort-order">
                        <span class="label-text font-medium">{{ __('Thứ tự hiển thị') }}</span>
                        <span class="label-text-alt text-xs text-base-content/40">{{ __('Nhỏ đứng trước') }}</span>
                    </label>
                    <input type="number" id="ocop-sort-order" name="sort_order" min="0" value="{{ old('sort_order', $product?->sort_order ?? 0) }}"
                           class="input input-bordered input-sm w-full @error('sort_order') input-error @enderror">
                    @error('sort_order')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="flex gap-2 pt-1">
                    <a href="{{ route('backend.ocop.products.index') }}" class="btn btn-ghost btn-sm flex-1">{{ __('Hủy') }}</a>
                    <button type="submit" class="btn btn-primary btn-sm flex-[2] gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $product ? 'M5 13l4 4L19 7' : 'M12 4v16m8-8H4' }}"/></svg>
                        {{ $product ? __('Lưu thay đổi') : __('Tạo mới') }}
                    </button>
                </div>
            </div>
        </section>

        @if($product?->qrIdentity)
        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 gap-0">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-2">{{ __('Tem truy xuất QR') }}</p>
                <p class="font-mono text-sm font-semibold">{{ $product->qrIdentity->qr_code }}</p>
                <a href="{{ $product->qrIdentity->publicUrl() }}" target="_blank" rel="noopener" class="mt-1 text-xs link link-hover link-primary break-all">{{ $product->qrIdentity->publicUrl() }}</a>
            </div>
        </section>
        @endif

        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 gap-0">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">{{ __('Ảnh sản phẩm') }}</p>
                    <span class="text-[11px] text-base-content/40">{{ __('Tối đa :count', ['count' => \Modules\Ocop\Models\OcopProduct::MAX_IMAGES]) }}</span>
                </div>
                    @php($images = $product?->getMedia(\Modules\Ocop\Models\OcopProduct::IMAGE_COLLECTION) ?? collect())
                    @if($images->isNotEmpty())
                    <div class="grid grid-cols-3 gap-2 mb-3">
                        @foreach($images as $image)
                        <label class="relative cursor-pointer group" title="{{ __('Đánh dấu để xoá ảnh này khi lưu') }}">
                            <input type="checkbox" name="remove_media_uuids[]" value="{{ $image->uuid }}" class="peer sr-only"
                                   @checked(in_array($image->uuid, old('remove_media_uuids', []), true))>
                            <img src="{{ app(\App\Services\Media\MediaUrlService::class)->url($image, 'thumb') }}" alt=""
                                 width="96" height="96" class="aspect-square w-full h-auto rounded-lg border border-base-300 object-cover peer-checked:opacity-30 peer-checked:border-error">
                            @if($loop->first)
                            <span class="badge badge-primary badge-xs absolute bottom-1 left-1">{{ __('Ảnh chính') }}</span>
                            @endif
                            <span class="absolute top-1 right-1 flex h-5 w-5 items-center justify-center rounded-full bg-base-100/90 text-xs text-error shadow peer-checked:bg-error peer-checked:text-white">✕</span>
                        </label>
                        @endforeach
                    </div>
                    @endif
                    @if($product)
                    <div id="gallery-filepond" data-context-type="ocop_product" data-context-id="{{ $product->id }}"
                         data-max-files="{{ max(1, \Modules\Ocop\Models\OcopProduct::MAX_IMAGES - $images->count()) }}"></div>
                    <p class="text-xs text-base-content/40 mt-1.5">{{ __('Ảnh đầu tiên là ảnh chính. Bấm ✕ để xoá khi lưu.') }}</p>
                    @else
                    <div id="gallery-filepond"></div>
                    <input type="hidden" name="media_uuids" id="gallery-media-uuids" value="">
                    <p class="text-xs text-base-content/40 mt-1.5">{{ __('Ảnh đầu tiên là ảnh chính.') }}</p>
                    @endif
                    @error('media_uuids')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>
        </section>

        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 gap-0 space-y-3">
                <div>
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">{{ __('Kênh mua hàng') }}</p>
                    <p class="mt-1 text-xs text-base-content/50">{{ __('Trang chi tiết hiển thị nút mua tương ứng.') }}</p>
                </div>
                @foreach($affiliateFields as [$field, $label, $placeholder])
                <div class="form-control">
                    <label class="label py-0 pb-1.5" for="ocop-{{ $field }}">
                        <span class="label-text font-medium">{{ $label }}</span>
                    </label>
                    <div class="join w-full">
                        <input type="url" id="ocop-{{ $field }}" name="{{ $field }}" value="{{ old($field, $product?->{$field}) }}"
                               data-val-url="{{ __('URL phải bắt đầu bằng https://') }}"
                               data-affiliate-input
                               class="input input-bordered input-sm join-item flex-1 min-w-0 @error($field) input-error @enderror"
                               placeholder="{{ $placeholder }}" maxlength="500">
                        <a href="#" target="_blank" rel="noopener noreferrer" data-affiliate-open
                           class="btn btn-sm join-item btn-disabled" aria-disabled="true" title="{{ __('Mở link để kiểm tra') }}">↗</a>
                    </div>
                    @error($field)<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>
                @endforeach
            </div>
        </section>

    </aside>

</div>
