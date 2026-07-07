<?php

namespace Modules\Product\Features\CatalogPicker\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Product\Models\Product;

class BatchGetProductsHandler implements QueryHandlerInterface
{
    /** Tra theo primary key — tốc độ không phụ thuộc kích thước catalog. */
    public function handle(QueryInterface $query): Collection
    {
        /** @var BatchGetProductsQuery $query */
        if (empty($query->ids)) {
            return new Collection();
        }

        return Product::query()->whereIn('id', $query->ids)->get();
    }
}
