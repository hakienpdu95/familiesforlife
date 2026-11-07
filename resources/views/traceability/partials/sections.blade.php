@if ($sections)
    <div class="rounded-lg border border-base-200 divide-y divide-base-200">
        @foreach ($sections as $section)
            <details class="group" @if ($loop->first) open @endif>
                <summary class="flex items-center justify-between gap-3 px-3 py-3 cursor-pointer list-none text-sm font-semibold">
                    {{ $section['title'] }}
                    <i class="fa-solid fa-chevron-down text-xs text-base-content/40 transition-transform group-open:rotate-180" aria-hidden="true"></i>
                </summary>
                <p class="px-3 pb-3 text-sm leading-relaxed text-base-content/80 whitespace-pre-line">{{ $section['text'] }}</p>
            </details>
        @endforeach
    </div>
@endif
