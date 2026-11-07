@if ($images)
    <div class="mt-4 flex snap-x snap-mandatory overflow-x-auto gap-2 px-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" aria-label="Thư viện ảnh">
        @foreach ($images as $i => $image)
            <img src="{{ $image }}" alt="{{ $title }} — ảnh {{ $i + 1 }}" width="{{ $imageWidth ?? 800 }}" height="{{ $imageHeight ?? 450 }}"
                 @class(['snap-center shrink-0 rounded-xl bg-base-200 object-cover', $imageClass ?? 'w-full aspect-video', 'w-[85%]' => count($images) > 1])
                 @if ($i > 0) loading="lazy" @endif>
        @endforeach
    </div>
    @if (count($images) > 1)
        <p class="px-4 mt-1 text-[11px] text-base-content/40 text-right">{{ count($images) }} ảnh · vuốt để xem</p>
    @endif
@endif

<div class="px-4 mt-4">
    @if ($badges)
        <div class="flex flex-wrap gap-1.5 mb-2">
            @foreach ($badges as $badge)
                <span class="badge badge-sm gap-1 {{ $badge['class'] ?? 'badge-outline' }}">
                    @if (! empty($badge['icon']))<i class="fa-solid {{ $badge['icon'] }}" aria-hidden="true"></i>@endif
                    {{ $badge['label'] }}
                </span>
            @endforeach
        </div>
    @endif
    <h1 class="text-2xl font-bold leading-snug">{{ $title }}</h1>
    @if ($subtitle)
        <p class="text-sm text-base-content/60 mt-1">{{ $subtitle }}</p>
    @endif
</div>
