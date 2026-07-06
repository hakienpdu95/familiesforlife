<?php

namespace Modules\OcopRubric\Enums;

enum ScoringSessionMode: string
{
    case Practice       = 'practice';
    case SelfAssessment = 'self_assessment';

    public function label(): string
    {
        return match ($this) {
            self::Practice       => 'Luyện tập',
            self::SelfAssessment => 'Tự đánh giá (Mẫu 02)',
        };
    }
}
