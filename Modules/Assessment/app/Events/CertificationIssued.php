<?php

namespace Modules\Assessment\Events;

use Modules\Assessment\Models\WorkforceCertification;
use Modules\Assessment\Models\WorkforceProfile;

class CertificationIssued
{
    public function __construct(
        public readonly WorkforceCertification $certification,
        public readonly WorkforceProfile       $profile,
    ) {}
}
