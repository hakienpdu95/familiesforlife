<?php

namespace Modules\Leave\Observers;

use Modules\Leave\Models\LeaveRequest;

class LeaveRequestObserver
{
    public function updated(LeaveRequest $request): void
    {
        if ($request->wasChanged('status')) {
            $status = $request->status->value;

            if ($status === 'approved') {
                activity()
                    ->performedOn($request)
                    ->withProperties(['days_count' => $request->days_count])
                    ->log('leave_approved');
            }

            if ($status === 'rejected') {
                activity()
                    ->performedOn($request)
                    ->withProperties(['reason' => $request->rejected_reason])
                    ->log('leave_rejected');
            }

            if ($status === 'cancelled') {
                activity()
                    ->performedOn($request)
                    ->log('leave_cancelled');
            }
        }
    }
}
