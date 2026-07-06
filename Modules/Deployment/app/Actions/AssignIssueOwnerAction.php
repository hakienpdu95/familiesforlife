<?php

namespace Modules\Deployment\Actions;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Deployment\Data\AssignIssueOwnerData;
use Modules\Deployment\Models\DeploymentIssue;
use Modules\Deployment\Notifications\IssueCreatedNotification;

class AssignIssueOwnerAction
{
    use AsAction;

    public function handle(DeploymentIssue $issue, AssignIssueOwnerData $data): void
    {
        $previousOwnerId = $issue->owner_id;

        $issue->update(['owner_id' => $data->owner_id]);

        // Thông báo cho người phụ trách mới, nếu vừa gán (khác người đang gán hiện tại và khác chính người thao tác)
        if ($data->owner_id && $data->owner_id !== $previousOwnerId && $data->owner_id !== auth()->id()) {
            $owner = User::find($data->owner_id);
            $owner?->notify(new IssueCreatedNotification($issue));
        }
    }
}
