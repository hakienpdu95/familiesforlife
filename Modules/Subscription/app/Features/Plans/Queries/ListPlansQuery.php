<?php

namespace Modules\Subscription\Features\Plans\Queries;

use App\Shared\Contracts\QueryInterface;

class ListPlansQuery implements QueryInterface
{
    public function __construct(
        public readonly bool $activeOnly   = false,
        public readonly bool $publicOnly   = false,
        public readonly bool $withFeatures = false,
    ) {}
}
