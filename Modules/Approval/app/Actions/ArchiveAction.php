<?php

namespace Modules\Approval\Actions;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Approval\Actions\Concerns\LogsApprovalActions;
use Modules\Approval\Enums\ApprovalStatus;
use Modules\Approval\Models\ApprovalSubject;

class ArchiveAction
{
    use AsAction;
    use LogsApprovalActions;

    public function handle(Model $subject): ApprovalSubject
    {
        return $this->transition($subject, $subject->approvalSubject, ApprovalStatus::Archived, 'archive');
    }
}
