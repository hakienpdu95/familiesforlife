@props([
    'lead',          // PostArticleTranslation — bài chính (card lớn)
    'side' => null,  // Collection<PostArticleTranslation>|null — tối đa 2 bài phụ
    'locale',
])

<section class="py-10 border-t border-base-300 first:border-t-0">
    <x-frontend.article-card :translation="$lead" :locale="$locale" size="lg" />

    @if(($side ?? collect())->isNotEmpty())
    <div class="mt-8 grid sm:grid-cols-2 gap-6">
        @foreach($side as $t)
        <x-frontend.article-card :translation="$t" :locale="$locale" size="md" />
        @endforeach
    </div>
    @endif
</section>
