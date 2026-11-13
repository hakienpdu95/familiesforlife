@if ($sections)
    <div class="tx-card tx-rows">
        @foreach ($sections as $section)
            <details class="tx-details group/acc" {{ \App\Support\Analytics\ClickTracking::toggle('qr_section_open', $section['key'] ?? null) }} @if ($loop->first && ($openFirst ?? false)) open @endif>
                <summary class="tx-row-link flex items-center justify-between gap-4 px-4 min-h-[3.25rem] py-3 text-[15px] font-semibold">
                    {{ $section['title'] }}
                    <span class="tx-chevron transition-transform group-open/acc:rotate-90">@include('traceability.partials.icon', ['name' => 'chevron'])</span>
                </summary>
                @if (! empty($section['html']))
                    <div class="tx-prose px-4 pb-4">{!! $section['html'] !!}</div>
                @else
                    <p class="px-4 pb-4 text-[15px] leading-relaxed text-base-content/80 whitespace-pre-line">{{ $section['text'] }}</p>
                @endif
            </details>
        @endforeach
    </div>
@endif
