<?php

namespace Modules\Assessment\Services\CampaignEligibility\Guards;

use App\Models\User;
use Modules\Assessment\Models\OpenAssessmentCampaign;
use Modules\Assessment\Services\CampaignEligibility\BlockResult;
use Modules\Assessment\Services\CampaignEligibility\Contracts\CampaignGuardContract;

class SuspendedAccountGuard implements CampaignGuardContract
{
    public function check(User $user, OpenAssessmentCampaign $campaign): ?BlockResult
    {
        if ($user->isSuspended()) {
            return new BlockResult('Tài khoản của bạn đã bị tạm khóa. Liên hệ bộ phận hỗ trợ để được giải quyết.');
        }

        return null;
    }
}
