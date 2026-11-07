@php($buttons = collect($item?->buttons ?? [])->each(fn ($b) => $b->setRelation('blockItem', $item))->concat($blockButtons ?? [])->filter(fn ($b) => filled($b->target_url = $b->resolveTargetUrl()))->values())
@if ($buttons->isNotEmpty())
    <div @class(['grid gap-2', 'grid-cols-2' => $buttons->count() > 1, $class ?? ''])>
        @foreach ($buttons as $button)
            <a href="{{ $button->target_url }}" target="{{ $button->target->value }}" rel="sponsored noopener"
               @class(['btn rounded-lg font-medium shadow-none', $size ?? 'h-11 min-h-11', $button->style->btnClass(), 'col-span-2' => $buttons->count() % 2 === 1 && $loop->last && $buttons->count() > 1])>{{ $button->display_label }}</a>
        @endforeach
    </div>
@elseif ($item?->display_url)
    <a href="{{ $item->display_url }}" @class(['btn btn-outline w-full rounded-lg font-medium shadow-none', $size ?? 'h-11 min-h-11', $class ?? ''])>Xem chi tiết sản phẩm →</a>
@endif
