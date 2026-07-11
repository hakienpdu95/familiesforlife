<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($translations as $t)
    <url>
        <loc>{{ route('post.public.article', ['locale' => $t->locale, 'slug' => $t->slug]) }}</loc>
        <lastmod>{{ $t->updated_at->toAtomString() }}</lastmod>
    </url>
@endforeach
</urlset>
