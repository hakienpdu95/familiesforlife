<?php

namespace Modules\Deployment\Actions;

use App\Foundation\VerticalRegistry;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyResponse;

/**
 * Tạo SurveyResponse thu thập dữ liệu khi target chuyển sang phase `surveying`.
 * Gọi từ AdvancePhaseAction khi to_phase = 'surveying'.
 * Idempotent — không tạo lại nếu đã có.
 */
class AssignDataCollectionSurveyAction
{
    use AsAction;

    public function handle(DeploymentTarget $target): SurveyResponse
    {
        if ($target->data_collection_response_id) {
            return SurveyResponse::findOrFail($target->data_collection_response_id);
        }

        $slug   = $this->resolveSlug($target);
        $survey = Survey::where('slug', $slug)->first();

        if (! $survey) {
            throw new \RuntimeException(
                "Survey template '{$slug}' không tồn tại. Chạy DataCollectionV1Seeder trước."
            );
        }

        $response = SurveyResponse::create([
            'survey_id'      => $survey->id,
            'respondent_ref' => 'target:' . ($target->uuid ?: $target->id),
            'status'         => 0, // partial
        ]);

        $target->update(['data_collection_response_id' => $response->id]);

        return $response;
    }

    private function resolveSlug(DeploymentTarget $target): string
    {
        try {
            $vertical = VerticalRegistry::resolveForOrganization($target->organization_id, $target->vertical_code);
            if ($vertical) {
                $slug = $vertical->dataCollectionTemplateSlug();
                if ($slug) return $slug;
            }
        } catch (\Throwable) {
        }

        return 'data_collection_v1';
    }
}
