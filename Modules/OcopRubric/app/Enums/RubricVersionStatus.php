<?php

namespace Modules\OcopRubric\Enums;

enum RubricVersionStatus: string
{
    case Draft   = 'draft';
    case Active  = 'active';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Draft   => 'Nháp',
            self::Active  => 'Đang áp dụng',
            self::Retired => 'Đã ngừng áp dụng',
        };
    }
}
