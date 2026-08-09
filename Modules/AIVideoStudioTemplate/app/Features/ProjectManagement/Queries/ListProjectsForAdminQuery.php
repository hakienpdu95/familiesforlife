<?php

namespace Modules\AIVideoStudioTemplate\Features\ProjectManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class ListProjectsForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $status = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
