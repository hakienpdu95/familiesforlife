{{-- Dùng chung create/edit — spec/Menu_Navigation_Technical_Specification.md §6.2. --}}
@php
    $linkType = old('link_type', $menuItem?->link_type?->value ?? 'none');
    $linkedTarget = match ($linkType) {
        'category' => $categoryOptions->firstWhere('id', (int) old('category_id', $menuItem?->category_id)),
        'page' => $pageOptions->firstWhere('id', (int) old('page_id', $menuItem?->page_id)),
        default => null,
    };
    $labelFallbacks = [];
    foreach (\App\Support\I18n\PortalLocale::prefixed() as $locale) {
        $value = $linkedTarget->translations[$locale] ?? null;
        $labelFallbacks[$locale] = $value ? [
            'value' => $value,
            'from' => $linkType === 'category'
                ? __('danh mục “:name”', ['name' => $linkedTarget->name])
                : __('trang “:name”', ['name' => $linkedTarget->title]),
        ] : null;
    }
@endphp

@php
    $isActive = (bool) old('is_active', $menuItem?->is_active ?? true);
    $resolvedUrl = $menuItem?->resolveUrl();
    $linkTypeIcons = [
        'category' => 'M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V7z',
        'page' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'url' => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
        'none' => 'M4 6h16M4 12h16M4 18h16',
    ];
    $linkTypeHints = [
        'category' => __('URL tự theo slug danh mục'),
        'page' => __('URL tự theo slug trang'),
        'url' => __('Link nội bộ hoặc bên ngoài'),
        'none' => __('Nhãn mở submenu, không có link'),
    ];
@endphp

