<?php

namespace Modules\Survey\Actions;

use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Assessment\Actions\RunAssessmentAction;
use Modules\Assessment\Events\AssessmentCompleted;
use Modules\Survey\Models\SurveyResponse;
use Modules\Survey\Services\WebhookDispatcher;

class CalculateSurveyScoreAction
{
    use AsAction;

    public function __construct(
        private readonly RunAssessmentAction $assessmentRunner,
        private readonly WebhookDispatcher   $webhooks,
    ) {}

    public function handle(int $responseId, bool $force = false): void
    {
        $response = SurveyResponse::with('survey')->find($responseId);

        if ($response === null) {
            Log::warning('scoring.action.response_not_found', ['response_id' => $responseId]);
            return;
        }

        if (!$response->getAssessmentCode()) {
            return;
        }

        // Delegate hoàn toàn sang Assessment module
        $this->assessmentRunner->handle($response, $force);

        // Webhook dispatch vẫn thuộc Survey responsibility
        // AssessmentCompleted event được lắng nghe bởi DispatchSurveyWebhookOnAssessmentCompleted
    }
}
