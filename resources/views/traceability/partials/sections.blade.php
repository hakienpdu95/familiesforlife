@if ($sections)
    <section class="px-4 mt-5">
        <div class="rounded-xl border border-base-200 divide-y divide-base-200">
            @foreach ($sections as $section)
                <details class="group" @if ($loop->first) open @endif>
                    <summary class="flex items-center justify-between gap-3 px-3 py-3 cursor-pointer list-none text-sm font-semibold">
                        <span class="flex items-center gap-2">
                            @if (! empty($section['icon']))<i class="fa-solid {{ $section['icon'] }} text-primary" aria-hidden="true"></i>@endif
                            {{ $section['title'] }}
                        </span>
                        <i class="fa-solid fa-chevron-right text-xs text-base-content/40 transition-transform group-open:rotate-90" aria-hidden="true"></i>
                    </summary>
                    @if (! empty($section['html']))
                        <div class="px-3 pb-3 prose prose-sm max-w-none prose-img:rounded-lg prose-a:text-primary [&_table]:block [&_table]:overflow-x-auto [&_iframe]:w-full [&_iframe]:aspect-video">{!! $section['html'] !!}</div>
                    @else
                        <p class="px-3 pb-3 text-sm leading-relaxed text-base-content/80 whitespace-pre-line">{{ $section['text'] }}</p>
                    @endif
                </details>
            @endforeach
        </div>
    </section>
@endif
