<?php

namespace Modules\ContentBrief\Features\BriefManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class GetBriefVersionHistoryQuery implements QueryInterface
{
    public function __construct(public readonly int $contentBriefId) {}
}
