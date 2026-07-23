<?php

namespace Modules\Post\Features\BreakingNews\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Post\Features\BreakingNews\Http\Resources\BreakingNewsListResource;
use Modules\Post\Features\BreakingNews\Queries\ListBreakingNewsForAdminHandler;
use Modules\Post\Features\BreakingNews\Queries\ListBreakingNewsForAdminQuery;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Models\PostBreakingNews;

/**
 * JSON backend cho Tabulator ở dashboard/breaking-news/items — cùng pattern BannerApiController.
 */
class BreakingNewsApiController extends Controller
{
    public function index(Request $request, ListBreakingNewsForAdminHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', PostBreakingNews::class);

        $validated = $request->validate([
            'page'      => ['nullable', 'integer', 'min:1'],
            'size'      => ['nullable', 'integer', 'min:5', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $sortRaw   = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'sort_order') : 'sort_order';
        $sortDir   = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'desc' ? 'desc' : 'asc';

        $query = new ListBreakingNewsForAdminQuery(
            isActive: array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : null,
            page:     max(1, (int) ($validated['page'] ?? 1)),
            perPage:  min(100, max(5, (int) ($validated['size'] ?? 20))),
            sortField: $sortField,
            sortDir:   $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data'      => BreakingNewsListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total'     => $paginator->total(),
        ]);
    }

    /**
     * Autocomplete "chọn bài viết" cho form tạo/sửa tin nóng (TomSelect remote — xem
     * resources/assets/js/pages/breaking-news-form.js, dùng createTsRemote() trực tiếp với
     * preload:true thay vì initAllTomSelects() dùng chung — xem docblock ở đó). Gán quyền theo
     * `breaking_news.manage`, KHÔNG dùng lại `backend.api.post.articles` (PostArticlePolicy::
     * viewAny() không cấp cho platform_ops — chỉ post_article.view/create/isPlatformContentEditor/
     * isPlatformContentHead — trong khi breaking_news.manage lại cấp cho platform_ops, gây 403
     * nếu tái dùng thẳng endpoint đó).
     *
     * Chỉ trả bài có bản dịch published đúng locale công khai — đúng điều kiện §5.1. Rỗng $q
     * (lần preload đầu tiên, chưa gõ gì) → trả 20 bài published gần đây nhất, để admin thấy
     * ngay danh sách thay vì phải gõ trước mới có gợi ý.
     *
     * Trả thêm category/published_at (ngoài id/text bắt buộc cho TomSelect) để hiển thị ngữ
     * cảnh trong dropdown — nhiều bài trùng/gần giống tên nhau rất dễ chọn nhầm nếu chỉ thấy
     * mỗi tiêu đề trần.
     */
    public function searchArticles(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PostBreakingNews::class);

        $search = (string) $request->string('q');

        $translations = PostArticleTranslation::published()
            ->where('locale', config('post.default_locale'))
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->with('article.categories')
            ->orderByDesc('published_at')
            ->limit(20)
            ->get(['id', 'article_id', 'title', 'published_at']);

        return response()->json(
            $translations->map(fn (PostArticleTranslation $t) => [
                'id'           => $t->article_id,
                'text'         => $t->title,
                'category'     => $t->article?->categories->first()?->name,
                'published_at' => $t->published_at?->format('d/m/Y'),
            ])->values()
        );
    }
}
