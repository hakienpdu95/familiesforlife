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
            ->when($query->categoryId, fn ($s, $categoryId) => $s->where('category_id', $categoryId))
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
            ->when($query->categoryId, fn ($q) => $q->where('category_id', $query->categoryId))
            ->when($query->search, fn ($q) => $q->where('name', 'like', "%{$query->search}%"))
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString();
    }
}
