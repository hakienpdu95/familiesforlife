<?php

namespace Modules\Leave\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Employee\Models\Employee;
use Modules\Leave\Models\LeaveRequest;

class ApproveLeaveAction
{
    use AsAction;

    public function handle(LeaveRequest $request, Employee $approver): LeaveRequest
    {
        if (!$request->isPending()) {
            throw new \RuntimeException('Chỉ có thể duyệt đơn ở trạng thái chờ duyệt.');
        }

        return DB::transaction(function () use ($request, $approver) {
            $request->update([
                'status'      => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            $balance = $request->balance;
            $balance->decrement('pending_days', $request->days_count);
            $balance->increment('used_days', $request->days_count);

            return $request->fresh();
        });
    }
}
