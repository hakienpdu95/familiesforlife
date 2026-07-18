<?php

namespace Modules\Post\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Modules\Post\Models\PostArticleTranslation;
use Modules\Post\Models\PostCategory;
use Throwable;

class ListPublishedArticlesHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListPublishedArticlesQuery $query */
        if ($query->search) {
            try {
                return $this->handleViaMeilisearch($query);
            } catch (Throwable $e) {
                Log::warning('Meilisearch search thất bại, fallback về LIKE query.', [
                    'search' => $query->search,
                    'error'  => $e->getMessage(),
                ]);
            }
        }

        return $this->handleViaDatabase($query);
    }

    /** Memoize trong vòng đời 1 request — Handler được resolve mới mỗi request (không phải singleton), nên property thường (không cần static) là đủ, không rò rỉ giữa các request. */
    private array $categorySlugCache = [];

    private function handleViaMeilisearch(ListPublishedArticlesQuery $query): LengthAwarePaginator
    {
        return PostArticleTranslation::search($query->search)
            ->where('locale', $query->locale)
            ->where('status', 'published')
            ->when($query->categoryId, fn ($s, $categoryId) => $s->where('category_slugs', $this->slugForCategoryId($categoryId)))
            ->query(fn ($q) => $q->with(['article.categories', 'article.createdBy']))
            ->paginate($query->perPage, 'page', $query->page)
            ->withQueryString();
    }

    /**
     * Đổi `int $categoryId` (hợp đồng cũ của `ListPublishedArticlesQuery`, không đổi) sang
     * `slug` mà Meilisearch filter được (`category_slugs` trong document là mảng slug, không
     * phải id — xem PostArticleTranslation::toSearchableArray()). Trong 1 request, Handler
     * chỉ gọi hàm này tối đa 1 lần (mỗi request gọi `handle()` đúng 1 lần), property
     * `$categorySlugCache` chủ yếu để an toàn nếu sau này `handle()` được gọi lặp trong cùng
     * vòng đời Handler (vd test), không phải tối ưu bắt buộc cho luồng hiện tại.
     *
     * KHÔNG dùng `findOrFail()`: `$categoryId` luôn đến từ `PostCategory` đã resolve qua route
     * model binding ở `PublicCategoryController::show()` (route `danh-muc/{category:slug}`) —
     * không có đường gọi nào truyền categoryId không tồn tại, nên throw ở đây không có ý nghĩa
     * xử lý gì thêm ngoài việc bị `catch (Throwable $e)` ở `handle()` nuốt rồi fallback DB —
     * cùng hành vi với để `?->slug` trả `null` như hiện tại, chỉ phức tạp hoá code không cần
     * thiết.
     *
     * KHÔNG cần cache toàn cục (Cache::remember qua request) thay cho property tạm này: đây là
     * 1 lượt `WHERE id = ?` theo khoá chính (`post_categories` có index PK) — KHÔNG phải N+1.
     * Cache toàn cục đổi 1 query rẻ lấy thêm 1 vấn đề thật (phải tự bust cache khi admin đổi
     * slug category) — không đáng đánh đổi cho 1 lookup theo PK.
     */
    private function slugForCategoryId(int $categoryId): ?string
    {
        return $this->categorySlugCache[$categoryId] ??= PostCategory::find($categoryId)?->slug;
    }

    /**
     * Y NGUYÊN toàn bộ logic cũ — bao gồm nhánh LIKE theo `search`, vì method này còn được
     * dùng làm fallback khi Meilisearch lỗi (§0 mục 5, §2, §11 "Fallback LIKE"), không chỉ cho
     * trường hợp browse không search. Khi `handle()` gọi thẳng method này với `$query->search`
     * rỗng (nhánh browse), điều kiện dưới tự bỏ qua — hành vi y hệt trước khi có Meilisearch.
     */
    private function handleViaDatabase(ListPublishedArticlesQuery $query): LengthAwarePaginator
    {
        $q = PostArticleTranslation::published()
            ->where('locale', $query->locale)
            ->with(['article.categories', 'article.createdBy'])
            ->whereHas('article');

        if ($query->categoryId) {
            $categoryId = $query->categoryId;
            $q->whereHas('article.categories', fn ($sub) => $sub->where('post_categories.id', $categoryId));
        }

        if ($query->search) {
            $search = $query->search;
            $q->where(fn ($sub) => $sub->where('title', 'like', "%{$search}%")
                ->orWhere('excerpt', 'like', "%{$search}%"));
        }

        if ($query->excludeArticleIds) {
            $q->whereNotIn('article_id', $query->excludeArticleIds);
        }

        // orderByDesc('id') phá thế hoà giữa các bài trùng published_at — cùng thứ tự
        // LoadMoreArticlesHandler dùng để nối tiếp bằng cursor (id, published_at) của dòng
        // cuối trang này, nên "Xem thêm" ở trang chủ không lặp/bỏ sót bài nào.
        return $q->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
