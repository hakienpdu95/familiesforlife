<?php

namespace Modules\Survey\Observers;

use Modules\Survey\Models\Survey;

class SurveyObserver
{
    /**
     * Khi survey bị soft-delete: vô hiệu hóa tất cả token.
     * survey_responses + survey_answers giữ nguyên (không xóa data lịch sử).
     */
    public function deleting(Survey $survey): void
    {
        // Raw query — không trigger model events, không ghi activity log thừa
        $survey->tokens()->update(['is_active' => false]);
    }
}
