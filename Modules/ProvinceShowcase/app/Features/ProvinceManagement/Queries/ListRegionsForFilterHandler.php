<?php

namespace Modules\ProvinceShowcase\Features\ProvinceManagement\Queries;

use App\Models\Region;
use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Illuminate\Support\Collection;

class ListRegionsForFilterHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): Collection
    {
        return Region::query()->orderBy('name')->get(['id', 'name']);
    }
}
