<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($events as $e)
    <url>
        <loc>{{ route('event.public.show', ['slug' => $e->slug]) }}</loc>
        <lastmod>{{ $e->updated_at->toAtomString() }}</lastmod>
    </url>
@endforeach
</urlset>
