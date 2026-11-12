@foreach($entry['urls'] as $url)
    <url>
        <loc>{{ $url }}</loc>
@if(! empty($entry['lastmod']))
        <lastmod>{{ $entry['lastmod']->toAtomString() }}</lastmod>
@endif
@if(! empty($entry['changefreq']))
        <changefreq>{{ $entry['changefreq'] }}</changefreq>
@endif
@if(! empty($entry['priority']))
        <priority>{{ $entry['priority'] }}</priority>
@endif
@if(count($entry['urls']) > 1)
@foreach($entry['urls'] as $locale => $alternate)
        <xhtml:link rel="alternate" hreflang="{{ $locale }}" href="{{ $alternate }}"/>
@endforeach
@isset($entry['urls'][$defaultLocale])
        <xhtml:link rel="alternate" hreflang="x-default" href="{{ $entry['urls'][$defaultLocale] }}"/>
@endisset
@endif
    </url>
@endforeach
