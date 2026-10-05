<?php

namespace Modules\OcopSubject\Features\PublicReading\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Ocop\Models\OcopProduct;

class ListBrandProductsHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LengthAwarePaginator
    {
        /** @var ListBrandProductsQuery $query */
        $products = OcopProduct::published()
            ->with(['category', 'media'])
            ->where('ocop_subject_id', $query->ocopSubjectId)
            ->when($query->search, fn ($q, $search) => $q->where('name', 'like', '%'.addcslashes($search, '%_\\').'%'))
            ->when($query->stars, fn ($q, $stars) => $q->whereIn('star_rating', $stars));

        match ($query->sort) {
            'star' => $products->orderByDesc('star_rating')->orderByDesc('created_at'),
            'name' => $products->orderBy('name'),
            default => $products->orderByDesc('created_at'),
        };

        return $products
            ->paginate($query->perPage, ['*'], 'page', $query->page)
            ->withQueryString()
            ->fragment('products');
    }
}
