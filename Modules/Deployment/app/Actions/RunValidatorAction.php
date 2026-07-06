<?php

namespace Modules\Deployment\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\Deployment\Services\DataQualityScoreService;
use Modules\Deployment\Services\ValidatorRuleEngine;

class RunValidatorAction
{
    use AsAction;

    public function handle(DeploymentTarget $target): array
    {
        $target->load(['checklistItems', 'progressLogs', 'issues']);

        $engine   = new ValidatorRuleEngine;
        $created  = $engine->run($target);
        $created  = array_filter($created, fn($i) => $i->exists);

        // Refresh issues after potential inserts
        $target->unsetRelation('issues');
        $score = (new DataQualityScoreService)->score($target);

        return [
            'new_issues' => count($created),
            'score'      => $score,
        ];
    }
}
