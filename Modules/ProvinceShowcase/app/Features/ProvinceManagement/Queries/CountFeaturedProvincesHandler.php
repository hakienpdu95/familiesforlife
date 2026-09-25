<?php

namespace Modules\ProvinceShowcase\Features\ProvinceManagement\Queries;

use App\Models\Province;
use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;

class CountFeaturedProvincesHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): int
    {
        return Province::query()->where('is_featured', true)->count();
    }
}
