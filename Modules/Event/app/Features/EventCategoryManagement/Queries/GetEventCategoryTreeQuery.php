<?php

namespace Modules\Event\Features\EventCategoryManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class GetEventCategoryTreeQuery implements QueryInterface
{
    public function __construct(
        public readonly bool $activeOnly = false,
    ) {}
}
