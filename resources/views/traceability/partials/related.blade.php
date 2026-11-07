@foreach ($related as $group)
    @if ($group['items'])
        <section class="mt-6">
            <h2 class="px-4 text-sm font-bold mb-2 flex items-center gap-1.5">
                <i class="fa-solid {{ $group['icon'] ?? 'fa-link' }} text-primary" aria-hidden="true"></i> {{ $group['title'] }}
            </h2>
            <div class="flex snap-x overflow-x-auto gap-3 px-4 pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($group['items'] as $item)
                    <a href="{{ $item['url'] }}" class="snap-start shrink-0 w-40 rounded-xl border border-base-200 bg-base-100 overflow-hidden hover:border-primary transition-colors">
                        @if ($item['image'])
                            <img src="{{ $item['image'] }}" alt="{{ $item['title'] }}" width="320" height="200" loading="lazy" class="w-40 h-24 object-cover bg-base-200">
                        @else
                            <div class="w-40 h-24 bg-base-200 flex items-center justify-center text-base-content/30"><i class="fa-solid {{ $group['icon'] ?? 'fa-link' }} text-2xl" aria-hidden="true"></i></div>
                        @endif
                        <div class="p-2">
                            <p class="text-xs font-semibold leading-snug line-clamp-2">{{ $item['title'] }}</p>
                            @if ($item['subtitle'])<p class="text-[11px] text-base-content/50 mt-0.5 line-clamp-1">{{ $item['subtitle'] }}</p>@endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
@endforeach
