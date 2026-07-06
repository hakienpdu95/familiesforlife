<?php

namespace Modules\Deployment\Actions;

use App\Foundation\VerticalRegistry;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyResponse;

class CloneSurveyAction
{
    use AsAction;

    /**
     * Create an independent SurveyResponse stub for a DeploymentTarget.
     *
     * Uses the shared `readiness_v1` template (no structural copy needed).
     * respondent_ref = "target:{uuid}" keeps responses isolated per target.
     */
    public function handle(DeploymentTarget $target): SurveyResponse
    {
        // Idempotent — return existing if already cloned
        if ($target->readiness_response_id) {
            return SurveyResponse::findOrFail($target->readiness_response_id);
        }

        // Find the template survey
        $vertical        = null;
        $templateSlug    = $this->resolveTemplateSlug($target);
        $templateSurvey  = Survey::where('slug', $templateSlug)->first();

        if (! $templateSurvey) {
            throw new \RuntimeException(
                "Survey template '{$templateSlug}' không tồn tại. Chạy ReadinessV1SurveySeeder trước."
            );
        }

        // Create a stub response (status = partial, not yet submitted)
        $response = SurveyResponse::create([
            'survey_id'      => $templateSurvey->id,
            'respondent_ref' => 'target:' . ($target->uuid ?: $target->id),
            'status'         => 0, // partial
        ]);

        // Link to target
        $target->update(['readiness_response_id' => $response->id]);

        return $response;
    }

    private function resolveTemplateSlug(DeploymentTarget $target): string
    {
        // Try to resolve from VerticalRegistry
        try {
            $vertical = VerticalRegistry::resolveForOrganization($target->organization_id, $target->vertical_code);
            if ($vertical) {
                $slug = $vertical->readinessTemplateSlag();
                if ($slug) return $slug;
            }
        } catch (\Throwable) {
        }

        return 'readiness_v1'; // fallback
    }
}
