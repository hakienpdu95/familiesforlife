@props([
    'event', // Modules\Event\Models\Event (with category loaded)
    'size' => 'md', // lg | md | sm
])

@php
    $styles = match ($size) {
        'lg' => ['ratio' => 'aspect-[16/10]', 'title' => 'text-2xl font-extrabold', 'gap' => 'gap-6'],
        'sm' => ['ratio' => 'aspect-[4/3]', 'title' => 'font-bold text-sm', 'gap' => 'gap-3'],
        default => ['ratio' => 'aspect-[16/9]', 'title' => 'font-bold', 'gap' => 'gap-3'],
    };
    $sameDay = $event->start_date?->isSameDay($event->end_date);
@endphp

<a href="{{ route('event.public.show', ['slug' => $event->slug]) }}"
   class="group flex flex-col {{ $styles['gap'] }}">
    <div class="{{ $styles['ratio'] }} rounded-xl overflow-hidden bg-base-200 relative">
        <img src="{{ $event->poster_path ? \Illuminate\Support\Facades\Storage::url($event->poster_path) : asset('images/post-cover-placeholder.svg') }}"
             alt="{{ $event->poster_alt ?? $event->title }}" class="h-full w-full object-cover" loading="lazy">
        <span class="absolute top-2 left-2 rounded-md bg-base-100/90 px-2 py-1 text-[11px] font-black uppercase tracking-wide text-secondary">
            {{ $event->start_date?->format('d/m') }}{{ ! $sameDay ? ' – '.$event->end_date?->format('d/m') : '' }}
        </span>
    </div>
    <div>
        @if($event->category)
        <span class="text-xs font-black uppercase tracking-wide text-primary">{{ $event->category->name }}</span>
        @endif
        <h3 class="{{ $styles['title'] }} leading-snug group-hover:text-primary mt-1">{{ $event->title }}</h3>
        <p class="mt-1 text-xs text-base-content/60">{{ $event->locationLabel() }}</p>
        <p class="mt-1 text-xs font-semibold text-secondary">{{ $event->priceLabel() }}</p>
    </div>
</a>
