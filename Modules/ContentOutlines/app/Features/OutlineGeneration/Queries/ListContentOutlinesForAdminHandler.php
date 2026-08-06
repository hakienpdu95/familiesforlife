<?php

namespace Modules\ContentOutlines\Features\OutlineGeneration\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\ContentOutlines\Models\ContentOutline;

class ListContentOutlinesForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['label', 'topic', 'target_keyword', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListContentOutlinesForAdminQuery $query */
        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'created_at';
        $sortDir = $query->sortDir === 'asc' ? 'asc' : 'desc';

        return ContentOutline::query()
            // PostArticle KHÔNG có cột title trực tiếp (đã chuyển sang PostArticleTranslation,
            // §xem ghi chú migration Post) — eager-load main_locale + translations, đọc qua
            // PostArticle::mainTranslation()->title ở Resource (ContentOutlineListResource).
            ->with(['category:id,uuid,name', 'createdBy:id,name', 'linkedArticle:id,main_locale', 'linkedArticle.translations'])
            ->when($query->postCategoryId, fn ($q) => $q->where('post_category_id', $query->postCategoryId))
            ->when($query->search, fn ($q) => $q->where(function ($q) use ($query) {
                $q->where('label', 'like', "%{$query->search}%")
                    ->orWhere('topic', 'like', "%{$query->search}%")
                    ->orWhere('target_keyword', 'like', "%{$query->search}%");
            }))
            ->orderBy($sortField, $sortDir)
            ->orderBy('id', 'desc')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
