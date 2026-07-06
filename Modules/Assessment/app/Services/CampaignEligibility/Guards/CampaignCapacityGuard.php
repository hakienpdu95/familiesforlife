<?php

namespace Modules\Assessment\Services\CampaignEligibility\Guards;

use App\Models\User;
use Modules\Assessment\Models\OpenAssessmentCampaign;
use Modules\Assessment\Services\CampaignEligibility\BlockResult;
use Modules\Assessment\Services\CampaignEligibility\Contracts\CampaignGuardContract;

class CampaignCapacityGuard implements CampaignGuardContract
{
    public function check(User $user, OpenAssessmentCampaign $campaign): ?BlockResult
    {
        if ($campaign->isFull()) {
            return new BlockResult('Campaign đã đủ số lượng người tham gia.');
        }

        return null;
    }
}
