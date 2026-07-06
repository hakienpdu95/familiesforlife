<?php

namespace Modules\Survey\Support;

use Modules\Survey\Enums\SurveyStatus;
use Modules\Survey\Exceptions\FieldImmutableException;
use Modules\Survey\Models\Survey;

/**
 * Chặn xóa field/option/section khi survey đã active và có ít nhất 1 response.
 * Dùng trong: DestroyFieldAction, DestroyOptionAction, DestroySectionAction.
 *
 * UpdateFieldAction dùng logic riêng (granular guard) thay vì trait này.
 */
trait GuardsSurveyIntegrity
{
    /**
     * Throw FieldImmutableException nếu survey đang active VÀ đã có complete responses.
     * Nếu chỉ active nhưng chưa có response → vẫn cho xóa.
     */
    protected function guardLockedSurvey(Survey $survey): void
    {
        if ($survey->status !== SurveyStatus::Active) {
            return;
        }

        $hasResponses = $survey->responses()->complete()->exists();

        if ($hasResponses) {
            throw new FieldImmutableException(
                'Survey đã active và đã có responses. Không thể xóa field/option/section. Chỉ được phép deactivate field.'
            );
        }
    }
}
