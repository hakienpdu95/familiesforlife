<?php

namespace Modules\Survey\Listeners;

use Modules\Assessment\Events\AssessmentCompleted;
use Modules\Survey\Models\SurveyResponse;
use Modules\Survey\Services\WebhookDispatcher;

class DispatchSurveyWebhookOnAssessmentCompleted
{
    public function __construct(
        private readonly WebhookDispatcher $webhooks,
    ) {}

    public function handle(AssessmentCompleted $event): void
    {
        // Chỉ xử lý khi subject là SurveyResponse
        if (!($event->subject instanceof SurveyResponse)) {
            return;
        }

        $response = $event->subject;
        $result   = $event->scoringResult;

        $this->webhooks->dispatch($response->survey_id, 'result.calculated', [
            'survey_id'      => $response->survey_id,
            'response_id'    => $response->id,
            'respondent_ref' => $response->respondent_ref,
            'overall_score'  => $result->overallScore !== null ? round($result->overallScore, 2) : null,
            'band_code'      => $result->classification->bandCode ?? null,
            'calculated_at'  => now()->toISOString(),
        ]);
    }
}
