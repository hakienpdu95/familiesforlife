{{-- Dùng chung create/edit — cùng convention Modules/Banner/resources/views/admin/banners/_form.blade.php.
     Tab-based form (docs/form-ui-spec.md §10) — 4 nhóm: cơ bản, nhà sản xuất, chi tiết & câu chuyện,
     hồ sơ & tiêu chuẩn. --}}
<div class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-6 items-start"
     x-data="{
        tab: 'basic',
        tabFields: {
            basic:    ['ocop_subject_id', 'name', 'category_id', 'star_rating', 'description', 'image'],
            producer: ['heritage_site_id', 'purchase_url'],
            details:  ['story', 'origin', 'production_date', 'shelf_life', 'ingredients', 'usage_instructions', 'storage_instructions'],
            documents: {{ Js::from(collect(\Modules\Ocop\Models\OcopProduct::DOCUMENT_COLLECTIONS)->map(fn ($c) => "documents.$c")->values()) }},
        },
        errs: {{ Js::from($errors->keys()) }},
        errCount(t) {
            return this.tabFields[t].filter(f => this.errs.some(e => e === f || e.startsWith(f + '.'))).length;
        },
        init() {
            const order = ['basic', 'producer', 'details', 'documents'];
            for (const t of order) {
                if (this.errCount(t) > 0) { this.tab = t; break; }
            }
        }
     }">

    {{-- ── Card chính với tab ──────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">

        {{-- Tab navigation --}}
        <div class="border-b border-base-200 px-3">
            <nav class="flex -mb-px overflow-x-auto whitespace-nowrap" role="tablist" aria-label="Form sections">

                <button type="button" role="tab" :aria-selected="tab === 'basic'"
                        @click="tab = 'basic'"
                        class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                        :class="tab === 'basic'
                            ? 'border-primary text-primary'
                            : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                    Thông tin sản phẩm
                    <span x-show="errCount('basic') > 0" x-text="errCount('basic')"
                          class="badge badge-error badge-xs"></span>
                </button>

                <button type="button" role="tab" :aria-selected="tab === 'producer'"
                        @click="tab = 'producer'"
                        class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                        :class="tab === 'producer'
                            ? 'border-primary text-primary'
                            : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                    Nhà sản xuất
                    <span x-show="errCount('producer') > 0" x-text="errCount('producer')"
                          class="badge badge-error badge-xs"></span>
                </button>

                <button type="button" role="tab" :aria-selected="tab === 'details'"
                        @click="tab = 'details'"
                        class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 transition-colors"
                        :class="tab === 'details'
                            ? 'border-primary text-primary'
                            : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                    Chi tiết &amp; Câu chuyện
                    <span x-show="errCount('details') > 0" x-text="errCount('details')"
                          class="badge badge-error badge-xs"></span>
                </button>

                <button type="button" role="tab" :aria-selected="tab === 'documents'"
                        @click="tab = 'documents'"
                        class="flex items-center gap-1.5 px-1 py-4 text-sm font-medium border-b-2 transition-colors"
                        :class="tab === 'documents'
                            ? 'border-primary text-primary'
                            : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                    Hồ sơ &amp; Tiêu chuẩn
                    <span x-show="errCount('documents') > 0" x-text="errCount('documents')"
                          class="badge badge-error badge-xs"></span>
                </button>

            </nav>
        </div>

        {{-- Tab panels --}}
        <div class="p-3">

            {{-- Panel: Thông tin sản phẩm --}}
            <div x-show="tab === 'basic'" data-tab-label="Thông tin sản phẩm" class="space-y-4">

                @php($selectedSubjectId = (string) old('ocop_subject_id', $selectedSubjectId ?? $product?->ocop_subject_id ?? ''))
                <div class="form-control"
                     x-data="{
                        subjects: {{ Js::from($ocopSubjects->keyBy('id')) }},
                        selectedId: {{ Js::from($selectedSubjectId) }},
                        get selected() { return this.subjects[this.selectedId] ?? null; },
                     }">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Chủ thể sản xuất <span class="text-error">*</span></span>
                        @can('create', \Modules\OcopSubject\Models\OcopSubject::class)
                        <a href="{{ route('backend.ocop-subjects.create') }}" target="_blank" rel="noopener"
                           class="label-text-alt link link-primary text-xs">+ Thêm chủ thể mới</a>
                        @endcan
                    </label>
                    <select id="ts-ocop_subject_id" name="ocop_subject_id"
                            data-req="Vui lòng chọn chủ thể sản xuất"
                            @change="selectedId = $event.target.value"
                            class="select select-bordered select-sm w-full ts-init @error('ocop_subject_id') select-error @enderror"
                            data-ts-placeholder="— Tìm theo tên hoặc mã số định danh —">
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
                            <dt class="text-base-content/50">Mã số định danh</dt>
                            <dd class="font-mono" x-text="selected?.tax_code"></dd>
                            <dt class="text-base-content/50">Địa chỉ</dt>
                            <dd x-text="selected?.address || '—'"></dd>
                        </dl>
                        <p class="mt-2 text-xs text-base-content/40">Tỉnh/thành và địa chỉ của sản phẩm lấy theo chủ thể này.</p>
                    </div>

                    @if($ocopSubjects->isEmpty())
                    <p class="mt-2 text-xs text-warning">Chưa có chủ thể nào — tạo chủ thể trước khi thêm sản phẩm.</p>
                    @endif
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Tên sản phẩm <span class="text-error">*</span></span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $product?->name) }}"
                           data-req="Vui lòng nhập tên sản phẩm"
                           data-val-maxlength="150"
                           class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                           placeholder="VD: Mè xửng Huế" maxlength="150" autofocus>
                    @error('name')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Danh mục</span>
                        </label>
                        {{-- spec/danhmuc.html — danh mục OCOP chính thức 3 cấp (Nhóm lớn → Nhóm →
                             Phân nhóm), $categoryTree đã phẳng hóa kèm depth (OcopCategory::flatTree()),
                             thụt lề bằng ideographic space (không bị trình duyệt gộp khoảng trắng
                             như space thường). Nhóm còn "con" (không phải cấp sâu nhất của nhánh)
                             bị disable — sản phẩm chỉ được gán vào đúng phân nhóm cụ thể nhất. --}}
                        <select id="ts-category_id" name="category_id"
                                class="select select-bordered select-sm w-full ts-init @error('category_id') select-error @enderror"
                                data-ts-placeholder="— Chọn danh mục —">
                            <option value="">— Chọn danh mục —</option>
                            @foreach($categoryTree as $row)
                            @php($cat = $row['category'])
                            <option value="{{ $cat->id }}"
                                    {{ (string) old('category_id', $product?->category_id) === (string) $cat->id ? 'selected' : '' }}
                                    {{ $cat->children->isNotEmpty() ? 'disabled' : '' }}>
                                {{ str_repeat('　', $row['depth']) }}{{ $row['depth'] > 0 ? '– ' : '' }}{{ $cat->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('category_id')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Hạng sao <span class="text-error">*</span></span>
                            <span class="label-text-alt text-xs text-base-content/40">OCOP quốc gia: 3–5 sao</span>
                        </label>
                        <select id="ts-star_rating" name="star_rating"
                                data-req="Vui lòng chọn hạng sao"
                                class="select select-bordered select-sm w-full ts-init @error('star_rating') select-error @enderror"
                                data-ts-placeholder="— Chọn —">
                            <option value="">— Chọn —</option>
                            @foreach([3, 4, 5] as $star)
                            <option value="{{ $star }}" {{ (string) old('star_rating', $product?->star_rating) === (string) $star ? 'selected' : '' }}>{{ $star }} sao</option>
                            @endforeach
                        </select>
                        @error('star_rating')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Mô tả</span>
                        <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                    </label>
                    <textarea name="description" rows="4"
                              class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror">{{ old('description', $product?->description) }}</textarea>
                    @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Ảnh sản phẩm</span>
                    </label>
                    @php($images = $product?->getMedia(\Modules\Ocop\Models\OcopProduct::IMAGE_COLLECTION) ?? collect())
                    @if($images->isNotEmpty())
                    <div class="flex flex-wrap gap-2 mb-2">
                        @foreach($images as $image)
                        <label class="relative cursor-pointer group" title="Đánh dấu để xoá ảnh này khi lưu">
                            <input type="checkbox" name="remove_media_uuids[]" value="{{ $image->uuid }}" class="peer sr-only"
                                   @checked(in_array($image->uuid, old('remove_media_uuids', []), true))>
                            <img src="{{ app(\App\Services\Media\MediaUrlService::class)->url($image, 'thumb') }}" alt=""
                                 class="h-20 w-20 rounded border border-base-300 object-cover peer-checked:opacity-30 peer-checked:border-error">
                            @if($loop->first)
                            <span class="badge badge-primary badge-xs absolute bottom-1 left-1">Ảnh chính</span>
                            @endif
                            <span class="absolute top-1 right-1 flex h-5 w-5 items-center justify-center rounded-full bg-base-100/90 text-xs text-error shadow peer-checked:bg-error peer-checked:text-white">✕</span>
                        </label>
                        @endforeach
                    </div>
                    @endif
                    @if($product)
                    <div id="gallery-filepond" data-context-type="ocop_product" data-context-id="{{ $product->id }}"
                         data-max-files="{{ max(1, \Modules\Ocop\Models\OcopProduct::MAX_IMAGES - $images->count()) }}"></div>
                    <p class="text-xs text-base-content/40 mt-1.5">Tối đa {{ \Modules\Ocop\Models\OcopProduct::MAX_IMAGES }} ảnh. Ảnh đầu tiên là ảnh chính. Bấm ✕ trên ảnh để xoá khi lưu.</p>
                    @else
                    <div id="gallery-filepond"></div>
                    <input type="hidden" name="media_uuids" id="gallery-media-uuids" value="">
                    <p class="text-xs text-base-content/40 mt-1.5">Tối đa {{ \Modules\Ocop\Models\OcopProduct::MAX_IMAGES }} ảnh. Ảnh đầu tiên là ảnh chính.</p>
                    @endif
                    @error('media_uuids')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                {{-- Tab footer: next --}}
                <div class="flex justify-end pt-2">
                    <button type="button" @click="tab = 'producer'" class="btn btn-ghost btn-sm gap-1.5">
                        Tiếp theo: Nhà sản xuất
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>

            </div>

            {{-- Panel: Nhà sản xuất --}}
            <div x-show="tab === 'producer'" data-tab-label="Nhà sản xuất" class="space-y-4">

                {{-- spec/Heritage_Technical_Specification.md §8.2 — tuỳ chọn, ưu tiên hiện di
                     tích/làng nghề (intangible/historical_monument) đầu danh sách. --}}
                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Làng nghề/di tích liên quan</span>
                        <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                    </label>
                    <select id="ts-heritage_site_id" name="heritage_site_id"
                            class="select select-bordered select-sm w-full ts-init @error('heritage_site_id') select-error @enderror"
                            data-ts-placeholder="— Không gắn di tích —">
                        <option value=""></option>
                        @foreach($heritageSites as $s)
                        <option value="{{ $s->id }}" {{ (string) old('heritage_site_id', $product?->heritage_site_id) === (string) $s->id ? 'selected' : '' }}>
                            {{ $s->name }}{{ $s->province_name ? " ({$s->province_name})" : '' }}
                        </option>
                        @endforeach
                    </select>
                    @error('heritage_site_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="divider my-1"></div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Link mua hàng</span>
                        <span class="label-text-alt text-xs text-base-content/40">Sàn TMĐT/liên hệ mua</span>
                    </label>
                    <input type="url" name="purchase_url" value="{{ old('purchase_url', $product?->purchase_url) }}"
                           data-val-url="URL không hợp lệ — phải bắt đầu bằng https://"
                           class="input input-bordered input-sm w-full @error('purchase_url') input-error @enderror"
                           maxlength="500" placeholder="https://...">
                    @error('purchase_url')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>

                {{-- Tab footer: prev --}}
                <div class="flex items-center justify-between pt-2">
                    <button type="button" @click="tab = 'basic'" class="btn btn-ghost btn-sm gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Thông tin sản phẩm
                    </button>
                    <button type="button" @click="tab = 'details'" class="btn btn-ghost btn-sm gap-1.5">
                        Tiếp theo: Chi tiết &amp; Câu chuyện
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>

            </div>

            {{-- Panel: Chi tiết & Câu chuyện --}}
            <div x-show="tab === 'details'" x-cloak data-tab-label="Chi tiết &amp; Câu chuyện" class="space-y-4">

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Câu chuyện sản phẩm</span>
                        <span class="label-text-alt text-xs text-base-content/40">Nguồn gốc, truyền thống, điểm khác biệt</span>
                    </label>
                    <textarea id="ocop-story" name="story" class="jodit-editor" data-jodit-preset="standard">{{ old('story', $product?->story) }}</textarea>
                    @error('story')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="divider my-1 text-xs text-base-content/40">Thông số kỹ thuật</div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @foreach(['origin' => ['Xuất xứ', 255, 'VD: Phú Vang, Thừa Thiên Huế'], 'production_date' => ['Ngày sản xuất', 100, 'VD: Xem trên bao bì'], 'shelf_life' => ['Hạn sử dụng', 100, 'VD: 12 tháng kể từ NSX']] as $field => [$label, $max, $placeholder])
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
                    <textarea name="{{ $field }}" rows="3" maxlength="5000" placeholder="{{ $placeholder }}"
                              class="textarea textarea-bordered textarea-sm w-full @error($field) textarea-error @enderror">{{ old($field, $product?->{$field}) }}</textarea>
                    @error($field)<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
                @endforeach

                <div class="flex items-center justify-between pt-2">
                    <button type="button" @click="tab = 'producer'" class="btn btn-ghost btn-sm gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Nhà sản xuất
                    </button>
                    <button type="button" @click="tab = 'documents'" class="btn btn-ghost btn-sm gap-1.5">
                        Tiếp theo: Hồ sơ &amp; Tiêu chuẩn
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>

            </div>

            {{-- Panel: Hồ sơ & Tiêu chuẩn --}}
            <div x-show="tab === 'documents'" x-cloak data-tab-label="Hồ sơ &amp; Tiêu chuẩn" class="space-y-5">

                <div>
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Nhãn mác &amp; Bao bì</p>
                    @include('ocop::admin.products._document-field', [
                        'collection' => 'ocop_label_docs',
                        'label' => 'Tài liệu thiết kế nhãn mác, bao bì',
                        'hint' => 'Tài liệu thiết kế nhãn sản phẩm đúng quy định pháp luật (thể hiện rõ tên, logo OCOP, hạn sử dụng, hướng dẫn bảo quản) và quy cách bao bì phù hợp với thị trường.',
                    ])
                </div>

                <div class="divider my-1"></div>

                <div class="space-y-5">
                    <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">Chất lượng &amp; Tiêu chuẩn kỹ thuật</p>
                    @include('ocop::admin.products._document-field', [
                        'collection' => 'ocop_quality_declaration',
                        'label' => 'Hồ sơ công bố chất lượng',
                        'hint' => 'Bản tự công bố sản phẩm hoặc số công bố tiêu chuẩn chất lượng (TCCS, TCVN, QCVN...).',
                    ])
                    @include('ocop::admin.products._document-field', [
                        'collection' => 'ocop_test_reports',
                        'label' => 'Phiếu kiểm nghiệm định kỳ',
                        'hint' => 'Kết quả kiểm nghiệm các chỉ tiêu an toàn thực phẩm, sinh học, hóa lý còn thời hạn.',
                    ])
                    @include('ocop::admin.products._document-field', [
                        'collection' => 'ocop_quality_certs',
                        'label' => 'Chứng nhận quản lý chất lượng',
                        'badge' => 'Bắt buộc với sản phẩm 4–5 sao',
                        'hint' => 'Hồ sơ chứng minh cơ sở đạt điều kiện ATTP, hoặc các tiêu chuẩn nâng cao như ISO, HACCP, GMP. Đặc biệt bắt buộc đối với sản phẩm hướng tới 4–5 sao.',
                    ])
                </div>

                <div class="flex items-center justify-between pt-2">
                    <button type="button" @click="tab = 'details'" class="btn btn-ghost btn-sm gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Chi tiết &amp; Câu chuyện
                    </button>
                    <span class="text-xs text-base-content/40">Điền xong? Nhấn <strong>{{ $product ? 'Lưu thay đổi' : 'Tạo mới' }}</strong> ở bên phải</span>
                </div>

            </div>

        </div>{{-- /tab panels --}}
    </div>{{-- /card chính --}}

    {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
    <div class="xl:sticky xl:top-4 space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-3">

                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">Xuất bản</p>

                <div class="form-control mb-4">
                    <label class="label py-0 pb-1">
                        <span class="label-text text-xs font-medium">Trạng thái <span class="text-error">*</span></span>
                    </label>
                    <select id="ts-status" name="status"
                            data-req="Vui lòng chọn trạng thái"
                            class="select select-bordered select-sm w-full ts-init @error('status') select-error @enderror"
                            data-ts-placeholder="— Chọn trạng thái —">
                        @foreach($statuses as $s)
                        <option value="{{ $s->value }}" {{ old('status', $product?->status?->value ?? 'draft') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                    @error('status')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <label class="flex items-start gap-2.5 cursor-pointer select-none group mb-4">
                    <input type="hidden" name="is_featured" value="0">
                    <input type="checkbox" name="is_featured" value="1"
                           class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0" {{ old('is_featured', $product?->is_featured) ? 'checked' : '' }}>
                    <span class="text-sm font-medium group-hover:text-primary transition-colors">Sản phẩm nổi bật</span>
                </label>

                <div class="form-control mb-3">
                    <label class="label py-0 pb-1">
                        <span class="label-text text-xs font-medium">Thứ tự hiển thị</span>
                    </label>
                    <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $product?->sort_order ?? 0) }}"
                           class="input input-bordered input-sm w-full @error('sort_order') input-error @enderror">
                    @error('sort_order')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('backend.ocop.products.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                    <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            @if($product)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            @endif
                        </svg>
                        {{ $product ? 'Lưu thay đổi' : 'Tạo mới' }}
                    </button>
                </div>

                <p class="text-center text-xs text-base-content/30 mt-2.5">
                    <span class="text-error">*</span> là trường bắt buộc
                </p>

            </div>
        </div>
    </div>

</div>
