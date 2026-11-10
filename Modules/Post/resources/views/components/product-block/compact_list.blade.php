<section class="not-prose my-7" aria-label="{{ $block->heading ?: 'Sản phẩm' }}">
    <h4 class="pb-2 border-b border-base-300 text-[11px] font-semibold uppercase tracking-[.12em] text-base-content/60">{{ $block->heading ?: ($block->isOcop() ? 'Sản phẩm OCOP' : 'Sản phẩm') }}</h4>
    <ul>
        @foreach ($block->items as $item)
            @php($price = $item->ocop_stars && blank($item->price_label_override) ? null : $item->display_price_label)
            @php($button = $item->buttons->each(fn ($b) => $b->setRelation('blockItem', $item))->first(fn ($b) => filled($b->target_url = $b->resolveTargetUrl())))
            <li class="flex items-center gap-3 py-3 border-b border-base-300">
                @include('post::components.product-block.partials.media', ['class' => 'w-14 h-14 shrink-0 rounded-md', 'size' => 112])
                <div class="min-w-0 flex-1">
                    <p class="text-[15px] font-medium leading-snug text-base-content line-clamp-2">
                        @include('post::components.product-block.partials.title')
                    </p>
                    <p class="mt-0.5 flex flex-wrap items-center gap-x-2 text-xs text-base-content/60">
                        @include('post::components.product-block.partials.stars', ['class' => 'text-xs'])
                        @if ($price)<span class="font-semibold text-primary">{{ $price }}</span>@endif
                        @if ($item->display_meta)<span class="truncate">{{ $item->display_meta }}</span>@endif
                    </p>
                </div>
                @if ($button)
                    <a href="{{ $button->target_url }}" target="{{ $button->target->value }}" rel="sponsored noopener"
                       class="btn btn-sm shrink-0 rounded-lg font-medium shadow-none gap-1.5 {{ $button->btn_class }}">@if ($button->channel_logo)<span class="w-5 h-5 rounded bg-white flex items-center justify-center shrink-0" aria-hidden="true"><img src="{{ $button->channel_logo }}" alt="" width="16" height="16" loading="lazy" decoding="async" class="w-4 h-4 object-contain"></span>@endif<span class="truncate">{{ $button->display_label }}</span></a>
                @elseif ($item->display_url)
                    <a href="{{ $item->display_url }}" class="shrink-0 px-1 text-lg text-base-content/30 hover:text-primary" aria-label="Xem {{ $item->display_title }}">›</a>
                @endif
            </li>
        @endforeach
    </ul>
    @include('post::components.product-block.partials.buttons', ['item' => null, 'blockButtons' => $block->buttons, 'class' => 'mt-4'])
</section>
