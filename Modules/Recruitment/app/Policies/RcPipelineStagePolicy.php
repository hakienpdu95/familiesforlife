<?php

namespace Modules\Recruitment\Policies;

use App\Models\User;
use Modules\Recruitment\Models\RcPipelineStage;

class RcPipelineStagePolicy
{
    public function manage(User $user): bool
    {
        return $user->hasPermissionTo('recruitment.manage');
    }
}
