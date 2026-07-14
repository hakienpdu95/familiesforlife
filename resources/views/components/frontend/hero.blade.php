@props([
    'featured', // PostArticleTranslation
    'locale',
])

<section class="relative overflow-hidden bg-warning/15 py-16 text-center">
    <div class="blob bg-warning h-24 w-24 -left-6 top-6"></div>
    <div class="blob bg-accent h-16 w-16 left-24 bottom-4"></div>
    <div class="blob bg-primary h-14 w-14 right-16 top-10"></div>
    <div class="blob bg-secondary h-20 w-20 -right-8 bottom-0"></div>

    <div class="relative max-w-2xl mx-auto px-4">
        <span class="inline-block rounded-full bg-base-100 px-6 py-2 font-black text-xs uppercase tracking-widest text-primary">Nổi Bật</span>
        <a href="{{ route('post.public.article', ['locale' => $locale, 'slug' => $featured->slug]) }}" class="group block">
            <h1 class="mt-4 font-black text-3xl sm:text-4xl text-secondary leading-tight group-hover:text-primary">{{ $featured->title }}</h1>
        </a>
        <p class="mt-3 text-sm font-semibold text-base-content/50 uppercase tracking-wide">{{ $featured->published_at?->format('d/m/Y') }}</p>
        @if($featured->excerpt)
        <p class="mt-1 text-sm text-base-content/60">{{ $featured->excerpt }}</p>
        @endif
    </div>
</section>
