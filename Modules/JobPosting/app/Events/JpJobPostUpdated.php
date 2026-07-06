<?php

namespace Modules\JobPosting\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\JobPosting\Models\JpJobPost;

class JpJobPostUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly JpJobPost $jobPost) {}
}
