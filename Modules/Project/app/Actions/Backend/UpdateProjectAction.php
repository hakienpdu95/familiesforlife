<?php

namespace Modules\Project\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Project\Data\Requests\UpdateProjectData;
use Modules\Project\Events\ProjectUpdated;
use Modules\Project\Models\Project;

class UpdateProjectAction
{
    use AsAction;

    public function handle(Project $project, UpdateProjectData $data): Project
    {
        $wasActive    = $project->status->value === 'active';
        $nowCompleted = $data->status->value === 'completed';

        $project->update([
            'branch_id'     => $data->branch_id,
            'department_id' => $data->department_id,
            'owner_id'      => $data->owner_id,
            'code'          => strtoupper(trim($data->code)),
            'name'          => $data->name,
            'description'   => $data->description,
            'category'      => $data->category,
            'status'        => $data->status->value,
            'priority'      => $data->priority->value,
            'start_date'    => $data->start_date,
            'end_date'      => $data->end_date,
            'budget'        => $data->budget,
            'currency'      => $data->currency ?? $project->currency,
            'updated_by'    => auth()->id(),
            'completed_at'  => ($nowCompleted && ! $project->completed_at) ? now() : $project->completed_at,
        ]);

        event(new ProjectUpdated($project));

        return $project;
    }
}
