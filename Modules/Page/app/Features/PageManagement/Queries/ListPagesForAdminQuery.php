<?php

namespace Modules\Page\Features\PageManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class ListPagesForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $status = null,
    ) {}
}
