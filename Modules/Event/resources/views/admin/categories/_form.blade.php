@php
    $isActive = (bool) old('is_active', $category?->is_active ?? true);
    $selectedParent = (string) old('parent_id', $category?->parent_id);
@endphp

<div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_320px] gap-6 items-start">

    <div class="space-y-6 min-w-0">
        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5 sm:p-6 gap-0">
                <header class="flex items-start gap-3 mb-5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-primary/10 text-primary shrink-0">
                        <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold leading-tight">{{ __('Thông tin danh mục') }}</h2>
                        <p class="text-xs text-base-content/50 mt-0.5">{{ __('Danh mục dùng để phân loại và lọc sự kiện') }}</p>
                    </div>
                </header>

                <div class="space-y-5">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5" for="event-category-name">
                            <span class="label-text font-medium">{{ __('Tên danh mục') }} <span class="text-error">*</span></span>
                        </label>
                        <input type="text" id="event-category-name" name="name" value="{{ old('name', $category?->name) }}"
                               data-req="{{ __('Vui lòng nhập tên danh mục') }}"
                               data-val-maxlength="100"
                               class="input input-bordered w-full text-base font-medium @error('name') input-error @enderror"
                               placeholder="{{ __('VD: Hoạt Động Ngoài Trời') }}" maxlength="100" @unless($category) autofocus @endunless>
                        @error('name')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        @if($category)
                        <p class="mt-1.5 text-xs text-base-content/50">{{ __('Slug') }}: <span class="font-mono text-base-content/70">{{ $category->slug }}</span></p>
                        @else
                        <p class="mt-1.5 text-xs text-base-content/40">{{ __('Slug tự sinh từ tên danh mục.') }}</p>
                        @endif
                    </div>

                    <x-admin.translation-fields :model="$category" :fields="[
                        'name' => ['label' => __('Tên danh mục'), 'maxlength' => 100],
                    ]" />
                </div>
            </div>
        </section>
    </div>

    <aside class="xl:sticky xl:top-4 space-y-4">

        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 gap-0">
                <label class="flex items-center justify-between gap-3 cursor-pointer select-none">
                    <span>
                        <span class="block text-sm font-semibold">{{ __('Hiển thị danh mục') }}</span>
                        <span class="block text-xs text-base-content/50 mt-0.5">{{ __('Tắt để tạm ẩn khỏi bộ lọc chọn danh mục') }}</span>
                    </span>
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="toggle toggle-success toggle-sm shrink-0" {{ $isActive ? 'checked' : '' }}>
                </label>

                <div class="flex gap-2 mt-4">
                    <a href="{{ route('backend.event.categories.index') }}" class="btn btn-ghost btn-sm flex-1">{{ __('Hủy') }}</a>
                    <button type="submit" class="btn btn-primary btn-sm flex-[2] gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ $category ? __('Lưu thay đổi') : __('Tạo mới') }}
                    </button>
                </div>
            </div>
        </section>

        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 gap-0 space-y-4">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">{{ __('Vị trí trong cây') }}</p>

                <div class="form-control">
                    <label class="label py-0 pb-1.5" for="ts-parent">
                        <span class="label-text font-medium">{{ __('Danh mục cha') }}</span>
                        <span class="label-text-alt text-xs text-base-content/40">{{ __('Không bắt buộc') }}</span>
                    </label>
                    <select id="ts-parent" name="parent_id"
                            class="select select-bordered select-sm w-full @error('parent_id') select-error @enderror"
                            data-ts-placeholder="{{ __('— Danh mục gốc —') }}">
                        <option value="">{{ __('— Danh mục gốc —') }}</option>
                        @foreach($parentOptions as $option)
                        <option value="{{ $option->id }}" data-name="{{ $option->name }}" data-depth="{{ $option->depth }}" data-path="{{ $option->path }}" data-inactive="{{ $option->is_active ? '' : '1' }}" @selected($selectedParent === (string) $option->id)>
                            {{ str_repeat('　', $option->depth) }}{{ $option->depth > 0 ? '– ' : '' }}{{ $option->name }}{{ $option->is_active ? '' : ' ('.__('Ẩn').')' }}
                        </option>
                        @endforeach
                    </select>
                    @error('parent_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5" for="event-category-sort">
                        <span class="label-text font-medium">{{ __('Thứ tự hiển thị') }}</span>
                        <span class="label-text-alt text-xs text-base-content/40">{{ __('Nhỏ đứng trước') }}</span>
                    </label>
                    <input type="number" id="event-category-sort" name="sort_order" min="0"
                           value="{{ old('sort_order', $category?->sort_order ?? 0) }}"
                           class="input input-bordered input-sm w-full @error('sort_order') input-error @enderror">
                    @error('sort_order')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        @if($category)
        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 gap-0">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">{{ __('Thông tin') }}</p>
                <dl class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2 text-xs">
                    <dt class="text-base-content/50">{{ __('Sự kiện') }}</dt>
                    <dd class="text-right font-medium tabular-nums">{{ number_format($category->events_count) }}</dd>
                    <dt class="text-base-content/50">{{ __('Danh mục con') }}</dt>
                    <dd class="text-right font-medium tabular-nums">{{ $category->children_count }}</dd>
                    <dt class="text-base-content/50">{{ __('Tạo lúc') }}</dt>
                    <dd class="text-right truncate">{{ format_datetime($category->created_at) }}@if($category->createdBy) · {{ $category->createdBy->name }}@endif</dd>
                    <dt class="text-base-content/50">{{ __('Cập nhật') }}</dt>
                    <dd class="text-right truncate">{{ format_datetime($category->updated_at) }}@if($category->updatedBy) · {{ $category->updatedBy->name }}@endif</dd>
                </dl>
            </div>
        </section>
        @endif

    </aside>

</div>
