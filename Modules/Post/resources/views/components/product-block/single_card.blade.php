@php($item = $block->items->first())
@php($price = $item?->ocop_stars && blank($item?->price_label_override) ? null : $item?->display_price_label)
<aside class="not-prose @container my-7 rounded-lg border border-base-300 bg-base-100 overflow-hidden" aria-label="{{ $block->heading ?: 'Sản phẩm' }}">
    <div class="flex items-center justify-between gap-3 px-4 py-2.5 border-b border-base-300 bg-base-200/50">
        <span class="text-[11px] font-semibold uppercase tracking-[.12em] text-base-content/60 truncate">{{ $block->heading ?: ($block->isOcop() ? 'Sản phẩm OCOP' : 'Sản phẩm') }}</span>
        @include('post::components.product-block.partials.stars', ['class' => 'text-[12px]'])
    </div>
    <div class="flex gap-4 p-4">
        @include('post::components.product-block.partials.media', ['class' => 'w-24 h-24 @md:w-32 @md:h-32 shrink-0 rounded-md', 'size' => 256])
        <div class="min-w-0 flex-1">
            <h3 class="font-serif tx-serif text-[17px] @md:text-lg font-bold leading-snug text-base-content line-clamp-2">
                @include('post::components.product-block.partials.title')
            </h3>
            @if ($item?->display_meta)
                <p class="mt-1 text-[13px] text-base-content/60 line-clamp-1">{{ $item->display_meta }}</p>
            @endif
            @if ($price)
                <p class="mt-1.5 text-base font-bold text-primary">{{ $price }}</p>
            @endif
            @if ($item?->display_description)
                <p class="mt-2 text-sm leading-relaxed text-base-content/75 line-clamp-3">{{ $item->display_description }}</p>
            @endif
        </div>
    </div>
    @include('post::components.product-block.partials.buttons', ['blockButtons' => $block->buttons, 'class' => 'px-4 pb-4'])
</aside>
