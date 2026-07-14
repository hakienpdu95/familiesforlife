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
            ->with(['article.categories'])
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

        if ($query->excludeArticleId) {
            $q->where('article_id', '!=', $query->excludeArticleId);
        }

        return $q->orderByDesc('published_at')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
