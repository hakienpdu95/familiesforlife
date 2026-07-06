<?php

namespace Modules\Survey\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Survey\Models\SurveyToken;

class RevokeSurveyTokenAction
{
    use AsAction;

    public function handle(SurveyToken $token): void
    {
        $token->update(['is_active' => false]);

        ActivityLogger::warning('Survey', 'token_revoked', $token, ['survey_id' => $token->survey_id, 'name' => $token->name]);
    }
}
