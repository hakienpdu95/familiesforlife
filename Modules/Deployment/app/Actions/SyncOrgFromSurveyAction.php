<?php

namespace Modules\Deployment\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Deployment\Models\DeploymentTarget;
use Modules\Survey\Models\SurveyResponse;

/**
 * Sync Section A answers (org_profile) → target Organization record.
 *
 * Được gọi từ DeploymentDataCollectionController@submit sau khi submit section A,
 * hoặc từ SurveyResponse Observer khi survey status → complete.
 *
 * Chỉ cập nhật Organization `target_organization_id` của target — không ảnh hưởng đến
 * org THUCHOCVN đang login.
 */
class SyncOrgFromSurveyAction
{
    use AsAction;

    public function handle(DeploymentTarget $target, SurveyResponse $response): void
    {
        $org = $target->targetOrganization;
        if (! $org) {
            return;
        }

        // Load section_code = 'org_profile' fields via join
        $answers = $response->answers()
            ->join('survey_fields', 'survey_answers.field_id', '=', 'survey_fields.id')
            ->where('survey_fields.section_id', function ($q) use ($response) {
                $q->select('id')
                    ->from('survey_sections')
                    ->where('survey_id', $response->survey_id)
                    ->where('section_code', 'org_profile')
                    ->limit(1);
            })
            ->get(['survey_fields.field_key', 'survey_answers.*'])
            ->keyBy('field_key');

        if ($answers->isEmpty()) {
            return;
        }

        $val = fn(string $k) => $answers[$k]?->value_string ?? null;

        $patch = array_filter([
            'name'         => $val('org_name'),
            'tax_code'     => $val('tax_code'),
            'city'         => $val('province'),
            'full_address' => $val('full_address'),
            'phone'        => $val('rep_phone'),
        ], fn($v) => filled($v));

        if (! empty($patch)) {
            $org->update($patch);
        }

        // Người đại diện lưu vào settings JSON
        $representative = $val('representative');
        if (filled($representative)) {
            $settings = $org->settings ?? [];
            $settings['representative']     = $representative;
            $settings['representative_phone'] = $val('rep_phone') ?? ($settings['representative_phone'] ?? null);
            $settings['main_product_type']    = $val('main_product_type') ?? ($settings['main_product_type'] ?? null);
            $org->update(['settings' => $settings]);
        }
    }
}
