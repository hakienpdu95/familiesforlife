@php($item = $block->items->first())
<div class="not-prose bg-gradient-to-r from-primary/10 to-primary/5 border border-primary/20 rounded-xl p-5 my-4 flex items-center gap-5 flex-wrap">
    @if($item?->display_image_url)
    <img src="{{ $item->display_image_url }}" alt="{{ $item->display_title }}" class="w-28 h-28 object-cover rounded-lg shrink-0">
    @endif
    <div class="flex-1 min-w-48">
        @if($block->heading)<p class="text-xs font-semibold text-primary uppercase tracking-wide">{{ $block->heading }}</p>@endif
        <h3 class="text-xl font-bold">{{ $item?->display_title }}</h3>
        @if($item?->display_price_label)<p class="text-2xl font-extrabold text-primary mt-1">{{ $item->display_price_label }}</p>@endif
    </div>
    <div class="flex flex-wrap gap-2">
        @foreach($item?->buttons ?? [] as $button)
        <a href="{{ route('post.cta.redirect', $button) }}" target="{{ $button->target->value }}"
           class="btn {{ $button->style->btnClass() }}">{{ $button->display_label }}</a>
        @endforeach
        @foreach($block->buttons as $button)
        <a href="{{ route('post.cta.redirect', $button) }}" target="{{ $button->target->value }}"
           class="btn {{ $button->style->btnClass() }}">{{ $button->display_label }}</a>
        @endforeach
    </div>
</div>
