@php
    $content = $listing->publicContent();
    $showRouteName = 'real-estate.public.' . $listing->listing_type->value . '.show';
    $gallery = $listing->galleryUrls('medium');
    $firstImage = $gallery[0] ?? null;
@endphp
<a href="{{ route($showRouteName, ['slug' => $listing->slug, 'id' => $listing->id]) }}"
   class="anland-card card bg-base-100 border border-base-300 shadow-sm hover:shadow-md overflow-hidden flex flex-col">
    <figure class="anland-card__figure aspect-[4/3] relative">
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
        @if(count($gallery) > 0)
        <span class="absolute bottom-2 right-2 flex items-center gap-1 bg-black/55 text-white text-[11px] rounded-md px-1.5 py-0.5">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M4 8h3l1.5-2h7L17 8h3a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/><circle cx="12" cy="13" r="3.5"/></svg>
            {{ count($gallery) }}
        </span>
        @endif
    </figure>
    <div class="card-body p-3.5 gap-1.5 flex-1">
        <h3 class="font-semibold text-sm text-base-content line-clamp-2 leading-snug min-h-[2.5rem]">{{ $content['title'] }}</h3>
        <div class="flex items-center justify-between text-[15px] font-bold">
            <span style="color:var(--az-price-blue)">{{ $listing->display_price }}</span>
            @if($listing->area)
            <span style="color:var(--az-navy)">{{ number_format((float) $listing->area, 0) }} m²</span>
            @endif
        </div>
        @if($listing->province)
        <div class="flex items-center gap-1 text-xs text-base-content/50">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5 shrink-0"><path d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
            <span class="truncate">{{ $listing->province->name }}</span>
        </div>
        @endif
        <div class="flex items-center justify-between mt-auto pt-2 border-t border-base-200">
            <span class="text-[11px] text-base-content/40">{{ $listing->created_at->diffForHumans() }}</span>
            @if($listing->bedrooms)
            <span class="text-[11px] text-base-content/40">{{ $listing->bedrooms }} PN</span>
            @endif
        </div>
    </div>
</a>
