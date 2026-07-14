<?php

namespace Modules\Event\Features\EventCategoryManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class ListEventCategoriesForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $search = null,
    ) {}
}
