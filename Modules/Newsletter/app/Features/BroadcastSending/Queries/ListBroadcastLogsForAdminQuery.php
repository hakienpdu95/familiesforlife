<?php

namespace Modules\Newsletter\Features\BroadcastSending\Queries;

use App\Shared\Contracts\QueryInterface;

class ListBroadcastLogsForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 25,
        public readonly ?string $search = null,
        public readonly string $sortField = 'created_at',
        public readonly string $sortDir = 'desc',
    ) {}
}
