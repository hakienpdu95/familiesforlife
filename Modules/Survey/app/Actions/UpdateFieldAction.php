<?php

namespace Modules\Survey\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Survey\Data\FieldFormData;
use Modules\Survey\Enums\SurveyStatus;
use Modules\Survey\Exceptions\FieldImmutableException;
use Modules\Survey\Models\Survey;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Survey\Models\SurveyField;
use Modules\Survey\Actions\BuildSurveySchemaAction;

class UpdateFieldAction
{
    use AsAction;

    public function handle(Survey $survey, SurveyField $field, FieldFormData $data): SurveyField
    {
        $isLocked = $survey->status === SurveyStatus::Active
            && $survey->responses()->complete()->exists();

        if ($isLocked) {
            // Khi locked: chỉ cho phép sửa label + placeholder
            // Nếu request muốn thay đổi field cấu trúc → reject
            $this->assertNoStructuralChange($field, $data);

            $field->update([
                'label'       => $data->label,
                'placeholder' => $data->placeholder,
            ]);
        } else {
            // field_key: bất biến vĩnh viễn — không bao giờ cập nhật
            $payload = [
                'label'           => $data->label,
                'is_required'     => $data->is_required,
                'placeholder'     => $data->placeholder,
                'rule_min'        => $data->rule_min,
                'rule_max'        => $data->rule_max,
                'rule_max_select' => $data->rule_max_select,
            ];

            // field_type bất biến khi survey đã active (nhưng chưa có responses)
            if ($survey->status !== SurveyStatus::Active) {
                $payload['field_type'] = $data->field_type;
                $payload['value_kind'] = $data->field_type->valueKind();
            }

            $field->update($payload);
        }

        ActivityLogger::info('Survey', 'field_updated', $field, ['field_key' => $field->field_key]);

        BuildSurveySchemaAction::purgeCache($survey->slug);

        return $field->fresh()->load('options');
    }

    /**
     * Throw nếu request muốn thay đổi các field ảnh hưởng đến tính toàn vẹn data.
     */
    private function assertNoStructuralChange(SurveyField $field, FieldFormData $data): void
    {
        $changed = $data->is_required !== $field->is_required
            || $data->rule_min !== $field->rule_min
            || $data->rule_max !== $field->rule_max
            || $data->rule_max_select !== $field->rule_max_select;

        if ($changed) {
            throw new FieldImmutableException(
                "Field '{$field->label}' không thể thay đổi is_required hoặc rule sau khi survey đã có responses. Chỉ được sửa label và placeholder."
            );
        }
    }
}
