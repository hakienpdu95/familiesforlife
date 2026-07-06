<?php

namespace Modules\Project\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Project\Models\Project;

class ProjectUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Project $project) {}
}
