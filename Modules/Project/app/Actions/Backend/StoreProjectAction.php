<?php

namespace Modules\Project\Actions\Backend;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Project\Data\Requests\StoreProjectData;
use Modules\Project\Events\ProjectCreated;
use Modules\Project\Models\Project;

class StoreProjectAction
{
    use AsAction;

    public function handle(StoreProjectData $data): Project
    {
        $project = Project::create([
            'uuid'          => Str::uuid(),
            'organization_id' => $data->organization_id,
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
            'currency'      => $data->currency ?? 'VND',
            'created_by'    => auth()->id(),
            'updated_by'    => auth()->id(),
        ]);

        event(new ProjectCreated($project));

        return $project;
    }
}
