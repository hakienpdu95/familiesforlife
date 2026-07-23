@props([
    'event', // Modules\Event\Models\Event (with category loaded)
    'size' => 'md', // lg | md | sm
])

@php
    $styles = match ($size) {
        'sm' => ['ratio' => 'aspect-[4/3]', 'title' => 'font-bold text-sm', 'gap' => 'gap-3'],
        default => ['ratio' => 'aspect-[16/9]', 'title' => 'font-bold', 'gap' => 'gap-3'],
    };
    $sameDay = $event->start_date?->isSameDay($event->end_date);
    $dateLabel = $event->start_date?->format('d/m').(! $sameDay ? ' – '.$event->end_date?->format('d/m') : '');
    $posterUrl = $event->poster_path ? \Illuminate\Support\Facades\Storage::url($event->poster_path) : asset('images/post-cover-placeholder.svg');
@endphp

@if($size === 'lg')
{{-- "Tin to" — cùng bố cục ngang (ảnh trái 60%/chữ phải 40%, không bo góc, có border) của
     x-frontend.article-card (Modules/Post), xem spec/page-detail. --}}
<a href="{{ route('event.public.show', ['slug' => $event->slug, 'id' => $event->id]) }}"
   class="group grid grid-cols-1 sm:grid-cols-5 sm:items-stretch overflow-hidden bg-base-100 border border-base-300">
    <div class="sm:col-span-3 aspect-[16/10] sm:aspect-auto bg-base-200 relative">
        <img src="{{ $posterUrl }}" alt="{{ $event->poster_alt ?? $event->title }}"
             class="h-full w-full object-cover" loading="lazy">
        <span class="absolute top-2 left-2 rounded-md bg-base-100/90 px-2 py-1 text-[11px] font-black uppercase tracking-wide text-secondary">
            {{ $dateLabel }}
        </span>
    </div>
    <div class="sm:col-span-2 flex flex-col justify-center p-6 sm:p-10">
        @if($event->category)
        <span class="text-xs font-black uppercase tracking-[0.2em] text-primary">{{ $event->category->name }}</span>
        @endif
        <h3 class="mt-3 text-2xl sm:text-3xl font-extrabold leading-snug group-hover:text-primary">{{ $event->title }}</h3>
        <p class="mt-4 text-sm text-base-content/60">{{ $event->locationLabel() }}</p>
        <p class="mt-1 text-sm font-bold text-secondary">{{ $event->priceLabel() }}</p>
    </div>
</a>
@else
<a href="{{ route('event.public.show', ['slug' => $event->slug, 'id' => $event->id]) }}"
   class="group flex flex-col {{ $styles['gap'] }}">
    <div class="{{ $styles['ratio'] }} rounded-xl overflow-hidden bg-base-200 relative">
        <img src="{{ $posterUrl }}" alt="{{ $event->poster_alt ?? $event->title }}"
             class="h-full w-full object-cover" loading="lazy">
        <span class="absolute top-2 left-2 rounded-md bg-base-100/90 px-2 py-1 text-[11px] font-black uppercase tracking-wide text-secondary">
            {{ $dateLabel }}
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
@endif
