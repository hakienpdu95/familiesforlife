{{-- Dùng chung create/edit — spec/Menu_Navigation_Technical_Specification.md §6.2. --}}
@php
    $linkType = old('link_type', $menuItem?->link_type?->value ?? 'none');
@endphp

<div class="grid grid-cols-1 xl:grid-cols-[1fr_268px] gap-6 items-start">

    {{-- ── Card chính ──────────────────────────────────────────────── --}}
    <div class="space-y-5">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body">

                <h2 class="card-title text-base mb-5">
                    <svg class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                    </svg>
                    Thông tin mục menu
                </h2>

                <div class="space-y-4">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Vị trí <span class="text-error">*</span></span>
                            </label>
                            <select name="location" id="ts-location"
                                    class="select select-bordered select-sm w-full ts-init @error('location') select-error @enderror">
                                @foreach(config('menu.locations') as $value => $labelText)
                                <option value="{{ $value }}" {{ old('location', $menuItem?->location ?? config('menu.default_location')) === $value ? 'selected' : '' }}>
                                    {{ $labelText }}
                                </option>
                                @endforeach
                            </select>
                            @error('location')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                            <p class="text-xs text-base-content/40 mt-1.5">
                                Muốn footer nhiều cột? Tạo 1 mục cấp 1 làm tiêu đề cột, sau đó thêm các mục cấp 2 (children) bên dưới nó — footer tự động render thành cột, không cần cấu hình gì thêm.
                            </p>
                        </div>

                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Mục cha</span>
                                <span class="label-text-alt text-xs text-base-content/40">Tối đa 3 cấp</span>
                            </label>
                            <select id="ts-parent" name="parent_id"
                                    class="select select-bordered select-sm w-full ts-init @error('parent_id') select-error @enderror"
                                    data-ts-placeholder="— Mục gốc (cấp 1) —">
                                <option value="">— Mục gốc (cấp 1) —</option>
                                @foreach($parentOptions as $option)
                                <option value="{{ $option->id }}" {{ (string) old('parent_id', $menuItem?->parent_id) === (string) $option->id ? 'selected' : '' }}>
                                    {{ str_repeat('— ', $option->depth) }}{{ $option->label }} ({{ config('menu.locations')[$option->location] ?? $option->location }})
                                </option>
                                @endforeach
                            </select>
                            @error('parent_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Nhãn hiển thị <span class="text-error">*</span></span>
                        </label>
                        <input type="text" name="label" value="{{ old('label', $menuItem?->label) }}"
                               class="input input-bordered input-sm w-full @error('label') input-error @enderror"
                               maxlength="150" placeholder="Vd: Cẩm Nang Nuôi Dạy Con">
                        @error('label')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Icon</span>
                            <span class="label-text-alt text-xs text-base-content/40">Không bắt buộc</span>
                        </label>
                        <input type="text" name="icon" value="{{ old('icon', $menuItem?->icon) }}"
                               class="input input-bordered input-sm w-full font-mono @error('icon') input-error @enderror"
                               placeholder="ti-baby-carriage">
                        @error('icon')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="divider my-1"></div>

                    <div class="form-control">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Đích liên kết <span class="text-error">*</span></span>
                        </label>
                        <div class="flex flex-col gap-2" data-link-type-group>
                            @foreach(\Modules\Menu\Enums\MenuLinkType::cases() as $type)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="link_type" value="{{ $type->value }}" class="radio radio-sm radio-primary"
                                       data-link-type-radio
                                       {{ $linkType === $type->value ? 'checked' : '' }}>
                                <span class="text-sm">{{ $type->label() }}</span>
                            </label>
                            @endforeach
                        </div>
                        @error('link_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-control {{ $linkType === 'category' ? '' : 'hidden' }}" data-link-target="category">
                        <label class="label py-0 pb-1.5">
                            <span class="label-text font-medium">Danh mục bài viết <span class="text-error">*</span></span>
                        </label>
                        <select name="category_id" id="ts-category"
                                class="select select-bordered select-sm w-full ts-init @error('category_id') select-error @enderror"
                                data-ts-placeholder="— Chọn danh mục —">
                            <option value="">— Chọn danh mục —</option>
                            @foreach($categoryOptions as $category)
                            <option value="{{ $category->id }}" {{ (string) old('category_id', $menuItem?->category_id) === (string) $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('category_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="{{ $linkType === 'url' ? '' : 'hidden' }}" data-link-target="url">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">URL <span class="text-error">*</span></span>
                            </label>
                            <input type="text" name="url" value="{{ old('url', $menuItem?->url) }}"
                                   class="input input-bordered input-sm w-full @error('url') input-error @enderror"
                                   maxlength="2048" placeholder="/su-kien hoặc https://...">
                            @error('url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>

                        <label class="flex items-start gap-2.5 cursor-pointer select-none group mt-3">
                            <input type="hidden" name="open_in_new_tab" value="0">
                            <input type="checkbox" name="open_in_new_tab" value="1"
                                   class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                                   {{ old('open_in_new_tab', $menuItem?->open_in_new_tab) ? 'checked' : '' }}>
                            <span class="text-sm font-medium group-hover:text-primary transition-colors">Mở trong tab mới</span>
                        </label>
                    </div>

                    <div class="divider my-1"></div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="form-control">
                            <label class="label py-0 pb-1.5">
                                <span class="label-text font-medium">Thứ tự hiển thị</span>
                            </label>
                            <input type="number" name="sort_order" min="0"
                                   value="{{ old('sort_order', $menuItem?->sort_order ?? 0) }}"
                                   class="input input-bordered input-sm w-full @error('sort_order') input-error @enderror">
                            @error('sort_order')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <label class="flex items-start gap-2.5 cursor-pointer select-none group">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                               class="checkbox checkbox-sm checkbox-primary mt-0.5 shrink-0"
                               {{ old('is_active', $menuItem?->is_active ?? true) ? 'checked' : '' }}>
                        <div>
                            <span class="text-sm font-medium group-hover:text-primary transition-colors">Hiển thị mục menu</span>
                            <p class="text-xs text-base-content/50 mt-0.5">Tắt để tạm ẩn khỏi menu công khai</p>
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
                    <a href="{{ route('backend.menu.items.index') }}" class="btn btn-ghost btn-sm flex-1">Hủy</a>
                    <button type="submit" class="btn btn-primary btn-sm flex-1 gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $menuItem ? 'Lưu thay đổi' : 'Tạo mới' }}
                    </button>
                </div>

                <p class="text-center text-xs text-base-content/30 mt-2.5">
                    <span class="text-error">*</span> là trường bắt buộc
                </p>

            </div>
        </div>
    </div>

</div>
