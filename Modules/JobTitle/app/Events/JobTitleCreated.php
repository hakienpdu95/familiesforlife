<?php

namespace Modules\JobTitle\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\JobTitle\Models\JobTitle;

class JobTitleCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly JobTitle $jobTitle,
    ) {}
}
