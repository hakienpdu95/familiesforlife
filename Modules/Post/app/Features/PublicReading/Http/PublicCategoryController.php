<?php

namespace Modules\Post\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Event\Models\Event;
use Modules\Post\Features\PublicReading\Queries\ListPublishedArticlesHandler;
use Modules\Post\Features\PublicReading\Queries\ListPublishedArticlesQuery;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Models\PostCategory;

/**
 * Cổng thông tin công khai chỉ phục vụ 1 locale (config('post.default_locale')) — không còn
 * {locale} trong URL (trước đây /{locale}/bai-viet, đổi vì mọi nội dung thực tế chỉ có tiếng
 * Việt và ghép "/en/bai-viet" không hợp lý). Bản dịch locale khác (nếu biên tập viên có tạo)
 * vẫn tồn tại trong DB cho quản trị nội bộ, chỉ không có route công khai nào trỏ tới.
 */
class PublicCategoryController extends Controller
{
    public function index(Request $request, ListPublishedArticlesHandler $handler): View
    {
        $locale = config('post.default_locale');
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

        // spec/Event_Management_Technical_Specification.md §12 — thay chỗ dùng
        // post_articles.is_sponsored làm placeholder "Sự Kiện Cho Bé" bằng dữ liệu Event thật,
        // giờ Modules\Event đã có domain "sự kiện" thật (Phase 3).
        $upcomingEvents = $search ? collect() : Event::published()
            ->upcoming()
            ->with('category')
            ->orderBy('start_date')
            ->take(5)
            ->get();

        $categories = PostCategory::navTree();

        return view('post::public.home', compact('articles', 'categories', 'locale', 'featured', 'upcomingEvents', 'search'));
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

    public function show(Request $request, PostCategory $category, ListPublishedArticlesHandler $handler): View
    {
        $locale = config('post.default_locale');
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

        // Không còn truyền 'categories' — Phase 3 chuyển nav sang MenuItem::tree() qua View
        // Composer (MenuServiceProvider), public.category.blade.php không tự dùng $categories
        // cho việc gì khác (xem spec/Menu_Navigation_Technical_Specification.md §8 Phase 4).
        return view('post::public.category', compact('articles', 'category', 'breadcrumb', 'locale', 'search'));
    }
}
