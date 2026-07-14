<?php

namespace Modules\Post\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Post\Features\PublicReading\Queries\ListPublishedArticlesHandler;
use Modules\Post\Features\PublicReading\Queries\ListPublishedArticlesQuery;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Models\PostCategory;

class PublicCategoryController extends Controller
{
    public function index(Request $request, string $locale, ListPublishedArticlesHandler $handler): View
    {
        abort_unless(array_key_exists($locale, config('post.locales')), 404);

        $search = $request->string('q')->trim()->value() ?: null;

        // Bài viết ghim làm hero (x-frontend.hero) — không tìm kiếm thì loại khỏi lưới bên
        // dưới để tránh hiển thị trùng lặp cùng 1 bài ở cả hero lẫn grid.
        $featured = $search ? null : $this->featuredArticle($locale);

        $articles = $handler->handle(new ListPublishedArticlesQuery(
            locale: $locale,
            page: max(1, $request->integer('page', 1)),
            search: $search,
            excludeArticleId: $featured?->article_id,
        ));

        // Bài viết tài trợ (is_sponsored) — dùng cho block "Đối Tác Đồng Hành"
        // (x-frontend.sponsor-spotlight), thay thế "Sự Kiện Cho Bé" của bản mẫu tĩnh vì
        // module Post chưa có domain "sự kiện" — đây là dữ liệu thật gần nhất tương đương.
        $sponsored = $search ? collect() : PostArticleTranslation::published()
            ->where('locale', $locale)
            ->whereHas('article', fn ($q) => $q->where('is_sponsored', true))
            ->with('article')
            ->orderByDesc('published_at')
            ->take(5)
            ->get();

        $categories = PostCategory::navTree();

        return view('post::public.home', compact('articles', 'categories', 'locale', 'featured', 'sponsored', 'search'));
    }

    private function featuredArticle(string $locale): ?PostArticleTranslation
    {
        return PostArticleTranslation::published()
            ->where('locale', $locale)
            ->whereHas('article', fn ($q) => $q->where('is_featured', true))
            ->with('article.categories')
            ->orderByDesc('published_at')
            ->first();
    }

    public function show(Request $request, string $locale, PostCategory $category, ListPublishedArticlesHandler $handler): View
    {
        abort_unless(array_key_exists($locale, config('post.locales')), 404);

        $search = $request->string('q')->trim()->value() ?: null;

        $articles = $handler->handle(new ListPublishedArticlesQuery(
            locale: $locale,
            page: max(1, $request->integer('page', 1)),
            categoryId: $category->id,
            search: $search,
        ));

        $breadcrumb = collect();
        $node = $category;
        while ($node) {
            $breadcrumb->prepend($node);
            $node = $node->parent;
        }

        $categories = PostCategory::navTree();

        return view('post::public.category', compact('articles', 'category', 'breadcrumb', 'categories', 'locale', 'search'));
    }
}
