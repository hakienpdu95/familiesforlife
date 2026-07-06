<?php

namespace Modules\Deployment\Actions;

use App\Models\User;
use App\Shared\Tenancy\TenantContext;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Deployment\Data\StoreDeploymentIssueData;
use Modules\Deployment\Enums\IssueStatus;
use Modules\Deployment\Models\DeploymentIssue;
use Modules\Deployment\Notifications\IssueCreatedNotification;

class StoreDeploymentIssueAction
{
    use AsAction;

    public function handle(StoreDeploymentIssueData $data): DeploymentIssue
    {
        $issue = DeploymentIssue::create([
            'organization_id'      => TenantContext::getOrganizationId(),
            'deployment_target_id' => $data->deployment_target_id,
            'project_id'           => $data->project_id,
            'title'                => $data->title,
            'description'          => $data->description,
            'severity'             => $data->severity->value,
            'issue_type'           => $data->issue_type,
            'severity_detail'      => $data->severity_detail,
            'status'               => IssueStatus::Open->value,
            'owner_id'             => $data->owner_id,
            'created_by'           => auth()->id(),
        ]);

        // Notify owner if specified and different from creator
        if ($data->owner_id) {
            $owner = User::find($data->owner_id);
            if ($owner && $owner->id !== auth()->id()) {
                $owner->notify(new IssueCreatedNotification($issue));
            }
        }

        return $issue;
    }
}
