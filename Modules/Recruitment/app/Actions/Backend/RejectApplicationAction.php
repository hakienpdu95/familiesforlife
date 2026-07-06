<?php

namespace Modules\Recruitment\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Recruitment\Enums\ApplicationStatus;
use Modules\Recruitment\Models\RcApplication;
use Modules\Recruitment\Models\RcApplicationStageLog;

class RejectApplicationAction
{
    use AsAction;

    public function handle(RcApplication $application, ?string $reason = null): RcApplication
    {
        RcApplicationStageLog::create([
            'application_id' => $application->id,
            'stage_id'       => $application->current_stage_id,
            'result'         => 'failed',
            'note'           => $reason ?? 'Từ chối ứng viên',
            'actioned_by'    => auth()->id(),
        ]);

        $application->update([
            'status'           => ApplicationStatus::Rejected,
            'rejection_reason' => $reason,
        ]);

        return $application;
    }
}
