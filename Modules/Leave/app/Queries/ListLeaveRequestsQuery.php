<?php

namespace Modules\Leave\Queries;

class ListLeaveRequestsQuery
{
    public function __construct(
        public readonly ?int    $employee_id = null,
        public readonly ?string $status      = null,
        public readonly ?string $date_from   = null,
        public readonly ?string $date_to     = null,
        public readonly ?string $leave_type  = null,
    ) {}
}
