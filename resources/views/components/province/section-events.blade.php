@if($events->isNotEmpty())
<section class="py-10 border-t border-base-200 bg-base-200/30">
    <div class="container">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-base-content">Sự kiện tại {{ $province->name }}</h2>
            <a href="{{ route('event.public.home') }}" class="text-sm font-semibold text-primary hover:underline">Xem tất cả sự kiện →</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($events as $event)
            <x-frontend.event-card :event="$event" size="md" />
            @endforeach
        </div>
    </div>
</section>
@endif
