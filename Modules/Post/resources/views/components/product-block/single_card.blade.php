@php($item = $block->items->first())
<div class="not-prose card lg:card-side bg-base-100 border border-base-200 shadow-sm my-4">
    @if($item?->display_image_url)
    <figure class="lg:w-64 shrink-0">
        <img src="{{ $item->display_image_url }}" alt="{{ $item->display_title }}" class="object-cover w-full h-full">
    </figure>
    @endif
    <div class="card-body">
        @if($block->heading)<p class="text-xs font-semibold text-base-content/40 uppercase tracking-wide">{{ $block->heading }}</p>@endif
        <h3 class="card-title">{{ $item?->display_title }}</h3>
        @if($item?->display_price_label)<p class="text-lg font-bold text-primary">{{ $item->display_price_label }}</p>@endif
        @if($item?->display_description)<p class="text-sm text-base-content/70">{{ $item->display_description }}</p>@endif
        {{-- rel="sponsored noopener" — xem banner.blade.php. --}}
        <div class="card-actions mt-2 flex-wrap gap-2">
            @foreach($item?->buttons ?? [] as $button)
            <a href="{{ route('post.cta.redirect', $button) }}" target="{{ $button->target->value }}" rel="sponsored noopener"
               class="btn btn-sm {{ $button->style->btnClass() }}">{{ $button->display_label }}</a>
            @endforeach
            @foreach($block->buttons as $button)
            <a href="{{ route('post.cta.redirect', $button) }}" target="{{ $button->target->value }}" rel="sponsored noopener"
               class="btn btn-sm {{ $button->style->btnClass() }}">{{ $button->display_label }}</a>
            @endforeach
        </div>
    </div>
</div>
