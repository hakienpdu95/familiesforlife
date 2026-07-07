<?php

namespace Modules\Post\Features\ArticleAuthoring\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Post\Models\PostArticle;

class ListArticlesForAdminHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListArticlesForAdminQuery $query */
        $q = PostArticle::query()->with(['categories', 'tags', 'createdBy:id,name']);

        if ($query->search) {
            $q->where('title', 'like', '%' . $query->search . '%');
        }

        if ($query->categoryId) {
            $q->whereHas('categories', fn ($sub) => $sub->where('post_categories.id', $query->categoryId));
        }

        if ($query->format) {
            $q->where('format', $query->format);
        }

        if ($query->status) {
            $q->where('status', $query->status);
        }

        return $q->orderByDesc('created_at')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
