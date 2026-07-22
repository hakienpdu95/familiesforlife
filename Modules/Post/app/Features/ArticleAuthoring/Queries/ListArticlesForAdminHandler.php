<?php

namespace Modules\Post\Features\ArticleAuthoring\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Post\Models\PostArticle;

class ListArticlesForAdminHandler implements QueryHandlerInterface
{
    private const SORTABLE = ['title', 'format', 'created_at'];

    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListArticlesForAdminQuery $query */
        $q = PostArticle::query()
            ->select('post_articles.*')
            ->with(['categories', 'tags', 'createdBy:id,name'])
            ->withMainTranslation();

        if ($query->search) {
            $term = $query->search;
            $q->whereHas('translations', fn ($sub) => $sub->where('title', 'like', '%' . $term . '%'));
        }

        if ($query->categoryId) {
            $q->whereHas('categories', fn ($sub) => $sub->where('post_categories.id', $query->categoryId));
        }

        if ($query->format) {
            $q->where('format', $query->format);
        }

        if ($query->status) {
            $status = $query->status;
            $q->whereHas('translations', fn ($sub) => $sub->where('status', $status));
        }

        $sortField = in_array($query->sortField, self::SORTABLE, true) ? $query->sortField : 'created_at';
        $sortDir   = $query->sortDir === 'asc' ? 'asc' : 'desc';

        // 'title' sống ở post_article_translations (bản dịch main_locale), không phải
        // post_articles — cùng kỹ thuật leftJoin+orderBy đã dùng cho 'province_name' ở
        // ListOrganizationsHandler (Modules/Organization) khi cột sort không nằm trên bảng chính.
        if ($sortField === 'title') {
            $q->leftJoin('post_article_translations as sort_trans', function ($join): void {
                $join->on('sort_trans.article_id', '=', 'post_articles.id')
                     ->on('sort_trans.locale', '=', 'post_articles.main_locale');
            })->orderBy('sort_trans.title', $sortDir);
        } else {
            $q->orderBy('post_articles.' . $sortField, $sortDir);
        }

        // Secondary sort ổn định trang khi nhiều bản ghi cùng giá trị sort chính.
        $q->orderBy('post_articles.id', $sortDir);

        return $q->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
