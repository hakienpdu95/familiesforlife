<?php

namespace Modules\Leave\Actions\Backend;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Employee\Models\Employee;
use Modules\Leave\Data\Requests\StoreLeaveRequestData;
use Modules\Leave\Enums\LeaveType;
use Modules\Leave\Models\LeaveBalance;
use Modules\Leave\Models\LeaveRequest;

class StoreLeaveRequestAction
{
    use AsAction;

    public function handle(StoreLeaveRequestData $data): LeaveRequest
    {
        $employee  = Employee::findOrFail($data->employee_id);
        $leaveType = $data->leave_type;
        $year      = Carbon::parse($data->date_from)->year;
        $daysCount = $this->calculateWorkingDays($data->date_from, $data->date_to);

        $balance = LeaveBalance::where('employee_id', $data->employee_id)
            ->where('leave_type', $leaveType->value)
            ->where('year', $year)
            ->firstOrFail();

        // Validate remaining balance (except sick and unpaid)
        if (!in_array($leaveType->value, LeaveType::noBalanceCheck())) {
            $remaining = $balance->remaining_days;
            if ($remaining < $daysCount) {
                throw new \RuntimeException(
                    "Số ngày nghỉ còn lại không đủ. Còn lại: {$remaining} ngày, yêu cầu: {$daysCount} ngày."
                );
            }
        }

        return DB::transaction(function () use ($data, $balance, $daysCount, $employee) {
            $request = LeaveRequest::create([
                'uuid'         => Str::uuid(),
                'organization_id' => $data->organization_id,
                'employee_id'  => $data->employee_id,
                'balance_id'   => $balance->id,
                'leave_type'   => $data->leave_type->value,
                'date_from'    => $data->date_from,
                'date_to'      => $data->date_to,
                'days_count'   => $daysCount,
                'status'       => 'pending',
                'reason'       => $data->reason,
                'attachment_url' => $data->attachment_url,
                'created_by'   => auth()->id(),
            ]);

            $balance->increment('pending_days', $daysCount);

            return $request;
        });
    }

    /** Count working days (Mon–Sat; excludes Sunday only — holiday list TBD) */
    private function calculateWorkingDays(string $from, string $to): float
    {
        $start = Carbon::parse($from)->startOfDay();
        $end   = Carbon::parse($to)->startOfDay();
        $days  = 0;

        while ($start->lte($end)) {
            if (!$start->isSunday()) {
                $days++;
            }
            $start->addDay();
        }

        return (float) $days;
    }
}
