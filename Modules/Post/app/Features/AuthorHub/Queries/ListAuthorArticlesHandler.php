<?php

namespace Modules\Post\Features\AuthorHub\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Post\Models\PostArticleTranslation;

/**
 * spec/Author_Contributor_Hub_Technical_Specification.md §7.3 — danh sách bài đã xuất bản của
 * 1 tác giả trên trang công khai `/tac-gia/{slug}`. KHÔNG hiển thị `view_count` (§0 "Số liệu
 * hiệu suất có hiển thị công khai không?") — chỉ dùng để lọc/sắp xếp, không trả field đó ra view.
 */
class ListAuthorArticlesHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListAuthorArticlesQuery $query */
        return PostArticleTranslation::published()
            ->where('locale', $query->locale)
            ->whereHas('article', fn ($q) => $q->where('created_by', $query->userId))
            ->with('article.categories')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
