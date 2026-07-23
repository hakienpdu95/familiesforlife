@props([
    'articles', // Collection<PostArticleTranslation>, đã with('article.categories')
])

@if($articles->isNotEmpty())
<section class="mt-10 pt-8 border-t border-base-300">
    <h2 class="text-lg font-bold text-base-content mb-4">Bài viết liên quan</h2>
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
        @foreach($articles as $translation)
        <x-frontend.article-card :translation="$translation" size="sm" />
        @endforeach
    </div>
</section>
@endif
