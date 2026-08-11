{{-- spec/Heritage_Technical_Specification.md §5.1 — mirror Modules/Ocop/resources/views/admin/products/_form.blade.php --}}
<div class="grid grid-cols-1 xl:grid-cols-[1fr_300px] gap-6 items-start">

    {{-- ── Card chính ──────────────────────────────────────────────── --}}
    <div class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body space-y-4">

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
                    <span class="label-text-alt text-xs text-base-content/40">Để trống để tự sinh</span>
                </label>
                <input type="text" name="slug" value="{{ old('slug', $site?->slug) }}"
                       class="input input-bordered input-sm w-full @error('slug') input-error @enderror"
                       maxlength="220" placeholder="tu-sinh-neu-de-trong">
                @error('slug')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Loại hình <span class="text-error">*</span></span>
                    </label>
                    <select id="ts-heritage_type" name="heritage_type"
                            data-req="Vui lòng chọn loại hình"
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
                            class="select select-bordered select-sm w-full ts-init @error('rank') select-error @enderror">
                        @foreach($ranks as $r)
                        <option value="{{ $r->value }}" {{ (string) old('rank', $site?->rank?->value ?? 'unranked') === $r->value ? 'selected' : '' }}>{{ $r->label() }}</option>
                        @endforeach
                    </select>
                    @error('rank')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium">Niên đại</span>
                    <span class="label-text-alt text-xs text-base-content/40">VD: "Thế kỷ 11", "Thời Lý - Trần"</span>
                </label>
                <input type="text" name="era" value="{{ old('era', $site?->era) }}"
                       class="input input-bordered input-sm w-full @error('era') input-error @enderror"
                       maxlength="100">
                @error('era')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium">Mô tả</span>
                    <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc — văn bản thường, không rich text</span>
                </label>
                <textarea name="description" rows="4" maxlength="3000"
                          class="textarea textarea-bordered textarea-sm w-full @error('description') textarea-error @enderror">{{ old('description', $site?->description) }}</textarea>
                @error('description')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium">Ảnh bìa</span>
                </label>
                @if($site?->getFirstMediaUrl('cover'))
                <img src="{{ $site->getFirstMediaUrl('cover', 'thumb') }}" alt=""
                     class="h-20 w-auto rounded border border-base-300 mb-2 object-cover">
                @endif
                @if($site)
                <div id="cover-filepond" data-context-type="heritage_site" data-context-id="{{ $site->id }}"></div>
                <p class="text-xs text-base-content/40 mt-1.5">Tải ảnh mới sẽ tự động thay ảnh hiện tại.</p>
                @else
                <div id="cover-filepond"></div>
                <input type="hidden" name="cover_media_uuid" id="cover-media-uuid" value="{{ old('cover_media_uuid') }}">
                @endif
                @error('cover_media_uuid')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

            <div class="divider my-1">Địa điểm</div>

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
                       class="input input-bordered input-sm w-full @error('address') input-error @enderror"
                       maxlength="255">
                @error('address')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Vĩ độ (latitude)</span>
                        <span class="label-text-alt text-xs text-base-content/40">Để trống cả 2 nếu không có</span>
                    </label>
                    <input type="number" step="0.0000001" name="latitude" value="{{ old('latitude', $site?->latitude) }}"
                           class="input input-bordered input-sm w-full @error('latitude') input-error @enderror">
                    @error('latitude')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>
                <div class="form-control">
                    <label class="label py-0 pb-1.5">
                        <span class="label-text font-medium">Kinh độ (longitude)</span>
                    </label>
                    <input type="number" step="0.0000001" name="longitude" value="{{ old('longitude', $site?->longitude) }}"
                           class="input input-bordered input-sm w-full @error('longitude') input-error @enderror">
                    @error('longitude')<p class="mt-1 text-xs text-error form-val-msg">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="form-control">
                <label class="label py-0 pb-1.5">
                    <span class="label-text font-medium">Tình trạng tham quan</span>
                </label>
                <select id="ts-visiting_status" name="visiting_status"
                        class="select select-bordered select-sm w-full ts-init @error('visiting_status') select-error @enderror">
                    @foreach($visitingStatuses as $vs)
                    <option value="{{ $vs->value }}" {{ (string) old('visiting_status', $site?->visiting_status?->value ?? 'unknown') === $vs->value ? 'selected' : '' }}>{{ $vs->label() }}</option>
                    @endforeach
                </select>
                @error('visiting_status')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
            </div>

        </div>
    </div>

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
                            class="select select-bordered select-sm w-full ts-init @error('status') select-error @enderror">
                        @foreach($statuses as $s)
                        <option value="{{ $s->value }}" {{ old('status', $site?->status?->value ?? 'draft') === $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                    @error('status')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <label class="flex items-start gap-2.5 cursor-pointer select-none group mb-4">
                    <input type="hidden" name="is_featured" value="0">
                    <input type="checkbox" name="is_featured" value="1"
                           class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0" {{ old('is_featured', $site?->is_featured) ? 'checked' : '' }}>
                    <span class="text-sm font-medium group-hover:text-primary transition-colors">Di tích nổi bật</span>
                </label>

                <div class="form-control mb-3">
                    <label class="label py-0 pb-1">
                        <span class="label-text text-xs font-medium">Thứ tự hiển thị</span>
                    </label>
                    <input type="number" name="sort_order" min="0" value="{{ old('sort_order', $site?->sort_order ?? 0) }}"
                           class="input input-bordered input-sm w-full @error('sort_order') input-error @enderror">
                    @error('sort_order')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="flex gap-2">
                    <a href="{{ route('backend.heritage.sites.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                    <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            @if($site)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            @endif
                        </svg>
                        {{ $site ? 'Lưu thay đổi' : 'Tạo mới' }}
                    </button>
                </div>

                <p class="text-center text-xs text-base-content/30 mt-2.5">
                    <span class="text-error">*</span> là trường bắt buộc
                </p>

            </div>
        </div>
    </div>

</div>
