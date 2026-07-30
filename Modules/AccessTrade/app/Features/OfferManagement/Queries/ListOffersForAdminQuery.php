<?php

namespace Modules\AccessTrade\Features\OfferManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class ListOffersForAdminQuery implements QueryInterface
{
    public function __construct(
        public readonly ?string $merchant = null,
        public readonly ?string $domain = null,
        public readonly ?bool $hasCoupon = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
        public readonly string $sortField = 'end_time',
        public readonly string $sortDir = 'asc',
    ) {}
}
