<?php

namespace Modules\Post\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Collection;
use Modules\Post\Models\PostArticleTranslation;

class LoadMoreArticlesHandler implements QueryHandlerInterface
{
    /** @return array{articles: Collection<int, PostArticleTranslation>, has_more: bool} */
    public function handle(QueryInterface $query): array
    {
        /** @var LoadMoreArticlesQuery $query */
        $q = PostArticleTranslation::published()
            ->where('locale', $query->locale)
            ->with(['article.categories', 'article.createdBy'])
            ->whereHas('article');

        if ($query->excludeArticleIds) {
            $q->whereNotIn('article_id', $query->excludeArticleIds);
        }

        if ($query->categoryId) {
            $categoryId = $query->categoryId;
            $q->whereHas('article.categories', fn ($sub) => $sub->where('post_categories.id', $categoryId));
        }

        // Keyset/cursor — cùng thứ tự orderByDesc(published_at)->orderByDesc(id) bên dưới,
        // "id" phá thế hoà khi nhiều bài trùng published_at (dữ liệu demo publish hàng loạt
        // cùng lúc — không có nó, LIMIT có thể lặp/bỏ sót dòng giữa 2 lần gọi kế tiếp).
        if ($query->afterPublishedAt !== null && $query->afterId !== null) {
            $q->where(function ($sub) use ($query) {
                $sub->where('published_at', '<', $query->afterPublishedAt)
                    ->orWhere(function ($tie) use ($query) {
                        $tie->where('published_at', '=', $query->afterPublishedAt)
                            ->where('id', '<', $query->afterId);
                    });
            });
        }

        // Lấy dư 1 dòng để biết còn nữa hay không mà không cần thêm 1 query count() riêng.
        $rows = $q->orderByDesc('published_at')
            ->orderByDesc('id')
            ->take($query->limit + 1)
            ->get();

        return [
            'articles' => $rows->take($query->limit)->values(),
            'has_more' => $rows->count() > $query->limit,
        ];
    }
}
