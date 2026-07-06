<?php

namespace Modules\Leave\Queries;

class ListPendingApprovalQuery
{
    public function __construct(
        public readonly int $manager_employee_id,
    ) {}
}
