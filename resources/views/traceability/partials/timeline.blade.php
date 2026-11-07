@if ($timeline)
    <section class="px-4 mt-5">
        <h2 class="text-sm font-bold mb-3 flex items-center gap-1.5"><i class="fa-solid fa-route text-primary" aria-hidden="true"></i> {{ $heading ?? 'Hành trình' }}</h2>
        <ol class="relative ml-2 border-l-2 border-base-300 space-y-4">
            @foreach ($timeline as $event)
                <li class="ml-4">
                    <span @class([
                        'absolute -left-[7px] mt-1 w-3 h-3 rounded-full ring-4 ring-base-100',
                        'bg-green-500' => $event['tone'] === 'success',
                        'bg-orange-500' => $event['tone'] === 'warning',
                        'bg-red-500' => $event['tone'] === 'error',
                        'bg-primary' => $event['tone'] === 'primary',
                        'bg-base-content/30' => $event['tone'] === 'neutral',
                    ])></span>
                    <p class="text-xs text-base-content/50">{{ $event['date'] }}</p>
                    <p class="text-sm font-semibold">{{ $event['label'] }}</p>
                    @if ($event['note'])<p class="text-xs text-base-content/60">{{ $event['note'] }}</p>@endif
                </li>
            @endforeach
        </ol>
    </section>
@endif
