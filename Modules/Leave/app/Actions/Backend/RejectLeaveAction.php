<?php

namespace Modules\Leave\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Leave\Models\LeaveRequest;

class RejectLeaveAction
{
    use AsAction;

    public function handle(LeaveRequest $request, string $reason): LeaveRequest
    {
        if (!$request->isPending()) {
            throw new \RuntimeException('Chỉ có thể từ chối đơn ở trạng thái chờ duyệt.');
        }

        return DB::transaction(function () use ($request, $reason) {
            $request->update([
                'status'          => 'rejected',
                'rejected_reason' => $reason,
            ]);

            $request->balance->decrement('pending_days', $request->days_count);

            return $request->fresh();
        });
    }
}
