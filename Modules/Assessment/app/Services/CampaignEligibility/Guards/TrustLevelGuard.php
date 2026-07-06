<?php

namespace Modules\Assessment\Services\CampaignEligibility\Guards;

use App\Models\User;
use Modules\Assessment\Models\OpenAssessmentCampaign;
use Modules\Assessment\Services\CampaignEligibility\BlockResult;
use Modules\Assessment\Services\CampaignEligibility\Contracts\CampaignGuardContract;

class TrustLevelGuard implements CampaignGuardContract
{
    public function check(User $user, OpenAssessmentCampaign $campaign): ?BlockResult
    {
        if ($user->trust_level < $campaign->min_trust_level) {
            return new BlockResult(
                "Cần Trust Level {$campaign->min_trust_level} để tham gia. Hiện tại của bạn: Lv{$user->trust_level}.",
                route('passport.verify.index'),
                'Xác minh ngay',
            );
        }

        return null;
    }
}
