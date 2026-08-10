@if($block->heading)
<h4 class="not-prose font-semibold text-base-content mt-6 mb-2">{{ $block->heading }}</h4>
@endif
<div class="not-prose grid grid-cols-2 md:grid-cols-3 gap-3 my-4">
    @foreach($block->items as $item)
    <div class="card bg-base-100 border border-base-200 shadow-sm">
        @if($item->display_image_url)
        <figure><img src="{{ $item->display_image_url }}" alt="{{ $item->display_title }}" class="aspect-square object-cover w-full"></figure>
        @endif
        <div class="card-body p-3">
            <h5 class="text-sm font-semibold line-clamp-2">{{ $item->display_title }}</h5>
            @if($item->display_price_label)<p class="text-primary font-bold text-sm">{{ $item->display_price_label }}</p>@endif
            {{-- rel="sponsored noopener" — xem banner.blade.php. --}}
            @foreach($item->buttons as $button)
            <a href="{{ route('post.cta.redirect', $button) }}" target="{{ $button->target->value }}" rel="sponsored noopener"
               class="btn btn-xs {{ $button->style->btnClass() }} w-full mt-1">{{ $button->display_label }}</a>
            @endforeach
        </div>
    </div>
    @endforeach
</div>
@if($block->buttons->isNotEmpty())
<div class="not-prose flex justify-center gap-2 mb-4">
    @foreach($block->buttons as $button)
    <a href="{{ route('post.cta.redirect', $button) }}" target="{{ $button->target->value }}" rel="sponsored noopener"
       class="btn btn-sm {{ $button->style->btnClass() }}">{{ $button->display_label }}</a>
    @endforeach
</div>
@endif
