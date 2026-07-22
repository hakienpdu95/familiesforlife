<?php

namespace Modules\Newsletter\Features\SubscriberManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class ListSubscribersForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 25,
        public readonly ?string $search = null,
        public readonly ?string $status = null,
        public readonly string $sortField = 'subscribed_at',
        public readonly string $sortDir = 'desc',
    ) {}
}
