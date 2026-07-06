<?php

namespace Modules\Subscription\Features\Payment\Queries;

use App\Shared\Contracts\QueryInterface;

class ListInvoicesQuery implements QueryInterface
{
    public function __construct(
        public readonly ?int    $organizationId = null,
        public readonly ?int    $status         = null,
        public readonly ?string $search         = null,
        public readonly int     $perPage        = 25,
        public readonly bool    $forAdmin        = false,
    ) {}
}
