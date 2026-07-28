<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('post.public.home') }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
@foreach($categories as $category)
    <url>
        <loc>{{ route('post.public.category', ['category' => $category->slug]) }}</loc>
        <lastmod>{{ $category->updated_at->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.7</priority>
    </url>
@endforeach
@foreach($translations as $t)
    <url>
        <loc>{{ route('post.public.article', ['slug' => $t->slug, 'id' => $t->id]) }}</loc>
        <lastmod>{{ $t->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
@endforeach
@foreach($authorProfiles as $profile)
    <url>
        <loc>{{ route('post.public.author-hub.show', $profile) }}</loc>
        <lastmod>{{ $profile->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.5</priority>
    </url>
@endforeach
</urlset>
