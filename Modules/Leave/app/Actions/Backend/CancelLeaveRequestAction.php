<?php

namespace Modules\Leave\Actions\Backend;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Leave\Models\LeaveRequest;

class CancelLeaveRequestAction
{
    use AsAction;

    public function handle(LeaveRequest $request): LeaveRequest
    {
        if (!$request->isPending()) {
            throw new \RuntimeException('Chỉ có thể hủy đơn ở trạng thái chờ duyệt.');
        }

        if ($request->date_from->isPast()) {
            throw new \RuntimeException('Không thể hủy đơn nghỉ đã qua ngày bắt đầu.');
        }

        return DB::transaction(function () use ($request) {
            $request->update(['status' => 'cancelled']);
            $request->balance->decrement('pending_days', $request->days_count);

            return $request->fresh();
        });
    }
}
