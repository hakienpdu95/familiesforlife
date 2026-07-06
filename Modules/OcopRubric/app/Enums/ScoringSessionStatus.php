<?php

namespace Modules\OcopRubric\Enums;

enum ScoringSessionStatus: string
{
    case InProgress = 'in_progress';
    case Completed  = 'completed';
    case Abandoned  = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'Đang thực hiện',
            self::Completed  => 'Đã hoàn thành',
            self::Abandoned  => 'Đã bỏ dở',
        };
    }
}
