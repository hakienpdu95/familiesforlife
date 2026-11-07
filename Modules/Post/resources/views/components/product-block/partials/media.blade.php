<span @class(['relative block overflow-hidden bg-base-200', $class ?? ''])>
    @if ($item?->display_image_url)
        <img src="{{ $item->display_image_url }}" alt="{{ $item->display_title }}" width="{{ $size ?? 320 }}" height="{{ $size ?? 320 }}" loading="lazy" decoding="async"
             class="absolute inset-0 w-full h-full object-cover">
    @else
        <span class="absolute inset-0 flex items-center justify-center text-base-content/20" aria-hidden="true">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="w-2/5 max-w-10 h-auto"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        </span>
    @endif
</span>
