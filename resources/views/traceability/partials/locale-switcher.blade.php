@php($portal = app(\App\Support\I18n\PortalLocale::class))
@if ($portal->active() && count(\App\Support\I18n\PortalLocale::supported()) > 1)
@php($links = $portal->switcherLinks())
@php($flags = config('i18n.portal_locale_flags', []))
@php($current = $portal->current())
<details class="relative shrink-0 group/lang">
    <summary class="list-none [&::-webkit-details-marker]:hidden flex items-center gap-1.5 h-9 pl-2 pr-1.5 rounded-lg border border-[rgb(28_25_23/.12)] bg-base-100 text-[13px] font-semibold cursor-pointer select-none hover:border-primary/50"
             aria-label="{{ __('Ngôn ngữ') }}: {{ $links[$current]['label'] ?? strtoupper($current) }}">
        <span class="text-base leading-none" aria-hidden="true">{{ $flags[$current] ?? '' }}</span>
        <span class="uppercase">{{ $current }}</span>
        <svg class="w-3.5 h-3.5 text-[#78716c] transition-transform group-open/lang:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </summary>
    <ul class="absolute right-0 mt-1.5 min-w-40 py-1 rounded-xl bg-base-100 border border-[rgb(28_25_23/.1)] shadow-lg z-30">
        @foreach ($links as $code => $link)
        <li>
            @if ($link['active'])
            <span class="flex items-center gap-2.5 px-3 py-2 text-[14px] font-semibold text-primary" aria-current="true">
                <span class="text-base leading-none" aria-hidden="true">{{ $flags[$code] ?? '' }}</span>{{ $link['label'] }}
            </span>
            @else
            <a href="{{ $link['url'] }}" hreflang="{{ $code }}" lang="{{ $code }}" class="flex items-center gap-2.5 px-3 py-2 text-[14px] hover:bg-base-200">
                <span class="text-base leading-none" aria-hidden="true">{{ $flags[$code] ?? '' }}</span>{{ $link['label'] }}
            </a>
            @endif
        </li>
        @endforeach
    </ul>
</details>
@endif
