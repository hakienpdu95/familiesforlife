@props([
    'event', // Modules\Event\Models\Event (with category loaded)
    'as' => 'a', // a | div
])

<{{ $as }} @if($as === 'a') href="{{ route('event.public.show', ['slug' => $event->slug, 'id' => $event->id]) }}" @endif
   {{ $attributes->class(['flex min-w-0 flex-col justify-center border-l-[12px] border-primary bg-white px-6 py-4 transition', 'group h-full hover:bg-gray-50' => $as === 'a']) }}>
    <span class="mb-2 text-xs font-bold uppercase tracking-wider text-primary">
        {{ $event->category?->name ?? 'Sự kiện' }} &middot; {{ $event->start_date?->format('d/m') }}
    </span>
    <h3 class="truncate text-lg font-medium leading-snug text-gray-900 group-hover:text-primary">{{ $event->title }}</h3>
</{{ $as }}>
