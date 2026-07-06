<?php

namespace Modules\Assessment\Services\CampaignEligibility\Contracts;

use App\Models\User;
use Modules\Assessment\Models\OpenAssessmentCampaign;
use Modules\Assessment\Services\CampaignEligibility\Advisory;

interface CampaignAdvisoryContract
{
    /**
     * Return an Advisory notice to surface to the user, or null if no notice applies.
     * Advisories never block joining — they are informational only.
     */
    public function advise(User $user, OpenAssessmentCampaign $campaign): ?Advisory;
}
