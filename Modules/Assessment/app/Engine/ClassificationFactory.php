<?php

namespace Modules\Assessment\Engine;

use Modules\Assessment\Engine\Classification\NoneClassification;
use Modules\Assessment\Engine\Classification\PassFailClassification;
use Modules\Assessment\Engine\Classification\PersonaMatchClassification;
use Modules\Assessment\Engine\Classification\ScoreBandClassification;
use Modules\Assessment\Engine\Contracts\ClassificationStrategy;

class ClassificationFactory
{
    public function make(string $type): ClassificationStrategy
    {
        return match ($type) {
            'score_band'    => new ScoreBandClassification(),
            'pass_fail'     => new PassFailClassification(),
            'persona_match' => new PersonaMatchClassification(),
            default         => new NoneClassification(),
        };
    }
}
