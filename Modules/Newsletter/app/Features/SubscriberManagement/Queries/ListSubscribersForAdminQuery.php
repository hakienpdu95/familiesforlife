<?php

namespace Modules\Newsletter\Features\SubscriberManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class ListSubscribersForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 25,
    ) {}
}
