<?php

namespace Modules\Assessment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Assessment\Models\WorkforceCertification;

class CertificationExpired
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly WorkforceCertification $certification,
    ) {}
}
