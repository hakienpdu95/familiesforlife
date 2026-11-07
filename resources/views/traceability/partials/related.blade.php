@php($groups = array_values(array_filter($related, fn ($g) => ! empty($g['items']))))
@foreach ($groups as $group)
    <section class="tx-section">
        <h2 class="tx-section-title">{{ $group['title'] }} <small>{{ count($group['items']) }}</small></h2>
        <ul class="tx-card tx-rows">
            @foreach ($group['items'] as $item)
                <li>
                    <a href="{{ $item['url'] }}" class="tx-row group">
                        @if ($item['image'])
                            <img src="{{ $item['image'] }}" alt="" width="56" height="56" loading="lazy" decoding="async" class="w-14 h-14 rounded-md object-cover bg-[#efefec] shrink-0">
                        @endif
                        <span class="min-w-0 flex-1">
                            <span class="block text-[15px] font-medium leading-snug group-hover:text-primary line-clamp-2">{{ $item['title'] }}</span>
                            @if ($item['subtitle'])<span class="block text-[13px] text-base-content/55 mt-0.5">{{ $item['subtitle'] }}</span>@endif
                        </span>
                        <span class="tx-chevron">@include('traceability.partials.icon', ['name' => 'chevron'])</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>
@endforeach
