<?php

namespace Modules\OcopSubject\Features\OcopSubjectManagement\Queries;

use App\Shared\Contracts\QueryInterface;

class ListOcopSubjectsForPickerQuery implements QueryInterface
{
    public function __construct(
        public readonly ?int $includeId = null,
    ) {}
}
