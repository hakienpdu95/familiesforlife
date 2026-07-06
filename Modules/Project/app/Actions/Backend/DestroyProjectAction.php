<?php

namespace Modules\Project\Actions\Backend;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Project\Models\Project;

class DestroyProjectAction
{
    use AsAction;

    public function handle(Project $project): string
    {
        $name = $project->name;
        $project->delete();
        return $name;
    }
}
