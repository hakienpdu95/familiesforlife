@php($item = $block->items->first())
@php($price = $item?->ocop_stars && blank($item?->price_label_override) ? null : $item?->display_price_label)
<aside class="not-prose @container my-7 rounded-lg bg-base-200/70 overflow-hidden" aria-label="{{ $block->heading ?: 'Sản phẩm nổi bật' }}">
    <div class="flex flex-col @md:flex-row">
        @include('post::components.product-block.partials.media', ['class' => 'aspect-[4/3] @md:aspect-square @md:w-52 shrink-0', 'size' => 480])
        <div class="flex flex-1 flex-col justify-center gap-3 p-5">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[.12em] text-primary">{{ $block->heading ?: ($block->isOcop() ? 'Sản phẩm OCOP nổi bật' : 'Sản phẩm nổi bật') }}</p>
                <h3 class="mt-1.5 font-serif tx-serif text-xl font-bold leading-snug text-base-content">
                    @include('post::components.product-block.partials.title')
                </h3>
                <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1">
                    @include('post::components.product-block.partials.stars', ['class' => 'text-[13px]'])
                    @if ($item?->display_meta)<span class="text-[13px] text-base-content/60">{{ $item->display_meta }}</span>@endif
                </div>
                @if ($price)<p class="mt-2 text-2xl font-bold text-primary">{{ $price }}</p>@endif
                @if ($item?->display_description)
                    <p class="mt-2 text-sm leading-relaxed text-base-content/75 line-clamp-2">{{ $item->display_description }}</p>
                @endif
            </div>
            @include('post::components.product-block.partials.buttons', ['blockButtons' => $block->buttons])
        </div>
    </div>
</aside>
