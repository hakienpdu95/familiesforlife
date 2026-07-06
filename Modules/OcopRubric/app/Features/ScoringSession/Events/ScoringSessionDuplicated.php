<?php

namespace Modules\OcopRubric\Features\ScoringSession\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\OcopRubric\Models\OcopScoringSession;

class ScoringSessionDuplicated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly OcopScoringSession $source,
        public readonly OcopScoringSession $newSession,
        public readonly bool $exactSameVersion,
        public readonly bool $sameGroup,
    ) {}
}
