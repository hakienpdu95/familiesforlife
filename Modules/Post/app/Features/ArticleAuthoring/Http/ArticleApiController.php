<?php

namespace Modules\Post\Features\ArticleAuthoring\Http;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Post\Enums\ArticleFormat;
use Modules\Post\Enums\TranslationStatus;
use Modules\Post\Features\ArticleAuthoring\Http\Resources\ArticleListResource;
use Modules\Post\Features\ArticleAuthoring\Queries\ListArticlesForAdminHandler;
use Modules\Post\Features\ArticleAuthoring\Queries\ListArticlesForAdminQuery;
use Modules\Post\Models\PostArticle;
use Modules\Post\Models\PostArticleTranslation;

/**
 * JSON backend cho Tabulator ở dashboard/posts/articles — cùng pattern
 * Modules/Organization/app/Http/Controllers/Api/OrganizationApiController (tham chiếu theo
 * yêu cầu): remote pagination/sort/filter, trả {data, last_page, total}.
 */
class ArticleApiController extends Controller
{
    public function index(Request $request, ListArticlesForAdminHandler $handler): JsonResponse
    {
        $this->authorize('viewAny', PostArticle::class);

        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'size' => ['nullable', 'integer', 'min:5', 'max:100'],
            'search' => ['nullable', 'string', 'max:200'],
            'category_id' => ['nullable', 'integer'],
            'format' => ['nullable', 'string', 'in:'.implode(',', array_column(ArticleFormat::cases(), 'value'))],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_column(TranslationStatus::cases(), 'value'))],
        ]);

        // Tabulator (sortMode: 'remote') gửi mảng sort[{field,dir}] — cùng cách đọc
        // OrganizationApiController đang dùng.
        $sortRaw = $request->input('sort.0');
        $sortField = is_array($sortRaw) ? (string) ($sortRaw['field'] ?? 'created_at') : 'created_at';
        $sortDir = is_array($sortRaw) && ($sortRaw['dir'] ?? '') === 'asc' ? 'asc' : 'desc';

        $query = new ListArticlesForAdminQuery(
            page: max(1, (int) ($validated['page'] ?? 1)),
            perPage: min(100, max(5, (int) ($validated['size'] ?? 25))),
            search: $validated['search'] ?? null,
            categoryId: $validated['category_id'] ?? null,
            format: $validated['format'] ?? null,
            status: $validated['status'] ?? null,
            sortField: $sortField,
            sortDir: $sortDir,
        );

        $paginator = $handler->handle($query);

        return response()->json([
            'data' => ArticleListResource::collection($paginator->items()),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    /**
     * Autocomplete "chọn bài viết" (TomSelect remote, `data-ts-remote-url`) cho trang Markdown
     * Preview (Modules\Post\Features\MarkdownPreview) — gated `post_article.view` (KHÔNG dùng lại
     * `backend.api.breaking-news.articles.search`: endpoint đó gate theo quyền
     * `breaking_news.manage`, sai ngữ nghĩa cho 1 công cụ chỉ cần xem bài viết). Trả `id` =
     * translation_id (khác `searchArticles()` của BreakingNews trả article_id) vì Markdown
     * Preview cần đúng 1 bản dịch cụ thể, không phải cả PostArticle.
     */
    public function searchTranslations(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PostArticle::class);

        $search = (string) $request->string('q');

        $translations = PostArticleTranslation::published()
            ->where('locale', config('post.default_locale'))
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->orderByDesc('published_at')
            ->limit(20)
            ->get(['id', 'title', 'published_at']);

        return response()->json(
            $translations->map(fn (PostArticleTranslation $t) => [
                'id' => $t->id,
                'text' => $t->title,
                'published_at' => $t->published_at?->format('d/m/Y'),
            ])->values()
        );
    }
}
