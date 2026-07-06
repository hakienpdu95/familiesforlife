<?php

namespace Modules\Deployment\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Deployment\Models\DeploymentChecklistItem;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\Survey\Enums\ResponseStatus;
use Modules\Survey\Models\SurveyResponse;

/**
 * Auto-tick checklist items dựa trên trạng thái survey và nội dung answers.
 *
 * Gọi sau khi submit form thu thập dữ liệu (section-level hoặc toàn survey).
 * Idempotent — chỉ update rows đang is_done = false.
 */
class ChecklistAutoTickAction
{
    use AsAction;

    public function handle(DeploymentTarget $target, SurveyResponse $response): void
    {
        // Load answers keyed by field_key
        $answers = $response->answers()
            ->join('survey_fields', 'survey_answers.field_id', '=', 'survey_fields.id')
            ->get(['survey_fields.field_key', 'survey_answers.*'])
            ->keyBy('field_key');

        $val = fn(string $k) => $answers[$k]?->value_string ?? $answers[$k]?->value_text ?? null;

        // Always tick when response exists (= survey was assigned)
        $this->tick($target, 'data_collection_assigned');

        // Section A — org profile filled
        if (filled($val('org_name')) && filled($val('tax_code'))) {
            $this->tick($target, 'entity_profile_verified');
        }

        // Section B — GPS captured
        if (filled($val('gps_lat')) && filled($val('gps_lng'))) {
            $this->tick($target, 'gps_captured');
        }

        // Survey fully complete
        if ($response->status instanceof ResponseStatus) {
            $isComplete = $response->status === ResponseStatus::Complete;
        } else {
            $isComplete = (int) $response->status === 1;
        }

        if ($isComplete) {
            $this->tick($target, 'field_survey_done');

            // Section C — product list filled
            if (filled($val('product_list'))) {
                $this->tick($target, 'product_list_confirmed');
            }
        }
    }

    private function tick(DeploymentTarget $target, string $key): void
    {
        DeploymentChecklistItem::where('deployment_target_id', $target->id)
            ->where('item_key', $key)
            ->where('is_done', false)
            ->update([
                'is_done' => true,
                'done_by' => auth()->id(),
                'done_at' => now(),
            ]);
    }
}
