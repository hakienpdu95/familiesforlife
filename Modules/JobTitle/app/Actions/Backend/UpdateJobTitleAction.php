<?php

namespace Modules\JobTitle\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\JobTitle\Data\Requests\UpdateJobTitleData;
use Modules\JobTitle\Events\JobTitleUpdated;
use Modules\JobTitle\Models\JobTitle;

class UpdateJobTitleAction
{
    use AsAction;

    public function handle(JobTitle $jobTitle, UpdateJobTitleData $data): JobTitle
    {
        $jobTitle->update([
            'name'        => $data->name,
            'code'        => strtoupper(trim($data->code)),
            'category'    => $data->category->value,
            'level'       => $data->level,
            'description' => $data->description,
            'is_active'   => $data->is_active,
        ]);

        event(new JobTitleUpdated($jobTitle));

        return $jobTitle;
    }
}
