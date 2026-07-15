<?php

namespace Modules\Post\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Post\Models\PostArticleTranslation;

class ListPublishedArticlesHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListPublishedArticlesQuery $query */
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
