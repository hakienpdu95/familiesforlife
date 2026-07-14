@props([
    'articles', // LengthAwarePaginator<PostArticleTranslation>
    'locale',
])

<section class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
    @forelse($articles as $t)
    <x-frontend.article-card :translation="$t" :locale="$locale" size="sm" />
    @empty
    <p class="col-span-full text-center text-base-content/40 py-10">Chưa có bài viết nào.</p>
    @endforelse
</section>

@if($articles->hasPages())
<div class="pt-10 flex justify-center">
    {{ $articles->onEachSide(1)->links() }}
</div>
@endif
