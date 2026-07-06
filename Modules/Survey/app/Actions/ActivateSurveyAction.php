<?php

namespace Modules\Survey\Actions;

use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Survey\Actions\BuildSurveySchemaAction;
use Modules\Survey\Enums\FieldType;
use Modules\Survey\Enums\SurveyStatus;
use Modules\Survey\Models\Survey;
use Modules\Survey\Models\SurveyField;
use Modules\ActivityLog\Core\ActivityLogger;
use Modules\Survey\Models\SurveySection;

class ActivateSurveyAction
{
    use AsAction;

    public function handle(Survey $survey): Survey
    {
        if ($survey->status === SurveyStatus::Active) {
            throw ValidationException::withMessages([
                'status' => 'Survey này đã ở trạng thái active.',
            ]);
        }

        // Phải có ít nhất 1 section
        $sectionCount = SurveySection::forSurvey($survey->id)->count();
        if ($sectionCount === 0) {
            throw ValidationException::withMessages([
                'sections' => 'Survey phải có ít nhất 1 section trước khi kích hoạt.',
            ]);
        }

        // Phải có ít nhất 1 field active
        $activeFieldCount = SurveyField::forSurvey($survey->id)->active()->count();
        if ($activeFieldCount === 0) {
            throw ValidationException::withMessages([
                'fields' => 'Survey phải có ít nhất 1 field đang active trước khi kích hoạt.',
            ]);
        }

        // Không cho activate nếu có section không có field active nào
        $emptySectionCount = SurveySection::where('survey_id', $survey->id)
            ->whereDoesntHave('fields', fn ($q) => $q->where('is_active', true))
            ->count();

        if ($emptySectionCount > 0) {
            throw ValidationException::withMessages([
                'sections' => "Có {$emptySectionCount} section không có field active nào. Vui lòng thêm field hoặc xóa section rỗng.",
            ]);
        }

        // Tất cả field is_required = 1 kiểu choice phải có ít nhất 1 option
        $choiceTypes = [FieldType::Radio->value, FieldType::Select->value, FieldType::Checkbox->value];

        $missingOptions = SurveyField::forSurvey($survey->id)
            ->active()
            ->where('is_required', true)
            ->whereIn('field_type', $choiceTypes)
            ->doesntHave('options')
            ->pluck('label');

        if ($missingOptions->isNotEmpty()) {
            throw ValidationException::withMessages([
                'fields' => 'Các field bắt buộc sau chưa có lựa chọn nào: ' . $missingOptions->join(', ') . '.',
            ]);
        }

        $survey->update(['status' => SurveyStatus::Active]);
        BuildSurveySchemaAction::purgeCache($survey->slug);

        ActivityLogger::info('Survey', 'survey_activated', $survey, ['slug' => $survey->slug]);

        return $survey->fresh();
    }
}
