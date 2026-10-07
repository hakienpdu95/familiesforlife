{{-- spec/Heritage_Technical_Specification.md §5.1 · docs/form-ui-spec.md §10 Tab-Based Form + §11 Sticky Submit Bar --}}
<div x-data="{
    tab: 'basic',
    tabFields: {
        basic:    ['name', 'slug', 'heritage_type', 'rank', 'era', 'visiting_status', 'description', 'content'],
        location: ['province_code', 'ward_code', 'address', 'latitude', 'longitude'],
        display:  ['cover_media_uuid', 'is_featured', 'sort_order'],
    },
    errs: {{ Js::from($errors->keys()) }},
    errCount(t) {
        return this.tabFields[t].filter(f => this.errs.includes(f)).length;
    },
    init() {
        for (const t of Object.keys(this.tabFields)) {
            if (this.errCount(t) > 0) { this.tab = t; break; }
        }
    }
}">

<form method="POST"
      action="{{ $site ? route('backend.heritage.sites.update', $site) : route('backend.heritage.sites.store') }}"
      novalidate data-heritage-site-form>
    @csrf
    @if($site) @method('PUT') @endif

    <div class="card bg-base-100 shadow-sm border border-base-200">

        <div class="border-b border-base-200 px-3">
            <nav class="flex -mb-px overflow-x-auto" role="tablist" aria-label="Các nhóm thông tin di tích">
                @foreach(['basic' => 'Thông tin cơ bản', 'location' => 'Địa điểm', 'display' => 'Ảnh & hiển thị'] as $key => $label)
                <button type="button" role="tab" :aria-selected="tab === '{{ $key }}'"
                        @click="tab = '{{ $key }}'"
                        class="flex items-center gap-1.5 px-1 py-4 mr-6 text-sm font-medium border-b-2 whitespace-nowrap transition-colors"
                        :class="tab === '{{ $key }}'
                            ? 'border-primary text-primary'
                            : 'border-transparent text-base-content/50 hover:text-base-content hover:border-base-content/20'">
                    {{ $label }}
                    <span x-show="errCount('{{ $key }}') > 0" x-text="errCount('{{ $key }}')" x-cloak
                          class="badge badge-error badge-xs"></span>
                </button>
                @endforeach
            </nav>
        </div>

        <div class="p-3">

            {{-- ── Tab 1: Thông tin cơ bản ─────────────────────────────── --}}
            <div x-show="tab === 'basic'" data-tab-label="Thông tin cơ bản" class="space-y-4">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tên di tích <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="name" value="{{ old('name', $site?->name) }}"
                               data-req="Vui lòng nhập tên di tích"
                               data-val-maxlength="200"
                               class="input input-bordered input-sm w-full @error('name') input-error @enderror"
                               placeholder="VD: Đại Nội Huế" maxlength="200" autofocus>
                        @error('name')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Đường dẫn (slug)</span>
                            <span class="label-text-alt text-xs text-base-content/40">
                                {{ $site ? 'Thận trọng khi thay đổi — link cũ sẽ hỏng' : 'Tự động tạo từ tên nếu để trống' }}
                            </span>
                        </label>
                        <input type="text" name="slug" value="{{ old('slug', $site?->slug) }}"
                               data-val-maxlength="220"
                               data-val-pattern="^[A-Za-z0-9_-]*$"
                               data-val-pattern-msg="Chỉ dùng chữ không dấu, số, dấu - và _"
                               class="input input-bordered input-sm w-full font-mono @error('slug') input-error @enderror"
                               maxlength="220" placeholder="dai-noi-hue">
                        <p class="mt-1 text-xs text-base-content/40">
                            Chỉ dùng chữ thường không dấu, số và dấu <code class="bg-base-200 px-1 rounded">-</code>
                        </p>
                        @error('slug')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Loại hình <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-heritage_type" name="heritage_type"
                                data-req="Vui lòng chọn loại hình"
                                data-ts-placeholder="— Chọn loại hình —"
                                class="select select-bordered select-sm w-full ts-init @error('heritage_type') select-error @enderror">
                            @foreach($heritageTypes as $t)
                            <option value="{{ $t->value }}" {{ (string) old('heritage_type', $site?->heritage_type?->value ?? 'historical_monument') === $t->value ? 'selected' : '' }}>{{ $t->label() }}</option>
                            @endforeach
                        </select>
                        @error('heritage_type')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Xếp hạng <span class="text-error">*</span></span>
                        </label>
                        <select id="ts-rank" name="rank"
                                data-req="Vui lòng chọn xếp hạng"
                                data-ts-placeholder="— Chọn xếp hạng —"
                                class="select select-bordered select-sm w-full ts-init @error('rank') select-error @enderror">
                            @foreach($ranks as $r)
                            <option value="{{ $r->value }}" {{ (string) old('rank', $site?->rank?->value ?? 'unranked') === $r->value ? 'selected' : '' }}>{{ $r->label() }}</option>
                            @endforeach
                        </select>
                        @error('rank')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Niên đại</span>
                        </label>
                        <input type="text" name="era" value="{{ old('era', $site?->era) }}"
                               data-val-maxlength="100"
                               class="input input-bordered input-sm w-full @error('era') input-error @enderror"
                               maxlength="100" placeholder="VD: Thế kỷ 11, Thời Lý - Trần">
                        @error('era')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Tình trạng tham quan</span>
                        </label>
                        <select id="ts-visiting_status" name="visiting_status"
                                data-ts-placeholder="— Chọn tình trạng —"
                                class="select select-bordered select-sm w-full ts-init @error('visiting_status') select-error @enderror">
                            @foreach($visitingStatuses as $vs)
                            <option value="{{ $vs->value }}" {{ (string) old('visiting_status', $site?->visiting_status?->value ?? 'unknown') === $vs->value ? 'selected' : '' }}>{{ $vs->label() }}</option>
                            @endforeach
                        </select>
                        @error('visiting_status')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="form-control" x-data="{ len: {{ Js::from(mb_strlen((string) old('description', $site?->description))) }} }">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Mô tả</span>
                        <span class="label-text-alt text-xs text-base-content/40"><span x-text="len">0</span>/3000</span>
                    </label>
                    <textarea name="description" rows="5" maxlength="3000"
                              data-val-maxlength="3000"
                              @input="len = $el.value.length"
                              class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror"
                              placeholder="Giới thiệu ngắn về lịch sử, giá trị và đặc điểm nổi bật của di tích...">{{ old('description', $site?->description) }}</textarea>
                    <p class="mt-1 text-xs text-base-content/40">Văn bản thường, không định dạng.</p>
                    @error('description')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Nội dung</span>
                        <span class="label-text-alt text-xs text-base-content/40">Lịch sử, kiến trúc, giá trị văn hoá — có thể chèn ảnh, bảng, video</span>
                    </label>
                    <textarea id="heritage-content" name="content"
                              class="jodit-editor textarea textarea-bordered textarea-sm w-full @error('content') textarea-error @enderror"
                              data-jodit-preset="standard">{{ old('content', $site?->content) }}</textarea>
                    @error('content')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>

                <div class="flex justify-end pt-2">
                    <button type="button" @click="tab = 'location'" class="btn btn-ghost btn-sm gap-1.5">
                        Tiếp theo: Địa điểm
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </button>
                </div>
            </div>

            {{-- ── Tab 2: Địa điểm ─────────────────────────────────────── --}}
            <div x-show="tab === 'location'" x-cloak data-tab-label="Địa điểm" class="space-y-4">

                <x-address-picker
                    instance-id="heritage-site"
                    :required="false"
                    :province-value="old('province_code', $site?->province_code)"
                    :ward-value="old('ward_code', $site?->ward_code)"
                />

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Địa chỉ chi tiết</span>
                    </label>
                    <input type="text" name="address" value="{{ old('address', $site?->address) }}"
                           data-val-maxlength="255"
                           class="input input-bordered input-sm w-full @error('address') input-error @enderror"
                           maxlength="255" placeholder="VD: 23 Tháng 8, phường Thuận Hòa">
                    @error('address')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Vĩ độ (latitude)</span>
                            <span class="label-text-alt text-xs text-base-content/40">-90 → 90</span>
                        </label>
                        <input type="number" step="any" min="-90" max="90" name="latitude"
                               value="{{ old('latitude', $site?->latitude) }}" data-coord="lat"
                               class="input input-bordered input-sm w-full font-mono @error('latitude') input-error @enderror"
                               placeholder="VD: 16.4698">
                        @error('latitude')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Kinh độ (longitude)</span>
                            <span class="label-text-alt text-xs text-base-content/40">-180 → 180</span>
                        </label>
                        <input type="number" step="any" min="-180" max="180" name="longitude"
                               value="{{ old('longitude', $site?->longitude) }}" data-coord="lng"
                               class="input input-bordered input-sm w-full font-mono @error('longitude') input-error @enderror"
                               placeholder="VD: 107.5786">
                        @error('longitude')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>
                </div>
                <p class="-mt-2 text-xs text-base-content/40">
                    Mẹo: dán cặp tọa độ copy từ Google Maps (VD: <code class="bg-base-200 px-1 rounded">16.4698, 107.5786</code>) vào ô Vĩ độ — hệ thống tự tách sang Kinh độ.
                    <a href="#" data-coord-map target="_blank" rel="noopener" class="link link-primary hidden">Xem trên bản đồ ↗</a>
                </p>

                <div class="flex items-center justify-between pt-2">
                    <button type="button" @click="tab = 'basic'" class="btn btn-ghost btn-sm gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                        Thông tin cơ bản
                    </button>
                    <button type="button" @click="tab = 'display'" class="btn btn-ghost btn-sm gap-1.5">
                        Tiếp theo: Ảnh & hiển thị
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </button>
                </div>
            </div>

            {{-- ── Tab 3: Ảnh & hiển thị ───────────────────────────────── --}}
            <div x-show="tab === 'display'" x-cloak data-tab-label="Ảnh & hiển thị" class="space-y-4">

                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Ảnh bìa</span>
                        <span class="label-text-alt text-xs text-base-content/40">JPG, PNG, WEBP</span>
                    </label>
                    @if($site?->getFirstMediaUrl('cover'))
                    <div class="mb-2">
                        <p class="text-xs text-base-content/50 mb-1">Ảnh hiện tại</p>
                        <img src="{{ $site->getFirstMediaUrl('cover', 'thumb') }}" alt="Ảnh bìa hiện tại"
                             class="h-24 w-auto rounded-lg border border-base-200 object-cover">
                    </div>
                    @endif
                    @if($site)
                    <div id="cover-filepond" data-context-type="heritage_site" data-context-id="{{ $site->id }}"></div>
                    <p class="text-xs text-base-content/40 mt-1.5">Tải ảnh mới sẽ tự động thay ảnh hiện tại.</p>
                    @else
                    <div id="cover-filepond"></div>
                    <input type="hidden" name="cover_media_uuid" id="cover-media-uuid" value="{{ old('cover_media_uuid') }}">
                    @endif
                    @error('cover_media_uuid')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Thứ tự hiển thị</span>
                            <span class="label-text-alt text-xs text-base-content/40">Số nhỏ hiển thị trước</span>
                        </label>
                        <input type="number" name="sort_order" min="0" step="1"
                               value="{{ old('sort_order', $site?->sort_order ?? 0) }}"
                               class="input input-bordered input-sm w-full @error('sort_order') input-error @enderror"
                               placeholder="0">
                        @error('sort_order')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control sm:pt-6">
                        <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                            <input type="hidden" name="is_featured" value="0">
                            <input type="checkbox" name="is_featured" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('is_featured', $site?->is_featured) ? 'checked' : '' }}>
                            <div>
                                <span class="text-sm font-medium group-hover:text-primary transition-colors">Di tích nổi bật</span>
                                <p class="text-xs text-base-content/50 mt-0.5">Ưu tiên hiển thị ở trang chủ và đầu danh sách</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    <button type="button" @click="tab = 'location'" class="btn btn-ghost btn-sm gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/></svg>
                        Địa điểm
                    </button>
                    <span class="text-xs text-base-content/40">Lưu nháp hoặc xuất bản ở thanh dưới cùng khi xong</span>
                </div>
            </div>

        </div>
    </div>

    {{-- ── Sticky Submit Bar (§11) — trạng thái quyết định bởi nút bấm (name="status") ── --}}
    @php
        $currentStatus = $site?->status ?? \Modules\Heritage\Enums\HeritageSiteStatus::Draft;
        $isPublished   = $currentStatus === \Modules\Heritage\Enums\HeritageSiteStatus::Published;
    @endphp
    <div class="form-submit-bar form-submit-bar--sticky">

        {{-- Nút mặc định khi nhấn Enter trong ô nhập: giữ nguyên trạng thái hiện tại, không bao giờ vô tình xuất bản/gỡ xuất bản --}}
        <button type="submit" name="status" value="{{ $currentStatus->value }}" class="sr-only" tabindex="-1" aria-hidden="true"></button>

        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <span class="text-xs font-medium text-base-content/60">Trạng thái:</span>
            <span class="badge badge-sm badge-soft {{ $currentStatus->badgeClass() }}">
                {{ $site ? $currentStatus->label() : 'Bản mới — chưa lưu' }}
            </span>
            @if($site && $isPublished)
            <a href="{{ route('heritage.public.show', ['slug' => $site->slug, 'id' => $site->id]) }}"
               target="_blank" rel="noopener" class="link link-primary text-xs">Xem trang công khai ↗</a>
            @endif
            @if($site)
            <span class="text-xs text-base-content/40">
                Tạo {{ $site->created_at?->format('d/m/Y') }} · Sửa {{ $site->updated_at?->diffForHumans() }}
            </span>
            @endif
            @error('status')<p class="w-full text-xs text-error">{{ $message }}</p>@enderror
        </div>

        <div class="submit-actions">
            <a href="{{ route('backend.heritage.sites.index') }}" class="btn btn-ghost btn-sm">Hủy</a>

            @if($isPublished)
            <button type="submit" name="status" value="draft"
                    data-confirm="Chuyển di tích về Nháp sẽ gỡ khỏi trang công khai. Tiếp tục?"
                    class="btn btn-ghost btn-sm text-warning">
                Chuyển về nháp
            </button>
            <button type="submit" name="status" value="published" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Cập nhật
            </button>
            @else
            <button type="submit" name="status" value="draft" class="btn btn-outline btn-sm">
                Lưu nháp
            </button>
            <button type="submit" name="status" value="published" class="btn btn-primary btn-sm gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                Xuất bản
            </button>
            @endif
        </div>

    </div>

</form>
</div>
