<?php
namespace Modules\Marketplace\Policies;

use Modules\Marketplace\Models\MktApplicant;
use Modules\Marketplace\Models\MktApplication;

class MktApplicationPolicy
{
    public function view(MktApplicant $applicant, MktApplication $application): bool
    {
        return $application->applicant_id === $applicant->id;
    }

    public function withdraw(MktApplicant $applicant, MktApplication $application): bool
    {
        return $application->applicant_id === $applicant->id && $application->canWithdraw();
    }
}
