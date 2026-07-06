<?php

namespace Modules\Leave\Queries;

use Illuminate\Database\Eloquent\Builder;
use Modules\Leave\Models\LeaveRequest;

class ListPendingApprovalHandler
{
    public function handle(ListPendingApprovalQuery $query): Builder
    {
        return LeaveRequest::with(['employee.department'])
            ->where('approved_by', $query->manager_employee_id)
            ->where('status', 'pending')
            ->orderBy('date_from', 'asc');
    }
}
