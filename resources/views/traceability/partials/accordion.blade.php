@if ($sections)
    <div class="border-t border-base-300">
        @foreach ($sections as $section)
            <details class="tx-details border-b border-base-300" @if ($loop->first && ($openFirst ?? false)) open @endif>
                <summary class="flex items-center justify-between gap-4 py-3.5 text-[15px] font-medium">
                    {{ $section['title'] }}
                    <span class="tx-plus font-mono text-lg text-base-content/40 leading-none"></span>
                </summary>
                @if (! empty($section['html']))
                    <div class="tx-prose pb-4">{!! $section['html'] !!}</div>
                @else
                    <p class="pb-4 text-[15px] leading-relaxed text-base-content/80 whitespace-pre-line">{{ $section['text'] }}</p>
                @endif
            </details>
        @endforeach
    </div>
@endif
