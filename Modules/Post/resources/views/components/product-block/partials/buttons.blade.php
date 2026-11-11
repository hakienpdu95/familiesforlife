@php($buttons = collect($item?->buttons ?? [])->each(fn ($b) => $b->setRelation('blockItem', $item))->concat($blockButtons ?? [])->filter(fn ($b) => filled($b->target_url = $b->resolveTargetUrl()))->values())
@if ($buttons->isNotEmpty())
    <div @class(['grid gap-2', 'grid-cols-2' => $buttons->count() > 1, $class ?? ''])>
        @foreach ($buttons as $button)
            <a href="{{ $button->target_url }}" target="{{ $button->target->value }}" rel="sponsored noopener"
               @class(['btn rounded-lg font-medium shadow-none gap-2', $size ?? 'h-11 min-h-11', $button->btn_class, 'col-span-2' => $buttons->count() % 2 === 1 && $loop->last && $buttons->count() > 1])>@if ($button->channel_logo)<span class="w-7 h-7 rounded-md bg-white flex items-center justify-center shrink-0" aria-hidden="true"><img src="{{ $button->channel_logo }}" alt="" width="22" height="22" loading="lazy" decoding="async" class="w-[22px] h-[22px] object-contain"></span>@endif<span class="truncate">{{ $button->display_label }}</span></a>
        @endforeach
    </div>
@elseif ($item?->display_url)
    <a href="{{ $item->display_url }}" @class(['btn btn-outline w-full rounded-lg font-medium shadow-none', $size ?? 'h-11 min-h-11', $class ?? ''])>{{ __('Xem chi tiết sản phẩm →') }}</a>
@endif
