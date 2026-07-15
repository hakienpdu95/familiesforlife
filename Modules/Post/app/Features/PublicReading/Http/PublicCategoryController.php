<?php

namespace Modules\Post\Features\PublicReading\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Event\Models\Event;
use Modules\Post\Features\PublicReading\Queries\ListPublishedArticlesHandler;
use Modules\Post\Features\PublicReading\Queries\ListPublishedArticlesQuery;
use Modules\Post\Features\PublicReading\Queries\LoadMoreArticlesHandler;
use Modules\Post\Features\PublicReading\Queries\LoadMoreArticlesQuery;
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

        // Hero 5 tin (x-frontend.hero, cấu trúc theo spec/hero.html) — 1 bài ghim (is_featured)
        // làm "col-middle" (to, giữa) + 4 bài mới nhất kế tiếp làm "col-left"/"col-right" (2 mỗi
        // bên). Không tìm kiếm thì loại cả 5 khỏi lưới bên dưới để tránh trùng lặp.
        $featured = $search ? null : $this->featuredArticle($locale);
        $heroSide = ($featured && ! $search) ? $this->heroSideArticles($locale, $featured->article_id) : collect();
        $page     = max(1, $request->integer('page', 1));

        // Trang chủ (không tìm kiếm, trang 1) dựng bố cục "tạp chí": 6 bài đầu vào feature
        // chunks (x-frontend.section-feature) + 8 bài vào khối lưới "Thêm Bài Viết" — khối lưới
        // này dùng "Xem thêm" (Alpine, xem loadMore()) thay vì Previous/Next nên cần đủ 14 bài
        // ngay từ lần tải đầu. Trang sau/tìm kiếm giữ nguyên phân trang cổ điển (perPage mặc định).
        $perPage = (! $search && $page === 1) ? 14 : 12;

        $heroArticleIds = $featured
            ? $heroSide->pluck('article_id')->push($featured->article_id)->all()
            : [];

        $articles = $handler->handle(new ListPublishedArticlesQuery(
            locale: $locale,
            page: $page,
            perPage: $perPage,
            search: $search,
            excludeArticleIds: $heroArticleIds,
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

        return view('post::public.home', compact('articles', 'categories', 'locale', 'featured', 'heroSide', 'upcomingEvents', 'search'));
    }

    /**
     * "Xem thêm bài viết" — khối lưới cuối trang chủ (Modules/Post/resources/views/public/
     * home.blade.php), gọi qua Alpine (resources/js/frontend.js `loadMoreArticles`). Trả JSON
     * (html đã render + has_more) thay vì điều hướng trang.
     *
     * Cursor (after_published_at/after_id) thay offset — xem LoadMoreArticlesQuery. exclude
     * chỉ gồm bài hero + feature chunks (cố định, không phình theo số lần bấm).
     *
     * `loaded` (tổng số bài phía client đã hiển thị, gồm hero+feature+lưới) cho phép chặn
     * SỚM ở đây khi đã chạm LOAD_MORE_MAX_TOTAL — trả về ngay, KHÔNG chạm DB — vừa là giới
     * hạn cứng (phòng client bị sửa/bypass), vừa đỡ 1 query không cần thiết.
     */
    public function loadMore(Request $request, LoadMoreArticlesHandler $handler): JsonResponse
    {
        $maxTotal  = (int) config('post.load_more_max_total');
        $loaded    = max(0, $request->integer('loaded', 0));
        $remaining = $maxTotal - $loaded;

        if ($remaining <= 0) {
            return response()->json(['html' => '', 'has_more' => false]);
        }

        $excludeArticleIds = collect(explode(',', (string) $request->string('exclude')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->values()
            ->all();

        $result = $handler->handle(new LoadMoreArticlesQuery(
            locale: config('post.default_locale'),
            afterPublishedAt: $request->string('after_published_at')->value() ?: null,
            afterId: $request->filled('after_id') ? $request->integer('after_id') : null,
            excludeArticleIds: $excludeArticleIds,
            // min(8, ...): giới hạn cứng phía server — client không thể yêu cầu 1 lần tải hơn
            // 8 bài dù có sửa request thủ công.
            limit: min(8, $remaining),
        ));

        $articles = $result['articles'];
        $last     = $articles->last();

        return response()->json([
            'html'        => view('post::public.partials.article-grid-items', ['articles' => $articles])->render(),
            'count'       => $articles->count(),
            // Cursor của dòng cuối vừa trả — client dùng cho lần "Xem thêm" kế tiếp, không tự
            // suy ra được từ HTML nên phải trả riêng.
            'next_cursor' => $last ? ['published_at' => $last->published_at->toISOString(), 'id' => $last->id] : null,
            'has_more'    => $result['has_more'] && ($loaded + $articles->count()) < $maxTotal,
        ]);
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

    /** 4 bài mới nhất kế tiếp (sau bài ghim) cho col-left/col-right của x-frontend.hero. */
    private function heroSideArticles(string $locale, int $excludeArticleId): \Illuminate\Support\Collection
    {
        return PostArticleTranslation::published()
            ->where('locale', $locale)
            ->where('article_id', '!=', $excludeArticleId)
            ->with('article.categories')
            ->whereHas('article')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->take(4)
            ->get();
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
