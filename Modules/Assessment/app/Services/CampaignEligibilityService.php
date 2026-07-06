<?php

namespace Modules\Assessment\Services;

use App\Models\User;
use Modules\Assessment\Models\OpenAssessmentCampaign;
use Modules\Assessment\Services\CampaignEligibility\Contracts\CampaignAdvisoryContract;
use Modules\Assessment\Services\CampaignEligibility\Contracts\CampaignGuardContract;
use Modules\Assessment\Services\CampaignEligibility\EligibilityReport;

/**
 * Runs the campaign join eligibility pipeline.
 *
 * Guards short-circuit on first block. Advisories are always collected.
 * Register guards and advisories via the service container (AssessmentServiceProvider).
 */
class CampaignEligibilityService
{
    /**
     * @param CampaignGuardContract[]    $guards
     * @param CampaignAdvisoryContract[] $advisories
     */
    public function __construct(
        private readonly array $guards,
        private readonly array $advisories,
    ) {}

    public function check(User $user, OpenAssessmentCampaign $campaign): EligibilityReport
    {
        foreach ($this->guards as $guard) {
            $block = $guard->check($user, $campaign);
            if ($block !== null) {
                return EligibilityReport::blocked($block);
            }
        }

        $advisories = array_values(array_filter(
            array_map(fn($advisory) => $advisory->advise($user, $campaign), $this->advisories),
        ));

        return EligibilityReport::allowed($advisories);
    }
}
