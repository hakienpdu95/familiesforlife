@props([
    'events', // LengthAwarePaginator<Event>
])

<section class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
    @forelse($events as $event)
    <x-frontend.event-card :event="$event" size="sm" />
    @empty
    <p class="col-span-full text-center text-base-content/40 py-10">Chưa có sự kiện nào sắp diễn ra.</p>
    @endforelse
</section>

@if($events->hasPages())
<div class="pt-10 flex justify-center">
    {{ $events->onEachSide(1)->links() }}
</div>
@endif