<div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_320px] gap-6 items-start">

    <div class="space-y-6 min-w-0">

        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5 sm:p-6 gap-0">
                <header class="flex items-start gap-3 mb-5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-primary/10 text-primary shrink-0">
                        <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold leading-tight">{{ __('Nội dung hiển thị') }}</h2>
                        <p class="text-xs text-base-content/50 mt-0.5">{{ __('Nhãn xuất hiện trên thanh điều hướng') }}</p>
                    </div>
                </header>

                <div class="space-y-4">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5" for="menu-item-label">
                            <span class="label-text font-medium">{{ __('Nhãn hiển thị') }} <span class="text-error">*</span></span>
                        </label>
                        <input type="text" id="menu-item-label" name="label" value="{{ old('label', $menuItem?->label) }}"
                               class="input input-bordered w-full @error('label') input-error @enderror"
                               maxlength="150" placeholder="{{ __('Vd: Cẩm Nang Nuôi Dạy Con') }}">
                        @error('label')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <x-admin.translation-fields :model="$menuItem" :fields="[
                        'label' => ['label' => __('Nhãn hiển thị'), 'maxlength' => 150],
                    ]" :fallbacks="['label' => $labelFallbacks]" :description="__('Nhãn trên menu ở trang /en/')"
                       :note="__('Để trống: mục trỏ tới danh mục hoặc trang tĩnh tự dùng tên tiếng Anh của đích; mục khác hiển thị nhãn gốc.')" />
                </div>
            </div>
        </section>

        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-5 sm:p-6 gap-0">
                <header class="flex items-start gap-3 mb-5">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-lg bg-primary/10 text-primary shrink-0">
                        <svg class="w-4.5 h-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $linkTypeIcons['url'] }}"/></svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold leading-tight">{{ __('Đích liên kết') }} <span class="text-error">*</span></h2>
                        <p class="text-xs text-base-content/50 mt-0.5">{{ __('Người dùng được đưa tới đâu khi bấm vào mục này') }}</p>
                    </div>
                </header>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5" data-link-type-group role="radiogroup">
                    @foreach(\Modules\Menu\Enums\MenuLinkType::cases() as $type)
                    <label class="relative flex items-start gap-3 rounded-box border border-base-300 p-3 cursor-pointer transition-colors hover:border-primary/50 hover:bg-base-200/40 has-[:checked]:border-primary has-[:checked]:bg-primary/5 has-[:checked]:ring-1 has-[:checked]:ring-primary">
                        <input type="radio" name="link_type" value="{{ $type->value }}" class="radio radio-sm radio-primary mt-0.5 shrink-0"
                               data-link-type-radio
                               {{ $linkType === $type->value ? 'checked' : '' }}>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-medium leading-snug">{{ $type->label() }}</span>
                            <span class="block text-xs text-base-content/50 mt-0.5">{{ $linkTypeHints[$type->value] }}</span>
                        </span>
                        <svg class="w-4 h-4 text-base-content/30 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $linkTypeIcons[$type->value] }}"/></svg>
                    </label>
                    @endforeach
                </div>
                @error('link_type')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror

                <div class="form-control mt-5 {{ $linkType === 'category' ? '' : 'hidden' }}" data-link-target="category">
                    <label class="label py-0 pb-1.5" for="ts-category">
                        <span class="label-text font-medium">{{ __('Danh mục bài viết') }} <span class="text-error">*</span></span>
                    </label>
                    <select name="category_id" id="ts-category"
                            class="select select-bordered w-full @error('category_id') select-error @enderror"
                            data-ts-placeholder="{{ __('— Chọn danh mục —') }}">
                        <option value="">{{ __('— Chọn danh mục —') }}</option>
                        @foreach($categoryOptions as $category)
                        <option value="{{ $category->id }}" data-name="{{ $category->name }}" data-depth="{{ $category->depth }}" data-path="{{ $category->path }}" data-target-name="{{ $category->name }}" data-target-translations='@json((object) $category->translations)' {{ (string) old('category_id', $menuItem?->category_id) === (string) $category->id ? 'selected' : '' }}>
                            {{ str_repeat('　', $category->depth) }}{{ $category->depth > 0 ? '– ' : '' }}{{ $category->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('category_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control mt-5 {{ $linkType === 'page' ? '' : 'hidden' }}" data-link-target="page">
                    <label class="label py-0 pb-1.5" for="ts-page">
                        <span class="label-text font-medium">{{ __('Trang tĩnh') }} <span class="text-error">*</span></span>
                        <a href="{{ route('backend.page.items.create') }}" target="_blank" rel="noopener" class="label-text-alt text-xs link link-primary">{{ __('+ Tạo trang mới') }}</a>
                    </label>
                    <select name="page_id" id="ts-page"
                            class="select select-bordered w-full ts-init @error('page_id') select-error @enderror"
                            data-ts-placeholder="{{ __('— Tìm và chọn trang —') }}">
                        <option value="">{{ __('— Tìm và chọn trang —') }}</option>
                        @foreach($pageOptions as $page)
                        <option value="{{ $page->id }}" data-title="{{ $page->title }}" data-target-name="{{ $page->title }}" data-target-translations='@json((object) $page->translations)'
                                {{ (string) old('page_id', $menuItem?->page_id) === (string) $page->id ? 'selected' : '' }}>
                            {{ $page->title }} — /{{ $page->slug }}{{ $page->is_published ? '' : ' ('.__('Nháp').')' }}
                        </option>
                        @endforeach
                    </select>
                    @error('page_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    <p class="mt-1.5 text-xs text-base-content/50">
                        {{ __('Link tự cập nhật khi đổi đường dẫn trang. Trang ở trạng thái Nháp sẽ tạm ẩn khỏi menu cho tới khi được xuất bản.') }}
                    </p>
                </div>

                <div class="mt-5 space-y-3 {{ $linkType === 'url' ? '' : 'hidden' }}" data-link-target="url">
                    <div class="form-control">
                        <label class="label py-0 pb-1.5" for="menu-item-url">
                            <span class="label-text font-medium">URL <span class="text-error">*</span></span>
                        </label>
                        <input type="text" id="menu-item-url" name="url" value="{{ old('url', $menuItem?->url) }}"
                               class="input input-bordered w-full font-mono text-sm @error('url') input-error @enderror"
                               maxlength="2048" placeholder="{{ __('/su-kien hoặc https://...') }}">
                        @error('url')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                    </div>

                    <label class="inline-flex items-center gap-2.5 cursor-pointer select-none">
                        <input type="hidden" name="open_in_new_tab" value="0">
                        <input type="checkbox" name="open_in_new_tab" value="1"
                               class="checkbox checkbox-sm checkbox-primary"
                               {{ old('open_in_new_tab', $menuItem?->open_in_new_tab) ? 'checked' : '' }}>
                        <span class="text-sm">{{ __('Mở trong tab mới') }}</span>
                    </label>
                </div>
            </div>
        </section>

    </div>

    <aside class="xl:sticky xl:top-4 space-y-4">

        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 gap-0">
                <label class="flex items-center justify-between gap-3 cursor-pointer select-none">
                    <span>
                        <span class="block text-sm font-semibold">{{ __('Hiển thị mục menu') }}</span>
                        <span class="block text-xs text-base-content/50 mt-0.5">{{ __('Tắt để tạm ẩn khỏi menu công khai') }}</span>
                    </span>
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="toggle toggle-success toggle-sm shrink-0" {{ $isActive ? 'checked' : '' }}>
                </label>

                @if($menuItem)
                <div class="mt-4 rounded-lg bg-base-200/60 px-3 py-2.5">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-base-content/40">{{ __('Đường dẫn hiện tại') }}</p>
                    @if($resolvedUrl)
                    <a href="{{ $resolvedUrl }}" target="_blank" rel="noopener" class="mt-0.5 flex items-center gap-1.5 text-xs font-mono link link-hover link-primary break-all">
                        <span class="min-w-0">{{ $resolvedUrl }}</span>
                        <svg class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                    @else
                    <p class="mt-0.5 text-xs text-base-content/50">{{ $menuItem->link_type === \Modules\Menu\Enums\MenuLinkType::None ? __('Không liên kết — chỉ mở submenu') : __('Đích không khả dụng, mục đang bị ẩn') }}</p>
                    @endif
                </div>
                @endif

                <div class="flex gap-2 mt-4">
                    <a href="{{ route('backend.menu.items.index') }}" class="btn btn-ghost btn-sm flex-1">{{ __('Hủy') }}</a>
                    <button type="submit" class="btn btn-primary btn-sm flex-[2] gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ $menuItem ? __('Lưu thay đổi') : __('Tạo mới') }}
                    </button>
                </div>
            </div>
        </section>

        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 gap-0 space-y-4">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">{{ __('Vị trí trong menu') }}</p>

                <div class="form-control">
                    <label class="label py-0 pb-1.5" for="ts-location">
                        <span class="label-text font-medium">{{ __('Vị trí') }} <span class="text-error">*</span></span>
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
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5" for="ts-parent">
                        <span class="label-text font-medium">{{ __('Mục cha') }}</span>
                        <span class="label-text-alt text-xs text-base-content/40">{{ __('Tối đa 3 cấp') }}</span>
                    </label>
                    <select id="ts-parent" name="parent_id"
                            class="select select-bordered select-sm w-full ts-init @error('parent_id') select-error @enderror"
                            data-ts-placeholder="{{ __('— Mục gốc (cấp 1) —') }}">
                        <option value="">{{ __('— Mục gốc (cấp 1) —') }}</option>
                        @foreach($parentOptions as $option)
                        <option value="{{ $option->id }}" {{ (string) old('parent_id', $menuItem?->parent_id) === (string) $option->id ? 'selected' : '' }}>
                            {{ str_repeat('— ', $option->depth) }}{{ $option->label }} ({{ config('menu.locations')[$option->location] ?? $option->location }})
                        </option>
                        @endforeach
                    </select>
                    @error('parent_id')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="form-control">
                    <label class="label py-0 pb-1.5" for="menu-item-sort">
                        <span class="label-text font-medium">{{ __('Thứ tự hiển thị') }}</span>
                        <span class="label-text-alt text-xs text-base-content/40">{{ __('Nhỏ đứng trước') }}</span>
                    </label>
                    <input type="number" id="menu-item-sort" name="sort_order" min="0"
                           value="{{ old('sort_order', $menuItem?->sort_order ?? 0) }}"
                           class="input input-bordered input-sm w-full @error('sort_order') input-error @enderror">
                    @error('sort_order')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-start gap-2 rounded-lg bg-info/5 px-3 py-2.5 text-xs text-base-content/60">
                    <svg class="w-4 h-4 text-info shrink-0 mt-px" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="leading-relaxed">{{ __('Footer nhiều cột: mục cấp 1 là tiêu đề cột, các mục cấp 2 bên dưới tự render thành cột.') }}</p>
                </div>
            </div>
        </section>

        @if($menuItem)
        <section class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 gap-0">
                <p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide mb-3">{{ __('Thông tin') }}</p>
                <dl class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2 text-xs">
                    <dt class="text-base-content/50">{{ __('Cấp') }}</dt>
                    <dd class="text-right font-medium">{{ $menuItem->depth + 1 }}</dd>
                    <dt class="text-base-content/50">{{ __('Mục con') }}</dt>
                    <dd class="text-right font-medium">{{ $menuItem->children_count }}</dd>
                    <dt class="text-base-content/50">{{ __('Tạo lúc') }}</dt>
                    <dd class="text-right truncate">{{ format_datetime($menuItem->created_at) }}@if($menuItem->createdBy) · {{ $menuItem->createdBy->name }}@endif</dd>
                    <dt class="text-base-content/50">{{ __('Cập nhật') }}</dt>
                    <dd class="text-right truncate">{{ format_datetime($menuItem->updated_at) }}@if($menuItem->updatedBy) · {{ $menuItem->updatedBy->name }}@endif</dd>
                </dl>
            </div>
        </section>
        @endif

    </aside>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const group = document.querySelector('[data-link-type-group]');
    const form = group?.closest('form');
    if (! form) {
        return;
    }
    const locales = @js(\App\Support\I18n\PortalLocale::prefixed());
    const fromText = {
        category: @js(__('danh mục “:name”')),
        page: @js(__('trang “:name”')),
    };
    const emit = () => {
        const type = form.querySelector('[name="link_type"]:checked')?.value;
        const select = type === 'category' || type === 'page' ? form.querySelector(`[name="${type}_id"]`) : null;
        const option = select?.selectedOptions?.[0];
        let names = {};
        try { names = JSON.parse(option?.dataset.targetTranslations || '{}'); } catch { names = {}; }
        locales.forEach((locale) => {
            const value = option && option.value ? names[locale] || null : null;
            window.dispatchEvent(new CustomEvent('translation-fallback', { detail: {
                key: 'label',
                locale,
                value,
                from: value ? fromText[type].replace(':name', option.dataset.targetName || '') : '',
            } }));
        });
    };
    form.addEventListener('change', (e) => {
        if (['link_type', 'category_id', 'page_id'].includes(e.target.name)) {
            emit();
        }
    });
});
</script>
@endpush
