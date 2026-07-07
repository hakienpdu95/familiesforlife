@if($block->heading)
<h4 class="not-prose font-semibold text-base-content mt-6 mb-2">{{ $block->heading }}</h4>
@endif
<div class="not-prose flex flex-col divide-y divide-base-200 border border-base-200 rounded-xl my-4 overflow-hidden">
    @foreach($block->items as $item)
    <div class="flex items-center gap-3 p-3 bg-base-100">
        @if($item->display_image_url)
        <img src="{{ $item->display_image_url }}" alt="{{ $item->display_title }}" class="w-12 h-12 object-cover rounded-lg shrink-0">
        @endif
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium truncate">{{ $item->display_title }}</p>
            @if($item->display_price_label)<p class="text-xs text-primary font-semibold">{{ $item->display_price_label }}</p>@endif
        </div>
        @foreach($item->buttons->take(1) as $button)
        <a href="{{ route('post.cta.redirect', $button) }}" target="{{ $button->target->value }}"
           class="btn btn-xs {{ $button->style->btnClass() }} shrink-0">{{ $button->display_label }}</a>
        @endforeach
    </div>
    @endforeach
</div>
@if($block->buttons->isNotEmpty())
<div class="not-prose flex justify-center gap-2 mb-4">
    @foreach($block->buttons as $button)
    <a href="{{ route('post.cta.redirect', $button) }}" target="{{ $button->target->value }}"
       class="btn btn-sm {{ $button->style->btnClass() }}">{{ $button->display_label }}</a>
    @endforeach
</div>
@endif
