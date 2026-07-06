<?php

namespace Modules\JobTitle\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\JobTitle\Models\JobTitle;

class DestroyJobTitleAction
{
    use AsAction;

    public function handle(JobTitle $jobTitle): string
    {
        $name = $jobTitle->name;
        $jobTitle->delete();

        return $name;
    }
}
