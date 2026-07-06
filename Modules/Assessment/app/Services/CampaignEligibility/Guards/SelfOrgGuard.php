<?php

namespace Modules\Assessment\Services\CampaignEligibility\Guards;

use App\Models\User;
use Modules\Assessment\Models\OpenAssessmentCampaign;
use Modules\Assessment\Services\CampaignEligibility\BlockResult;
use Modules\Assessment\Services\CampaignEligibility\Contracts\CampaignGuardContract;

class SelfOrgGuard implements CampaignGuardContract
{
    public function check(User $user, OpenAssessmentCampaign $campaign): ?BlockResult
    {
        if ($user->current_org_id !== null && $user->current_org_id === $campaign->organization_id) {
            return new BlockResult(
                'Bạn không thể tham gia campaign tuyển dụng của tổ chức mình. Liên hệ HR để được đánh giá qua luồng nội bộ.',
            );
        }

        return null;
    }
}
