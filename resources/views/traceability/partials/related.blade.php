@php($groups = array_values(array_filter($related, fn ($g) => ! empty($g['items']))))
@foreach ($groups as $group)
    <section class="px-5 mt-9">
        <h2 class="tx-label mb-1">{{ $group['title'] }}</h2>
        <ul>
            @foreach ($group['items'] as $item)
                <li>
                    <a href="{{ $item['url'] }}" class="flex items-center gap-3 py-3 border-b border-base-300 group">
                        @if ($item['image'])
                            <img src="{{ $item['image'] }}" alt="" width="64" height="64" loading="lazy" class="w-14 h-14 rounded object-cover bg-base-200 shrink-0">
                        @endif
                        <span class="min-w-0 flex-1">
                            <span class="block text-[15px] font-medium leading-snug group-hover:text-primary line-clamp-2">{{ $item['title'] }}</span>
                            @if ($item['subtitle'])<span class="block text-xs text-base-content/60 mt-0.5">{{ $item['subtitle'] }}</span>@endif
                        </span>
                        <span class="text-base-content/30 group-hover:text-primary" aria-hidden="true">›</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endforeach
