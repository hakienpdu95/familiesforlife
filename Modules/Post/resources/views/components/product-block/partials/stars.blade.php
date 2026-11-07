@if ($item?->ocop_stars)
    <span @class(['inline-flex items-center gap-1 whitespace-nowrap', $class ?? 'text-xs'])>
        <span class="tracking-[.08em] text-amber-500" aria-hidden="true">{{ str_repeat('★', $item->ocop_stars) }}<span class="text-base-300">{{ str_repeat('★', max(0, 5 - $item->ocop_stars)) }}</span></span>
        <span class="font-medium text-base-content/70">OCOP {{ $item->ocop_stars }} sao</span>
    </span>
@endif
