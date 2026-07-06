<?php

namespace Modules\JobTitle\Actions\Backend;

use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\JobTitle\Data\Requests\StoreJobTitleData;
use Modules\JobTitle\Events\JobTitleCreated;
use Modules\JobTitle\Models\JobTitle;

class StoreJobTitleAction
{
    use AsAction;

    public function handle(StoreJobTitleData $data): JobTitle
    {
        $jobTitle = JobTitle::create([
            'uuid'            => Str::uuid(),
            'organization_id' => $data->organization_id,
            'name'            => $data->name,
            'code'        => strtoupper(trim($data->code)),
            'category'    => $data->category->value,
            'level'       => $data->level,
            'description' => $data->description,
            'is_active'   => $data->is_active,
            'is_system'   => false,
            'is_locked'   => false,
        ]);

        event(new JobTitleCreated($jobTitle));

        return $jobTitle;
    }
}
