<?php

namespace Modules\Menu\Features\MenuManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class GetMenuTreeForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $location = null,
        public readonly ?string $search = null,
    ) {}
}
