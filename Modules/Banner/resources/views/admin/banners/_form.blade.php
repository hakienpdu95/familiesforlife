{{-- Dùng chung create/edit — spec/Banner_Management_Technical_Specification.md §6.2/§7.4.
     Alpine (bannerForm) đảm nhận 2 việc: gợi ý kích thước động theo placement, và ẩn/hiện +
     validate-phía-UI select category theo target_type — KHÔNG có tương tác nào khác trong
     module này (trang công khai <x-frontend.banner-slot> là HTML tĩnh, không cần Alpine). --}}
@php
    $recommendedSizes = collect($placements)->mapWithKeys(fn ($p, $key) => [$key => $p['recommended_size']]);
    $initialTargetType = old('target_type', $banner?->target_type?->value ?? 'global');
@endphp

<div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start"
     x-data="bannerForm(
         {{ $recommendedSizes->toJson() }},
         '{{ old('placement', $banner?->placement ?? array_key_first($placements)) }}',
         '{{ $initialTargetType }}',
         '{{ old('target_value', $banner?->target_value) }}'
     )">

    {{-- ── Card chính ──────────────────────────────────────────────── --}}
    <div class="space-y-5">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5h16v10H4z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 19h16"/>
                    </svg>
                    Thông tin banner
                </h2>

                <div class="space-y-4">

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Vị trí (placement) <span class="text-error">*</span></span>
                        </label>
                        <select name="placement" x-model="placement"
                                class="select select-bordered select-sm w-full @error('placement') select-error @enderror">
                            @foreach($placements as $key => $p)
                            <option value="{{ $key }}">{{ $p['label'] }}</option>
                            @endforeach
                        </select>
                        @error('placement')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        <p class="text-xs text-base-content/40 mt-1.5">
                            Kích thước gợi ý: <span x-text="recommendedSize"></span>
                        </p>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">
                                {{ $banner?->image_path ? 'Đổi ảnh banner' : 'Ảnh banner' }}
                                @if(! $banner?->image_path)<span class="text-error">*</span>@endif
                            </span>
                        </label>
                        @if($banner?->image_path)
                        <img src="{{ Illuminate\Support\Facades\Storage::url($banner->image_path) }}" alt=""
                             class="h-16 w-auto rounded border border-base-300 mb-2 object-cover">
                        @endif
                        <input type="file" name="image" accept="image/*"
                               class="file-input file-input-bordered file-input-sm w-full @error('image') file-input-error @enderror">
                        @error('image')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        @if($banner?->image_path)
                        <p class="text-xs text-base-content/40 mt-1.5">Để trống nếu muốn giữ ảnh hiện tại.</p>
                        @endif
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Alt text</span>
                            <span class="label-text-alt text-xs text-base-content/40">SEO/accessibility</span>
                        </label>
                        <input type="text" name="alt_text" value="{{ old('alt_text', $banner?->alt_text) }}"
                               class="input input-bordered input-sm w-full @error('alt_text') input-error @enderror"
                               maxlength="255">
                        @error('alt_text')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Ghi chú nội bộ</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không hiển thị công khai</span>
                        </label>
                        <input type="text" name="title" value="{{ old('title', $banner?->title) }}"
                               class="input input-bordered input-sm w-full @error('title') input-error @enderror"
                               maxlength="150" placeholder="Vd: Banner đối tác ABC — Q3/2026">
                        @error('title')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="divider my-1"></div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Hiển thị cho <span class="text-error">*</span></span>
                        </label>
                        <div class="flex flex-col gap-2">
                            @foreach($targetTypes as $key => $label)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="target_type" value="{{ $key }}" x-model="targetType"
                                       class="radio radio-sm radio-primary">
                                <span class="text-sm">{{ $label }}</span>
                            </label>
                            @endforeach
                        </div>
                        @error('target_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control" x-show="needsCategory" x-cloak>
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Danh mục bài viết <span class="text-error">*</span></span>
                        </label>
                        <select name="target_value" x-model="targetValue"
                                class="select select-bordered select-sm w-full @error('target_value') select-error @enderror">
                            <option value="">— Chọn danh mục —</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->slug }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('target_value')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        <p class="text-xs text-warning mt-1.5" x-show="needsCategory && !isValid" x-cloak>
                            Vui lòng chọn danh mục khi hiển thị theo danh mục.
                        </p>
                    </div>

                    <div class="divider my-1"></div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Link đích</span>
                            <span class="label-text-alt text-xs text-base-content/40">Để trống nếu banner chỉ trang trí/thông báo</span>
                        </label>
                        <input type="url" name="link_url" value="{{ old('link_url', $banner?->link_url) }}"
                               class="input input-bordered input-sm w-full @error('link_url') input-error @enderror"
                               maxlength="2048" placeholder="https://...">
                        @error('link_url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                        <input type="hidden" name="open_in_new_tab" value="0">
                        <input type="checkbox" name="open_in_new_tab" value="1"
                               class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                               {{ old('open_in_new_tab', $banner?->open_in_new_tab) ? 'checked' : '' }}>
                        <span class="text-sm font-medium group-hover:text-primary transition-colors">Mở link trong tab mới</span>
                    </label>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Nhãn minh bạch quảng cáo</span>
                            <span class="label-text-alt text-xs text-base-content/40">Vd "Quảng cáo", "Tài trợ"</span>
                        </label>
                        <input type="text" name="badge_label" value="{{ old('badge_label', $banner?->badge_label) }}"
                               class="input input-bordered input-sm w-full @error('badge_label') input-error @enderror"
                               maxlength="40">
                        @error('badge_label')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="divider my-1"></div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Bắt đầu chạy</span>
                                <span class="label-text-alt text-xs text-base-content/40">Để trống = chạy ngay</span>
                            </label>
                            <input type="date" name="start_date" value="{{ old('start_date', $banner?->start_date?->toDateString()) }}"
                                   class="input input-bordered input-sm w-full @error('start_date') input-error @enderror">
                            @error('start_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Kết thúc chạy</span>
                                <span class="label-text-alt text-xs text-base-content/40">Để trống = không giới hạn</span>
                            </label>
                            <input type="date" name="end_date" value="{{ old('end_date', $banner?->end_date?->toDateString()) }}"
                                   class="input input-bordered input-sm w-full @error('end_date') input-error @enderror">
                            @error('end_date')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Thứ tự hiển thị</span>
                        </label>
                        <input type="number" name="sort_order" min="0"
                               value="{{ old('sort_order', $banner?->sort_order ?? 0) }}"
                               class="input input-bordered input-sm w-full @error('sort_order') input-error @enderror">
                        @error('sort_order')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                               {{ old('is_active', $banner?->is_active ?? true) ? 'checked' : '' }}>
                        <div>
                            <span class="text-sm font-medium group-hover:text-primary transition-colors">Hiển thị banner</span>
                            <p class="text-xs text-base-content/50 mt-0.5">Tắt để tạm ẩn khỏi mọi trang, không cần xoá</p>
                        </div>
                    </label>

                </div>
            </div>
        </div>
    </div>

    {{-- ── Sidebar ──────────────────────────────────────────────────── --}}
    <div class="xl:sticky xl:top-4 space-y-4">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4">

                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">
                    Xuất bản
                </p>

                <div class="flex gap-2">
                    <a href="{{ route('backend.banner.items.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                    <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5" :disabled="!isValid">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $banner ? 'Lưu thay đổi' : 'Tạo mới' }}
                    </button>
                </div>

                <p class="text-center text-xs text-base-content/30 mt-2.5">
                    <span class="text-error">*</span> là trường bắt buộc
                </p>

            </div>
        </div>
    </div>

</div>
