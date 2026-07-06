<?php

namespace Modules\Leave\Queries;

use Illuminate\Database\Eloquent\Builder;
use Modules\Leave\Models\LeaveRequest;

class ListLeaveRequestsHandler
{
    public function handle(ListLeaveRequestsQuery $query): Builder
    {
        $builder = LeaveRequest::with(['employee', 'approvedBy'])
            ->orderBy('date_from', 'desc');

        if ($query->employee_id) {
            $builder->where('employee_id', $query->employee_id);
        }

        if ($query->status) {
            $builder->where('status', $query->status);
        }

        if ($query->leave_type) {
            $builder->where('leave_type', $query->leave_type);
        }

        if ($query->date_from) {
            $builder->where('date_from', '>=', $query->date_from);
        }

        if ($query->date_to) {
            $builder->where('date_to', '<=', $query->date_to);
        }

        return $builder;
    }
}
