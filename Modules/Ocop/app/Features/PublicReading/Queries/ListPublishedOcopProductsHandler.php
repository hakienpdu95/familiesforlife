<?php

namespace Modules\Ocop\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Modules\Ocop\Models\OcopProduct;
use Throwable;

class ListPublishedOcopProductsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListPublishedOcopProductsQuery $query */
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

    private function handleViaMeilisearch(ListPublishedOcopProductsQuery $query): LengthAwarePaginator
    {
        return OcopProduct::search($query->search)
            ->where('status', 'published')
            ->when($query->provinceCode, fn ($s, $provinceCode) => $s->where('province_code', $provinceCode))
            ->when($query->categoryIds, fn ($s, $ids) => $s->whereIn('category_id', $ids))
            ->when(! $query->categoryIds && $query->categoryId, fn ($s) => $s->where('category_id', $query->categoryId))
            ->when($query->wardCodes, fn ($s, $wardCodes) => $s->whereIn('ward_code', $wardCodes))
            ->when($query->stars, fn ($s, $stars) => $s->where('star_rating', $stars))
            ->query(fn ($q) => $q->with('category'))
            ->paginate($query->perPage, 'page', $query->page)
            ->withQueryString();
    }

    /**
     * Y NGUYÊN logic cũ — cũng dùng làm fallback khi Meilisearch lỗi (giống
     * ListPublishedArticlesHandler::handleViaDatabase()). Khi search rỗng (nhánh browse),
     * điều kiện `when($query->search, ...)` tự bỏ qua, hành vi y hệt trước khi có Meilisearch.
     */
    private function handleViaDatabase(ListPublishedOcopProductsQuery $query): LengthAwarePaginator
    {
        return OcopProduct::published()
            ->with('category')
            ->when($query->provinceCode, fn ($q) => $q->where('province_code', $query->provinceCode))
            ->when($query->categoryIds, fn ($q) => $q->whereIn('category_id', $query->categoryIds))
            ->when(! $query->categoryIds && $query->categoryId, fn ($q) => $q->where('category_id', $query->categoryId))
            ->when($query->search, fn ($q) => $q->where('name', 'like', "%{$query->search}%"))
            ->when($query->wardCodes, fn ($q) => $q->whereIn('ward_code', $query->wardCodes))
            ->when($query->stars, fn ($q) => $q->where('star_rating', $query->stars))
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
