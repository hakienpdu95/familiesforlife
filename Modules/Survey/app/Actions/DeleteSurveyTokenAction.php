<?php

namespace Modules\Survey\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Survey\Models\SurveyToken;

class DeleteSurveyTokenAction
{
    use AsAction;

    public function handle(SurveyToken $token): void
    {
        // Log trước khi xóa — model ID vẫn còn tồn tại lúc này
        ActivityLogger::warning('Survey', 'token_deleted', $token, ['survey_id' => $token->survey_id, 'name' => $token->name]);

        $token->delete();
    }
}
