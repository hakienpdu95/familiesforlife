<?php

namespace Modules\Organization\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\Organization\Models\Organization;

class GetOrganizationQuery implements QueryInterface
{
    public function __construct(
        public readonly Organization $organization,
        public readonly int $membersLimit = 10,
    ) {}
}
