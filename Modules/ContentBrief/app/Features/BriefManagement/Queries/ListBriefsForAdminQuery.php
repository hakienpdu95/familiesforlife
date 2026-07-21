<?php

namespace Modules\ContentBrief\Features\BriefManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class ListBriefsForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $status = null,
    ) {}
}
