@php
    $content = $listing->publicContent();
    $showRouteName = 'real-estate.public.' . $listing->listing_type->value . '.show';
    $firstImage = $listing->galleryUrls('medium')[0] ?? null;
@endphp
<a href="{{ route($showRouteName, ['slug' => $listing->slug, 'id' => $listing->id]) }}"
   class="anland-card card bg-base-100 border border-base-300 shadow-sm hover:shadow-md overflow-hidden">
    <figure class="anland-card__figure aspect-video relative">
        @if($firstImage)
        <img src="{{ $firstImage }}" alt="{{ $content['title'] }}" class="w-full h-full object-cover">
        @else
        <div class="w-full h-full flex items-center justify-center text-base-content/30 text-sm">Chưa có ảnh</div>
        @endif
        @if($listing->is_featured)
        <span class="badge badge-accent badge-sm absolute top-2 left-2">Nổi bật</span>
        @endif
        @if($listing->isSale() && $listing->is_urgent)
        <span class="badge badge-error badge-sm absolute top-2 right-2">Bán gấp</span>
        @endif
    </figure>
    <div class="card-body p-3 gap-1">
        <h3 class="font-semibold text-sm text-base-content line-clamp-2 min-h-[2.5rem]">{{ $content['title'] }}</h3>
        <p class="text-primary font-bold">{{ $listing->display_price }}</p>
        <p class="text-xs text-base-content/50">
            {{ $listing->area ? number_format((float) $listing->area, 0) . ' m²' : '' }}
            @if($listing->bedrooms) &middot; {{ $listing->bedrooms }} PN @endif
        </p>
    </div>
</a>
