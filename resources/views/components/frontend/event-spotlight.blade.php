@props([
    'events', // Collection<Modules\Event\Models\Event> (with category loaded) — sắp diễn ra, gần nhất trước
])

@if($events->isNotEmpty())
@php
    $lead = $events->first();
    $rest = $events->slice(1, 4);
@endphp
<section class="vgd-events bg-neutral text-neutral-content pt-10 pb-10">
    <div class="container">
        <div class="flex flex-col items-center text-center">
            <h2 class="font-normal text-3xl tracking-wide">Sự kiện sắp diễn ra</h2>
            <h3 class="subtitle">Sự kiện nổi bật</h3>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[5fr_7fr] gap-5">
            <a href="{{ route('event.public.show', ['slug' => $lead->slug, 'id' => $lead->id]) }}"
               class="group flex flex-col gap-5 h-full">
                <div class="relative w-full flex-1 min-h-[250px] overflow-hidden bg-base-200 lg:min-h-0">
                    <img src="{{ $lead->posterUrl() }}"
                         alt="{{ $lead->poster_alt ?? $lead->title }}"
                         class="absolute inset-0 w-full h-full object-cover transition duration-300 group-hover:scale-105">
                </div>
                <x-frontend.event-spotlight-item :event="$lead" as="div" />
            </a>

            <ul class="flex flex-col gap-5 h-full">
                @foreach($rest as $event)
                <li class="flex-1">
                    <x-frontend.event-spotlight-item :event="$event" />
                </li>
                @endforeach
            </ul>
        </div>
    </div>
</section>

@php
    $eventFilters = [
        ['label' => 'Tất cả sự kiện', 'url' => route('event.public.home')],
        ['label' => 'Tuần này', 'url' => route('event.public.home', ['thoi-gian' => 'tuan-nay'])],
        ['label' => 'Tháng này', 'url' => route('event.public.home', ['thoi-gian' => 'thang-nay'])],
        ['label' => 'Đăng sự kiện', 'url' => route('event.public.submit.form')],
        ['label' => 'Tìm kiếm sự kiện', 'url' => route('event.public.home').'#tim-su-kien'],
    ];
@endphp
<nav class="vgd-events-filters bg-neutral" aria-label="Lọc sự kiện">
    <div class="container">
        <div class="flex justify-between flex-wrap">
            @foreach($eventFilters as $i => $filter)
            <div class="vgd-events-filter vgd-events-filter-{{ $i + 1 }} grow basis-0 max-w-full">
                <a href="{{ $filter['url'] }}" title="{{ $filter['label'] }}"
                   class="block px-3 py-3.5 text-center text-white text-xs font-medium uppercase">{{ $filter['label'] }}</a>
            </div>
            @endforeach
        </div>        
    </div>
</nav>
@endif
