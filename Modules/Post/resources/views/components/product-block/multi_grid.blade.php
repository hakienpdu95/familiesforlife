<section class="not-prose @container my-7" {{ \App\Support\Analytics\ClickTracking::context('article_product_block:'.$block->template?->value) }} aria-label="{{ $block->heading ?: __('Sản phẩm') }}">
    <h4 class="pb-2 mb-4 border-b border-base-300 text-[11px] font-semibold uppercase tracking-[.12em] text-base-content/60">{{ $block->heading ?: ($block->isOcop() ? __('Sản phẩm OCOP') : __('Sản phẩm')) }}</h4>
    <ul class="grid grid-cols-2 @xl:grid-cols-3 gap-x-4 gap-y-6">
        @foreach ($block->items as $item)
            @php($price = $item->ocop_stars && blank($item->price_label_override) ? null : $item->display_price_label)
            @php($button = $item->buttons->each(fn ($b) => $b->setRelation('blockItem', $item))->first(fn ($b) => filled($b->target_url = $b->resolveTargetUrl())))
            <li class="flex flex-col" {{ $item->trackingAttributes() }}>
                @include('post::components.product-block.partials.media', ['class' => 'aspect-square rounded-md', 'size' => 400])
                <h5 class="mt-2.5 text-[15px] font-semibold leading-snug text-base-content line-clamp-2">
                    @include('post::components.product-block.partials.title')
                </h5>
                <div class="mt-1 flex flex-col gap-0.5">
                    @include('post::components.product-block.partials.stars', ['class' => 'text-[12px]'])
                    @if ($price)<span class="text-sm font-bold text-primary">{{ $price }}</span>@endif
                    @if ($item->display_meta)<span class="text-[12px] text-base-content/55 line-clamp-1">{{ $item->display_meta }}</span>@endif
                </div>
                @if ($button)
                    <div class="mt-auto pt-3">
                        <a href="{{ $button->target_url }}" target="{{ $button->target->value }}" rel="sponsored noopener" {{ $button->trackingAttributes() }}
                           class="btn btn-sm w-full rounded-lg font-medium shadow-none gap-1.5 {{ $button->btn_class }}">@if ($button->channel_logo)<span class="w-5 h-5 rounded bg-white flex items-center justify-center shrink-0" aria-hidden="true"><img src="{{ $button->channel_logo }}" alt="" width="16" height="16" loading="lazy" decoding="async" class="w-4 h-4 object-contain"></span>@endif<span class="truncate">{{ $button->display_label }}</span></a>
                    </div>
                @endif
            </li>
        @endforeach
    </ul>
    @include('post::components.product-block.partials.buttons', ['item' => null, 'blockButtons' => $block->buttons, 'class' => 'mt-6'])
</section>
