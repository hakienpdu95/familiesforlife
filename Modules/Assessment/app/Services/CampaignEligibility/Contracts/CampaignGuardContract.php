<?php

namespace Modules\Assessment\Services\CampaignEligibility\Contracts;

use App\Models\User;
use Modules\Assessment\Models\OpenAssessmentCampaign;
use Modules\Assessment\Services\CampaignEligibility\BlockResult;

interface CampaignGuardContract
{
    /**
     * Return a BlockResult to halt the pipeline, or null to pass.
     */
    public function check(User $user, OpenAssessmentCampaign $campaign): ?BlockResult;
}
