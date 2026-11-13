@php($portal = app(\App\Support\I18n\PortalLocale::class))
@if($portal->active() && \App\Models\SystemSetting::showPortalLocaleSwitcher())
<span class="portal-locale-switcher inline-flex items-center gap-1 mx-3 text-xs font-semibold" aria-label="{{ __('Ngôn ngữ') }}">
    @foreach($portal->switcherLinks() as $code => $link)
        @if(! $loop->first)<span aria-hidden="true">|</span>@endif
        @if($link['active'])
        <span aria-current="true" title="{{ $link['label'] }}">{{ strtoupper($code) }}</span>
        @else
        <a href="{{ $link['url'] }}" hreflang="{{ $code }}" lang="{{ $code }}" title="{{ $link['label'] }}" class="opacity-70 hover:opacity-100">{{ strtoupper($code) }}</a>
        @endif
    @endforeach
</span>
@endif
