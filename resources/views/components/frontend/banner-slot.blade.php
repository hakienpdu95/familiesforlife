@props([
    'placement',
    'context' => [], // vd ['category_slug' => $category->slug] — xem spec/Banner_Management_Technical_Specification.md §7.5
    'limit' => 1,
])

@php($banners = \Modules\Banner\Models\Banner::forPlacement($placement, $context, $limit))

@if($banners->isNotEmpty())
<div class="banner-slot banner-slot--{{ $placement }}">
    @foreach($banners as $banner)
    <a href="{{ route('banner.click', $banner) }}"
       @if($banner->open_in_new_tab) target="_blank" @endif
       @if($banner->open_in_new_tab || $banner->isExternalUrl())
       rel="{{ trim(($banner->open_in_new_tab ? 'noopener ' : '') . ($banner->isExternalUrl() ? 'nofollow' : '')) }}"
       @endif
       class="banner-slot__link">
        <img src="{{ Illuminate\Support\Facades\Storage::url($banner->image_path) }}"
             alt="{{ $banner->alt_text ?? '' }}"
             class="banner-slot__img" loading="lazy">
        @if($banner->badge_label)
        <span class="badge badge-neutral badge-xs banner-slot__badge">{{ $banner->badge_label }}</span>
        @endif
    </a>
    @endforeach
</div>
@endif
