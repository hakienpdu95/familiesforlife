<?php

namespace Modules\JobPosting\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\JobPosting\Enums\JobPostStatus;
use Modules\JobPosting\Models\JpJobPost;

class JpJobPostStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly JpJobPost    $jobPost,
        public readonly JobPostStatus $oldStatus,
        public readonly JobPostStatus $newStatus,
    ) {}
}
