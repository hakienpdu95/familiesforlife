<?php

namespace Modules\Post\Features\BreakingNews\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Post\Models\PostBreakingNews;

class ListBreakingNewsForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['sort_order', 'starts_at', 'ends_at', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListBreakingNewsForAdminQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'sort_order';
        $sortDir   = $query->sortDir === 'desc' ? 'desc' : 'asc';

        return PostBreakingNews::query()
            ->with(['article.translations' => fn ($t) => $t->where('locale', config('post.default_locale'))])
            ->when($query->isActive !== null, fn ($q) => $q->where('is_active', $query->isActive))
            ->orderBy($sortField, $sortDir)
            ->orderBy('id')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
