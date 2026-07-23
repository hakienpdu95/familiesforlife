@props([
    'articles', // LengthAwarePaginator<PostArticleTranslation> — vẫn dùng để phân trang bên dưới
    'items' => null, // Collection<PostArticleTranslation>|null — override danh sách hiển thị (vd loại bỏ bài đã dùng làm "lead" size=lg phía trên); mặc định null = dùng chính $articles
])

@php($items = $items ?? $articles)

<section class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
    @forelse($items as $t)
    <x-frontend.article-card :translation="$t" size="sm" />
    @empty
    <p class="col-span-full text-center text-base-content/40 py-10">Chưa có bài viết nào.</p>
    @endforelse
</section>

@if($articles->hasPages())
<div class="pt-10 flex justify-center">
    {{ $articles->onEachSide(1)->links() }}
</div>
@endif
