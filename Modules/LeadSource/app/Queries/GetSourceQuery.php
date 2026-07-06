<?php

namespace Modules\LeadSource\Queries;

use App\Shared\Contracts\QueryInterface;
use Modules\LeadSource\Models\LeadSource;

class GetSourceQuery implements QueryInterface
{
    public function __construct(
        public readonly LeadSource $source,
    ) {}
}
