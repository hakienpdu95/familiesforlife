<?php

namespace Modules\Deployment\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Deployment\Data\UpdateDeploymentIssueData;
use Modules\Deployment\Models\DeploymentIssue;

class UpdateDeploymentIssueAction
{
    use AsAction;

    public function handle(DeploymentIssue $issue, UpdateDeploymentIssueData $data): void
    {
        $issue->update([
            'title'           => $data->title,
            'description'     => $data->description,
            'severity'        => $data->severity->value,
            'issue_type'      => $data->issue_type,
            'severity_detail' => $data->severity_detail,
            'status'          => $data->status->value,
            'owner_id'        => $data->owner_id,
        ]);
    }
}
