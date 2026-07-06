<?php

namespace Modules\Deployment\Actions;

use App\Models\User;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Deployment\Enums\IssueStatus;
use Modules\Deployment\Models\DeploymentIssue;
use Modules\Deployment\Notifications\IssueResolvedNotification;

class ResolveDeploymentIssueAction
{
    use AsAction;

    public function handle(DeploymentIssue $issue): void
    {
        if (! $issue->isActive()) {
            throw new \RuntimeException('Issue đã được đóng hoặc giải quyết.');
        }

        $issue->update([
            'status'      => IssueStatus::Resolved->value,
            'resolved_at' => now(),
        ]);

        // Notify the issue creator
        if ($issue->created_by && $issue->created_by !== auth()->id()) {
            $creator = User::find($issue->created_by);
            $creator?->notify(new IssueResolvedNotification($issue));
        }
    }
}
