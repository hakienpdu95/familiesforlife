@php($portal = app(\App\Support\I18n\PortalLocale::class))
@if($portal->active())
    @if($portal->showFallbackNotice())
    <meta name="robots" content="noindex, follow">
    @endif
    @foreach($portal->hreflangs() as $hreflang => $href)
    <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}">
    @endforeach
@endif
