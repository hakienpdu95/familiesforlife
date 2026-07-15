<?php

namespace Modules\Banner\Features\BannerManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class ListBannersForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $placement = null,
        public readonly ?string $targetType = null, // 'global'|'category' (form/UI value — xem Handler)
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {}
}
