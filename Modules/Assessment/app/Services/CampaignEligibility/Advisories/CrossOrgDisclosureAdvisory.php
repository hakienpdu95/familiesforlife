<?php

namespace Modules\Assessment\Services\CampaignEligibility\Advisories;

use App\Models\User;
use Modules\Assessment\Models\OpenAssessmentCampaign;
use Modules\Assessment\Services\CampaignEligibility\Advisory;
use Modules\Assessment\Services\CampaignEligibility\Contracts\CampaignAdvisoryContract;

class CrossOrgDisclosureAdvisory implements CampaignAdvisoryContract
{
    public function advise(User $user, OpenAssessmentCampaign $campaign): ?Advisory
    {
        if (!$user->isOrgMember()) {
            return null;
        }

        if ($user->current_org_id === $campaign->organization_id) {
            return null;
        }

        return new Advisory(
            severity:    'info',
            message:     'Bạn đang tham gia với tư cách cá nhân. Kết quả sẽ được lưu vào Competency Passport của bạn và không liên quan đến hồ sơ tại tổ chức hiện tại.',
            actionUrl:   route('passport.index'),
            actionLabel: 'Xem Passport',
        );
    }
}
