<?php

namespace Modules\LeadSource\Queries;

use App\Shared\Contracts\QueryHandlerInterface;
use App\Shared\Contracts\QueryInterface;
use Modules\LeadSource\Models\LeadSource;

class GetSourceHandler implements QueryHandlerInterface
{
    public function handle(QueryInterface $query): LeadSource
    {
        /** @var GetSourceQuery $query */
        return $query->source->loadCount('leads');
    }
}
