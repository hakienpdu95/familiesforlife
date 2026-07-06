<?php

namespace Modules\Survey\Services;

use Modules\Survey\Jobs\SurveyWebhookJob;
use Modules\Survey\Models\SurveyWebhook;

class WebhookDispatcher
{
    public function dispatch(int $surveyId, string $event, array $payload): void
    {
        SurveyWebhook::where('survey_id', $surveyId)
            ->where('is_active', true)
            ->get()
            ->each(function (SurveyWebhook $webhook) use ($event, $payload) {
                if ($webhook->listensTo($event)) {
                    SurveyWebhookJob::dispatch($webhook->id, $event, $payload);
                }
            });
    }
}
