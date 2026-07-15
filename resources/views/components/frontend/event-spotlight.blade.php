@props([
    'events', // Collection<Modules\Event\Models\Event> (with category loaded) — sắp diễn ra, gần nhất trước
])

{{--
  spec/Event_Management_Technical_Specification.md §12 — thay x-frontend.sponsor-spotlight
  (đang dùng post_articles.is_sponsored làm placeholder "Sự Kiện Cho Bé") bằng dữ liệu Event
  thật, giữ đúng bố cục 1 khối lớn + danh sách bên cạnh của bản mẫu tĩnh gốc.
--}}
@if($events->isNotEmpty())
@php
    $lead = $events->first();
    $rest = $events->slice(1);
@endphp
<section class="bg-neutral text-neutral-content pt-12 pb-10">
    <div class="container">
        <div class="flex items-center justify-between mb-10">
            <h2 class="font-normal text-3xl tracking-wide">Sự Kiện Sắp Diễn Ra</h2>
            <a href="{{ route('event.public.home') }}" class="text-sm font-bold uppercase tracking-wide text-primary hover:underline">Xem Tất Cả</a>
        </div>

        <div class="grid lg:grid-cols-[5fr_7fr] gap-6 items-stretch">
            <a href="{{ route('event.public.show', ['slug' => $lead->slug]) }}" class="group flex flex-col h-full">
                <div class="flex-1 min-h-[220px] bg-base-200">
                    @if($lead->poster_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($lead->poster_path) }}" alt="{{ $lead->poster_alt ?? $lead->title }}" class="h-full w-full object-cover">
                    @else
                    <div class="ph h-full w-full"></div>
                    @endif
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
                        <a href="{{ route('event.public.show', ['slug' => $event->slug]) }}" class="group flex bg-base-100 text-base-content">
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
@endif
