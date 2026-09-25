@props([
    'events', // Collection<Modules\Event\Models\Event> (with category loaded) — sắp diễn ra, gần nhất trước
])

@if($events->isNotEmpty())
@php
    $lead = $events->first();
    $rest = $events->slice(1);
@endphp
<section class="vgd-events bg-neutral text-neutral-content pt-10 pb-10">
    <div class="container">
        <div class="flex flex-col items-center text-center">
            <h2 class="font-normal text-3xl tracking-wide">Sự kiện sắp diễn ra</h2>
            <h3 class="subtitle">Sự kiện nổi bật</h3>
        </div>

        <div class="grid lg:grid-cols-[5fr_7fr] gap-6 items-stretch">
            <a href="{{ route('event.public.show', ['slug' => $lead->slug, 'id' => $lead->id]) }}" class="group flex flex-col h-full">
                <div class="flex-1 min-h-[220px] bg-base-200">
                    <img src="{{ $lead->poster_path ? \Illuminate\Support\Facades\Storage::url($lead->poster_path) : asset('images/post-cover-placeholder.svg') }}"
                         alt="{{ $lead->poster_alt ?? $lead->title }}" class="h-full w-full object-cover">
                </div>
                <div class="flex bg-base-100 text-base-content flex-none">
                    <span class="flex-none w-1.5 bg-primary"></span>
                    <div class="px-4 py-3">
                        <span class="text-[11px] font-black uppercase tracking-wide text-primary">{{ $lead->start_date?->format('d/m/Y') }} &middot; {{ $lead->category?->name ?? 'Sự kiện' }}</span>
                        <h3 class="font-bold leading-snug truncate group-hover:text-primary">{{ $lead->title }}</h3>
                    </div>
                </div>
            </a>

            <div class="flex flex-col h-full">
                <ul class="flex flex-col gap-1 flex-1 justify-between">
                    @foreach($rest as $event)
                    <li>
                        <a href="{{ route('event.public.show', ['slug' => $event->slug, 'id' => $event->id]) }}" class="group flex bg-base-100 text-base-content">
                            <span class="flex-none w-1.5 bg-primary"></span>
                            <div class="px-4 py-3">
                                <div class="text-[11px] font-black uppercase tracking-wide text-secondary">{{ $event->start_date?->format('d/m/Y') }}</div>
                                <h3 class="font-bold leading-snug group-hover:text-primary">{{ $event->title }}</h3>
                            </div>
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>
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
