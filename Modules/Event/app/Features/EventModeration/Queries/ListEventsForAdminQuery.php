<?php

namespace Modules\Event\Features\EventModeration\Queries;

use App\Shared\Contracts\QueryInterface;

class ListEventsForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $status = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly string $sortField = 'created_at',
        public readonly string $sortDir = 'desc',
    ) {}
}
